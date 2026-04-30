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
 *          USERNAME: string,
 *          PASSWORD: string,
 *          INSTANCE_TYPE?: string,
 *          CIPHER_KEY?: string,
 *          HTTPS_REDIRECT?: string,
 *          MEM_LIMIT?: string,
 *          CPU_LIMIT?: string,
 *          INSTANCE_SUBTYPE?: string,
 *          INSTANCE_UUID?: string,
 *          GLOBAL_ACCESS_TOKEN?: string,
 *          GLOBAL_URL?: string
 *        }                             $data   The data for the new instance.
 * @return array{code: int, body: string}
 * @throws Exception
 */
function instance_create(array $data): array {

    // check env for required binaries

    if (!shell_exec('command -v git')) {
        // "Git not found. Please install Git before running this script."
        throw new InvalidArgumentException("missing_git", 400);
    }

    if (!shell_exec('command -v docker')) {
        // "Docker not found. Please install Docker before running this script."
        throw new InvalidArgumentException("missing_docker", 400);
    }

    // check presence of mandatory params

    if(!isset($data['USERNAME'])) {
        throw new InvalidArgumentException("missing_USERNAME", 400);
    }

    if(!isset($data['PASSWORD'])) {
        throw new InvalidArgumentException("missing_PASSWORD", 400);
    }

    // check params validity

    if(!is_string($data['USERNAME']) || empty($data['USERNAME'])) {
        throw new InvalidArgumentException("invalid_USERNAME", 400);
    }

    // Linux user names are limited to 32 characters.
    if(strlen($data['USERNAME']) > 32) {
        throw new InvalidArgumentException("invalid_USERNAME_length", 400);
    }

    if(preg_match('/^(?!\-)(?:[a-zA-Z0-9\-]{1,63}\.)+[a-zA-Z]{2,}$/', $data['USERNAME']) === 0) {
        throw new InvalidArgumentException("invalid_USERNAME", 400);
    }

    if(instance_exists($data['USERNAME'])) {
        throw new InvalidArgumentException("instance_already_exists", 400);
    }

    if(!is_string($data['PASSWORD']) || strlen($data['PASSWORD']) < 8 || strlen($data['PASSWORD']) > 70) {
        throw new InvalidArgumentException("invalid_PASSWORD", 400);
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

    if(isset($data['CPU_LIMIT']) && !is_numeric($data['CPU_LIMIT']) ) {
        throw new InvalidArgumentException("invalid_CPU_LIMIT", 400);
    }

    $allowed_instance_types = ['equal', 'wordpress', 'equalpress', 'symbiose', 'fmt'];

    if(isset($data['INSTANCE_TYPE']) && (!is_string($data['INSTANCE_TYPE']) || !in_array($data['INSTANCE_TYPE'], $allowed_instance_types))) {
        throw new InvalidArgumentException("invalid_INSTANCE_TYPE", 400);
    }

    $default_data = [
        'CIPHER_KEY'        => md5(bin2hex(random_bytes(32))),
        'INSTANCE_TYPE'     => 'equal',
        'HTTPS_REDIRECT'    => 'noredirect',
        'MEM_LIMIT'         => '1000M',
        'CPU_LIMIT'         => '1',
        'EQ_MEM_FREE_LIMIT' => '256M'
    ];

    if(isset($data['INSTANCE_TYPE'])) {
        switch($data['INSTANCE_TYPE']) {
            case 'fmt':
                // add default data for FMT
                $default_data['INSTANCE_SUBTYPE'] = 'agency';

                $allowed_instance_subtypes = ['global', 'agency'];
                if(isset($data['INSTANCE_SUBTYPE']) && (!is_string($data['INSTANCE_SUBTYPE']) || !in_array($data['INSTANCE_SUBTYPE'], $allowed_instance_subtypes))) {
                    throw new InvalidArgumentException("invalid_INSTANCE_SUBTYPE", 400);
                }
                elseif($data['INSTANCE_SUBTYPE'] === 'agency') {
                    if(empty($data['INSTANCE_UUID']) || !is_string($data['INSTANCE_UUID'])) {
                        throw new InvalidArgumentException("invalid_INSTANCE_UUID", 400);
                    }

                    if(empty($data['GLOBAL_ACCESS_TOKEN']) || !is_string($data['GLOBAL_ACCESS_TOKEN'])) {
                        throw new InvalidArgumentException("invalid_GLOBAL_ACCESS_TOKEN", 400);
                    }

                    if(empty($data['GLOBAL_URL']) || !is_string($data['GLOBAL_URL']) || !filter_var($data['GLOBAL_URL'], FILTER_VALIDATE_URL)) {
                        throw new InvalidArgumentException("invalid_GLOBAL_URL", 400);
                    }
                }
                break;
        }
    }

    // assign default values for non-mandatory parameters if not provided
    $data = array_merge($default_data, $data);

    // $create_equal_instance_bash = BASE_DIR.'/conf/instance/create.bash';

    // Create specific log file for creation to record creation instance
    $log_file = BASE_DIR . '/logs/instance_create_' . $data['USERNAME'] . '-' . date('YmdHis') . '.log';

    // Execute create equal instance bash that will use previously set env variables
    // exec("bash $create_equal_instance_bash > $log_file 2>&1");

    $USERNAME = $data['USERNAME'];
    $PASSWORD = $data['PASSWORD'];
    $CIPHER_KEY = $data['CIPHER_KEY'];
    $MEM_LIMIT = $data['MEM_LIMIT'];
    $CPU_LIMIT = $data['CPU_LIMIT'];
    $HTTPS_REDIRECT = $data['HTTPS_REDIRECT'];
    $EQ_MEM_FREE_LIMIT = $data['EQ_MEM_FREE_LIMIT'];
    $INSTANCE_TYPE = $data['INSTANCE_TYPE'];

    // create a new user and set password
    exec("id -u $USERNAME >/dev/null 2>&1 || adduser --force-badname --disabled-password --gecos ',,,' $USERNAME");
    exec("echo '$USERNAME:$PASSWORD' | chpasswd");

    // add user to docker group
    exec("usermod -a -G docker $USERNAME");

    // add user directories
    exec("mkdir -p /home/$USERNAME/import /home/$USERNAME/export /home/$USERNAME/www");
    exec("usermod -d /home/$USERNAME/www $USERNAME");
    exec("chmod g+w -R /home/$USERNAME/www");

    // define ssh-login as default shell for user
    // #memo - ssh-login is expected to have been installed in /usr/local/bin/ssh-login by the install script
    exec("chsh -s /usr/local/bin/ssh-login $USERNAME");

    // restart SFTP service
    exec("systemctl restart vsftpd");

    // Créer un dossier pour le mode maintenance
    exec("mkdir -p /srv/docker/nginx/html/$USERNAME");

    file_put_contents($log_file, "User created and configured.\n", FILE_APPEND | LOCK_EX);

    $EXTERNAL_IP_ADDRESS = trim(shell_exec("ip -4 addr show ens3 | grep -oP '(?<=inet\\s)\\d+(\\.\\d+){3}'"));

    $env = <<<EOT
        # Username should be FQDN as defined in DNS (e.g. example.com)
        USERNAME=$USERNAME
        PASSWORD=$PASSWORD

        # Cipher key for setting secrets encryption and decryption
        CIPHER_KEY=$CIPHER_KEY

        # Whether HTTP requests should be redirected to their HTTPS equivalent (Possible values are: redirect, noredirect).
        HTTPS_REDIRECT=$HTTPS_REDIRECT

        # Relay host public IP address to allow container calling itself (enforce sending the requests to the reverse proxy).
        EXTERNAL_IP_ADDRESS=$EXTERNAL_IP_ADDRESS

        # Limits for resources allocated by docker to the container
        MEM_LIMIT=$MEM_LIMIT
        CPU_LIMIT=$CPU_LIMIT

        EQ_MEM_FREE_LIMIT=$EQ_MEM_FREE_LIMIT
        EOT;

    if($INSTANCE_TYPE === 'fmt') {
        $env .= PHP_EOL.PHP_EOL.<<<EOT
            # FMT
            INSTANCE_SUBTYPE={$data['INSTANCE_SUBTYPE']}
            EOT;

        if($data['INSTANCE_SUBTYPE'] === 'agency') {
            $env .= PHP_EOL.<<<EOT
            INSTANCE_UUID={$data['INSTANCE_UUID']}
            GLOBAL_ACCESS_TOKEN={$data['GLOBAL_ACCESS_TOKEN']}
            GLOBAL_URL={$data['GLOBAL_URL']}
            EOT;
        }
    }
    $env_file = "/home/$USERNAME/.env";
    file_put_contents($env_file, $env.PHP_EOL);

    file_put_contents($log_file, ".env file created.\n", FILE_APPEND | LOCK_EX);

    // copy configuration files
	$dir = BASE_DIR . "/conf/instance/$INSTANCE_TYPE";

	if(!is_dir($dir) || !is_file("$dir/init.sh")) {
		throw new RuntimeException("invalid_instance_type", 500);
	}

	foreach(glob("$dir/*") ?: [] as $f) {
        if(!is_file($f)) {
            continue;
        }

        $dest = "/home/$USERNAME/".basename($f);
        if(copy($f, $dest)) {
            // if it's a .sh file, make it executable
            if(substr($dest, -3) === '.sh') {
                chmod($dest, 0755);
            }
        }
	}

    // replace {{db_ID}} in docker-compose.yml
    $hash = substr(md5($USERNAME), 0, 5);
    $docker_compose_path = "/home/$USERNAME/docker-compose.yml";
    $docker_compose_content = file_get_contents($docker_compose_path);
    $docker_compose_content = str_replace("{{db_ID}}", 'db_'.$hash, $docker_compose_content);
    file_put_contents($docker_compose_path, $docker_compose_content);

    if($INSTANCE_TYPE === 'fmt') {
        // replace {{variable}} in config.json
        $config_path = "/home/$USERNAME/config.json";
        $config_content = file_get_contents($config_path);
        foreach($data as $key => $value) {
            $config_content = str_replace("{{$key}}", $value, $config_content);
        }
        // remove all optional {{variable}}
        $config_content = preg_replace('/\{\{[^}]+\}\}/', '', $config_content);
        // modify config.json
        file_put_contents($config_path, $config_content);
    }

    file_put_contents($log_file, "Instance successfully created.\n", FILE_APPEND | LOCK_EX);

    return [
        'code' => 201,
        'body' => [ 'result' => 'instance_successfully_created' ]
    ];
}
