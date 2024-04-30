<?php

/**
 * Restore an instance.
 * ! Not sure
 *
 * @param array{instance: string, backup_id: string} $data
 * @return array{code: int, message: string}
 */
function instance_restore(array $data): array
{
    $status_code = 201;
    $message = '';

    if (!isset($data['instance']) || !is_string($data['instance']) || strlen($data['instance']) === 0) {
        $status_code = 400;
    } else {
        exec('sh /home/docker/backups/restore.sh ' . $data['instance']);
    }

    return [
        'code' => $status_code,
        'message' => $message
    ];
}