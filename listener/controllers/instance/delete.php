<?php

/**
 * Delete an instance.
 *
 * @param array{instance: string} $data
 * @return array{code: int, body: string}
 */
function instance_delete(array $data): array {
    if(
        !isset($data['instance']) || !is_string($data['instance']) || strlen($data['instance']) === 0
        || preg_match('/^(?!\-)(?:[a-zA-Z0-9\-]{1,63}\.)+[a-zA-Z]{2,}$/', $data['instance']) === 0
        || !is_dir('/home/'.$data['instance'])
    ) {
        throw new InvalidArgumentException("invalid_instance", 400);
    }

    // Stop and remove the instance with docker compose to /home/$data['instance']
    exec('docker compose -f /home/'.$data['instance'].'/docker-compose.yml down -v');

    // Rename the instance directory to /home/$data['instance']_deleted
    exec('mv /home/'.$data['instance'].' /home/'.$data['instance'].'_deleted');

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
