<?php

function parse_arguments($argv)
{
    $options = [
        'route'  => '',
        'method' => 'GET',
        'params' => [],
    ];

    foreach ($argv as $arg) {
        if(preg_match('/^--route=(.+)$/', $arg, $matches)) {
            $options['route'] = $matches[1];
        } 
        elseif(preg_match('/^--method=(.+)$/', $arg, $matches)) {
            $options['method'] = strtoupper($matches[1]);
        } 
        elseif(strpos($arg, '--') === 0) {
            $parts = explode('=', substr($arg, 2), 2);
            $key = $parts[0];
            $value = isset($parts[1]) ? $parts[1] : true;
            if(in_array($value, ['true', 'false'])) {
                $value = ($value === 'true');
            }
            $options['params'][$key] = $value;
        }
    }

    if (empty($options['route'])) {
        echo "Error: --route is required.\n";
        exit(1);
    }

    return $options;
}

function send_http_request($url, $method, $params)
{
    $context = [
        'http' => [
            'method'        => $method,
            'header'        => "Content-Type: application/json\r\n",
            'content'       => json_encode($params),
            // #memo - high timeout because operation can require some time (upload/download)
            'timeout'       => 600,
            'ignore_errors' => true
        ]
    ];

    if($method === 'GET') {
        if(count($params)) {
            $url .= '?' . http_build_query($params);
        }
        unset($context['http']['content']);
    }

    $streamContext = stream_context_create($context);

    $response = @file_get_contents($url, false, $streamContext);
    $http_code = is_array($response) ? 200 : 500;

    if(isset($http_response_header)) {
        foreach($http_response_header as $header) {
            if(preg_match('/^HTTP\/\d\.\d (\d{3})/', $header, $matches)) {
                $http_code = intval($matches[1]);
                break;
            }
        }
    }

    return [$http_code, $response];
}

function main($argv)
{
    $options = parse_arguments(array_slice($argv, 1));

    [$http_code, $response] = send_http_request(
            "http://127.0.0.1:8000" . str_replace('//', '/', '/'.$options['route']), 
            $options['method'], 
            $options['params']
        );

    $data = json_decode($response, true);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

main($argv);
