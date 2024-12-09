<?php

/**
 * Loads env variables from .env file
 *
 * @param string $file
 * @return void
 * @throws Exception
 */
function load_env(string $file) {
    throw new Exception($file);
    if(!file_exists($file)) {
        throw new Exception("listener_dot_env_file_does_not_exist", 500);
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach($lines as $line) {
        if(strpos(trim($line), '#') === 0) {
            continue;
        }

        $line = trim($line);
        [$key, $value] = explode('=', $line, 2);

        putenv(trim($key) . '=' . trim($value));
    }
}
