<?php
 /*
    This file is part of the B2 package <http://github.com/yesbabylon/b2>
    Some Rights Reserved, Yesbabylon, 2025
    Licensed under MIT License <https://opensource.org/licenses/MIT>
*/

/**
 * Returns list of instances present on the host.
 *
 * @param array{with_deleted?: bool} $data
 * @return array{code: int, body: string[]}
 * @throws Exception
 */
function instances(array $data): array {
    if(isset($data['with_deleted'])) {
        if(!in_array(strtolower($data['with_deleted']), ['true', '1', 'yes', 'false', '0', 'no'])) {
            throw new InvalidArgumentException("invalid_with_deleted", 400);
        }

        $data['with_deleted'] = in_array(strtolower($data['with_deleted']), ['true', '1', 'yes']);
    }

    $instances = get_instances($data['with_deleted'] ?? false);

    return [
        'code' => 200,
        'body' => $instances
    ];
}
