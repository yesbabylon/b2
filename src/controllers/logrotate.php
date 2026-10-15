<?php
 /*
    This file is part of the B2 package <http://github.com/yesbabylon/b2>
    Some Rights Reserved, Yesbabylon, 2025
    Licensed under MIT License <https://opensource.org/licenses/MIT>
*/

/**
 * Rotates logs for all host instances.
 *
 * @return array{code: int, body: array<string, array>}
 * @throws Exception
 */
function logrotate(): array {
    $result = [];
    $handlers_by_instance_type = [
        'equal' => 'logrotate_equal'
    ];

    foreach(get_instances() as $instance) {
        try {
            $instance_type = logrotate_getInstanceType($instance);
            $handler = $handlers_by_instance_type[$instance_type] ?? null;

            if($handler === null) {
                $result[$instance] = [
                    'code' => 200,
                    'body' => [
                        'result'        => 'logrotate_not_supported',
                        'instance_type' => $instance_type
                    ]
                ];
                continue;
            }

            $result[$instance] = $handler($instance);
        }
        catch(Exception $exception) {
            $result[$instance] = [
                'code' => $exception->getCode(),
                'body' => [
                    'error' => $exception->getMessage()
                ]
            ];
        }
    }

    return [
        'code' => 200,
        'body' => $result
    ];
}

/**
 * Rotates the logs of an eQual instance.
 *
 * @return array{code: int, body: array{result: string, instance_type: string, output: string[]}}
 * @throws Exception
 */
function logrotate_equal(string $instance): array {
    $tool = BASE_DIR.'/tools/instance-eq-logrotate.sh';
    if(!is_file($tool) || !is_executable($tool)) {
        throw new Exception('logrotate_tool_unavailable', 500);
    }

    $output = [];
    $exit_code = 0;
    $command = escapeshellarg($tool).' '.escapeshellarg($instance);

    exec($command.' 2>&1', $output, $exit_code);

    if($exit_code !== 0) {
        throw new Exception('logrotate_failed', 500);
    }

    return [
        'code' => 200,
        'body' => [
            'result'        => 'logrotate_completed',
            'instance_type' => 'equal',
            'output'        => $output
        ]
    ];
}

/**
 * Reads the instance type without exposing the other values stored in its .env file.
 *
 * @throws Exception
 */
function logrotate_getInstanceType(string $instance): string {
    $env_file = "/home/$instance/.env";
    if(!is_readable($env_file)) {
        throw new Exception('could_not_read_instance_env', 500);
    }

    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if($lines === false) {
        throw new Exception('could_not_read_instance_env', 500);
    }

    foreach($lines as $line) {
        if(!preg_match('/^\s*INSTANCE_TYPE\s*=\s*(.+?)\s*$/', $line, $matches)) {
            continue;
        }

        $instance_type = trim($matches[1], "'\"");
        if(!preg_match('/^[A-Za-z0-9_-]+$/', $instance_type)) {
            throw new Exception('invalid_instance_type', 500);
        }

        return strtolower($instance_type);
    }

    throw new Exception('missing_instance_type', 500);
}
