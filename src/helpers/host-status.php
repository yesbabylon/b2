<?php
 /*
    This file is part of the B2 package <http://github.com/yesbabylon/b2>
    Some Rights Reserved, Yesbabylon, 2025
    Licensed under MIT License <https://opensource.org/licenses/MIT>
*/

/**
 * Executes a status command and returns the result
 *
 * @param string $command
 * @return false|mixed|null
 */
function exec_status_cmd(string $command) {
    $result = null;
    if(exec($command, $output) !== false) {
        $result = reset($output);
    }
    return $result;
}

/**
 * Adapts the unit of the given value
 *
 * @param string $value
 * @return string
 */
function adapt_unit(string $value): string {
    $value = str_replace(' ', '', $value);

    return str_replace(
        ['GiB', 'Gi', 'MiB', 'Mi', 'KiB', 'Ki', 'kbit/s'],
        ['G', 'G', 'M', 'M', 'K', 'K', 'kbs'],
        $value
    );
}
