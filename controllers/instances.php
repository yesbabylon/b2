<?php

/**
 * Returns list of instances present on the host.
 *
 * @param array{with_deleted?: bool} $data
 * @return array{code: int, body: string[]}
 * @throws Exception
 */
function instances(array $data): array {
    if(isset($data['with_deleted']) && !is_bool($data['with_deleted'])) {
        throw new InvalidArgumentException("invalid_with_deleted", 400);
    }

    $instances = get_instances($data['with_deleted'] ?? false);

    return [
        'code' => 200,
        'body' => $instances
    ];
}
