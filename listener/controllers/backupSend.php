<?php

/**
 * Send a backup to a backup host.
 * ! Note sure
 *
 * @param array{instance: string, backup_filename: string} $data
 * @return array{code: int, message: string}
 * @throws Exception
 */
function backupSend(array $data): array
{
    $status_code = 201;
    $message = '';

    if (!isset($data['instance']) || !isset($data['backup_filename'])) {
        throw new InvalidArgumentException('Bad Request', 400);
    }

    $backup_host = 'http://backup-host';

    // While the token is not available, wait for it 60 seconds and try again.
    while ($ftp_credentials = file_get_contents($backup_host . '/token')) {
        sleep(60);
    }

    $username = $ftp_credentials['message']['credentials']['username'];
    $password = $ftp_credentials['message']['credentials']['password'];

    // Connect to the backup host
    $ftpConnection = ftp_connect($backup_host);

    // Login to the backup host
    if (!$ftpConnection || !ftp_login($ftpConnection, $username, $password)) {
        throw new Exception('Server Error while connecting to backup host', 500);
    }

    $backup_file = '/home/' . $data['instance'] . '/export/' . $data['backup_filename'];

    // Send the backup to the backup host
    if (!ftp_put($ftpConnection, $data['backup_filename'], $backup_file)) {
        throw new Exception('Server Error while sending backup', 500);
    }

    $message = "Backup: " . $data['backup_filename'] . " sent";

    ftp_close($ftpConnection);

    $jwt = file_get_contents('/home/status/jwt.txt');

    // Release the token on the backup host
    file_get_contents($backup_host . '/token-release', false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode([
                'JWT' => $jwt,
                'instance' => $data['instance']
            ])
        ]
    ]));

    return [
        'code' => $status_code,
        'message' => $message
    ];
}
