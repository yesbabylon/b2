<?php

/**
 * Returns host status statistics.
 * body.config.ip_* can be false if interfaces names are not ens3 (protected), veth0 (public) and ens4 (private).
 *
 * @return array{
 *     code: int,
 *     body: array{
 *         type: string,
 *         stats: array{
 *             net: array{
 *                 rx: string,
 *                 tx: string,
 *                 total: string,
 *                 avg_rate: string,
 *             },
 *             cpu: string,
 *             uptime: string
 *         },
 *         instant: array{
 *             mysql_mem: string,
 *             apache_mem: string,
 *             nginx_mem: string,
 *             apache_proc: string,
 *             nginx_proc: string,
 *             mysql_proc: string,
 *             total_proc: string,
 *             ram_use: string,
 *             cpu_use: string,
 *             dsk_use: string,
 *             usr_active: string,
 *             usr_total: string,
 *         },
 *         config: array{
 *             host: string,
 *             uptime: string,
 *             mem: string,
 *             cpu_qty: string,
 *             cpu_freq: string,
 *             disk: string,
 *             ip_protected: string|false,
 *             ip_public: string|false,
 *             ip_private: string|false,
 *             env: array{
 *                 ADMIN_HOST: string,
 *                 BACKUP_HOST: string,
 *                 STATS_HOST: string,
 *                 GPG_NAME: string,
 *                 GPG_EMAIL: string,
 *                 GPG_EXPIRY_DATE: string
 *             }
 *         }
 *     }
 * }
 * @throws Exception
 */
