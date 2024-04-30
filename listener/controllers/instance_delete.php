<?php

/**
 * Delete an instance.
 * ! Not sure
 *
 * @param array{instance: string} $data
 * @return array
 */
function instance_delete(array $data): array
{
    $status_code = 201;
    $message = '';

    if (!isset($data['instance']) || !is_string($data['instance']) || strlen($data['instance']) === 0) {
        $status_code = 400;
    } else {
        exec('docker stop ' . $data['instance']);
        exec('docker rm -f ' . $data['instance']);
    }

    return [
        'code' => $status_code,
        'message' => $message
    ];
}