<?php
 /*
    This file is part of the B2 package <http://github.com/yesbabylon/b2>
    Some Rights Reserved, Yesbabylon, 2025
    Licensed under MIT License <https://opensource.org/licenses/MIT>
*/

/**
 * Generates a TTL (Time-To-Live) integer representing the number of days a backup should be retained,
 * calculated from the current date, and determined to maintain a theoretical total of 14 backups,
 * distributed over a period of 4 months.
 *
 * Logic is based on the day number in the year:
 * - every 4 months, a backup is created with a TTL of 4 months
 * - every 3 months, a backup is created with a TTL of 3 months
 * - every 2 months, a backup is created with a TTL of 2 months
 * - every Sunday, a backup is created with a TTL of 1 month
 * - in other cases, a backup is created with a lifespan of 7 days
 *
 * @return int The number of days the backup should be retained.
 */
function get_ttl() {
    $ttl = 7;

    $day_of_week = (int) date('N');
    $day_of_year = (int) date('z');

    if($day_of_year % 112 == 0) {
        $ttl = 112;
    }
    elseif($day_of_year % 84 == 0) {
        $ttl = 84;
    }
    elseif($day_of_year % 56 == 0) {
        $ttl = 56;
    }
    elseif($day_of_week == 7) {
        $ttl = 28;
    }

    return $ttl;
}

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

    $gpg_name = gethostname();

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
    $config_files = [".env", "docker-compose.yml", "php.ini", "mysql.cnf"];
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


    $ttl = get_ttl();

    $backup_file = "/home/$instance/export/{$instance}_{$timestamp}_{$ttl}.tar";
    exec("cd $tmp_backup_dir && tar -cvf $backup_file $to_export_str");

    // Remove tmp directory for backup
    exec("rm -rf $tmp_backup_dir");

    // Restart docker containers
    exec("docker compose -f $docker_file_path start");

    if($data['encrypt']) {
        // Encrypt backup
        exec("gpg --trust-model always --output $backup_file.gpg --encrypt --recipient $gpg_name $backup_file");

        // Remove non-encrypted backup (keep only crypted one)
        unlink($backup_file);

        $backup_file = $backup_file.'.gpg';
    }

    instance_disable_maintenance_mode($instance);

    return [
        'code' => 201,
        'body' => [ 'result' => basename($backup_file) ]
    ];
}
