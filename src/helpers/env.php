<?php

/**
 * Loads env variables from given .env file
 *
 * @param string $file
 * @return void
 * @throws Exception
 */
function load_env(string $file) {
    if(!file_exists($file)) {
        throw new Exception("missing_env_file", 500);
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach($lines as $line) {
        $line = trim($line);
        if(strpos($line, '#') === 0 || empty($line)) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);

        putenv(trim($key) . '=' . trim($value));
    }
}
