<?php
 /*
    This file is part of the B2 package <http://github.com/yesbabylon/b2>
    Some Rights Reserved, Yesbabylon, 2025
    Licensed under MIT License <https://opensource.org/licenses/MIT>
*/

include_once './boot.lib.php';

$cron_jobs = [
    [
        'description'   => "Create new backups for all active instances of the host every day at 2 AM.",
        'crontab'       => '0 2 * * *',
        'controller'    => 'backup',
        'data'          => []
    ]
];

$results = handle_cron_jobs($cron_jobs);

echo json_encode($results, JSON_PRETTY_PRINT).PHP_EOL;
