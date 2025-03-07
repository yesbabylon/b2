<?php
 /*
    This file is part of the B2 package <http://github.com/yesbabylon/b2>
    Some Rights Reserved, Yesbabylon, 2025
    Licensed under MIT License <https://opensource.org/licenses/MIT>
*/

/**
 * Disables the maintenance mode for a specific instance
 *
 * @param array{instance: string} $data
 * @return array{code: int, body: string}
 * @throws Exception
 */
function instance_disable_maintenance(array $data): array {
    if(!isset($data['instance'])) {
        throw new InvalidArgumentException("missing_instance", 400);
    }

    if(!is_string($data['instance']) || !instance_exists($data['instance'])) {
        throw new InvalidArgumentException("invalid_instance", 400);
    }

    $instance = $data['instance'];

    if(!file_exists("/srv/docker/nginx/html/$instance/maintenance")) {
        throw new Exception("instance_not_in_maintenance_mode", 400);
    }

    instance_disable_maintenance_mode($instance);

    return [
        'code' => 200,
        'body' => [ 'result' => 'instance_maintenance_disabled' ]
    ];
}
