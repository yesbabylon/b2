<?php

/**
 * Returns status statistics about a given instance.
 *
 * @param array{instance: string} $data
 * @return array{code: int, body: string}
 * @throws Exception
 */
function instance_status(array $data): array {
    $docker_stats_json = exec('docker stats '.$data['instance'].' --no-stream --format "{{ json . }}"');
    $result = json_decode($docker_stats_json);

    if($result === null) {
        throw new Exception("instance_not_found", 404);
    }

    return [
        'code' => 200,
        'body' => $result
    ];
}
