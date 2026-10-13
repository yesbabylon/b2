<?php
 /*
    This file is part of the B2 package <http://github.com/yesbabylon/b2>
    Some Rights Reserved, Yesbabylon, 2025
    Licensed under MIT License <https://opensource.org/licenses/MIT>
*/

/**
 * Returns the Docker images used by each instance stack.
 *
 * @param array $data
 * @return array{code: int, body: array<string, string[]>}
 * @throws Exception
 */
function stacks(array $data): array {
    return [
        'code' => 200,
        'body' => get_stacks()
    ];
}
