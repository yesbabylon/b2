<?php

/**
 * Returns status statistics about a given instance.
 *
 * @param array{instance: string} $data
 * @return array{code: int, body: string}
 * @throws Exception
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

    $commands = [
        'status' => [

            'up' => [
                'description' => "mem consumption mysql (%MEM)",
                'command'     => 'true',
                'adapt'       => function ($res) use($container_id) {
                    return exec("docker inspect -f '{{.State.Running}}' {$container_id}") === 'true';
                }
            ],

            'maintenance' => [
                'description' => "mem consumption mysql (%MEM)",
                'command'     => 'true',
                'adapt'       => function ($res) use($data) {
                    return instance_is_maintenance_enabled($data['instance']);
                }
            ],

            'containers' => [
                'description' => "mem consumption mysql (%MEM)",
                'command'     => 'true',
                'adapt'       => function ($res) use($data) {
                    return explode(' ', exec("docker compose --project-directory /home/{$data['instance']} ps 2>/dev/null | awk 'NR>1 {print $1}' | paste -sd ' '"));
                }
            ],
        ],
        'instant' => [

            'dsk_use' => [
                'description' => "mem consumption mysql (%MEM)",
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
                'description' => "mem consumption mysql (%MEM)",
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
                'description' => "mem consumption mysql (%MEM)",
                'command'     => 'true',
                'adapt'       => function ($res) use($data) {                
                    return fetchDockerStats($data['instance'])['PIDs'];
                }
            ],

            'docker_stats'    => [
                'description' => "mem consumption mysql (%MEM)",
                'command'     => 'true',
                'adapt'       => function ($res) use($data) {
                    return fetchDockerStats($data['instance']);
                }
            ]

        ],
        'config' => [
            'host' => [
                'description' => "host name",
                'command'     => 'hostname',
                'adapt'       => function ($res) {
                    return $res;
                }
            ]
        ]

    ];

    $result = [];

    /*
    $result = [
        'up'            => $up,
        'containers'    => $up,
        'maintenance'   => instance_is_maintenance_enabled($data['instance']),
        'instant'       => [
            'dsk_use'       => $dsk_use,
            'cpu_use'       => $docker_stats['CPUPerc'],
            'ram_use'       => $docker_stats['MemPerc'],
            'total_proc'    => $docker_stats['PIDs'],
        ],
        'docker_stats'  => $docker_stats
    ];
    */

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
