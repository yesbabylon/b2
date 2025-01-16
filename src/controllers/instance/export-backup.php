<?php

/**
 * Exports a backup file to the configured backup host.
 *
 * @param array{instance: string, backup_id: string} $data
 * @return array{code: int, body: string}
 * @throws Exception
 */
function instance_export_backup(array $data): array {
    if(!isset($data['instance'])) {
        throw new InvalidArgumentException("missing_instance", 400);
    }

    if(!is_string($data['instance']) || !instance_exists($data['instance'])) {
        throw new InvalidArgumentException("invalid_instance", 400);
    }

    if(!isset($data['backup_id'])) {
        throw new InvalidArgumentException("missing_backup_id", 400);
    }

    $backup_file = '/home/'.$data['instance'].'/export/'.$data['instance'].'_'.$data['backup_id'].'.tar.gpg';
    if(!file_exists($backup_file)) {
        $backup_file = '/home/'.$data['instance'].'/export/'.$data['instance'].'_'.$data['backup_id'].'.tar';
        if(!file_exists($backup_file)) {
            throw new Exception("backup_not_found", 404);
        }
    }

    $backup_host = getenv('BACKUP_HOST') ?: false;
    if(!$backup_host) {
        throw new Exception("missing_BACKUP_HOST", 500);
    }

    $backup_host_url = 'http://'.$backup_host.':8000';

    $max_retries = 5;
    $retry_count = 0;

    while(true) {
        $create_token_response = create_token($backup_host_url, $data['instance']);
        if($create_token_response !== false) {
            break;
        }
        if($retry_count >= $max_retries) {
            throw new Exception("error_requesting_token", 500);            
        }
        $retry_count++;
        sleep(60 * $retry_count); 
    }

    // Get token and created user ftp credentials
    $values = json_decode($create_token_response, true);

    if(!$values) {
        throw new Exception("unexpected_response_format", 500);
    }

    ['token' => $token, 'credentials' => $ftp_credentials] = $values ?? []; 

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

    if(!ftp_put($ftp_connection_id, basename($backup_file), $backup_file, FTP_BINARY)) {
        ftp_close($ftp_connection_id);
        release_token($backup_host_url, $data['instance'], $token);

        throw new Exception("error_while_exporting_backup_file", 500);
    }

    ftp_close($ftp_connection_id);
    release_token($backup_host_url, $data['instance'], $token);

    return [
        'code' => 200,
        'body' => [ 'result' => 'backup_sent' ]
    ];
}
