<?php

/**
 * Returns status statistics about a given instance.
 *
 * @param array{instance: string} $data
 * @return array{code: int, body: string}
 * @throws Exception
 */
function instance_status(array $data): array {
    if(!isset($data['instance'])) {
        throw new InvalidArgumentException("missing_instance", 400);
    }

    if(!is_string($data['instance']) || !instance_exists($data['instance'])) {
        throw new InvalidArgumentException("invalid_instance", 400);
    }

    $docker_stats_json = exec('docker stats '.$data['instance'].' --no-stream --format "{{ json . }}"');
    $docker_stats = json_decode($docker_stats_json, true);

    if(is_null($docker_stats)) {
        throw new Exception("instance_not_found", 404);
    }

    $result = [
        'maintenance_enabled'   => instance_is_maintenance_enabled($data['instance']),
        'docker_stats'          => $docker_stats
    ];

    return [
        'code' => 200,
        'body' => $result
    ];
}
