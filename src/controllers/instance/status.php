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

    $up = exec("docker inspect -f '{{.State.Running}}' {$data['instance']}") === 'true';

    $docker_stats_json = exec("docker stats {$data['instance']} --no-stream --format '{{ json . }}'");
    $docker_stats = json_decode($docker_stats_json, true);

    if(is_null($docker_stats)) {
        throw new Exception("instance_not_found", 404);
    }

    $up = exec("docker inspect -f '{{.State.Running}}' {$data['instance']}") === 'true';

    $dsk_use = '0.0%';

    $dsk_use_db = exec('du -sh $(docker inspect -f \'{{ range .Mounts }}{{ if eq .Destination "/var/lib/mysql" }}{{ .Source }}{{ end }}{{ end }}\' sql.'.$data['instance'].') | awk \'{print $1}\'');
    $dsk_use_fs = exec('du -hs /home/'.$data['instance'].'/www | awk \'{print $1}\'');
    $total_dsk = exec('lsblk -o NAME,SIZE,TYPE | awk \'$1=="sda" && $3=="disk" {print $2}\'');

    $avail_dsk = convertToBytes($total_dsk);
    $used_dsk = convertToBytes($dsk_use_db) + convertToBytes($dsk_use_fs);
    
    if($avail_dsk > 0) {
        $dsk_use = round((float) 100 * $used_dsk / $avail_dsk, 2) . '%';
    }

    /*
    $ram_parts = explode('/', $docker_stats['MemUsage']);
    $used_ram = convertToBytes($ram_parts[0]);
    */

    $result = [
        'up'                    => $up,
        'dsk_use'               => $dsk_use,
        'cpu_use'               => $docker_stats['CPUPerc'],
        'ram_use'               => $docker_stats['MemPerc'],
        'total_proc'            => $docker_stats['PIDs'],    
        'maintenance_enabled'   => instance_is_maintenance_enabled($data['instance']),
        'docker_stats'          => $docker_stats
    ];

    return [
        'code' => 200,
        'body' => $result
    ];
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
