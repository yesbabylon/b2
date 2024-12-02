<?php

/**
 * Sends a backup file to a backup host.
 * ! Note sure
 *
 * @param array{instance: string, backup_filename: string} $data
 * @return array{code: int, body: string}
 * @throws Exception
 */
function instance_send_backup(array $data): array {
    if(!isset($data['instance'])) {
        throw new InvalidArgumentException('missing_instance', 400);
    }

    if(!isset($data['backup_filename'])) {
        throw new InvalidArgumentException('missing_backup_filename', 400);
    }

    $backup_host = 'http://backup-host';

    // While the token is not available, wait for it 60 seconds and try again.
    while($ftp_credentials = file_get_contents($backup_host . '/token')) {
        sleep(60);
    }

    ['username' => $username, 'password' => $password] = $ftp_credentials['message']['credentials'];

    // Connect to the backup host
    $ftp_connection = ftp_connect($backup_host);

    // Login to the backup host
    if(!$ftp_connection || !ftp_login($ftp_connection, $username, $password)) {
        throw new Exception('Server Error while connecting to backup host', 500);
    }

    $backup_file = "/home/${$data['instance']}/export/${$data['backup_filename']}";
    if(!ftp_put($ftp_connection, $data['backup_filename'], $backup_file, FTP_BINARY)) {
        throw new Exception('error_while_sending_backup', 500);
    }

    ftp_close($ftp_connection);

    $jwt = file_get_contents('/home/status/jwt.txt');

    // Release the token on the backup host
    file_get_contents("$backup_host/token-release", false, stream_context_create([
        'http' => [
            'method'    => 'POST',
            'header'    => 'Content-Type: application/json',
            'content'   => json_encode([
                'JWT'       => $jwt,
                'instance'  => $data['instance']
            ])
        ]
    ]));

    return [
        'code' => 200,
        'body' => "backup_sent"
    ];
}
