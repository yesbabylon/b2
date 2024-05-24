<?php

/**
 * Retrieve the logs of an instance.
 *
 * @param array{instance: string} $data
 * @return array{code: int, message: string}
 */
function instance_logs(array $data): array
{
    $status_code = 201;
    $message = '';

    if (!isset($data['instance'])) {
        $status_code = 400;
        $message = 'Bad Request';

        return [
            'code' => $status_code,
            'message' => $message
        ];
    }

    // change directory to /home/$data['instance']/export/logs
    chdir('/home/' . $data['instance'] . '/export/logs');

    // if there is no file /home/$data['instance']/exports/logs/*.log
    $log_files = glob('/*.log');

    if (count($log_files) === 0 || $log_files === false) {
        $status_code = 200;
        $message = 'No logs found';

        return [
            'code' => $status_code,
            'message' => $message
        ];
    }

    $logs = [];

    foreach ($log_files as $log_file) {
        $file_data = file_get_contents($log_file);

        if ($file_data === false) {
            $status_code = 404;
            $message = 'Server Error while reading log file ' . $log_file;

            return [
                'code' => $status_code,
                'message' => $message
            ];
        }

        $logs[basename('/home/' . $data['instance'] . '/export/logs/' . $log_file)] = file_get_contents($log_file);
    }

    return [
        'code' => $status_code,
        'message' => $logs
    ];
}