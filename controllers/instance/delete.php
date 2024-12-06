<?php

/**
 * Delete an instance.
 *
 * @param array{instance: string} $data
 * @return array{code: int, body: string}
 * @throws Exception
 */
function instance_delete(array $data): array {
    if(!isset($data['instance'])) {
        throw new InvalidArgumentException("missing_instance", 400);
    }

    if(!is_string($data['instance']) || !instance_exists($data['instance'])) {
        throw new InvalidArgumentException("invalid_instance", 400);
    }

    // Stop and remove the instance with docker compose to /home/$data['instance']
    exec('docker compose -f /home/'.$data['instance'].'/docker-compose.yml down -v');

    // Create delete directory name
    $original_delete_directory = $delete_directory = $data['instance'].'_deleted';
    $counter = 1;

    // Loop until a unique directory name is found
    while (file_exists('/home/' . $delete_directory)) {
        $delete_directory = $original_delete_directory . '_' . $counter;
        $counter++;
    }

    // Rename the instance directory to /home/$data['instance']_deleted (add counter if it has been deleted previously)
    exec('mv /home/'.$data['instance'].' /home/'.$delete_directory);

    // Remove all files and directories in /home/$data['instance']_deleted but keep the directory
    exec('rm -rf /home/'.$data['instance'].'_deleted/*');

    // Remove maintenance folder
    exec('rm -rf /srv/docker/nginx/html/'.$data['instance']);

    // Delete linux user
    exec('userdel -f '.$data['instance']);

    return [
        'code' => 200,
        'body' => "instance_successfully_deleted"
    ];
}
