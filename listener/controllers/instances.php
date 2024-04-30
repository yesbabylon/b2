<?php

/**
 * Get the list of instances.
 * ! Not sure
 *
 * @param array $data
 * @return array{code: int, message: string}
 */
function instances(array $data): array
{
    $status_code = 201;
    $message = '';

    // Get the list of instances
    $instances = scandir('/home');

    if ($instances === false) {
        $status_code = 500;
    } else {
        // Remove the '.' and '..' and 'ubuntu' and 'docker' entries
        $instances = array_diff($instances, ['.', '..', 'ubuntu', 'docker']);
        $message = json_encode($instances, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    return [
        'code' => $status_code,
        'message' => $message
    ];
}