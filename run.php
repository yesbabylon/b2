<?php

function parseArguments($argv)
{
    $options = [
        'route' => '',
        'method' => 'GET',
        'params' => [],
    ];

    foreach ($argv as $arg) {
        if (preg_match('/^--route=(.+)$/', $arg, $matches)) {
            $options['route'] = $matches[1];
        } 
		elseif (preg_match('/^--method=(.+)$/', $arg, $matches)) {
            $options['method'] = strtoupper($matches[1]);
        } 
		elseif (preg_match('/^--params=(.+)$/', $arg, $matches)) {
            parse_str($matches[1], $options['params']);
        }
    }

    if (empty($options['route'])) {
        echo "Error: --route is required.\n";
        exit(1);
    }

    return $options;
}

function sendHttpRequest($url, $method, $params)
{
    $contextOptions = [
        'http' => [
            'method'  => $method,
            'header'  => "Content-Type: application/json\r\n",
            'content' => json_encode($params),
            'timeout' => 10
        ]
    ];

    if ($method === 'GET') {
        if(count($params)) {
			$url .= '?' . http_build_query($params);
		}
        unset($contextOptions['http']['content']);
    }

    $context = stream_context_create($contextOptions);

    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        $error = error_get_last();
        echo "Erreur lors de la requête HTTP : " . $error['message'] . "\n";
        return null;
    }

    $httpCode = null;
    if (isset($http_response_header)) {
        foreach ($http_response_header as $header) {
            if (preg_match('/^HTTP\/\d\.\d (\d{3})/', $header, $matches)) {
                $httpCode = intval($matches[1]);
                break;
            }
        }
    }

    return [$httpCode, $response];
}

function main($argv)
{
    $options = parseArguments(array_slice($argv, 1));

    $url = "http://127.0.0.1:8000" . $options['route'];
    $method = $options['method'];
    $params = $options['params'];

    list($httpCode, $response) = sendHttpRequest($url, $method, $params);

    echo "HTTP Status Code: $httpCode\n";
    $decodedResponse = json_decode($response, true);
    echo json_encode($decodedResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

main($argv);
