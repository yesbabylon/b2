<?php

include_once './helpers/cron-handler.php';
include_once './helpers/env.php';

const BASE_DIR = __DIR__;
const CONTROLLERS_DIR = __DIR__ . '/controllers';
const SCRIPTS_DIR = __DIR__ . '/scripts';

$cron_jobs = [
    // Add cron jobs here, like following example:
    // [
    //     'description'   => "Release expired backup tokens every 5 minutes.",
    //     'crontab'       => '*/1 * * * *',
    //     'controller'    => 'release-expired-tokens'
    // ]
];

$results = handle_cron_jobs($cron_jobs);

echo json_encode($results, JSON_PRETTY_PRINT).PHP_EOL;
