<?php

/**
 * Delete a log file from the instance.
 * ! Not sure
 *
 * @param array{instance: string, log_name: string} $data
 * @return array{code: int, body: string}
 * @throws Exception
 */
function instance_log_delete(array $data): array {
    if(empty($data['instance'])) {
        throw new InvalidArgumentException("missing_instance");
    }

    if(empty($data['log_name'])) {
        throw new InvalidArgumentException("missing_log_name");
    }

    $base_dir = "/home/${$data['instance']}/export/logs";
    if(!is_dir($base_dir)) {
        throw new Exception("instance_export_logs_directory_not_found", 404);
    }

    $log_file = realpath("$base_dir/${$data['log_name']}");
    if($log_file === false || strpos($log_file, realpath($base_dir)) !== 0) {
        throw new Exception("invalid_log_file_path", 403);
    }

    if(!is_file($log_file)) {
        throw new Exception("log_file_not_found", 404);
    }

    if(!unlink($log_file)) {
        throw new Exception("log_file_delete_failed", 500);
    }

    return [
        'code' => 200,
        'body' => "log_file_deleted"
    ];
}
