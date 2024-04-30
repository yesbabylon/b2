<?php

/**
 * Restore an instance.
 * TODO: Need to change backup scripts logic for export.php and restore.php
 *
 * @param array{instance: string, backup_id: string} $data
 * @return array{code: int, message: string}
 */
function instance_restore(array $data): array
{
    $status_code = 201;
    $message = '';

    if (!isset($data['instance']) || !isset($data['backup_id'])) {
        $status_code = 400;
        $message = 'Bad Request';
    }

    // 1. active maintenance mode
    exec('sh /home/' . $data['instance'] . '/status/maintenance/enabled.sh');
    // 2. create backup
    exec('sh /root/b2/backup/backup.sh ' . $data['instance']);
    // 3. export backups
    exec('user/bin/php /root/b2/backup/export.php ' . $data['instance']);
    // 3. restore backup with $data['backup_id']
    // 4. deactivate maintenance mode
    exec('sh /home/' . $data['instance'] . '/status/maintenance/disable.sh');

    return [
        'code' => $status_code,
        'message' => $message
    ];
}