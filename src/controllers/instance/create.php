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
 *           PASSWORD: string,
 *           APP_USERNAME: string,
 *           CIPHER_KEY?: string,
 *           HTTPS_REDIRECT?: string,
 *           MEM_LIMIT?: string
 *           CPU_LIMIT?: string
 *          }                               $data   The data for the new instance.
 * @return array{code: int, body: string}
 * @throws Exception
 */
function instance_create(array $data): array {

    if (!shell_exec('command -v git')) {
        // "Git not found. Please install Git before running this script."
        throw new InvalidArgumentException("missing_git", 400);
    }

    if (!shell_exec('command -v docker')) {
        // "Docker not found. Please install Docker before running this script."
        throw new InvalidArgumentException("missing_docker", 400);
    }

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

    if(!isset($data['PASSWORD'])) {
        throw new InvalidArgumentException("missing_PASSWORD", 400);
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

    $data = array_merge([
            'CIPHER_KEY'        => md5(bin2hex(random_bytes(32))),
            'HTTPS_REDIRECT'    => 'noredirect',
            'MEM_LIMIT'         => '1000M',
            'CPU_LIMIT'         => '1'
        ], $data);

    // $create_equal_instance_bash = BASE_DIR.'/conf/instance/create/create.bash';

    // Create specific log file for creation to record creation instance
    $log_file = BASE_DIR.'/logs/instance_create_'.$data['USERNAME'].'-'.date('YmdHis').'.log';

    // Execute create equal instance bash that will use previously set env variables
    // exec("bash $create_equal_instance_bash > $log_file 2>&1");

    $USERNAME = $data['USERNAME'];
    $PASSWORD = $data['PASSWORD'];
    $APP_USERNAME = $data['APP_USERNAME'];
    $CIPHER_KEY = $data['CIPHER_KEY'];
    $MEM_LIMIT = $data['MEM_LIMIT'];
    $CPU_LIMIT = $data['CPU_LIMIT'];
    $HTTPS_REDIRECT = $data['HTTPS_REDIRECT'];

    // create a new user and set password
    exec("adduser --force-badname --disabled-password --gecos ',,,' $USERNAME");
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

    // create .env file
    $env_file = "/home/$USERNAME/.env";
    file_put_contents($env_file, <<<EOT
        # Username should be FQDN as defined in DNS (e.g. example.com)
        USERNAME=$USERNAME
        PASSWORD=$PASSWORD

        # Name of the user user that will be automatically created and access the application (eQual or Wordpress).
        APP_USERNAME=$APP_USERNAME

        # Cipher key for setting secrets encryption and decryption
        CIPHER_KEY=$CIPHER_KEY

        # Whether HTTP requests should be redirected to their HTTPS equivalent (Possible values are: redirect, noredirect).
        HTTPS_REDIRECT=$HTTPS_REDIRECT

        # Relay host public IP address to allow container calling itself (enforce sending the requests to the reverse proxy).
        EXTERNAL_IP_ADDRESS=$EXTERNAL_IP_ADDRESS

        # Limits for resources allocated by docker to the container
        MEM_LIMIT=$MEM_LIMIT
        CPU_LIMIT=$CPU_LIMIT
        EOT
    );

    file_put_contents($log_file, ".env file created.\n", FILE_APPEND | LOCK_EX);

    // copy configuration files
    exec("cp /root/b2/conf/instance/create/template/* /home/$USERNAME/");

    // replace {{db_ID}} in docker-compose.yml
    $hash = substr(md5($USERNAME), 0, 5);
    $docker_compose_path = "/home/$USERNAME/docker-compose.yml";
    $docker_compose_content = file_get_contents($docker_compose_path);
    $docker_compose_content = str_replace("{{db_ID}}", 'db_'.$hash, $docker_compose_content);
    file_put_contents($docker_compose_path, $docker_compose_content);

    file_put_contents($log_file, "Instance successfully created.\n", FILE_APPEND | LOCK_EX);

    return [
        'code' => 201,
        'body' => [ 'result' => 'instance_successfully_created' ]
    ];
}
