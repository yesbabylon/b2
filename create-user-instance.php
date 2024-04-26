<?php

// Include the http-response.php file to use the send_http_response function
include_once 'instance_management/http-response.php';

// Verify that the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_http_response("Method not allowed!", 405);
    exit;
}

// Check if the URL is correct
$allowed_routes = [
    '/create-user-instance'
];

if (!in_array($_SERVER['REQUEST_URI'], $allowed_routes)) {
    send_http_response("Unknown route", 404);
    exit;
}

try {
    // Get the request body
    $json_data = file_get_contents("php://input");

    // Decode JSON data
    $data = json_decode($json_data, true);

    // Check if data decoded successfully
    if ($data === null) {
        send_http_response("JSON data is invalid!", 400);
    }

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

    $output = '';
    $exit_code = 0;
    // Execute the init.bash script with the flags
    exec("bash $init_bash_script $flags 2>&1", $output, $exit_code);

    // Respond with HTTP status code 200 (OK)
    send_http_response("User instance created successfully!", 200);
} catch (Exception $e) {
    // Respond with the exception message and status code
    send_http_response($e->getMessage(), $e->getCode());
}

