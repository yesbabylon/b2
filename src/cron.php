<?php
include_once './boot.lib.php';

$cron_jobs = [    
    [
        'description'   => "Create new backups for all active instances of the host.",
        'crontab'       => '* 2 * * *',
        'controller'    => 'backup',
        'data'          => []
    ]
];

$results = handle_cron_jobs($cron_jobs);

echo json_encode($results, JSON_PRETTY_PRINT).PHP_EOL;
