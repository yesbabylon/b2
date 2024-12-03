<?php

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

    if(
        in_array($data['instance'], ['..', '.', 'docker', 'ubuntu'])
        || $data['instance'] !== basename($data['instance'])
    ) {
        throw new InvalidArgumentException("invalid_instance", 400);
    }

    if(!file_exists('/home/'.$data['instance']) || !is_dir('/home/'.$data['instance'])) {
        throw new \Exception("instance_not_found", 404);
    }

    $backup_files = array_filter(glob('/home/'.$data['instance'].'/export/*'), 'is_file');

    return [
        'code' => 200,
        'body' => compact('backup_files')
    ];
}
