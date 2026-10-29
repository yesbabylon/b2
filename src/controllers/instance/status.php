<?php
 /*
    This file is part of the B2 package <http://github.com/yesbabylon/b2>
    Some Rights Reserved, Yesbabylon, 2025
    Licensed under MIT License <https://opensource.org/licenses/MIT>
*/

 /**
  * Retrieves Docker container statistics as an associative array.
  *
  * @param array{instance: string, scope: string} $data
  *
  * @throws Exception
  *
  * @return array{
  *     instant: array{
  *         dsk_use: string,                // Disk usage percentage (e.g., "2.07%").
  *         cpu_use: string,                // CPU usage percentage (e.g., "0.01%").
  *         ram_use: string,                // RAM usage percentage (e.g., "5.46%").
  *         total_proc: string,             // Total number of processes running in the container.
  *     },
  *     state: array{
  *         up: bool,                       // Indicates if the container is running (true if running).
  *         pid: string,                    // The PID of the container's main process on the host.
  *         maintenance: bool,              // Indicates if the container is in maintenance mode.
  *         containers: string[],           // List of container names associated with this instance.
  *         net: string,                    // Network I/O usage in the format "<received> / <transmitted>" (e.g., "89MB / 19MB").
  *         dsk: string                     // Disk I/O usage in the format "<written> / <read>" (e.g., "84.8MB / 4.1kB").
  *     },
  *     config: array{
  *         id: string,                     // The Docker container ID (e.g., "76197eca3602").
  *         image: string,                  // The Docker image used by the container (e.g., "b2-wordpress:7.1-r2").
  *         php: array{
  *             version: string|null,       // The PHP version installed in the container (e.g., "8.4.11").
  *             build_date: string|null     // The PHP build date in ISO 8601 format (e.g., "2026-07-30T15:39:15+00:00").
  *         },
  *         type: string,                   // The instance stack type (e.g., "equal").
  *         framework: string|null,         // The installed framework ("equal" or "wordpress").
  *         version: string|null,           // The installed framework version.
  *         sources: array<int, array{
  *             path: string,
  *             repository: string|null,
  *             branch: string|null
  *         }>,
  *         mem_limit: float,               // Memory limit in MB.
  *         cpu_limit: float                // CPU limit.
  *     }
  * }
  */
