<?php

/**
 * Creates a backup of a specific instance
 *
 * @param array{instance: string, encrypt?: bool} $data
 * @return array{code: int, body: string}
 * @throws Exception
 */
function instance_backup(array $data): array {
    if(!isset($data['instance'])) {
        throw new InvalidArgumentException("missing_instance", 400);
    }

    if(!is_string($data['instance']) || !instance_exists($data['instance'])) {
        throw new InvalidArgumentException("invalid_instance", 400);
    }

    if(isset($data['encrypt']) && !is_bool($data['encrypt'])) {
        throw new InvalidArgumentException("invalid_encrypt", 400);
    }
    elseif(!isset($data['encrypt'])) {
        $data['encrypt'] = true;
    }

    $gpg_email = null;
    if($data['encrypt']) {
        $gpg_email = getenv('GPG_EMAIL') ?? false;
        if(empty($gpg_email)) {
            throw new Exception("GPG_EMAIL_not_configured", 500);
        }
    }

    // TODO: Put in maintenance mode

    $instance = $data['instance'];

    $docker_file_path = "/home/$instance/docker-compose.yml";
    exec("docker compose -f $docker_file_path stop");

    // Remove old export, if any
    exec("rm -rf /home/$instance/export");
    exec("mkdir /home/$instance/export");

    // Backup
    $volume_name = str_replace('.', '', $instance).'_db_data';

    $to_export = [
        "/var/lib/docker/volumes/$volume_name/_data",
        "/home/$instance/.env",
        "/home/$instance/docker-compose.yml",
        "/home/$instance/php.ini",
        "/home/$instance/mysql.cnf",
        "/home/$instance/www"
    ];

    $timestamp = date('YmdHis');
    $to_export_str = implode(' ', $to_export);

    $backup_file = "/home/$instance/export/{$instance}_$timestamp.tar.gz";

    exec("tar -cvzf $backup_file $to_export_str");

    exec("docker compose -f $docker_file_path start");

    if($data['encrypt']) {
        exec("gpg --trust-model always --output $backup_file.gpg --encrypt --recipient $gpg_email $backup_file");
        exec("rm $backup_file");
    }

    // TODO: Remove from maintenance mode

    return [
        'code' => 201,
        'body' => "instance_backup_created"
    ];
}
