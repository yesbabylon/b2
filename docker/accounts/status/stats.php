<?php

/**
 * Run a given command and retrieve the resulting output.
 */
$do_cmd = function($command) {
	$result = null;
    if(exec($command, $output) !== false) {
        $result = reset($output);
    }
	return $result;
};


$adapt_units = function($str) {
	return str_replace(['GiB','Gi','MiB','Mi','KiB','Ki','kbit/s'], ['G', 'G', 'M', 'M', 'K', 'K', 'kbs'], str_replace(' ', '', $str));
};

// retrieve interface (usually either eth0 or ens3)
$res = $do_cmd("ip link show | head -3 | tail -1 | awk '{print $2}'");
if(!$res) {
	throw new Exception('unable to retrieve main interface');
}

$interface = trim($res, ':');


$commands = [
	'stats'		=> [
		'net' => [
			'description'	=> "montly network volume",
			'command' 		=> 'vnstat -i '.$interface.' -m | tail -3 | head -1',
			'adapt'			=> function($res) use($adapt_units) {
				$parts = preg_split('/\s{2,10}/', $res, 3);
				$b = array_map($adapt_units, array_map('trim', explode('|', $parts[2])));
				return array_combine(['rx', 'tx', 'total', 'avg_rate'], $b);
			}
		],
		'cpu' => [
			'description' 	=> "average CPU load (%) since last reboot",
			'command' 		=> 'vmstat | tail -1| awk \'{print $15}\'',
			'adapt'			=> function($res) {
				return (100-intval($res)).'%';
			}
		]
	],
	'instant'		=> [
		'mysql_mem' => [
			'description'	=> "mem consumption mysql (%MEM)",
			'command' 		=> 'ps -o %mem,command ax | grep mysqld | head -1 | cut -d\' \' -f 1',
			'adapt'			=> function($res) {
				return intval($res).'%';
			}
		],
		'apache_mem' => [
			'description'	=> "mem consumption apache (%MEM)",
			'command' 		=> 'ps aux| awk \'/apach[e]/{total+=$4}END{print total}\'',
			'adapt'			=> function($res) {
				return $res.'%';
			}
		],
		'nginx_mem' => [
			'description'	=> "mem consumption nginx (%MEM)",
			'command' 		=> 'ps aux| awk \'/nginx/{total+=$4}END{print total}\'',
			'adapt'			=> function($res) {
				return $res.'%';
			}
		],
		'apache_proc' => [
			'description'	=> "number of apache processes",
			'command' 		=> 'ps -o command ax | grep apache2 | head -n -1 | wc -l',
			'adapt'			=> function($res) {
				return $res;
			}
		],
		'nginx_proc' => [
			'description'	=> "number of nginx processes",
			'command' 		=> 'ps -o command ax | grep nginx | head -n -1 | wc -l',
			'adapt'			=> function($res) {
				return $res;
			}
		],
		'mysql_proc' => [
			'description'	=> "number of mysql processes",
			'command' 		=> 'ps -o command ax | grep mysql | head -n -1 | wc -l',
			'adapt'			=> function($res) {
				return $res;
			}
		],
		'total_proc' => [
			'description'	=> "total number of running processes",
			'command' 		=> 'ps aux | head -n -1 | wc -l',
			'adapt'			=> function($res) {
				return $res;
			}
		],
		'ram_use' => [
			'description'	=> "used RAM (Bytes)",
			'command' 		=> 'free -mh |awk \'/Mem/{print $3}\'',
			'adapt'			=> function($res) use($adapt_units) {
				return $adapt_units($res);
			}
		],
		'cpu_use' => [
			'description'	=> "used CPU (%)",
			'command' 		=> 'top -bn2 -d 0.1 | grep "Cpu" | tail -1 | awk \'{print $2}\'',
			'adapt'			=> function($res) {
				return $res.'%';
			}
		],
		'disk_use' => [
			'description'	=> "consumed disk space",
			'command' 		=> 'df . -h | tail -1 | awk \'{print $3}\'',
			'adapt'			=> function($res) use($adapt_units) {
				return $adapt_units($res);
			}
		]
	],
	'config'		=> [
		'mem' => [
			'description'	=> "total RAM",
			'command' 		=> 'free -mh | awk \'/Mem/{print $2}\'',
			'adapt'			=> function($res) use($adapt_units) {
				return $adapt_units($res);
			}
		],
		'cpu_qty' => [
			'description' 	=> "number of CPU (#)",
			'command' 		=> 'cat /proc/cpuinfo | grep processor | wc -l',
			'adapt'			=> function($res) {
				return intval($res);
			}
		],
		'cpu_freq' => [
			'description' 	=> "CPU frequency (MHz)",
			'command' 		=> 'cat /proc/cpuinfo | grep -m1 MHz | awk \'{ print $4 }\'',
			'adapt'			=> function($res) {
				$res = floatval($res);
				if($res > 1000) {
					$res /= 1000;
				}
				return round($res, 1).'GHz';
			}
		],
		'disk' => [
			'description'	=> "total disk space",
			'command' 		=> 'df . -h | tail -1 | awk \'{print $2}\'',
			'adapt'			=> function($res) use($adapt_units)  {
				return $adapt_units($res);
			}
		]
	]
];

$longopts  = [
		"instance::",
	];
	
$options = getopt('', $longopts);

// JSON encoded response
$response = '';

if(isset($options['instance'])) {
	$json = $do_cmd('docker stats '.$options['instance'].' --no-stream --format "{{ json . }}"');
	$result = json_decode($json);
	$response = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
else {
	$result = [];

	foreach($commands as $cat => $cat_commands) {
		foreach($cat_commands as $cmd => $command) {
			$res = $do_cmd($command['command']);
			$result[$cat][$cmd] = $command['adapt']($res);
		}
	}

	$response = json_encode($result, JSON_PRETTY_PRINT);
}

echo $response.PHP_EOL;