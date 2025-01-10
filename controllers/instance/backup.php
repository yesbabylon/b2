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
        $gpg_email = getenv('GPG_EMAIL') ?: false;
        if(!$gpg_email) {
            throw new Exception("GPG_EMAIL_not_configured", 500);
        }
    }

    $db_hostname = getenv('DB_HOSTNAME') ?: false;
    if(!$db_hostname) {
        throw new Exception("DB_HOSTNAME_not_configured", 500);
    }

    $db_backup_username = getenv('DB_BACKUP_USERNAME') ?: false;
    if(!$db_backup_username) {
        throw new Exception("DB_BACKUP_USERNAME_not_configured", 500);
    }

    $db_backup_password = getenv('DB_BACKUP_PASSWORD') ?: false;
    if(!$db_backup_password) {
        throw new Exception("DB_BACKUP_PASSWORD_not_configured", 500);
    }

    $db_name = getenv('DB_NAME') ?: 'equal';

    $instance = $data['instance'];

    $tmp_backup_dir = "/home/$instance/tmp_backup";
    exec("rm -rf $tmp_backup_dir");
    if(!mkdir($tmp_backup_dir)) {
        throw new Exception("failed_create_tmp_restore_directory", 500);
    }

    instance_enable_maintenance_mode($instance);

    // Remove old export, if any
    exec("rm -rf /home/$instance/export/*");

    // Create mysql dump
    $create_mysql_dump = "docker exec $db_hostname /usr/bin/mysqldump -u $db_backup_username --password=\"$db_backup_password\" --single-transaction --skip-lock-tables $db_name > $tmp_backup_dir/backup.sql";
    exec($create_mysql_dump);

    // Stop docker containers
    $docker_file_path = "/home/$instance/docker-compose.yml";
    exec("docker compose -f $docker_file_path stop");

    // Compress dump
    $compress_mysql_dump = "cd $tmp_backup_dir && gzip -c backup.sql > backup.sql.gz";
    exec($compress_mysql_dump);

    // Create config.tar
    $config_files = [".env", "docker-compose.yml", "conf"];
    $config_files_str = implode(' ', $config_files);
    $create_configs_archive = "cd /home/$instance && tar -cvf $tmp_backup_dir/config.tar $config_files_str";
    exec($create_configs_archive);

    // Create filestore.tar.gz for www files
    $compress_filestore = "cd /home/$instance && tar -cvzf $tmp_backup_dir/filestore.tar.gz www";
    exec($compress_filestore);

    // Create archive to unite files
    $to_export = ["backup.sql.gz", "config.tar", "filestore.tar.gz"];
    $to_export_str = implode(' ', $to_export);
    $timestamp = date('Ymd').sprintf('%05d', time() - strtotime('today'));

    $backup_file = "/home/$instance/export/{$instance}_$timestamp.tar";
    exec("cd $tmp_backup_dir && tar -cvf $backup_file $to_export_str");

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
