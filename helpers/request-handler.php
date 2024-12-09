<?php


include_once '../helpers/env.php';
include_once '../helpers/http-response.php';

/**
 * Handles the incoming request
 *
 * @param array{
 *     method: string,
 *     uri: string,
 *     content_type: string,
 *     data: string
 * } $request
 * @param array $allowed_routes
 * @return void
 */
function handle_request(array $request, array $allowed_routes): void {
    try {
        // By convention, we accept only POST requests
        if($request['method'] !== 'POST') {
            throw new Exception("method_not_allowed", 405);
        }

        // Check if the requested route is allowed
        if(!in_array($request['uri'], $allowed_routes)) {
            throw new Exception("unknown_route", 404);
        }

        if($request['content_type'] !== 'application/json') {
            throw new Exception("invalid_body", 400);
        }

        // Get the request body
        $json = $request['data'];

        // Decode JSON data
        $data = json_decode($json, true);

        // Check if data decoded successfully
        if(!is_array($data)) {
            throw new Exception("invalid_json", 400);
        }

        $handler = trim($request['uri'], '/');

        $controller_file = __DIR__ . '/controllers/' . $handler . '.php';

        // Check if the controller or script file exists
        if(!file_exists($controller_file)) {
            throw new Exception("missing_script_file", 503);
        }

        // Include the controller file
        include_once $controller_file;

        $handler_method_name = preg_replace('/[-\/]/', '_', $handler);

        // Call the controller function with the request data
        if(!is_callable($handler_method_name)) {
            throw new Exception("missing_method", 501);
        }

        define('BASE_DIR', __DIR__);
        define('TOKENS_DIR', __DIR__ . '/tokens');

        load_env(BASE_DIR . '/.env');

        // Respond with the returned body and code
        ['body' => $body, 'code' => $code] = $handler_method_name($data);
    } catch (Exception $e) {
        // Respond with the exception message and status code
        [$body, $code] = [$e->getMessage(), $e->getCode()];
    }

    // Send response with body and code
    send_http_response($body, $code);
}
