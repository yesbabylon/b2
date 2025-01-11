<?php

/**
 * Enables the maintenance mode for a specific instance
 *
 * @param array{instance: string} $data
 * @return array{code: int, body: string}
 * @throws Exception
 */
function instance_enable_maintenance(array $data): array {
    if(!isset($data['instance'])) {
        throw new InvalidArgumentException("missing_instance", 400);
    }

    if(!is_string($data['instance']) || !instance_exists($data['instance'])) {
        throw new InvalidArgumentException("invalid_instance", 400);
    }

    $instance = $data['instance'];

    if(file_exists("/srv/docker/nginx/html/$instance/maintenance")) {
        throw new Exception("instance_already_in_maintenance_mode", 400);
    }

    instance_enable_maintenance_mode($instance);

    return [
        'code' => 200,
        'body' => "instance_maintenance_enabled",
    ];
}
