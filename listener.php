<?php

include_once './helpers/request-handler.php';

$allowed_routes = [
    '/reboot',                  /* @link reboot() */
    '/status',                  /* @link status() */
    '/ip',                      /* @link ip() */
    '/instances',               /* @link instances() */
    '/instance/backup',         /* @link instance_backup() */
    '/instance/backups',        /* @link instance_backups() */
    '/instance/export-backup',  /* @link instance_export_backup() */
    '/instance/import-backup',  /* @link instance_import_backup() */
    '/instance/create',         /* @link instance_create() */
    '/instance/delete',         /* @link instance_delete() */
    '/instance/restore',        /* @link instance_restore() */
    '/instance/status'          /* @link instance_status() */
];

handle_request(
    [
        'method'        => $_SERVER['REQUEST_METHOD'],
        'uri'           => $_SERVER['REQUEST_URI'],
        'content_type'  => $_SERVER['CONTENT_TYPE'],
        'data'          => file_get_contents("php://input"),
    ],
    $allowed_routes
);
