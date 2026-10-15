<?php
 /*
    This file is part of the B2 package <http://github.com/yesbabylon/b2>
    Some Rights Reserved, Yesbabylon, 2025
    Licensed under MIT License <https://opensource.org/licenses/MIT>
*/

/**
 * Runs the log rotation tool associated with an instance type.
 *
 * @param array{instance: string} $data
 * @return array{code: int, body: array{result: string, instance_type: string, output?: string[]}}
 * @throws Exception
 */
function instance_logrotate(array $data): array {
    if(!isset($data['instance'])) {
        throw new InvalidArgumentException('missing_instance', 400);
    }

    if(!is_string($data['instance']) || !instance_exists($data['instance'])) {
        throw new InvalidArgumentException('invalid_instance', 400);
    }

    $instance_type = instance_logrotate_getType($data['instance']);
    $tools_by_instance_type = [
        'equal' => 'instance-eq-logrotate.sh',
        'fmt'   => 'instance-eq-logrotate.sh'
    ];

    if(!isset($tools_by_instance_type[$instance_type])) {
        return [
            'code' => 200,
            'body' => [
                'result'        => 'logrotate_not_supported',
                'instance_type' => $instance_type
            ]
        ];
    }

    $tool = BASE_DIR.'/tools/'.$tools_by_instance_type[$instance_type];
    if(!is_file($tool) || !is_executable($tool)) {
        throw new Exception('logrotate_tool_unavailable', 500);
    }

    $output = [];
    $exit_code = 0;
    $command = escapeshellarg($tool).' '.escapeshellarg($data['instance']);

    exec($command.' 2>&1', $output, $exit_code);

    if($exit_code !== 0) {
        throw new Exception('logrotate_failed', 500);
    }

    return [
        'code' => 200,
        'body' => [
            'result'        => 'logrotate_completed',
            'instance_type' => $instance_type,
            'output'        => $output
        ]
    ];
}

/**
 * Reads the instance type without exposing the other values stored in its .env file.
 *
 * @throws Exception
 */
function instance_logrotate_getType(string $instance): string {
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
