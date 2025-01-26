<?php
 /*
    This file is part of the B2 package <http://github.com/yesbabylon/b2>
    Some Rights Reserved, Yesbabylon, 2025
    Licensed under MIT License <https://opensource.org/licenses/MIT>
*/

const BASE_DIR = __DIR__ . '/..';
const CONTROLLERS_DIR = __DIR__ . '/controllers';

// load all helpers
$helpers_dir = __DIR__ . '/helpers';
if (is_dir($helpers_dir)) {
    foreach (glob($helpers_dir . '/*.php') as $helper_file) {
        include_once $helper_file;
    }
}

/**
 * @return array Returns an array with 'body' and 'code' keys.
 */
function exec_controller($controller, $payload) {
    $result = ['body' => '', 'code' => 0];

    try {
        $controller_file = CONTROLLERS_DIR."/$controller.php";
        // Check if the controller or script file exists
        if(!file_exists($controller_file)) {
            throw new Exception("unknown_controller", 503);
        }

        // Include the controller file
        include_once $controller_file;

        $handler_method_name = preg_replace('/[-\/]/', '_', $controller);

        // Call the controller function with the request data
        if(!is_callable($handler_method_name)) {
            throw new Exception("unknown_method", 501);
        }

        // Load host env variables
        load_env(BASE_DIR.'/.env');

        // Load env variables of a specific instance if needed
        if( strpos($controller, 'instance') === 0
            && isset($payload['instance'])
            && instance_exists($payload['instance'])
            && file_exists("/home/{$payload['instance']}/.env")
        ) {
            load_env("/home/{$payload['instance']}/.env");
        }

        // Respond with the returned body and code
        $result = $handler_method_name($payload);
    }
    catch(Exception $e) {
        // Respond with the exception message and status code
        $result = [
            'body'  => [ 'error' => $e->getMessage() ],
            'code'  => $e->getCode()
        ];
    }

    return $result;
}
