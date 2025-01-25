<?php
 /*
    This file is part of the B2 package <http://github.com/yesbabylon/b2>
    Some Rights Reserved, Yesbabylon, 2025
    Licensed under MIT License <https://opensource.org/licenses/MIT>
*/

/**
 * Creates a user instance with the specified data.
 *
 * @param array{
 *     symbiose?: bool,
 *     equalpress?: bool,
 *     USERNAME: string,
 *     APP_USERNAME: string,
 *     APP_PASSWORD: string,
 *     CIPHER_KEY?: string,
 *     HTTPS_REDIRECT?: string,
 *     WP_VERSION?: string,
 *     WP_EMAIL?: string,
 *     WP_TITLE?: string,
 *     MEM_LIMIT?: string
 * } $data
 * @return array{code: int, body: string}
 * @throws Exception
 */
function instance_create(array $data): array {

    if(!isset($data['USERNAME'])) {
        throw new InvalidArgumentException("missing_USERNAME", 400);
    }

    $domain_name_pattern = '/^(?!\-)(?:[a-zA-Z0-9\-]{1,63}\.)+[a-zA-Z]{2,}$/';
    if(
        !is_string($data['USERNAME']) || empty($data['USERNAME']) || strlen($data['USERNAME']) > 32
        || preg_match($domain_name_pattern, $data['USERNAME']) === 0
    ) {
        throw new InvalidArgumentException("invalid_USERNAME", 400);
    }

    if(instance_exists($data['USERNAME'])) {
        throw new InvalidArgumentException("instance_already_exists", 400);
    }

    if(!isset($data['APP_USERNAME'])) {
        throw new InvalidArgumentException("missing_APP_USERNAME", 400);
    }

    if(!is_string($data['APP_USERNAME']) || empty($data['APP_USERNAME'])) {
        throw new InvalidArgumentException("invalid_APP_USERNAME", 400);
    }

    if(!isset($data['APP_PASSWORD'])) {
        throw new InvalidArgumentException("missing_APP_PASSWORD", 400);
    }

    if(!is_string($data['APP_PASSWORD']) || strlen($data['APP_PASSWORD']) < 8 || strlen($data['APP_PASSWORD']) > 70) {
        throw new InvalidArgumentException("invalid_APP_PASSWORD", 400);
    }

    if(isset($data['CIPHER_KEY']) && (!is_string($data['CIPHER_KEY']) || strlen($data['CIPHER_KEY']) !== 32)) {
        throw new InvalidArgumentException("invalid_CIPHER_KEY", 400);
    }

    if(isset($data['HTTPS_REDIRECT']) && !in_array($data['HTTPS_REDIRECT'], ['redirect', 'noredirect'])) {
        throw new InvalidArgumentException("invalid_HTTPS_REDIRECT", 400);
    }

    if(isset($data['MEM_LIMIT']) && (!is_string($data['MEM_LIMIT']) || !preg_match('/^\d+[MG]$/', strtoupper($data['MEM_LIMIT'])))) {
        throw new InvalidArgumentException("invalid_MEM_LIMIT", 400);
    }

    $data = array_merge([
        'symbiose'          => false,
        'equalpress'        => false,
        'CIPHER_KEY'        => md5(bin2hex(random_bytes(32))),
        'HTTPS_REDIRECT'    => 'noredirect',
        'WP_VERSION'        => '6.4',
        'WP_EMAIL'          => 'root@equal.local',
        'WP_TITLE'          => 'eQualPress',
        'MEM_LIMIT'         => '1000M'
    ], $data);

    foreach($data as $key => $value) {
        if(is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }
        if(!putenv("$key=$value")) {
            throw new Exception("failed_setting_env_var", 500);
        }
    }

    $create_equal_instance_bash = BASE_DIR.'/conf/instance/create/create.bash';

    // Create specific log file for creation to record creation instance
    $instance = $data['USERNAME'];
    $timestamp = date('YmdHis');
    $log_file = BASE_DIR."/logs/instance_create_$instance-$timestamp.log";

    // Execute create equal instance bash that will use previously set env variables
    exec("bash $create_equal_instance_bash > $log_file 2>&1");

    return [
        'code' => 201,
        'body' => [ 'result' => 'instance_successfully_created' ]
    ];
}