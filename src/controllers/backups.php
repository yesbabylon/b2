<?php
/*
    This file is part of the B2 package <http://github.com/yesbabylon/b2>
    Some Rights Reserved, Yesbabylon, 2026
    Licensed under MIT License <https://opensource.org/licenses/MIT>
*/

/**
 * Returns the local import and export backups, grouped by instance.
 *
 * @param array $data
 * @return array{code: int, body: array<string, array{export: array, import: array}>}
 * @throws Exception
 */
function backups(array $data): array {
    $backups = [];

    foreach(get_instances() as $instance) {
        $response = exec_controller('instance/backups', ['instance' => $instance]);
        if($response['code'] !== 200 || !is_array($response['body'])) {
            throw new Exception("could_not_list_instance_backups", 500);
        }

        $backups[$instance] = $response['body'];
    }

    return [
        'code' => 200,
        'body' => $backups
    ];
}
