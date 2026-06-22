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
    if($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
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

// inject FMT secrets from SECRETS variable, if present
$secrets = ["GOOGLE_DOCAI_PRIVATE_KEY", "GOOGLE_DOCAI_CLIENT_EMAIL", "GOOGLE_DOCAI_PROJECT_ID", "GOOGLE_DOCAI_PROCESSOR_ID", "GOOGLE_GMAIL_CLIENT_ID", "GOOGLE_GMAIL_CLIENT_SECRET", "MS_TENANT_ID", "MS_OUTLOOK_CLIENT_ID", "MS_OUTLOOK_CLIENT_SECRET"];

if(!empty($data['SECRETS'])) {
    $decoded = base64_decode($data['SECRETS'], true);
    $secret_values = $decoded !== false ? json_decode($decoded, true) : null;
    if(!is_array($secret_values)) {
        throw new InvalidArgumentException("invalid_SECRETS", 400);
    }

    foreach($secrets as $key) {
        if(array_key_exists($key, $secret_values)) {
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
