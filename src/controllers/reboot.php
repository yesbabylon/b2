<?php

/**
 * Reboots the system, with sleep of 5 seconds and in detached mod to return the response.
 *
 * @return array{code: int, body: string}
 */
function reboot(): array {
    exec('nohup sh -c "sleep 5 && reboot" > /dev/null 2>&1 &');

    return [
        'code' => 200,
        'body' => "host_will_reboot_now",
    ];
}
