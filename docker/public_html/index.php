<?php
/**
 *  Controller serving micro API for fetching system info.
 *
 *  This file is intended to be run using follwing command: 
 *
 *  php -S 0.0.0.0:7000 -t /home/docker/public_html/ >&/home/docker/public_html/php.log &
 * 
 * dependencies : ps, free, top, vnstat, vmstat
 */
 
/**
 * Run a child process using built-in function to execute a command in the terminal.
 *
 * This function sequencialy falls back to : system, passthru, exec, shell_exe
 * 
 */
function terminal($command) {
        // init
        $return_var = 0;
        //system
        if(function_exists('system')) {
                ob_start();
                system($command , $return_var);
                $output = ob_get_contents();
                ob_end_clean();
        }
        //passthru
        else if(function_exists('passthru')) {
                ob_start();
                passthru($command , $return_var);
                $output = ob_get_contents();
                ob_end_clean();
        }
        //exec
        else if(function_exists('exec')) {
                exec($command , $output , $return_var);
                $output = implode("\n", $output);
        }
        //shell_exec
        else if(function_exists('shell_exec')) {
                $output = shell_exec($command) ;
        }
        else {
                $output = 'Command execution not possible on this system';
                $return_var = 1;
        }
        return array('output' => trim($output), 'status' => $return_var);
}
// 1) set default HTTP headers
// allow CORS
header("Access-Control-Allow-Origin: *");
// specify content type
header("Content-type: application/json; charset=UTF-8");
// default response
$response = ['status' => 'success'];
// 2) try to run requested command
try {
    
    // 2.1) data validation
    if( !isset($_GET['get']) || empty($_GET['get']) ) {
        throw new \Exception('Malformed request', 403);
    }
    
    // 2.2) get command to run based on request
    switch($_GET['get']) {
        // network.volume : monthtly data volume
        case 'network.volume':
            
            $command = <<<EOT
vnstat -i ens3 -m | grep "`date +"%b '%y"`" | awk '{print $9" "substr ($10, 1, 1)}'
EOT;
            break;
        // cpu.load : all time average CPU load
        case 'cpu.load':            
            $command = <<<EOT
echo $((100-$(vmstat |tail -1|awk '{print $15}')))%
EOT;
            break;
        // service.mysql.mem : instant amount of memory consumed by MySQL, in %
        case 'service.mysql.mem':
            $command = <<<EOT
ps -o %mem,command ax | grep mysqld | head -1 | cut -d' ' -f 1
EOT;
            break;
        // service.apache.mem : instant amount of memory consumed by Apache, in %
        case 'service.apache.mem':
            $command = <<<EOT
ps aux | awk '/apach[e]/{total+=$4}END{print total}'
EOT;
            break;
        // service.apache.count : instant count of Apache threads, in %
        case 'service.apache.count':
            $command = <<<EOT
ps -o command ax | grep apache2 | head -n -1 | wc -l
EOT;
            break;
        // service.*.count : instant count of running processes
        case 'service.*.count':
            $command = <<<EOT
ps aux | head -n -1 | wc -l
EOT;
            break;
        // resources.mem.count : total RAM of the system
        case 'resources.mem.count':
            $command = <<<EOT
free -m | awk '/Mem/{print $2}'
EOT;
        // resources.mem.avail : instant amount of available RAM
        case 'resources.mem.avail':
            $command = <<<EOT
free -m | awk '/Mem/{print $7}'
EOT;
        // resources.mem.used : instant amount of used RAM
        case 'resources.mem.used':
            $command = <<<EOT
free -m | awk '/Mem/{print $3}'
EOT;
        // resources.cpu.used : instant amount of used CPU, in %
        case 'resources.cpu.used':
            // memo: format top -d ss.tt isn't supported on all systems
            $command = <<<EOT
top -bn2 -d 1 | grep "Cpu" | tail -1 | awk '{print $2}'
EOT;
        // resources.cpu.count : number of CPU of the system
        case 'resources.cpu.count':
            $command = <<<EOT
cat /proc/cpuinfo | grep processor | wc -l
EOT;
        // resources.cpu.freq : frequency of the CPU
        case 'resources.cpu.freq':
            $command = <<<EOT
cat /proc/cpuinfo | grep -m1 MHz | awk '{ print $4; }'
EOT;


        default:
            throw new \Exception('Malformed request');
    }
    
    
    // 2.3) run requested command
    $result = terminal($command);
    if($result['status'] == 0) {
        $response['result'] = $result['output'];
    }
    else  {
        throw new \Exception($result['output'], 403);
    }
}
catch(\Exception $e) {
    http_response_code($e->getCode());
    $response = [
        'status' => 'error',
        'error'  => $e->getMessage()
    ];
}
// 3) send the result
echo json_encode($response);
exit(0);