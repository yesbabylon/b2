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
 * @param array[] $routes Array mapping existing routes (level-1 = method, level-2 = path)
 * @return array{body: string|array, code: int}
 */
function handle_request(array $request, array $routes): array {
    try {
        $method = $request['method'];
		$payload = [];
		
        if(!isset($routes[$method])) {
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

        $controller = trim($request['uri'], '/');
        $result = exec_controller($controller, $payload);
    }
    catch(Exception $e) {
        // Respond with the exception message and status code
        $result = [
            'body' => [ 'error' => $e->getMessage() ], 
            'code' => $e->getCode()
        ];
    }

    return $result;
}
