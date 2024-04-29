<?php

// Include the http-response.php file to use the send_http_response function
include_once 'helpers/http-response.php';

try {
    $allowed_routes = [
        '/create-user-instance'
    ];

    // Check if the requested route is allowed
    if (!in_array($_SERVER['REQUEST_URI'], $allowed_routes)) {
        throw new Exception("Unknown route", 404);
    }

    // Get the request body
    $json_data = file_get_contents("php://input");

    // Decode JSON data
    $data = json_decode($json_data, true);

    // Check if data decoded successfully
    if ($data === null || gettype($data) !== 'array') {
        throw new Exception("JSON data is invalid!", 400);
    }

    // search if the file named as the same as the route exists in controllers directory
    $controller_file = __DIR__ . '/controllers' . $_SERVER['REQUEST_URI'] . '.php';

    if (!file_exists($controller_file)) {
        throw new Exception("Controller file not found", 404);
    }

    // Include the controller file
    include_once $controller_file;

    // format the request route to get the controller function name
    $controller_function = str_replace('-', '_', str_replace('/', '', $_SERVER['REQUEST_URI']));

    // Call the controller function with the request data
    $controller_function($data);

} catch (Exception $e) {
    // Respond with the exception message and status code
    send_http_response($e->getMessage(), $e->getCode());
}

