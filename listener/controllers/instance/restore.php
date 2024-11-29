<?php

/**
 * Restore an instance.
 * TODO: Need to change backup scripts logic for export.php and restore.php
 *
 * @param array{instance: string, backup_id: string} $data
 * @return array{code: int, message: string}
 */
function instance_restore(array $data): array {
    if (!isset($data['instance'])) {
        throw new InvalidArgumentException("missing_instance", 400);
    }

    if(!isset($data['backup_id'])) {
        throw new InvalidArgumentException("missing_backup_id", 400);
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
        'code' => 200,
        'body' => "instance_successfully_restored"
    ];
}