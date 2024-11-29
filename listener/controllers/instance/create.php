<?php

/**
 * Creates a user instance with the specified data.
 *
 * @param array{
 *     USERNAME: string,
 *     APP_USERNAME: string,
 *     APP_PASSWORD: string,
 *     CIPHER_KEY?: string,
 *     HTTPS_REDIRECT?: string,
 *     WITH_SB?: bool,
 *     WITH_WP?: bool,
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

    if(strlen($data['USERNAME']) > 32) {
        throw new InvalidArgumentException("invalid_USERNAME", 400);
    }

    if(!isset($data['APP_USERNAME'])) {
        throw new InvalidArgumentException("missing_APP_USERNAME", 400);
    }

    if(!isset($data['APP_PASSWORD'])) {
        throw new InvalidArgumentException("missing_APP_PASSWORD", 400);
    }

    if(strlen($data['APP_PASSWORD']) < 8 || strlen($data['APP_PASSWORD']) > 70) {
        throw new InvalidArgumentException("invalid_APP_PASSWORD", 400);
    }

    if(isset($data['WITH_SB'])) {
        $data['WITH_SB'] = $data['WITH_SB'] ? 'true' : 'false';
    }

    if(isset($data['WITH_WP'])) {
        $data['WITH_WP'] = $data['WITH_WP'] ? 'true' : 'false';
    }

    $data = array_merge([
        'CIPHER_KEY'        => md5(bin2hex(random_bytes(32))),
        'HTTPS_REDIRECT'    => 'noredirect',
        'WITH_SB'           => 'false',
        'WITH_WP'           => 'false',
        'WP_VERSION'        => '6.4',
        'WP_EMAIL'          => 'root@equal.local',
        'WP_TITLE'          => 'eQualPress',
        'MEM_LIMIT'         => '1000M'
    ], $data);

    foreach ($data as $key => $value) {
        if(!putenv("$key=$value")) {
            throw new Exception("failed_to_set_environment_variable", 500);
        }
    }

    $create_equal_instance_bash = SCRIPTS_DIR.'/instance/create/create.bash';

    // Create specific log file for creation to record creation instance
    $instance = $data['USERNAME'];
    $timestamp = date('YmdHis');
    $log_file = BASE_DIR."/logs/$instance-$timestamp.log";

    // Execute create equal instance bash that will use previously set env variables
    exec("bash $create_equal_instance_bash > $log_file 2>&1");

    return [
        'code' => 201,
        'body' => "instance_successfully_created"
    ];
}