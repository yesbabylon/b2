<?php

include_once '../helpers/http-response.php';

/**
 * Create a user instance with the specified data.
 *
 * @param array $data
 * @throws Exception
 */
function instance_create(array $data): array {

    // Set default flags
    $flags = '';

    // Check values in the request body
    if (isset($data['symbiose']) && $data['symbiose'] === true) {
        $flags .= ' -s';
    }

    if (isset($data['equalpress']) && $data['equalpress'] === true) {
        $flags .= ' -w';
    }

    // Remove 'symbiose' and 'equalpress' keys from $data
    unset($data['symbiose'], $data['equalpress']);

    // Path to the directory for the .env file
    $env_file_path = '/root/b2/equal/.env';

    // Check if the .env file exists, and create it if not
    if (!file_exists($env_file_path)) {
        // Create the .env file
        touch($env_file_path);

        // Set permissions for the .env file
        chmod($env_file_path, 0644);
    } else {
        // Clear the contents of the .env file
        file_put_contents($env_file_path, '');
    }

    // Write data to the .env file
    foreach ($data as $key => $value) {
        // Write data to the .env file
        file_put_contents($env_file_path, "$key=$value\n", FILE_APPEND);
    }

    // Execute the init.bash script with appropriate flags
    $init_bash_script = '/root/b2/equal/init.bash';

    // Execute the init.bash script with the flags
    exec("bash $init_bash_script $flags 2>&1");

    return [
        'code'      => 201,
        'message'   => ''
    ];
}