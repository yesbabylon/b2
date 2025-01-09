<?php

include_once './helpers/backup.php';
include_once './helpers/env.php';
include_once './helpers/instances.php';
include_once './helpers/host-status.php';
include_once './helpers/http-response.php';
include_once './helpers/request-handler.php';

const BASE_DIR = __DIR__;
const CONTROLLERS_DIR = __DIR__ . '/controllers';
const SCRIPTS_DIR = __DIR__ . '/scripts';

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

trigger_error('result: '.$body, E_USER_NOTICE);

send_http_response($body, $code);
