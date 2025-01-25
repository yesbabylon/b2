<?php
 /*
    This file is part of the B2 package <http://github.com/yesbabylon/b2>
    Some Rights Reserved, Yesbabylon, 2025
    Licensed under MIT License <https://opensource.org/licenses/MIT>
*/

/**
 * Returns host status statistics.
 * body.config.ip_* can be false if interfaces names are not ens3 (protected), veth0 (public) and ens4 (private).
 *
 * @return array{
 *     code: int,
 *     body: array{
 *         type: string,
 *         instant: array{
 *              mysql_mem: string,      // Memory consumption by MySQL as a percentage.
 *              apache_mem: string,     // Memory consumption by Apache as a percentage.
 *              nginx_mem: string,      // Memory consumption by Nginx as a percentage.
 *              apache_proc: int,       // Number of Apache processes.
 *              nginx_proc: int,        // Number of Nginx processes.
 *              mysql_proc: int,        // Number of MySQL processes.
 *              total_proc: int,        // Total number of running processes.
 *              ram_use: string,        // RAM usage as a percentage.
 *              cpu_use: string,        // CPU usage as a percentage.
 *              dsk_use: string,        // Disk usage as a percentage.
 *              usr_active: int,        // Number of logged-in users.
 *              usr_total: int          // Total number of system users.
 *          },
 *          state: array{
 *              uptime: string,         // Uptime in days (e.g., "3days").
 *              fw_secured: bool,       // Indicates if the firewall secures the public IP.
 *              net: string             // Network usage metrics.
 *              cpu: string             // Average CPU load since the last reboot.
 *          },
 *          config: array{
 *              host: string,           // Hostname of the server.
 *              platform_ver: string,   // Operating system version.
 *              kernel_ver: string,     // Kernel version.
 *              mem: string,            // Total memory available.
 *              cpu_qty: int,           // Number of CPU cores.
 *              cpu_freq: string,       // CPU frequency (e.g., "3.2GHz").
 *              disk: string,           // Total disk space available.
 *              ip_protected: string,   // Main IP address protected by the firewall.
 *              ip_public: string,      // Public or failover IP address.
 *              ip_private: string      // Private VLAN IP address.
 *          }
 *     }
 * }
 * @throws Exception
 */
function status(array $data): array {
    // retrieve interface (usually either eth0 or ens3)
    $interface = exec_status_cmd('ip link show | head -3 | tail -1 | awk \'{print $2}\'');
    if(!$interface) {
        throw new Exception("unable_to_retrieve_main_interface", 500);
    }

    $interface = trim($interface, ':');

    $commands = [
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
                    return $res;
                }
            ],
            'cpu_use' => [
                'description' => "used CPU (%)",
                'command'     => 'top -bn1 | grep "Cpu(s)" | awk \'{printf "%.2f%%\n", $2 + $4}\'',
                'adapt'       => function ($res) {
                    return $res;
                }
            ],
            'dsk_use' => [
                'description' => "used DISK (%)",
                'command'     => 'df -h . | tail -1 | awk \'{printf "%.2f%%\n", $3/$2 * 100}\'',
                'adapt'       => function ($res) {
                    return $res;
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
        'state' => [
            'uptime' => [
                'description' => "quantity of days passed since the server is up",
                'command'     => 'cat /proc/uptime | awk \'{print $1}\'',
                'adapt'       => function ($res) {
                    return (intval($res / 86400) + 1).'days';
                }
            ],
            'fw_secured' => [
                'description' => "Flag telling if public IP is secured by firewall.",
                'command'     => 'IP=$(ip addr show veth0 | grep \'inet \' | awk \'{print $2}\' | cut -d/ -f1) && \
                    iptables-save | grep -qE "\-A INPUT -d $IP -p tcp -m tcp --dport 443 -j ACCEPT" && \
                    iptables-save | grep -qE "\-A INPUT -d $IP -p tcp -m tcp --dport 80 -j ACCEPT" && \
                    iptables-save | grep -qE "\-A INPUT -d $IP -j DROP" && echo "true" || echo "false"',
                'adapt'       => function ($res) {
                    return ($res === 'true');
                }
            ],
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
            ]
        ],
        'config' => [
            'host' => [
                'description' => "host name",
                'command'     => 'hostname',
                'adapt'       => function ($res) {
                    return $res;
                }
            ],
            'platform_ver' => [
                'description' => "OS version",
                'command'     => 'hostnamectl | awk -F: \'/Operating System/ {print $2}\' | xargs',
                'adapt'       => function ($res) {
                    return $res;
                }
            ],
            'kernel_ver' => [
                'description' => "Linux kernel version",
                'command'     => 'hostnamectl | awk -F: \'/Kernel/ {print $2}\' | xargs',
                'adapt'       => function ($res) {
                    return $res;
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
                'command'     => 'ip -4 addr show ens3 | grep \'inet \' | awk \'{print $2}\' | cut -d/ -f1',
                'adapt'       => function ($res) {
                    return $res;
                }
            ],
            'ip_public' => [
                'description' => "public/failover IP address",
                'command'     => 'ip -4 addr show veth0 | grep \'inet \' | awk \'{print $2}\' | cut -d/ -f1',
                'adapt'       => function ($res) {
                    return $res;
                }
            ],
            'ip_private' => [
                'description' => "private vlan IP address",
                'command'     => 'ip -4 addr show ens4 | grep \'inet \' | awk \'{print $2}\' | cut -d/ -f1',
                'adapt'       => function ($res) {
                    return $res;
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

    $result['type'] = 'b2';

    // #memo - this adds up too much info and could reveal sensitive data
    // $result['config']['env'] = getenv();

    return [
        'code' => 200,
        'body' => $result
    ];
}
