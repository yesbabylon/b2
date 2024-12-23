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
 * @param string[] $routes Array mapping existing routes (level-1 = method, level-2 = path)
 * @return array{body: string|array, code: int}
 */
function handle_request(array $request, array $routes): array {
    try {
        $method = $request['method'];
		$payload = [];
		
        if(!isset($routes[$method]) {
            throw new Exception("method_not_allowed", 405);
        }

		if($method == 'GET') {
			$parts = explode('?', $request['uri'], 2);
			$request['uri'] = $parts[0];
			if(count($parts) > 1) {
				parse_str($parts[1], $payload);
			}
		}

        // Check if the requested route is allowed
        if(!in_array($request['uri'], $routes[$method])) {
            throw new Exception("unknown_route", 404);
        }

		if($method != 'GET') { 
	        if($request['content_type'] !== 'application/json') {
				throw new Exception("invalid_body", 400);
			}

			// Get the request body
			$json = $request['data'];

			// Decode JSON data
			$payload = json_decode($json, true);

			// Check if data decoded successfully
			if(!is_array($payload)) {
				throw new Exception("invalid_json", 400);
			}
        }

        $handler = trim($request['uri'], '/');

        $controller_file = CONTROLLERS_DIR."/$handler.php";

        // Check if the controller or script file exists
        if(!file_exists($controller_file)) {
            throw new Exception("missing_script_file", 503);
        }

        // Include the controller file
        include_once $controller_file;

        $handler_method_name = preg_replace('/[-\/]/', '_', $handler);

        // Call the controller function with the request data
        if(!is_callable($handler_method_name)) {
            throw new Exception("missing_script_method", 501);
        }

        // Load host env variables
        load_env(BASE_DIR.'/.env');

        // Load env variables of a specific instance if needed
        if(
            strpos($request['uri'], '/instance/') === 0
            && $payload['instance'] ?? false
            && instance_exists($payload['instance'])
            && file_exists("/home/{$payload['instance']}/.env")
        ) {
            load_env("/home/{$payload['instance']}/.env");
        }

        // Respond with the returned body and code
        ['body' => $body, 'code' => $code] = $handler_method_name($payload);
    }
    catch(Exception $e) {
        // Respond with the exception message and status code
        [$body, $code] = ['{ "error": "'.$e->getMessage().'" }', $e->getCode()];
    }

    return compact('body', 'code');
}
