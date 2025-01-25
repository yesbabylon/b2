<?php
 /*
    This file is part of the B2 package <http://github.com/yesbabylon/b2>
    Some Rights Reserved, Yesbabylon, 2025
    Licensed under MIT License <https://opensource.org/licenses/MIT>
*/

/**
 * Creates a backup token for specific instance.
 *
 * @param string $backup_host_url
 * @param string $instance
 * @param bool $no_delay Should the backup server retry multiple time if max token reached
 * @return false|string
 */
function create_token(string $backup_host_url, string $instance, bool $no_delay = false) {
    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode(compact('instance', 'no_delay')),
        ]
    ];

    $context = stream_context_create($options);

    return file_get_contents($backup_host_url.'/instance/create-token', false, $context);
}

/**
 * Releases a backup token for a specific instance.
 *
 * @param string $backup_host_url
 * @param string $instance
 * @param string $token
 * @return false|string
 */
function release_token(string $backup_host_url, string $instance, string $token) {
    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode([
                'instance'  => $instance,
                'token'     => $token
            ])
        ]
    ];

    $context = stream_context_create($options);

    return file_get_contents($backup_host_url.'/instance/release-token', false, $context);
}
