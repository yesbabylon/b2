<?php
/*
   This file is part of the B2 package <http://github.com/yesbabylon/b2>
   Some Rights Reserved, Yesbabylon, 2025
   Licensed under MIT License <https://opensource.org/licenses/MIT>
*/

/**
 * Stops an instance.
 *
 * @param array{instance: string} $data
 * @return array{code: int, body: string}
 * @throws Exception
 */
function instance_stop(array $data): array {

    if(!isset($data['instance'])) {
        throw new InvalidArgumentException("missing_instance", 400);
    }

    if(!is_string($data['instance']) || !instance_exists($data['instance'])) {
        throw new InvalidArgumentException("invalid_instance", 400);
    }

    // Stop the instance with docker compose to /home/$data['instance']
    exec('docker compose -f /home/'.$data['instance'].'/docker-compose.yml stop');

    return [
        'code' => 200,
        'body' => [ 'result' => 'instance_successfully_stopped' ]
    ];
}
