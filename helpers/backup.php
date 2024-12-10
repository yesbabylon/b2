<?php

/**
 * Creates a backup token for specific instance.
 *
 * @param string $backup_host_url
 * @param string $instance
 * @return false|string
 */
function create_token(string $backup_host_url, string $instance) {
    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode(['instance' => $instance])
        ]
    ];

    $context = stream_context_create($options);

    return file_get_contents($backup_host_url.'/token/create', false, $context);
}

/**
 * Releases a backup token for a specific instance.
 *
 * @param string $backup_host_url
 * @param string $instance
 * @param string $token
 * @return false|string
 */
function release_token(string $backup_host_url, string $instance, string $token)
{
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

    return file_get_contents($backup_host_url.'/token/release', false, $context);
}
