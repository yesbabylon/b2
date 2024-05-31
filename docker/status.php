<?php

/**
 * Run a given command and retrieve the resulting output.
 * ! Not sure
 *
 * @param array $data
 * @return array{code: int, message: string}
 * @throws Exception
 */

function status(array $data): array
{
    $do_cmd = function ($command) {
        $result = null;
        if (exec($command, $output) !== false) {
            $result = reset($output);
        }
        return $result;
    };


    $adapt_units = function ($str) {
        return str_replace(['GiB', 'Gi', 'MiB', 'Mi', 'KiB', 'Ki', 'kbit/s'], ['G', 'G', 'M', 'M', 'K', 'K', 'kbs'], str_replace(' ', '', $str));
    };

    // retrieve interface (usually either eth0 or ens3)
    $res = $do_cmd("ip link show | head -3 | tail -1 | awk '{print $2}'");
    if (!$res) {
        throw new Exception('unable to retrieve main interface');
    }

    $interface = trim($res, ':');

    $commands = [
        'stats' => [
            'net' => [
                'description' => "monthly network volume",
                'command' => 'vnstat -i ' . $interface . ' -m | tail -3 | head -1',
                'adapt' => function ($res) use ($adapt_units) {
                    $parts = preg_split('/\s{2,10}/', $res, 3);
                    $b = array_map($adapt_units, array_map('trim', explode('|', $parts[2])));
                    return array_combine(['rx', 'tx', 'total', 'avg_rate'], $b);
                }
            ],
            'cpu' => [
                'description' => "average CPU load (%) since last reboot",
                'command' => 'vmstat | tail -1| awk \'{print $15}\'',
                'adapt' => function ($res) {
                    return (100 - intval($res)) . '%';
                }
            ],
            'uptime' => [
                'description' => "average CPU load (%) since last reboot",
                'command' => 'cat /proc/uptime | awk \'{print $1}\'',
                'adapt' => function ($res) {
                    return (intval($res / 86400) + 1) . 'days';
                }
            ]
        ],
        'instant' => [
            'mysql_mem' => [
                'description' => "mem consumption mysql (%MEM)",
                'command' => 'ps -o %mem,command ax | grep mysqld | head -1 | cut -d\' \' -f 1',
                'adapt' => function ($res) {
                    return intval($res) . '%';
                }
            ],
            'apache_mem' => [
                'description' => "mem consumption apache (%MEM)",
                'command' => 'ps aux| awk \'/apach[e]/{total+=$4}END{print total}\'',
                'adapt' => function ($res) {
                    return $res . '%';
                }
            ],
            'nginx_mem' => [
                'description' => "mem consumption nginx (%MEM)",
                'command' => 'ps aux| awk \'/nginx/{total+=$4}END{print total}\'',
                'adapt' => function ($res) {
                    return $res . '%';
                }
            ],
            'apache_proc' => [
                'description' => "number of apache processes",
                'command' => 'ps -o command ax | grep apache2 | head -n -1 | wc -l',
                'adapt' => function ($res) {
                    return $res;
                }
            ],
            'nginx_proc' => [
                'description' => "number of nginx processes",
                'command' => 'ps -o command ax | grep nginx | head -n -1 | wc -l',
                'adapt' => function ($res) {
                    return $res;
                }
            ],
            'mysql_proc' => [
                'description' => "number of mysql processes",
                'command' => 'ps -o command ax | grep mysql | head -n -1 | wc -l',
                'adapt' => function ($res) {
                    return $res;
                }
            ],
            'total_proc' => [
                'description' => "total number of running processes",
                'command' => 'ps aux | head -n -1 | wc -l',
                'adapt' => function ($res) {
                    return $res;
                }
            ],
            'ram_use' => [
                'description' => "used RAM (Bytes)",
                'command' => 'free -mh |awk \'/Mem/{print $3}\'',
                'adapt' => function ($res) use ($adapt_units) {
                    return $adapt_units($res);
                }
            ],
            'cpu_use' => [
                'description' => "used CPU (%)",
                'command' => 'top -bn2 -d 0.1 | grep "Cpu" | tail -1 | awk \'{print $2}\'',
                'adapt' => function ($res) {
                    return $res . '%';
                }
            ],
            'disk_use' => [
                'description' => "consumed disk space",
                'command' => 'df . -h | tail -1 | awk \'{print $3}\'',
                'adapt' => function ($res) use ($adapt_units) {
                    return $adapt_units($res);
                }
            ],
            'usr_active' => [
                'description' => "total number of logged in users",
                'command' => 'w -h  | wc -l',
                'adapt' => function ($res) {
                    return $res;
                }
            ],
            'usr_total' => [
                'description' => "total number of logged in users",
                'command' => 'awk -F\':\' \'$3 >= 1000 && $3 < 60000 {print $1}\' /etc/passwd | wc -l',
                'adapt' => function ($res) {
                    return $res;
                }
            ]
        ],
        'config' => [
            'host' => [
                'description' => "host name",
                'command' => 'hostname',
                'adapt' => function ($res) use ($adapt_units) {
                    return $adapt_units($res);
                }
            ],
            'uptime' => [
                'description' => "time since last reboot",
                'command' => 'uptime -s',
                'adapt' => function ($res) {
                    return date('c', strtotime($res));
                }
            ],
            'mem' => [
                'description' => "total RAM",
                'command' => 'free -mh | awk \'/Mem/{print $2}\'',
                'adapt' => function ($res) use ($adapt_units) {
                    return $adapt_units($res);
                }
            ],
            'cpu_qty' => [
                'description' => "number of CPU (#)",
                'command' => 'cat /proc/cpuinfo | grep processor | wc -l',
                'adapt' => function ($res) {
                    return intval($res);
                }
            ],
            'cpu_freq' => [
                'description' => "CPU frequency (MHz)",
                'command' => 'cat /proc/cpuinfo | grep -m1 MHz | awk \'{ print $4 }\'',
                'adapt' => function ($res) {
                    $res = floatval($res);
                    if ($res > 1000) {
                        $res /= 1000;
                    }
                    return round($res, 1) . 'GHz';
                }
            ],
            'disk' => [
                'description' => "total disk space",
                'command' => 'df . -h | tail -1 | awk \'{print $2}\'',
                'adapt' => function ($res) use ($adapt_units) {
                    return $adapt_units($res);
                }
            ],
            'ip_protected' => [
                'description' => "main IP address",
                'command' => 'ifconfig ens3 | grep \'inet \' | awk \'{print $2}\'',
                'adapt' => function ($res) {
                    return $res;
                }
            ],
            'ip_public' => [
                'description' => "public/failover IP address",
                'command' => 'ifconfig veth0 | grep \'inet \' | awk \'{print $2}\'',
                'adapt' => function ($res) {
                    return $res;
                }
            ],
            'ip_private' => [
                'description' => "total disk space",
                'command' => 'ifconfig ens4 | grep \'inet \' | awk \'{print $2}\'',
                'adapt' => function ($res) {
                    return $res;
                }
            ]
        ]
    ];

    //$long_opts = [
    //    "instance::",
    //];
    //
    //$options = getopt('', $long_opts);

    $result = [];

    foreach ($commands as $cat => $cat_commands) {
        foreach ($cat_commands as $cmd => $command) {
            $res = $do_cmd($command['command']);
            $result[$cat][$cmd] = $command['adapt']($res);
        }
    }

    $response = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

    return [
        'code' => 201,
        'message' => $response
    ];
}
