<?php
 /*
    This file is part of the B2 package <http://github.com/yesbabylon/b2>
    Some Rights Reserved, Yesbabylon, 2025
    Licensed under MIT License <https://opensource.org/licenses/MIT>
*/

/**
 * Reboots the system, with sleep of 5 seconds and in detached mod to return the response.
 *
 * @return array{code: int, body: string}
 */
function reboot(): array {
    exec('nohup sh -c "sleep 5 && reboot" > /dev/null 2>&1 &');

    return [
        'code' => 200,
        'body' => [ 'result' => 'host_will_reboot_now' ]
    ];
}
