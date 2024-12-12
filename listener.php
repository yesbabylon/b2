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
    'method'        => $_SERVER['REQUEST_METHOD'],
    'uri'           => $_SERVER['REQUEST_URI'],
    'content_type'  => $_SERVER['CONTENT_TYPE'],
    'data'          => file_get_contents("php://input"),
];

if($request['uri'] === '/api-doc') {
    header('Content-Type: text/html');
    echo file_get_contents(BASE_DIR.'/doc/api-doc.html');
}
else {
    $allowed_routes = [
        '/reboot',                          /* @link reboot() */
        '/status',                          /* @link status() */
        '/ip',                              /* @link ip() */
        '/instances',                       /* @link instances() */
        '/instance/backup',                 /* @link instance_backup() */
        '/instance/backups',                /* @link instance_backups() */
        '/instance/export-backup',          /* @link instance_export_backup() */
        '/instance/import-backup',          /* @link instance_import_backup() */
        '/instance/create',                 /* @link instance_create() */
        '/instance/delete',                 /* @link instance_delete() */
        '/instance/restore',                /* @link instance_restore() */
        '/instance/status',                 /* @link instance_status() */
        '/instance/enable-maintenance',     /* @link instance_enable_maintenance() */
        '/instance/disable-maintenance'     /* @link instance_disable_maintenance() */
    ];

    ['body' => $body, 'code' => $code] = handle_request($request, $allowed_routes);

    send_http_response($body, $code);
}
