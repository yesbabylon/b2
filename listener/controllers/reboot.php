<?php

/**
 * Reboot the system.
 * ! Not sure
 *
 * @param array $data
 * @return array{code: int, message: string}
 */
function reboot(array $data): array
{
    // reboot the system with sleep of 5 sec in detached mod for having the return of the function
    exec('nohup sh -c "sleep 5 && reboot" > /dev/null 2>&1 &');

    return [
        'code' => 201,
        'message' => ''
    ];
}