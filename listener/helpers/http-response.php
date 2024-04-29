<?php

/**
 * Send an HTTP response with the specified status code and message.
 *
 * @param $message
 * @param $status_code
 * @return void
 */
function send_http_response($message, $status_code): void
{
    // Define the response status codes and their respective messages
    $status_messages = [
        200 => 'OK',
        400 => 'Bad Request',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        // Add more status codes and messages as needed
    ];

    // Set the HTTP response status code
    http_response_code($status_code);

    // Set the HTTP response status code and message
    $status_message = $status_messages[$status_code] ?? '';
    header("HTTP/1.1 $status_code $status_message");

    // Set the Content-Type header to indicate JSON response
    header('Content-Type: application/json');

    // Construct the response body as a JSON object
    $response = [
        'message' => $message
    ];

    // Convert the response data to JSON format
    $json_response = json_encode($response);

    // Output the JSON response
    echo $json_response;
}
