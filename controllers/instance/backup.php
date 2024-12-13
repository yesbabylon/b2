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

    $db_hostname = getenv('DB_HOSTNAME') ?? false;
    if(empty($db_hostname)) {
        throw new Exception("DB_HOSTNAME_not_configured", 500);
    }

    $backup_username = getenv('BACKUP_USERNAME') ?? false;
    if(empty($username)) {
        throw new Exception("BACKUP_USERNAME_not_configured", 500);
    }

    $backup_password = getenv('BACKUP_PASSWORD') ?? false;
    if(empty($password)) {
        throw new Exception("BACKUP_PASSWORD_not_configured", 500);
    }

    $instance = $data['instance'];

    instance_enable_maintenance_mode($instance);

    // Stop docker containers
    $docker_file_path = "/home/$instance/docker-compose.yml";
    exec("docker compose -f $docker_file_path stop");

    // Remove old export, if any
    exec("rm -rf /home/$instance/export");
    exec("mkdir /home/$instance/export");

    $create_mysql_dump = "docker exec $db_hostname /usr/bin/mysqldump -u $backup_username --password=$backup_password --single-transaction --skip-lock-tables equal > database.sql gzip database.sql";
    exec($create_mysql_dump);

    $to_export = [
        "/home/$instance/.env",
        "/home/$instance/docker-compose.yml",
        "/home/$instance/php.ini",
        "/home/$instance/mysql.cnf",
        "/home/$instance/www"
    ];

    $timestamp = date('YmdHis');
    $backup_file = "/home/$instance/export/{$instance}_$timestamp.tar.gz";

    // Unite files to back up in one file
    $to_export_str = implode(' ', $to_export);
    exec("tar -cvzf $backup_file $to_export_str");

    // Restart docker containers
    exec("docker compose -f $docker_file_path start");

    if($data['encrypt']) {
        // Encrypt backup
        exec("gpg --trust-model always --output $backup_file.gpg --encrypt --recipient $gpg_email $backup_file");

        // Remove not encrypted backup to keep only secure one
        exec("rm $backup_file");
    }

    instance_disable_maintenance_mode($instance);

    return [
        'code' => 201,
        'body' => "instance_backup_created"
    ];
}
