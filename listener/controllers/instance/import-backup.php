<?php

/**
 * Imports a backup file from the configured backup host.
 *
 * @param array{instance: string, backup_id?: string} $data
 * @return array{code: int, body: string}
 * @throws Exception
 */
function instance_import_backup(array $data): array {
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
        throw new Exception("instance_not_found", 404);
    }

    if(!isset($data['backup_id'])) {
        // TODO: handle get latest if no id provided
        throw new InvalidArgumentException("missing_backup_id", 400);
    }

    // TODO: Handle import

    return [
        'code' => 200,
        'body' => "backup_imported"
    ];
}
