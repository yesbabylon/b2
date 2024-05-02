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
    $directories = scandir('/home');

    if ($directories === false) {
        $status_code = 500;
    } else {
        // Remove the '.' and '..' and 'ubuntu' and 'docker' entries
        $directories = array_values(array_diff($directories, ['.', '..', 'ubuntu', 'docker']));

        // remove _deleted instances
        $active_instances = [];

        foreach ($directories as $instance) {
            if (str_contains($instance, '_deleted') === false) {
                $active_instances[] = $instance;
            }
        }

        $message = json_encode($active_instances, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    return [
        'code' => $status_code,
        'message' => $message
    ];
}
