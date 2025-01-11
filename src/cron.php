<?php
include_once './boot.lib.php';

$cron_jobs = [
    // Add cron jobs here, like following example:
    // [
    //     'description'   => "Release expired backup tokens every 5 minutes.",
    //     'crontab'       => '*/5 * * * *',
    //     'controller'    => 'release-expired-tokens'
    //     'data'          => []
    // ]
];

$results = handle_cron_jobs($cron_jobs);

echo json_encode($results, JSON_PRETTY_PRINT).PHP_EOL;
