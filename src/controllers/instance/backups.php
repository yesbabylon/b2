<?php
 /*
    This file is part of the B2 package <http://github.com/yesbabylon/b2>
    Some Rights Reserved, Yesbabylon, 2025
    Licensed under MIT License <https://opensource.org/licenses/MIT>
*/

/**
 * Returns the list a backups for a specific instance
 *
 * @param array{instance: string} $data
 * @return array
 * @throws Exception
 */
function instance_backups(array $data): array {
    if(!isset($data['instance'])) {
        throw new InvalidArgumentException("missing_instance", 400);
    }

    if(!is_string($data['instance']) || !instance_exists($data['instance'])) {
        throw new InvalidArgumentException("invalid_instance", 400);
    }

    $export = [];
    $import = [];

    $backup_files = array_map(
        'basename',
        array_filter(glob('/home/'.$data['instance'].'/export/*'), 'is_file')
    );

    foreach($backup_files as $backup_file) {
        $backup_id = null;
        if(preg_match('/_(.*?)\./', $backup_file, $matches)) {
            $backup_id = $matches[1];
        }
        $export[] = [
            'id'        => $backup_id,
            'filename'  => $backup_file
        ];
    }

    $backup_files = array_map(
        'basename',
        array_filter(glob('/home/'.$data['instance'].'/import/*'), 'is_file')
    );

    foreach($backup_files as $backup_file) {
        $backup_id = null;
        if(preg_match('/_(.*?)\./', $backup_file, $matches)) {
            $backup_id = $matches[1];
        }
        $import[] = [
            'id'        => $backup_id,
            'filename'  => $backup_file
        ];
    }

    return [
        'code' => 200,
        'body' => [
            'export' => $export,
            'import' => $import
        ]
    ];
}
