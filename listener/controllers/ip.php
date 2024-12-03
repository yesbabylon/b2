<?php

/**
 * Sets public interface IP address (veth0)
 *
 * @return array{code: int, body: string}
 * @throws Exception
 */
function ip(array $data): array {
    if(!isset($data['ip_address'])) {
        throw new InvalidArgumentException("missing_ip_address", 400);
    }

    $is_valid_ipv4_regex = '/^((25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])\.){3}(25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])$/';
    if(preg_match($is_valid_ipv4_regex, $data['ip_address']) !== 1) {
        throw new InvalidArgumentException("invalid_ip_address", 400);
    }

    if(!isset($data['subnet'])) {
        throw new InvalidArgumentException("missing_subnet", 400);
    }

    if(!preg_match('/^\d{1,2}$/', $data['subnet']) || (int) $data['subnet'] > 32) {
        throw new InvalidArgumentException("invalid_subnet", 400);
    }

    throw new Exception('ip addr add '.$data['ip_address'].'/'.$data['subnet'].' dev veth0', 400);
    exec('ip addr add '.$data['ip_address'].'/'.$data['subnet'].' dev veth0');

    return [
        'code' => 200,
        'body' => "ip_address_updated"
    ];
}
