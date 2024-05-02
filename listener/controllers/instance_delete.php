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
        // Get the current working directory
        $pwd = getcwd();

        // going to /home/$data['instance']
        chdir('/home/' . $data['instance']);

        // Stop and remove the instance with docker-compose to /home/$data['instance']
        exec('docker-compose down -v');

        // Rename the instance directory to /home/$data['instance']_deleted
        exec('mv /home/' . $data['instance'] . ' /home/' . $data['instance'] . '_deleted');

        // Remove all files and directories in /home/$data['instance']_deleted but keep the directory
        exec('rm -rf /home/' . $data['instance'] . '_deleted/*');

        // return to the previous directory
        chdir($pwd);

        // Delete linux user
        exec('userdel -f ' . $data['instance']);
    }

    return [
        'code' => $status_code,
        'message' => $message
    ];
}
