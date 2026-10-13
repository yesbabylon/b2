<?php
 /*
    This file is part of the B2 package <http://github.com/yesbabylon/b2>
    Some Rights Reserved, Yesbabylon, 2025
    Licensed under MIT License <https://opensource.org/licenses/MIT>
*/

/**
 * Returns the list of Docker images installed on the host.
 *
 * @param array $data
 * @return array{code: int, body: string[]}
 * @throws Exception
 */
function images(array $data): array {
    return [
        'code' => 200,
        'body' => get_images()
    ];
}
