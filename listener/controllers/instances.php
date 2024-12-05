<?php

/**
 * Returns list of instances present on the host.
 *
 * @param array{with_deleted?: bool} $data
 * @return array{code: int, body: string[]}
 * @throws Exception
 */
function instances(array $data): array {
    if(isset($data['with_deleted']) && !is_bool($data['with_deleted'])) {
        throw new InvalidArgumentException("invalid_with_deleted", 400);
    }

    // Get the list of instances
    $directories = scandir('/home');
    if($directories === false) {
        throw new Exception("could_not_read_home_directory", 500);
    }

    // Remove the '.', '..', 'ubuntu' and 'docker' entries
    $directories = array_values(array_diff($directories, ['.', '..', 'ubuntu', 'docker']));

    // Add also deleted instances
    $with_deleted = $data['with_deleted'] ?? false;

    $instances = [];
    foreach($directories as $instance) {
        if($with_deleted || strpos($instance, '_deleted') === false) {
            $instances[] = $instance;
        }
    }

    return [
        'code' => 200,
        'body' => $instances
    ];
}
