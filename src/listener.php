<?php
 /*
    This file is part of the B2 package <http://github.com/yesbabylon/b2>
    Some Rights Reserved, Yesbabylon, 2025
    Licensed under MIT License <https://opensource.org/licenses/MIT>
*/

include_once './boot.lib.php';

const HOST_SECRET_FILE = '/root/b2/host.secrets';

enforce_security();

$request = [
    'method'        => $_SERVER['REQUEST_METHOD'] ?? 'GET',
    'uri'           => $_SERVER['REQUEST_URI'] ?? '/',
    'content_type'  => $_SERVER['CONTENT_TYPE'] ?? 'application/json',
    'data'          => file_get_contents("php://input"),
];

$routes = [
	'GET' 	=> [
		'/status',                          /* @link status() */
		'/instances',                       /* @link instances() */
		'/instance/status',                 /* @link instance_status() */
		'/instance/backups',                /* @link instance_backups() */
	],
	'POST' 	=> [
		'/reboot',                          /* @link reboot() */
		'/ip',                              /* @link ip() */
		'/instance/backup',                 /* @link instance_backup() */
		'/instance/export-backup',          /* @link instance_export_backup() */
		'/instance/import-backup',          /* @link instance_import_backup() */
		'/instance/create',                 /* @link instance_create() */
		'/instance/delete',                 /* @link instance_delete() */
		'/instance/restore',                /* @link instance_restore() */
		'/instance/enable-maintenance',     /* @link instance_enable_maintenance() */
		'/instance/disable-maintenance',    /* @link instance_disable_maintenance() */
	]
];

['body' => $body, 'code' => $code] = handle_request($request, $routes);

trigger_error('result: '.serialize((array) $body), E_USER_NOTICE);

send_http_response($body, $code);

function enforce_security() {
	is_readable(HOST_SECRET_FILE) && check_basic_auth();
}

function check_basic_auth() {
    [$user, $pass] = parse_basic_auth();

	if($user !== 'root' || !check_password($pass)) {
        send_http_response(
            ['error' => 'Unauthorized'],
            401,
            ['WWW-Authenticate' => 'Basic realm="host-api"']
        );
        exit;
    }
}

function check_password($password) {
	static $hash = null;
	
	if($hash === null) {
		if(!is_readable(HOST_SECRET_FILE)) {
			return false;
		}
		$hash = trim(file_get_contents(HOST_SECRET_FILE));
	}
	
    return $hash !== '' && password_verify($password, $hash);
}

function parse_basic_auth(): array {

	$headers = array_change_key_case(getallheaders(), CASE_LOWER);

    if(!isset($headers['authorization'])) {
        return [null, null];
    }

    if(!preg_match('/^Basic\s+(.*)$/i', $headers['authorization'], $matches)) {
        return [null, null];
    }

    $decoded = base64_decode($matches[1], true);

    if($decoded === false || !str_contains($decoded, ':')) {
        return [null, null];
    }

    return explode(':', $decoded, 2);
}