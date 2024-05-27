<?php

/**
 * Delete a log file from the instance.
 * ! Not sure
 *
 * @param array{instance: string, log_name: string} $data
 * @return array{code: int, message: string}
 */
function instance_logsAck(array $data): array
{

    $status_code = 201;
    $message = '';

    if (!isset($data['instance']) || !isset($data['log_name'])) {
        $status_code = 400;
        $message = 'Bad Request';

        return [
            'code' => $status_code,
            'message' => $message
        ];
    }

    if (!file_exists('/home/' . $data['instance'] . '/export/logs/' . $data['log_name'])) {
        $status_code = 404;
        $message = 'Log file not found';
    } else {
        unlink('/home/' . $data['instance'] . '/export/logs/' . $data['log_name']);
        $message = 'Log file deleted';
    }

    return [
        'code' => $status_code,
        'message' => $message
    ];
}
