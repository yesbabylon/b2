<?php

/**
 * Handles the given request and returns the response body and status code
 *
 * @param array{
 *     method: string,
 *     uri: string,
 *     content_type: string,
 *     data: string
 * } $request
 * @param string[] $allowed_routes
 * @return array{body: string|array, code: string}
 */
function handle_request(array $request, array $allowed_routes): array {
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

        $controller_file = CONTROLLERS_DIR . '/' . $handler . '.php';

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

        load_env(BASE_DIR . '/.env');

        throw new Exception('loaded env');

        // Respond with the returned body and code
        ['body' => $body, 'code' => $code] = $handler_method_name($data);
    }
    catch (Exception $e) {
        // Respond with the exception message and status code
        [$body, $code] = [$e->getMessage(), $e->getCode()];
    }

    return compact('body', 'code');
}
