<?php

/**
 * Creates a backup of a specific instance
 *
 * @param array{instance: string} $data
 * @return array
 * @throws Exception
 */
function instance_backup(array $data): array {
    if(!isset($data['instance'])) {
        throw new InvalidArgumentException("missing_instance", 400);
    }

    $instance = escapeshellarg($data['instance']);

    if(in_array($instance, ['..', '.', 'docker', 'ubuntu'])) {
        throw new InvalidArgumentException("invalid_instance", 400);
    }

    if(!file_exists('/home/'.$instance) || !is_dir('/home/'.$instance)) {
        throw new \Exception("instance_not_found", 404);
    }

    // Remove old export, if any
    exec("rm -rf /home/$instance/export");
    exec("mkdir /home/$instance/export");

    // Backup
    $volume_name = str_replace('.', '', $data['instance']).'_db_data';

    $to_export = [
        "/var/lib/docker/volumes/$volume_name/_data",
        "/home/$instance/.env",
        "/home/$instance/docker-compose.yml",
        "/home/$instance/php.ini",
        "/home/$instance/mysql.cnf",
        "/home/$instance/www"
        // TODO: Handle SSL/TLS Certificates
    ];

    $timestamp = date('YmdHis');
    $to_export_str = implode('', $to_export);

    exec("tar -cvzf /home/$instance/export/backup-$timestamp.tar.gz $to_export_str");

    return [
        'code' => 201,
        'body' => "instance_backup_created"
    ];
}
