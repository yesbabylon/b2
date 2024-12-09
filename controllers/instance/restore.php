<?php

/**
 * Restore a specific instance using the backup file matching the given backup_id.
 *
 * @param array{instance: string, backup_id: string} $data
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

    $possible_backup_files = [
        '/home/'.$data['instance'].'/import/backup_'.$data['backup_id'].'.tar.gz',
        '/home/'.$data['instance'].'/import/backup_'.$data['backup_id'].'.tar.gz.gpg',
        '/home/'.$data['instance'].'/export/backup_'.$data['backup_id'].'.tar.gz',
        '/home/'.$data['instance'].'/export/backup_'.$data['backup_id'].'.tar.gz.gpg'
    ];

    $backup_file = null;
    foreach ($possible_backup_files as $file) {
        if(file_exists($file)) {
            $backup_file = $file;
            break;
        }
    }

    if(is_null($backup_file)) {
        throw new Exception("backup_not_found", 404);
    }

    // TODO: Put in maintenance mode

    $instance = $data['instance'];

    $tmp_restore_dir = "/home/$instance/tmp_restore";

    exec("rm -rf $tmp_restore_dir");
    exec("mkdir $tmp_restore_dir", $output, $return_var);
    if($return_var !== 0) {
        throw new Exception("failed_create_tmp_restore_directory", 500);
    }

    if(substr($backup_file, -strlen('.gpg')) === '.gpg') {
        $encrypted_backup_file = $backup_file;
        $backup_file = preg_replace('/\.gpg$/', '', $backup_file);

        exec("gpg --output $backup_file --decrypt $encrypted_backup_file");
    }

    exec("tar -xvzf $backup_file -C $tmp_restore_dir", $output, $return_var);
    if($return_var !== 0) {
        throw new Exception("failed_to_extract_backup_archive", 500);
    }

    $volume_name = str_replace('.', '', $instance).'_db_data';

    $original_paths = [
        "/var/lib/docker/volumes/$volume_name/_data",
        "/home/$instance/.env",
        "/home/$instance/docker-compose.yml",
        "/home/$instance/php.ini",
        "/home/$instance/mysql.cnf",
        "/home/$instance/www"
    ];

    $docker_file_path = escapeshellarg("/home/$instance/docker-compose.yml");
    exec("docker compose -f $docker_file_path stop");

    foreach($original_paths as $dest) {
        $src = $tmp_restore_dir.$dest;
        if(file_exists($src)) {
            $dest_escaped = escapeshellarg($dest);
            if(file_exists($dest)) {
                exec("rm -rf $dest_escaped");
            }

            $src_escaped = escapeshellarg($src);
            exec("cp -rp $src_escaped $dest_escaped");
        }
    }

    $tmp_restore_dir_escaped = escapeshellarg($tmp_restore_dir);
    exec("rm -rf $tmp_restore_dir_escaped");

    exec("docker compose -f $docker_file_path start");

    // TODO: Remove from maintenance mode

    return [
        'code' => 200,
        'body' => "instance_restore_success"
    ];
}