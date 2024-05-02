<?php

/**
 * Retrieve information about a given docker instance.
 *
 * @param array{instance: string} $data
 * @return array{code: int, message: string}
 */
function instance_info(array $data): array
{
    $status_code = 201;
    $message = '';

    $json = exec('docker stats ' . $data['instance'] . ' --no-stream --format "{{ json . }}"');
    $result = json_decode($json);

    if ($result === null) {
        $status_code = 404;
    } else {
        $message = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    return [
        'code' => $status_code,
        'message' => $message
    ];
}
