<?php

/**
 * Send an HTTP response with the specified status code and message.
 *
 * @param $body
 * @param $status_code
 * @return void
 */
function send_http_response($body, $status_code) : void {
    // Define the response status codes and their respective messages
    $map_status_messages = [
        200 => 'OK',
        201 => 'Created',
        204 => 'No Content',
        400 => 'Bad Request',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        503 => 'Service Unavailable',
    ];

    if(!isset($map_status_messages[$status_code])) {
        $status_code = 200;
    }

    // Set the HTTP response status code
    http_response_code($status_code);

    // Set the Content-Type header to indicate JSON response
    header('Content-Type: application/json');

    $data = $body;

    if($status_code > 299) {
        $data = [
            'errors' => $body
        ];
    }

    // Convert the response data to JSON format
    $json_response = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    if(!$json_response) {
        $json_response = '';
    }

    // Output the JSON response
    echo $json_response;
}
