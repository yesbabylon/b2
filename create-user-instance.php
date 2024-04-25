<?php

/**
 * Send an HTTP response with the specified status code and message.
 *
 * @param $status_code
 * @param $message
 * @return void
 */
function send_http_response($status_code, $message): void
{
    // Define the response status codes and their respective messages
    $status_messages = array(
        200 => 'OK',
        400 => 'Bad Request',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        // Add more status codes and messages as needed
    );

    // Set the HTTP response status code and message
    $status_message = $status_messages[$status_code] ?? '';
    header("HTTP/1.1 $status_code $status_message");

    // Set the Content-Type header to indicate JSON response
    header('Content-Type: application/json');

    // Construct the response body as a JSON object
    $response = array(
        'status' => $status_code,
        'message' => $message
    );

    // Convert the response data to JSON format
    $json_response = json_encode($response);

    // Output the JSON response
    echo $json_response;
}

/**
 * Log the request data to a file.
 *
 * @param $log_message
 * @return void
 */
function log_request($log_message): void
{
    // Chemin du fichier de journal
    $log_file_path = __DIR__ . "/instance-creation.log";

    // Ouvrir un fichier de journal en mode écriture (ajout)
    $log_file = fopen($log_file_path, "a");

    // Vérifier si l'ouverture du fichier de journal a réussi
    if ($log_file !== false) {
        // Obtenir la date et l'heure actuelles
        $current_datetime = date("Y-m-d H:i:s");

        // Obtenir l'URI de la requête
        $request_uri = $_SERVER['REQUEST_URI'];

        // Vérifier le type de log_message
        if (!is_string($log_message)) {
            // Si ce n'est pas une chaîne de caractères, tenter de convertir en JSON
            $log_message = json_encode($log_message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
        }

        // Construire le message à enregistrer dans le fichier journal
        $log_entry = "[$current_datetime] [$request_uri] $log_message\n";

        // Écrire le message dans le fichier journal
        fwrite($log_file, $log_entry);

        // Fermer le fichier de journal
        fclose($log_file);
    }
}

// Check if the request is a POST method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the URL is correct
    if ($_SERVER['REQUEST_URI'] === '/create-user-instance') {

        // Get the request body
        $json_data = file_get_contents("php://input");
        log_request($json_data);

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
            
            // change directory
            chdir('/root/b2/equal');

            // change owner of user directory
            exec('chown -r www-data:www-data /home/' . $data['USERNAME'] . '/www'):
            
            // Execute the init.bash script with appropriate flags
            $init_bash_script = '/root/b2/equal/init.bash';

            $output = '';
            $exit_code = 0;
            // Execute the init.bash script with the flags
            exec("bash $init_bash_script $flags", $output, $exit_code);
            log_request('----------- init.bash script output -----------');
            log_request(json_encode(str_replace(",", "\n", $output)) . "\n Exit code: " . $exit_code);
            log_request('-----------------------------------------------');

            // Respond with HTTP status code 200 (OK)
            send_http_response(200, "User instance created successfully!");
        } else {
            // Respond with HTTP status code 400 (Bad Request) if JSON data is invalid
            send_http_response(400, "JSON data is invalid!");
        }
    } else {
        // Respond with HTTP status code 404 (Not Found) if the URL is not correct
        send_http_response(404, "Page not found!");
    }
} else {
    // Respond with HTTP status code 405 (Method Not Allowed) if the method is not POST
    send_http_response(405, "Method not allowed!");
}
?>