function instance_status(array $data): array {
    if(!isset($data['instance'])) {
        throw new InvalidArgumentException("missing_instance", 400);
    }

    if(!is_string($data['instance']) || !instance_exists($data['instance'])) {
        throw new InvalidArgumentException("invalid_instance", 400);
    }

    $container_id = exec('docker ps -qf "name=^'.preg_quote($data['instance']).'$"');

    if($container_id === '') {
        throw new Exception("instance_not_found", 404);
    }

    $instance_type = null;
    $get_instance_type = static function () use($data, &$instance_type): string {
        if($instance_type === null) {
            $instance_type = getTypeOfInstance($data['instance']);
        }
        return $instance_type;
    };

    $commands = [
        'instant' => [
            'dsk_use' => [
                'description' => "disk consumption mysql (%DISK)",
                'command'     => 'true',
                'adapt'       => function ($res) use($data) {
                    $dsk_use = '0.0%';

                    $dsk_use_db = exec('du -sh $(docker inspect -f \'{{ range .Mounts }}{{ if eq .Destination "/var/lib/mysql" }}{{ .Source }}{{ end }}{{ end }}\' sql.'.$data['instance'].') | awk \'{print $1}\'');
                    $dsk_use_fs = exec('du -hs /home/'.$data['instance'].'/www | awk \'{print $1}\'');
                    $total_dsk = exec('lsblk -o NAME,SIZE,TYPE | awk \'$1=="sda" && $3=="disk" {print $2}\'');

                    $avail_dsk = convertToBytes($total_dsk);
                    $used_dsk = convertToBytes($dsk_use_db) + convertToBytes($dsk_use_fs);

                    if($avail_dsk > 0) {
                        $dsk_use = round((float) 100 * $used_dsk / $avail_dsk, 2) . '%';
                    }
                    return $dsk_use;
                }
            ],
            'cpu_use' => [
                'description' => "cpu consumption mysql (%CPU)",
                'command'     => 'true',
                'adapt'       => function ($res) use($data) {
                    return fetchDockerStats($data['instance'])['CPUPerc'];
                }
            ],
            'ram_use'       => [
                'description' => "mem consumption mysql (%MEM)",
                'command'     => 'true',
                'adapt'       => function ($res) use($data) {
                    return fetchDockerStats($data['instance'])['MemPerc'];
                }
            ],
            'total_proc'    => [
                'description' => "number of running processes",
                'command'     => 'true',
                'adapt'       => function ($res) use($data) {
                    return fetchDockerStats($data['instance'])['PIDs'];
                }
            ]
        ],
        'state' => [
            'up' => [
                'description' => "Flag telling if the instance is running.",
                'command'     => 'true',
                'adapt'       => function ($res) use($container_id) {
                    return exec("docker inspect -f '{{.State.Running}}' {$container_id}") === 'true';
                }
            ],
            'pid' => [
                'description' => "ID of the host process running the container.",
                'command'     => 'true',
                'adapt'       => function ($res) use($container_id) {
                    return exec("docker inspect -f '{{.State.Pid}}' {$container_id}");
                }
            ],
            'maintenance' => [
                'description' => "Flag telling if the instance is in maintenance mode.",
                'command'     => 'true',
                'adapt'       => function ($res) use($data) {
                    return instance_is_maintenance_enabled($data['instance']);
                }
            ],
            'containers' => [
                'description' => "Running containers associated with the instance (same stack).",
                'command'     => 'true',
                'adapt'       => function ($res) use($data) {
                    return explode(' ', exec("docker compose --project-directory /home/{$data['instance']} ps 2>/dev/null | awk 'NR>1 {print $1}' | paste -sd ' '"));
                }
            ],
            'net' => [
                'description' => "Total network input and output traffic for the container.",
                'command'     => 'true',
                'adapt'       => function ($res) use($data) {
                    return fetchDockerStats($data['instance'])['NetIO'];
                }
            ],
            'dsk' => [
                'description' => "Total block I/O usage by the container.",
                'command'     => 'true',
                'adapt'       => function ($res) use($data) {
                    return fetchDockerStats($data['instance'])['BlockIO'];
                }
            ]
        ],
        'config' => [
            'id' => [
                'description' => "Docker Container ID.",
                'command'     => 'true',
                'adapt'       => function ($res) use($container_id) {
                    return $container_id;
                }
            ],
            'image' => [
                'description' => "Name of the image used by the container.",
                'command'     => 'true',
                'adapt'       => function ($res) use($container_id) {
                    return exec("docker inspect -f '{{.Config.Image}}' {$container_id}");
                }
            ],
            'type' => [
                'description' => "Instance stack type.",
                'command'     => 'true',
                'adapt'       => function ($res) use($get_instance_type) {
                    return $get_instance_type();
                }
            ],
            'framework' => [
                'description' => "Framework installed by the instance stack.",
                'command'     => 'true',
                'adapt'       => function ($res) use($get_instance_type) {
                    return getFrameworkFromInstanceType($get_instance_type());
                }
            ],
            'version' => [
                'description' => "Version of the framework installed by the instance stack.",
                'command'     => 'true',
                'adapt'       => function ($res) use($container_id, $get_instance_type) {
                    return getFrameworkVersion(
                        $container_id,
                        $get_instance_type()
                    );
                }
            ],
            'sources' => [
                'description' => "Source directories and their current Git branches.",
                'command'     => 'true',
                'adapt'       => function ($res) use($container_id, $get_instance_type) {
                    return getSourcesOfInstance(
                        $container_id,
                        $get_instance_type()
                    );
                }
            ],
            'php' => [
                'description' => "PHP information for the container.",
                'command'     => 'true',
                'adapt'       => function ($res) use($container_id) {
                    return getContainerPhpInfo($container_id);
                }
            ],
            'mem_limit' => [
                'description' => "Amount of memory allocated to the container (in MB).",
                'command'     => 'true',
                'adapt'       => function ($res) use($container_id) {
                    $mem_count = exec("docker inspect -f '{{.HostConfig.Memory}}' {$container_id}");
                    return round(intval($mem_count) / (1024 * 1024), 0);
                }
            ],
            'cpu_limit' => [
                'description' => "Amount of CPU allocated to the container.",
                'command'     => 'true',
                'adapt'       => function ($res) use($container_id) {
                    $cpu_count = exec("docker inspect -f '{{.HostConfig.NanoCpus}}' {$container_id}");
                    return round(floatval($cpu_count) / 1000000000, 2);
                }
            ]
        ]
    ];

    $result = [];

    foreach($commands as $cat => $cat_commands) {
        if(isset($data['scope']) && $data['scope'] !== $cat) {
            continue;
        }
        foreach($cat_commands as $cmd => $command) {
            $res = exec_status_cmd($command['command']);
            $result[$cat][$cmd] = $command['adapt']($res);
        }
    }

    return [
        'code' => 200,
        'body' => $result
    ];
}

