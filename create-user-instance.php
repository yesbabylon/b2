<?php

// Check if the request is a POST method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the URL is correct
    if ($_SERVER['REQUEST_URI'] === '/create-user-instance') {
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

            // Path to the directory for the .env file
            $env_file_path = '/root/b2/equal/.env';

            // Check if the .env file exists, and create it if not
            if (!file_exists($env_file_path)) {
                // Create the .env file
                touch($env_file_path);
                // Set permissions for the .env file
                chmod($env_file_path, 0644);
            }

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
