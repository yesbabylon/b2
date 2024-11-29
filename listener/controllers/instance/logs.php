<?php

/**
 * Retrieves the logs of an instance.
 * ! Not sure
 *
 * @param array{instance: string} $data
 * @return array{code: int, body: string[]}
 * @throws Exception
 */
function instance_logs(array $data): array {
    if(empty($data['instance'])) {
        throw new InvalidArgumentException("invalid_instance", 400);
    }

    $log_dir = "/home/${$data['instance']}/export/logs";

    // Attempt to change to the log directory.
    if(!chdir($log_dir)) {
        throw new Exception("failed_to_access_logs_directory", 500);
    }

    // Look for log files in the current directory.
    $log_files = glob('*.log');

    if($log_files === false || count($log_files) === 0) {
        return [
            'code' => 200,
            'body' => "no_logs_found"
        ];
    }

    $logs = [];
    foreach($log_files as $log_file) {
        $file_data = file_get_contents($log_file);

        if($file_data === false) {
            throw new Exception("error_while_reading_logs", 500);
        }

        $logs[basename($log_file)] = $file_data;
    }

    return [
        'code' => 200,
        'body' => $logs
    ];
}