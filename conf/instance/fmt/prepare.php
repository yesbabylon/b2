<?php

$env_file = __DIR__.'/.env';
if(!is_file($env_file)) {
    throw new RuntimeException("missing_env_file", 500);
}

$parse_env_value = function(string $value): string {
    $value = trim($value);

    if(strlen($value) >= 2 && $value[0] === "'" && substr($value, -1) === "'") {
        return str_replace("'\"'\"'", "'", substr($value, 1, -1));
    }

    if(strlen($value) >= 2 && $value[0] === '"' && substr($value, -1) === '"') {
        return stripcslashes(substr($value, 1, -1));
    }

    return $value;
};

$data = [];
foreach(file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
    $line = trim($line);
    if($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
        continue;
    }

    [$key, $value] = explode('=', $line, 2);
    $key = trim($key);
    if(!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key)) {
        continue;
    }

    $data[$key] = $parse_env_value($value);
}

if(empty($data['USERNAME']) || !is_string($data['USERNAME'])) {
    throw new InvalidArgumentException("missing_USERNAME", 400);
}

$USERNAME = $data['USERNAME'];

if(empty($data['AUTH_SECRET_KEY'])) {
    $data['AUTH_SECRET_KEY'] = bin2hex(random_bytes(32));
}


$allowed_instance_subtypes = ['global', 'agency'];
if(isset($data['INSTANCE_SUBTYPE']) && (!is_string($data['INSTANCE_SUBTYPE']) || !in_array($data['INSTANCE_SUBTYPE'], $allowed_instance_subtypes))) {
    throw new InvalidArgumentException("invalid_INSTANCE_SUBTYPE", 400);
}

if(isset($data['SYNC']) && !is_bool($data['SYNC'])) {
    if(is_string($data['SYNC'])) {
        $data['SYNC'] = in_array(strtolower($data['SYNC']), ['1', 'true', 'yes']);
    }
    elseif(is_int($data['SYNC'])) {
        $data['SYNC'] = $data['SYNC'] === 1;
    }
    else {
        throw new InvalidArgumentException("invalid_SYNC", 400);
    }
}

if(isset($data['SYNC']) && $data['SYNC']) {
    if(isset($data['INSTANCE_SUBTYPE']) && $data['INSTANCE_SUBTYPE'] === 'global') {
        throw new InvalidArgumentException("invalid_INSTANCE_SUBTYPE", 400);
    }

    if(empty($data['INSTANCE_UUID']) || !is_string($data['INSTANCE_UUID'])) {
        throw new InvalidArgumentException("invalid_INSTANCE_UUID", 400);
    }

    if(empty($data['GLOBAL_ACCESS_TOKEN']) || !is_string($data['GLOBAL_ACCESS_TOKEN'])) {
        throw new InvalidArgumentException("invalid_GLOBAL_ACCESS_TOKEN", 400);
    }

    if(empty($data['GLOBAL_URL']) || !is_string($data['GLOBAL_URL']) || !filter_var($data['GLOBAL_URL'], FILTER_VALIDATE_URL)) {
        throw new InvalidArgumentException("invalid_GLOBAL_URL", 400);
    }

    if(isset($data['SYNC_LEVEL']) && !in_array($data['SYNC_LEVEL'], ['required', 'recommended', 'optional', 'demo'])) {
        throw new InvalidArgumentException("invalid_SYNC_LEVEL", 400);
    }
}

// Inject FMT secrets from the host secrets file, if present.
$secrets = [
	"GOOGLE_DOCAI_PRIVATE_KEY",
	"GOOGLE_DOCAI_CLIENT_EMAIL",
	"GOOGLE_DOCAI_PROJECT_ID",
	"GOOGLE_DOCAI_PROCESSOR_ID",
	"GOOGLE_GMAIL_CLIENT_ID",
	"GOOGLE_GMAIL_CLIENT_SECRET",
	"MS_TENANT_ID",
	"MS_OUTLOOK_CLIENT_ID",
	"MS_OUTLOOK_CLIENT_SECRET",
	"GOOGLE_PROJECT_ID",
	"GOOGLE_PROJECT_NUMBER",
	"GOOGLE_OAUTH_CLIENT_ID",
	"GOOGLE_OAUTH_CLIENT_SECRET",
	"GOOGLE_DOCUMENT_AI_PROCESSOR_ID",
	"GOOGLE_SERVICE_ACCOUNT_CLIENT_ID",
	"GOOGLE_SERVICE_ACCOUNT_CLIENT_EMAIL",
	"GOOGLE_SERVICE_ACCOUNT_PRIVATE_KEY_ID",
	"GOOGLE_SERVICE_ACCOUNT_PRIVATE_KEY"
];

$secrets_file = '/root/b2/secrets/fmt.json';
if(file_exists($secrets_file)) {
    if(!is_file($secrets_file) || !is_readable($secrets_file)) {
        throw new RuntimeException("unreadable_secrets_file", 500);
    }

    $secrets_content = file_get_contents($secrets_file);
    if($secrets_content === false) {
        throw new RuntimeException("unreadable_secrets_file", 500);
    }

    try {
        $secret_values = json_decode(
            $secrets_content,
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }
    catch(JsonException $e) {
        throw new RuntimeException("invalid_secrets_file", 500, $e);
    }

    if(!is_array($secret_values)) {
        throw new RuntimeException("invalid_secrets_map", 500);
    }

    foreach($secrets as $key) {
        if(array_key_exists($key, $secret_values)) {
            if(!is_string($secret_values[$key])) {
                throw new RuntimeException("invalid_secret_{$key}", 500);
            }
            $data[$key] = $secret_values[$key];
        }
    }
}

// replace {{variable}} in config.json
$config_path = "/home/$USERNAME/config.json";
$config_content = file_get_contents($config_path);

foreach($data as $key => $value) {
    if(is_bool($value)) {
        $value = $value ? 'true' : 'false';
    }
    elseif($value === null) {
        $value = '';
    }
    elseif(is_array($value) || is_object($value)) {
        $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if($value === false) {
            $value = '';
        }
    }
    else {
        $value = (string) $value;
    }

    $value = substr(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 1, -1);
    $config_content = str_replace("{{{$key}}}", $value, $config_content);
}

// remove all remaining {{variable}}
$config_content = preg_replace('/\{\{[^}]+\}\}/', '', $config_content);

// modify config.json
file_put_contents($config_path, $config_content);
