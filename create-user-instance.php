<?php
// Check if the request is a POST method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the URL is correct
    if ($_SERVER['REQUEST_URI'] === '/create-user-instance') {
        // Get the request body
        $json_data = file_get_contents("php://input");
        
        // Decode JSON data
        $data = json_decode($json_data, true);
        
        // Check if data decoded successfully
        if ($data !== null) {
            // Set default flags
            $flags = '';
            
            // Check values in the request body
            if (isset($data['symbiose']) && $data['symbiose'] === true) {
                $flags .= ' -s';
            }
            
            if (isset($data['equalpress']) && $data['equalpress'] === true) {
                $flags .= ' -w';
            }
            
            // Path to the directory for the .env file
            $env_file_path = '/root/b2/equal/.env';
            
            // Write data to the .env file
            foreach ($data as $key => $value) {
                // Write data to the .env file
                file_put_contents($env_file_path, "$key=$value\n", FILE_APPEND);
            }
            
            // Execute the init.bash script with appropriate flags
            $init_bash_script = '/root/b2/equal/init.bash';
            exec("bash $init_bash_script $flags");
            
            // Respond with HTTP status code 200 (OK)
            http_response_code(200);
            echo "User instance created successfully!";
        } else {
            // Respond with HTTP status code 400 (Bad Request) if JSON data is invalid
            http_response_code(400);
            echo "JSON data is invalid!";
        }
    } else {
        // Respond with HTTP status code 404 (Not Found) if the URL is not correct
        http_response_code(404);
        echo "Page not found!";
    }
} else {
    // Respond with HTTP status code 405 (Method Not Allowed) if the method is not POST
    http_response_code(405);
    echo "Method not allowed!";
}
?>
