<?php

/**
 * Retrieve information about a given docker instance.
 *
 * @param array $data
 * @return array{code: int, message: string}
 */
function instance_info(array $data): array
{
    $json = exec('docker stats ' . $data['instance'] . ' --no-stream --format "{{ json . }}"');
    $result = json_decode($json);
    $response = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    return [
        'code' => 201,
        'message' => $response
    ];
}