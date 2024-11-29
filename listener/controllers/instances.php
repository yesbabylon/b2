<?php

/**
 * Returns list of instances present on the host, separates active and deleted instances.
 *
 * @return array{
 *     code: int,
 *     body: array{
 *         active_instances: string[],
 *         deleted_instances: string[]
 *     }
 * }
 * @throws Exception
 */
function instances(): array {
    // Get the list of instances
    $directories = scandir('/home');
    if($directories === false) {
        throw new Exception("could_not_read_home_directory", 500);
    }

    // Remove the '.' and '..' and 'ubuntu' and 'docker' entries
    $directories = array_values(array_diff($directories, ['.', '..', 'ubuntu', 'docker']));

    // Separate active and deleted instances
    $active_instances = [];
    $deleted_instances = [];
    foreach($directories as $instance) {
        if(strpos($instance, '_deleted') === false) {
            $active_instances[] = $instance;
        }
        else {
            $deleted_instances[] = $instance;
        }
    }

    return [
        'code' => 200,
        'body' => compact('active_instances', 'deleted_instances')
    ];
}
