<?php
/*
    This file is part of the B2 package <http://github.com/yesbabylon/b2>
    Some Rights Reserved, Yesbabylon, 2026
    Licensed under MIT License <https://opensource.org/licenses/MIT>
*/

/**
 * Returns the backups stored on the configured backup host, grouped by instance.
 *
 * @param array $data
 * @return array{code: int, body: array<string, string[]>}
 * @throws Exception
 */
function backups(array $data): array {
    $backup_host = getenv('BACKUP_HOST') ?: false;
    if(!$backup_host) {
        throw new Exception("missing_BACKUP_HOST", 500);
    }

    $response = @file_get_contents('http://'.$backup_host.':8000/backups');
    if($response === false) {
        throw new Exception("could_not_retrieve_backups", 502);
    }

    $backups = json_decode($response, true);
    if(!is_array($backups)) {
        throw new Exception("unexpected_response_format", 502);
    }

    foreach($backups as $instance => $instance_backups) {
        if(!is_string($instance)
            || !is_array($instance_backups)
            || array_filter($instance_backups, fn($filename) => !is_string($filename))
        ) {
            throw new Exception("unexpected_response_format", 502);
        }
    }

    return [
        'code' => 200,
        'body' => $backups
    ];
}
