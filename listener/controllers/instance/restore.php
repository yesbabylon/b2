<?php

/**
 * Restore a specific instance using the backup file matching the given backup_id.
 *
 * @param array{instance: string, backup_id: string} $data
 * @return array{code: int, message: string}
 * @throws Exception
 */
function instance_restore(array $data): array {
    if(!isset($data['instance'])) {
        throw new InvalidArgumentException("missing_instance", 400);
    }

    if(
        in_array($data['instance'], ['..', '.', 'docker', 'ubuntu'])
        || $data['instance'] !== basename($data['instance'])
    ) {
        throw new InvalidArgumentException("invalid_instance", 400);
    }

    if(!file_exists('/home/'.$data['instance']) || !is_dir('/home/'.$data['instance'])) {
        throw new \Exception("instance_not_found", 404);
    }

    if(!isset($data['backup_id'])) {
        throw new InvalidArgumentException("missing_backup_id", 400);
    }

    if(file_exists('/home/'.$data['instance'].'/import/backup-'.$data['backup_id'].'.tar.gz')) {
        $backup_file = '/home/'.$data['instance'].'/import/backup-'.$data['backup_id'].'.tar.gz';
    }
    elseif(file_exists('/home/'.$data['instance'].'/export/backup-'.$data['backup_id'].'.tar.gz')) {
        $backup_file = '/home/'.$data['instance'].'/export/backup-'.$data['backup_id'].'.tar.gz';
    }
    else {
        throw new \Exception("backup_not_found", 404);
    }

    // TODO: Put in maintenance mode

    $instance_escaped = escapeshellarg($data['instance']);

    $tmp_dir = "/home/$instance_escaped/tmp_restore";

    exec("rm -rf $tmp_dir");
    exec("mkdir $tmp_dir", $output, $return_var);
    if($return_var !== 0) {
        throw new \Exception("failed_create_tmp_restore_directory", 500);
    }

    exec("tar -xvzf $backup_file -C $tmp_dir", $output, $return_var);
    if($return_var !== 0) {
        throw new \Exception("failed_to_extract_backup_archive", 500);
    }

    $map_original_paths = [
        "$tmp_dir/_data"                => "/var/lib/docker/volumes/{$instance_escaped}_db_data/_data",
        "$tmp_dir/.env"                 => "/home/$instance_escaped/.env",
        "$tmp_dir/docker-compose.yml"   => "/home/$instance_escaped/docker-compose.yml",
        "$tmp_dir/php.ini"              => "/home/$instance_escaped/php.ini",
        "$tmp_dir/mysql.cnf"            => "/home/$instance_escaped/mysql.cnf",
        "$tmp_dir/www"                  => "/home/$instance_escaped/www"
    ];

    foreach($map_original_paths as $src => $dest) {
        if(file_exists($src)) {
            exec('rm -r $dest', $output, $return_var);
            if ($return_var !== 0) {
                throw new \Exception("failed_to_restore", 500);
            }

            exec("cp -r $src $dest", $output, $return_var);
            if ($return_var !== 0) {
                throw new \Exception("failed_to_restore", 500);
            }
        }
    }

    exec("rm -rf $tmp_dir");

    // TODO: Remove maintenance mode

    return [
        'code' => 200,
        'body' => "instance_successfully_restored"
    ];
}