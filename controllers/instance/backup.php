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

    $db_backup_username = getenv('DB_BACKUP_USERNAME') ?? false;
    if(empty($db_backup_username)) {
        throw new Exception("DB_BACKUP_USERNAME_not_configured", 500);
    }

    $db_backup_password = getenv('DB_BACKUP_PASSWORD') ?? false;
    if(empty($db_backup_password)) {
        throw new Exception("DB_BACKUP_PASSWORD_not_configured", 500);
    }

    $instance = $data['instance'];

    $tmp_backup_dir = "/home/$instance/tmp_backup";
    exec("rm -rf $tmp_backup_dir");
    if(!mkdir($tmp_backup_dir)) {
        throw new Exception("failed_create_tmp_restore_directory", 500);
    }

    instance_enable_maintenance_mode($instance);

    // Stop docker containers
    $docker_file_path = "/home/$instance/docker-compose.yml";
    exec("docker compose -f $docker_file_path stop");

    // Remove old export, if any
    exec("rm -rf /home/$instance/export");
    exec("mkdir /home/$instance/export");

    // Create mysql dump
    $create_mysql_dump = "docker exec $db_hostname /usr/bin/mysqldump -u $db_backup_username --password=\"$db_backup_password\" --single-transaction --skip-lock-tables equal > $tmp_backup_dir/backup.sql";
    exec($create_mysql_dump);

    // Compress dump
    $compress_mysql_dump = "gzip -c $tmp_backup_dir/backup.sql > $tmp_backup_dir/backup.sql.gz";
    exec($compress_mysql_dump);

    // Create config.tar
    $config_files_paths = [
        "/home/$instance/.env",
        "/home/$instance/docker-compose.yml",
        "/home/$instance/php.ini",
        "/home/$instance/mysql.cnf",
    ];
    $config_files_paths_str = implode(' ', $config_files_paths);
    $create_configs_archive = "tar -cvf $tmp_backup_dir/config.tar $config_files_paths_str";
    exec($create_configs_archive);

    // Create filestore.tar.gz for www files
    $compress_filestore = "tar -cvzf $tmp_backup_dir/filestore.tar.gz /home/$instance/www";
    exec($compress_filestore);

    // Create archive to unite files
    $to_export = [
        "$tmp_backup_dir/backup.sql.gz",
        "$tmp_backup_dir/config.tar",
        "$tmp_backup_dir/filestore.tar.gz"
    ];
    $timestamp = date('YmdHis');
    $backup_file = "/home/$instance/export/{$instance}_$timestamp.tar";
    $to_export_str = implode(' ', $to_export);
    exec("tar -cvf $backup_file $to_export_str");

    // Remove tmp directory for backup
    exec("rm -rf $tmp_backup_dir");

    // Restart docker containers
    exec("docker compose -f $docker_file_path start");

    if($data['encrypt']) {
        // Encrypt backup
        exec("gpg --trust-model always --output $backup_file.gpg --encrypt --recipient $gpg_email $backup_file");

        // Remove not encrypted backup to keep only secure one
        unlink($backup_file);
    }

    instance_disable_maintenance_mode($instance);

    return [
        'code' => 201,
        'body' => "instance_backup_created"
    ];
}