function getTypeOfInstance(string $instance): string {
    $env_file = "/home/$instance/.env";
    if(!is_readable($env_file)) {
        throw new Exception('could_not_read_instance_env', 500);
    }

    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if($lines === false) {
        throw new Exception('could_not_read_instance_env', 500);
    }

    foreach($lines as $line) {
        if(!preg_match('/^\s*INSTANCE_TYPE\s*=\s*(.+?)\s*$/', $line, $matches)) {
            continue;
        }

        $instance_type = trim($matches[1], "'\"");
        if(!preg_match('/^[A-Za-z0-9_-]+$/', $instance_type)) {
            throw new Exception('invalid_instance_type', 500);
        }

        return strtolower($instance_type);
    }

    throw new Exception('missing_instance_type', 500);
}

function getFrameworkFromInstanceType(string $instance_type): ?string {
    if($instance_type === 'wordpress') {
        return 'wordpress';
    }

    if(in_array($instance_type, ['equal', 'equalpress', 'symbiose', 'fmt'], true)) {
        return 'equal';
    }

    return null;
}

function getFrameworkVersion(string $container_id, string $instance_type): ?string {
    $framework = getFrameworkFromInstanceType($instance_type);

    if($framework === 'wordpress') {
        return getContainerWordpressVersion($container_id);
    }

    if($framework === 'equal') {
        return getContainerEqualVersion($container_id);
    }

    return null;
}

/**
 * @return array<int, array{path: string, repository: string|null, branch: string|null}>
 */
function getSourcesOfInstance(string $container_id, string $instance_type): array {
    $framework = getFrameworkFromInstanceType($instance_type);
    if($framework === null) {
        return [];
    }

    $root_source = getContainerGitSource($container_id, '/var/www/html');
    $sources = [[
        'path'       => '/var/www/html',
        'repository' => $root_source['repository'],
        'branch'     => $root_source['branch']
    ]];

    if(in_array($instance_type, ['equal', 'symbiose', 'fmt'], true)) {
        $packages_source = getContainerGitSource(
            $container_id,
            '/var/www/html/packages'
        );
        $sources[] = [
            'path'       => '/var/www/html/packages',
            'repository' => $packages_source['repository'],
            'branch'     => $packages_source['branch']
        ];
    }

    return $sources;
}

/**
 * @return array{repository: string|null, branch: string|null}
 */