function status(): array {
    // retrieve interface (usually either eth0 or ens3)
    $interface = exec_status_cmd('ip link show | head -3 | tail -1 | awk \'{print $2}\'');
    if(!$interface) {
        throw new Exception("unable_to_retrieve_main_interface", 500);
    }

    $interface = trim($interface, ':');

    $commands = [
        'stats' => [
            'net' => [
                'description' => "monthly network volume",
                'command'     => 'vnstat -i '.$interface.' -m | tail -3 | head -1',
                'adapt'       => function ($res) {
                    if(strpos($res, '|') === false) {
                        return [
                            'rx'        => 'No data yet',
                            'tx'        => 'No data yet',
                            'total'     => 'No data yet',
                            'avg_rate'  => 'No data yet',
                        ];
                    }

                    $parts = preg_split('/\s{2,10}/', $res, 3);
                    $b = array_map('adapt_unit', array_map('trim', explode('|', $parts[2])));
                    return array_combine(['rx', 'tx', 'total', 'avg_rate'], $b);
                }
            ],
            'cpu' => [
                'description' => "average CPU load (%) since last reboot",
                'command'     => 'vmstat | tail -1| awk \'{print $15}\'',
                'adapt'       => function ($res) {
                    return (100 - intval($res)).'%';
                }
            ],
            'uptime' => [
                'description' => "quantity of days passed since the server is up",
                'command'     => 'cat /proc/uptime | awk \'{print $1}\'',
                'adapt'       => function ($res) {
                    return (intval($res / 86400) + 1).'days';
                }
            ]
        ],
        'instant' => [
            'mysql_mem' => [
                'description' => "mem consumption mysql (%MEM)",
                'command'     => 'ps -o %mem,command ax | grep mysqld | head -1 | cut -d\' \' -f 1',
                'adapt'       => function ($res) {
                    return intval($res).'%';
                }
            ],
            'apache_mem' => [
                'description' => "mem consumption apache (%MEM)",
                'command'     => 'ps aux| awk \'/apach[e]/{total+=$4}END{print total}\'',
                'adapt'       => function ($res) {
                    return $res.'%';
                }
            ],
            'nginx_mem' => [
                'description' => "mem consumption nginx (%MEM)",
                'command'     => 'ps aux| awk \'/nginx/{total+=$4}END{print total}\'',
                'adapt'       => function ($res) {
                    return $res.'%';
                }
            ],
            'apache_proc' => [
                'description' => "number of apache processes",
                'command'     => 'ps -o command ax | grep apache2 | head -n -1 | wc -l',
                'adapt'       => function ($res) {
                    return $res;
                }
            ],
            'nginx_proc' => [
                'description' => "number of nginx processes",
                'command'     => 'ps -o command ax | grep nginx | head -n -1 | wc -l',
                'adapt'       => function ($res) {
                    return $res;
                }
            ],
            'mysql_proc' => [
                'description' => "number of mysql processes",
                'command'     => 'ps -o command ax | grep mysql | head -n -1 | wc -l',
                'adapt'       => function ($res) {
                    return $res;
                }
            ],
            'total_proc' => [
                'description' => "total number of running processes",
                'command'     => 'ps aux | head -n -1 | wc -l',
                'adapt'       => function ($res) {
                    return $res;
                }
            ],
            'ram_use' => [
                'description' => "used RAM (%)",
                'command'     => 'free -m | awk \'/Mem/{printf "%.2f%%\n", $3/$2 * 100}\'',
                'adapt'       => function ($res) {
                    return $res.'%';
                }
            ],
            'cpu_use' => [
                'description' => "used CPU (%)",
                'command'     => 'top -bn2 -d 0.1 | grep "Cpu" | tail -1 | awk \'{print $2}\'',
                'adapt'       => function ($res) {
                    return $res.'%';
                }
            ],
            'dsk_use' => [
                'description' => "used DISK (%)",
                'command'     => 'df -h . | tail -1 | awk \'{printf "%.2f%%\n", $3/$2 * 100}\'',
                'adapt'       => function ($res) {
                    return $res.'%';
                }
            ],
            'usr_active' => [
                'description' => "total number of logged in users",
                'command'     => 'w -h  | wc -l',
                'adapt'       => function ($res) {
                    return $res;
                }
            ],
            'usr_total' => [
                'description' => "total number of logged in users",
                'command'     => 'awk -F\':\' \'$3 >= 1000 && $3 < 60000 {print $1}\' /etc/passwd | wc -l',
                'adapt'       => function ($res) {
                    return $res;
                }
            ]
        ],
        'config' => [
            'host' => [
                'description' => "host name",
                'command'     => 'hostname',
                'adapt'       => function ($res) {
                    return adapt_unit($res);
                }
            ],
            'uptime' => [
                'description' => "time since last reboot",
                'command'     => 'uptime -s',
                'adapt'       => function ($res) {
                    return date('c', strtotime($res));
                }
            ],
            'mem' => [
                'description' => "total RAM",
                'command'     => 'free -mh | awk \'/Mem/{print $2}\'',
                'adapt'       => function ($res) {
                    return adapt_unit($res);
                }
            ],
            'cpu_qty' => [
                'description' => "number of CPU (#)",
                'command'     => 'cat /proc/cpuinfo | grep processor | wc -l',
                'adapt'       => function ($res) {
                    return $res;
                }
            ],
            'cpu_freq' => [
                'description' => "CPU frequency (MHz)",
                'command'     => 'cat /proc/cpuinfo | grep -m1 MHz | awk \'{ print $4 }\'',
                'adapt'       => function ($res) {
                    $res = floatval($res);
                    if($res > 1000) {
                        $res /= 1000;
                    }
                    return round($res, 1).'GHz';
                }
            ],
            'disk' => [
                'description' => "total disk space",
                'command'     => 'df . -h | tail -1 | awk \'{print $2}\'',
                'adapt'       => function ($res) {
                    return adapt_unit($res);
                }
            ],
            'ip_protected' => [
                'description' => "main IP address",
                'command'     => 'ip -4 addr show ens3 | grep \'inet \' | awk \'{print $2}\'',
                'adapt'       => function ($res) {
                    return $res;
                }
            ],
            'ip_public' => [
                'description' => "public/failover IP address",
                'command'     => 'ip -4 addr show veth0 | grep \'inet \' | awk \'{print $2}\'',
                'adapt'       => function ($res) {
                    return $res;
                }
            ],
            'ip_private' => [
                'description' => "total disk space",
                'command'     => 'ip -4 addr show ens4 | grep \'inet \' | awk \'{print $2}\'',
                'adapt'       => function ($res) {
                    return $res;
                }
            ]
        ]
    ];

    $result = [];
    foreach($commands as $cat => $cat_commands) {
        foreach($cat_commands as $cmd => $command) {
            $res = exec_status_cmd($command['command']);
            $result[$cat][$cmd] = $command['adapt']($res);
        }
    }

    $result['type'] = 'b2';
    
    // #memo - this adds up too much data and could reveal sensitive data
    // $result['config']['env'] = getenv();

    return [
        'code' => 200,
        'body' => $result
    ];
}
