<?php

/**
 * Imports a backup file from the configured backup host.
 *
 * @param array{instance: string, backup_id: string} $data
 * @return array{code: int, body: string}
 * @throws Exception
 */
function instance_import_backup(array $data): array {
    if(!isset($data['instance'])) {
        throw new InvalidArgumentException("missing_instance", 400);
    }

    if(!is_string($data['instance']) || !instance_exists($data['instance'])) {
        throw new InvalidArgumentException("invalid_instance", 400);
    }

    if(!isset($data['backup_id'])) {
        throw new InvalidArgumentException("missing_backup_id", 400);
    }

    $backup_host = getenv('BACKUP_HOST') ?: false;
    if(!$backup_host) {
        throw new Exception("missing_BACKUP_HOST", 500);
    }

    $backup_host_url = 'http://'.$backup_host.':8000';

    $create_token_response = create_token($backup_host_url, $data['instance'], true);
    if($create_token_response === false) {
        throw new Exception("error_while_asking_for_token", 500);
    }

    // Get token and created user ftp credentials
    ['token' => $token, 'credentials' => $ftp_credentials] = json_decode($create_token_response, true);

    $ftp_connection_id = ftp_connect($backup_host);
    if(!$ftp_connection_id) {
        release_token($backup_host_url, $data['instance'], $token);

        throw new Exception("could_not_connect_to_ftp_server", 500);
    }

    if(!ftp_login($ftp_connection_id, $ftp_credentials['username'], $ftp_credentials['password'])) {
        ftp_close($ftp_connection_id);
        release_token($backup_host_url, $data['instance'], $token);

        throw new Exception("could_not_log_in_ftp_server", 500);
    }

    $backup_file = '/home/'.$data['instance'].'/import/'.$data['instance'].'_'.$data['backup_id'].'.tar.gpg';
    if(!ftp_get($ftp_connection_id, $backup_file, basename($backup_file), FTP_BINARY)) {
        $backup_file = '/home/'.$data['instance'].'/import/'.$data['instance'].'_'.$data['backup_id'].'.tar';
        if(!ftp_get($ftp_connection_id, $backup_file, basename($backup_file), FTP_BINARY)) {
            ftp_close($ftp_connection_id);
            release_token($backup_host_url, $data['instance'], $token);

            throw new Exception("error_while_importing_backup_file", 500);
        }
    }

    ftp_close($ftp_connection_id);
    release_token($backup_host_url, $data['instance'], $token);

    return [
        'code' => 200,
        'body' => "backup_imported"
    ];
}
