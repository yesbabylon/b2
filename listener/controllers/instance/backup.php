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

    if(in_array($data['instance'], ['docker', 'ubuntu'])) {
        throw new InvalidArgumentException("invalid_instance", 400);
    }

    if(!file_exists('/home/'.$data['instance']) || !is_dir('/home/'.$data['instance'])) {
        throw new \Exception("instance_not_found", 404);
    }

    // Remove old export, if any
    exec("rm -rf /home/${$data['instance']}/export");
    exec("mkdir /home/${$data['instance']}/export");

    // Backup
    $volume_name = str_replace('.', '', $data['instance']).'_db_data';

    $to_export = [
        "/var/lib/docker/volumes/$volume_name/_data",
        "/home/${$data['instance']}/.env",
        "/home/${$data['instance']}/docker-compose.yml",
        "/home/${$data['instance']}/php.ini",
        "/home/${$data['instance']}/mysql.cnf",
        "/home/${$data['instance']}/www"
        // TODO: Handle SSL/TLS Certificates
    ];

    $to_export_str = implode('', $to_export);

    exec("tar -cvzf /home/${$data['instance']}/export/backup.tar.gz $to_export_str");

    return [
        'code' => 201,
        'body' => "instance_backup_created"
    ];
}
