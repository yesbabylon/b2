<?php

// Include the http-response.php file to use the send_http_response function
include_once 'helpers/http-response.php';

$code = 200;
$message = '';

try {
    $allowed_routes = [
            '/reboot',
            '/info',
            '/instances',
            '/instance/info',
            '/instance/create',
            '/instance/delete',
            '/instance/restore'
        ];

    // By convention, we accept only POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("method_not_allowed", 405);
    }

    // Check if the requested route is allowed
    if (!in_array($_SERVER['REQUEST_URI'], $allowed_routes)) {
        throw new Exception("unknown_route", 404);
    }

    if($_SERVER['CONTENT_TYPE'] != 'application/json') {
        throw new Exception("invalid_body", 400);
    }

    // Get the request body
    $json = file_get_contents("php://input");

    // Decode JSON data
    $data = json_decode($json, true);

    // Check if data decoded successfully
    if ($data === null || gettype($data) !== 'array') {
        throw new Exception("invalid_json", 400);
    }

    $handler = str_replace('/', '_', (trim($_SERVER['REQUEST_URI'], '/')));

    // search if the file named as the same as the route exists in controllers directory
    $controller_file = __DIR__ . '/controllers/' . $handler . '.php';

    if (!file_exists($controller_file)) {
        throw new Exception("missing_controller", 503);
    }

    // Include the controller file
    include_once $controller_file;

    // Call the controller function with the request data
    if(!is_callable($handler)) {
        throw new Exception("missing_method", 501);
    }

   $result = $handler($data);
   list($message, $code) = [$result['message'], $result['code']];
}
catch (Exception $e) {
    // Respond with the exception message and status code
    $message = $e->getMessage();
    $code = $e->getCode();
}

// Respond with the exception message and status code
send_http_response($message, $code);
