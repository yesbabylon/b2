<?php

/**
 * Restores a specific instance using the backup file matching the given backup_id.
 *
 * @param array{instance: string, backup_id: string, passphrase?: string} $data
 * @return array{code: int, body: string}
 * @throws Exception
 */
function instance_restore(array $data): array {
    if(!isset($data['instance'])) {
        throw new InvalidArgumentException("missing_instance", 400);
    }

    if(!is_string($data['instance']) || !instance_exists($data['instance'])) {
        throw new InvalidArgumentException("invalid_instance", 400);
    }

    if(!isset($data['backup_id'])) {
        throw new InvalidArgumentException("missing_backup_id", 400);
    }

    if(isset($data['passphrase']) && (!is_string($data['passphrase']) || empty($data['passphrase']))) {
        throw new InvalidArgumentException("invalid_passphrase", 400);
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

    $instance = $data['instance'];
    $backup_id = $data['backup_id'];
    $encrypted = isset($data['passphrase']);

    $possible_backup_files = [
        "/home/$instance/import/{$instance}_$backup_id.tar",
        "/home/$instance/export/{$instance}_$backup_id.tar"
    ];
    if($encrypted) {
        $possible_backup_files = array_map(
            function ($file) {return "$file.gpg";},
            $possible_backup_files
        );
    }

    $backup_file = null;
    foreach($possible_backup_files as $file) {
        if(file_exists($file)) {
            $backup_file = $file;
            break;
        }
    }

    if(is_null($backup_file)) {
        throw new Exception("backup_not_found", 404);
    }

    $tmp_restore_dir = "/home/$instance/tmp_restore";
    exec("rm -rf $tmp_restore_dir");
    if(!mkdir($tmp_restore_dir)) {
        throw new Exception("failed_create_tmp_restore_directory", 500);
    }

    if($encrypted) {
        if(!is_string($data['passphrase']) || empty($data['passphrase'])) {
            throw new InvalidArgumentException("invalid_passphrase", 400);
        }

        $encrypted_backup_file = $backup_file;
        $backup_file = preg_replace('/\.gpg$/', '', $backup_file);

        exec("gpg --batch --pinentry-mode=loopback --yes --passphrase {$data['passphrase']} --output $backup_file --decrypt $encrypted_backup_file");
    }

    exec("tar -xvf $backup_file -C $tmp_restore_dir", $output, $return_var);

    if($return_var === 0 && $encrypted) {
        unlink($backup_file);
    }

    if($return_var !== 0) {
        throw new Exception("failed_to_extract_backup_archive", 500);
    }

    instance_enable_maintenance_mode($instance);

    // Restore database
    exec("cd $tmp_restore_dir && gunzip backup.sql.gz");
    exec("docker exec $db_hostname /usr/bin/mysql -u $db_backup_username --password=$db_backup_password -e \"DROP DATABASE IF EXISTS equal; CREATE DATABASE equal;\"");
    exec("docker exec -i $db_hostname /usr/bin/mysql -u $db_backup_username --password=$db_backup_password equal < $tmp_restore_dir/backup.sql");

    // Stop docker containers
    $docker_file_path = "/home/$instance/docker-compose.yml";
    exec("docker compose -f $docker_file_path stop");

    // Restore config
    exec("cd $tmp_restore_dir && tar -xvf config.tar");
    $config_files = [".env", "docker-compose.yml", "conf"];
    foreach($config_files as $file) {
        exec("mv -f $tmp_restore_dir/$file /home/$instance");
    }

    // Restore filestore
    exec("cd $tmp_restore_dir && tar -xvzf filestore.tar.gz");
    exec("rm -rf /home/$instance/www");
    exec("mv $tmp_restore_dir/www /home/$instance");

    // Remove tmp directory for restore
    exec("rm -rf $tmp_restore_dir");

    // Restart docker containers
    exec("docker compose -f $docker_file_path start");

    instance_disable_maintenance_mode($instance);

    return [
        'code' => 200,
        'body' => "instance_restore_success"
    ];
}