<?php

/**
 * Imports a backup file from the configured backup host.
 *
 * @param array{instance: string, backup_id?: string} $data
 * @return array{code: int, body: string}
 * @throws Exception
 */
function instance_import_backup(array $data): array {
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
        throw new Exception("instance_not_found", 404);
    }

    if(!isset($data['backup_id'])) {
        // TODO: handle get latest if no id provided
        throw new InvalidArgumentException("missing_backup_id", 400);
    }

    $backup_host_url = getenv('BACKUP_HOST_URL') ?? false;
    if(empty($backup_host_url)) {
        throw new Exception("BACKUP_HOST_URL_not_configured", 500);
    }

    $backup_host_ftp = getenv('BACKUP_HOST_FTP') ?? false;
    if(empty($backup_host_ftp)) {
        throw new Exception("BACKUP_HOST_FTP_not_configured", 500);
    }

    $create_token_response = create_token($backup_host_url, $data['instance']);
    if ($create_token_response === false) {
        throw new Exception("error_while_asking_for_token", 500);
    }

    // Get token and created user ftp credentials
    ['token' => $token, 'credentials' => $ftp_credentials] = json_decode($create_token_response, true);

    $ftp_connection_id = ftp_connect($backup_host_ftp);
    if(!$ftp_connection_id) {
        throw new Exception("could_not_connect_to_ftp_server", 500);
    }

    if(!ftp_login($ftp_connection_id, $ftp_credentials['username'], $ftp_credentials['password'])) {
        ftp_close($ftp_connection_id);
        throw new Exception("could_not_log_in_ftp_server", 500);
    }

    $backup_file = '/home/'.$data['instance'].'/import/backup_'.$data['backup_id'].'.tar.gz';
    if(!ftp_get($ftp_connection_id, $backup_file, basename($backup_file), FTP_BINARY)) {
        ftp_close($ftp_connection_id);
        throw new Exception("error_while_importing_backup_file", 500);
    }

    ftp_close($ftp_connection_id);

    release_token($backup_host_url, $data['instance'], 'dddd');

    return [
        'code' => 200,
        'body' => "backup_imported"
    ];
}