function getContainerGitSource(string $container_id, string $repository): array {
    $source = [
        'repository' => null,
        'branch'     => null
    ];
    $output = [];
    $exit_code = 0;
    $git_command = 'docker exec '.escapeshellarg($container_id)
        .' git -c '.escapeshellarg('safe.directory='.$repository)
        .' -C '.escapeshellarg($repository);
    exec(
        $git_command.' rev-parse --show-toplevel 2>/dev/null',
        $output,
        $exit_code
    );

    if($exit_code !== 0 || count($output) === 0) {
        return $source;
    }

    $repository_root = rtrim(end($output), '/');
    if($repository_root !== rtrim($repository, '/')) {
        return $source;
    }

    $output = [];
    exec(
        $git_command.' symbolic-ref --quiet --short HEAD 2>/dev/null',
        $output,
        $exit_code
    );

    if($exit_code === 0 && count($output) > 0) {
        $source['branch'] = end($output);
    }

    $output = [];
    exec(
        $git_command.' remote get-url origin 2>/dev/null',
        $output,
        $exit_code
    );

    if($exit_code === 0 && count($output) > 0) {
        $remote = trim(end($output));
        if($remote !== '') {
            $remote = preg_replace(
                '#^([a-z][a-z0-9+.-]*://)(?:[^/@]+@)+#i',
                '$1',
                $remote
            ) ?? '';
            $remote = preg_replace(
                '/^[^@\s]+@(?=[^:\s]+:)/',
                '',
                $remote
            ) ?? '';
            $source['repository'] = $remote !== '' ? $remote : null;
        }
    }

    return $source;
}

function getContainerEqualVersion(string $container_id): ?string {
    $output = [];
    $exit_code = 0;
    exec(
        'docker exec '.escapeshellarg($container_id)
        .' cat '.escapeshellarg('/var/www/html/VERSION')
        .' 2>/dev/null',
        $output,
        $exit_code
    );

    if($exit_code !== 0 || count($output) === 0) {
        return null;
    }

    $version = trim(implode("\n", $output));
    return $version !== '' ? $version : null;
}

function getContainerWordpressVersion(string $container_id): ?string {
    $output = [];
    $exit_code = 0;
    exec(
        'docker exec '.escapeshellarg($container_id)
        .' php -r '.escapeshellarg(
            'require "/var/www/html/wp-includes/version.php"; echo $wp_version ?? "";'
        )
        .' 2>/dev/null',
        $output,
        $exit_code
    );

    if($exit_code !== 0 || count($output) === 0) {
        return null;
    }

    $version = trim(end($output));
    return $version !== '' ? $version : null;
}

/**
 * @return array{version: string|null, build_date: string|null}
 */
function getContainerPhpInfo(string $container_id): array {
    $version = exec(
        'docker exec '.escapeshellarg($container_id)
        .' php -r '.escapeshellarg('echo PHP_VERSION;')
        .' 2>/dev/null'
    );
    $build_date = exec(
        'docker exec '.escapeshellarg($container_id)
        .' php -i 2>/dev/null'
        .' | sed -n '.escapeshellarg('s/^Build Date => //p')
    );
    return [
        'version'    => $version !== false && $version !== '' ? $version : null,
        'build_date' => $build_date !== false && $build_date !== '' ? date('c', strtotime($build_date)) : null
    ];
}

function fetchDockerStats(string $instance): array {
    static $result;
    if(!$result) {
        $docker_stats_json = exec("docker stats {$instance} --no-stream --format '{{ json . }}'");
        $result = json_decode($docker_stats_json, true);
    }
    return $result;
}

function convertToBytes(string $size): int {
    $size = strtolower(trim($size));

    if(preg_match('/^([\d.]+)\s*(k|m|g|t|kib|mib|gib|tib|kb|mb|gb|tb)$/i', $size, $matches)) {
        $value = floatval($matches[1]);
        $unit = $matches[2];

        switch ($unit) {
            case 't':
            case 'tib':
                $value *= 1024;
            case 'g':
            case 'gib':
                $value *= 1024;
            case 'm':
            case 'mib':
                $value *= 1024;
            case 'k':
            case 'kib':
                $value *= 1024;
                break;
            case 'tb':
                $value *= 1000;
            case 'gb':
                $value *= 1000;
            case 'mb':
                $value *= 1000;
            case 'kb':
                $value *= 1000;
                break;
        }
        return (int) $value;
    }
    return 0;
}
