<?php
include_once './boot.lib.php';

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
                $value = (bool) $value;
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



function main($argv)
{
    $options = parse_arguments(array_slice($argv, 1));

    $controller = trim('/', str_replace('//', '/', '/'.$options['route']));
    $result = exec_controller($controller, $options['params']);

    echo "HTTP Status Code: {$result['code']}\n";
    $data = json_decode($result['body'], true);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

main($argv);
