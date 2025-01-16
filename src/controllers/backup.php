<?php

/**
 * Creates new backups for all host instances and attempts to export them to related backup host.
 *
 * @throws Exception
 */
function backup(): array {
    $result = [];
    
    $instances = get_instances();

    foreach($instances as $instance) {
        $res_export = null;
        $res_backup = exec_controller('instance/backup', ['instance' => $instance]);
        $backup_file = $res_backup['body']['result'] ?? null;
        if($backup_file) {
            if(preg_match('/_(.*?)\./', $backup_file, $matches)) {
                $backup_id = $matches[1];
                $res_export = exec_controller('instance/export-backup', ['instance' => $instance, 'backup_id' => $backup_id]);
            } 
        }
        $result[$instance] = [
            'backup' => $res_backup,
            'export' => $res_export
        ];
    }

    return [
        'code' => 200,
        'body' => $result
    ];
}
