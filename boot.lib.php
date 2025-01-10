<?php
const BASE_DIR = __DIR__;
const CONTROLLERS_DIR = __DIR__ . '/controllers';
const SCRIPTS_DIR = __DIR__ . '/scripts';

include_once './helpers/backup.php';
include_once './helpers/instances.php';
include_once './helpers/host-status.php';
include_once './helpers/http-response.php';
include_once './helpers/request-handler.php';
include_once './helpers/cron-handler.php';

include_once './helpers/env.php';

function exec_controller($controller, $payload) {
    try {
        $controller_file = CONTROLLERS_DIR."/$controller.php";
        // Check if the controller or script file exists
        if(!file_exists($controller_file)) {
            throw new Exception("missing_script_file", 503);
        }

        // Include the controller file
        include_once $controller_file;

        $handler_method_name = preg_replace('/[-\/]/', '_', $controller);

        // Call the controller function with the request data
        if(!is_callable($handler_method_name)) {
            throw new Exception("missing_script_method", 501);
        }

        // Load host env variables
        load_env(BASE_DIR.'/.env');

        // Load env variables of a specific instance if needed
        if(
            strpos($controller, 'instance') === 0
            && isset($payload['instance'])
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
    
    return [$body, $code];
}
