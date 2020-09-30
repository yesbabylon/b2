<?php
/**
*
* Export backup / upload
*/

if(!isset($argv) || !isset($argv[1])) {
    die("missing mandatory argument\n");
}

define('DOMAIN_NAME', $argv[1]);

require_once('/home/'.DOMAIN_NAME.'/status/backup/config.inc.php');

(defined('DOMAIN_NAME')
&& defined('FS_DIR')
) or die("missing mandatory constant: check config file\n");


function get_ttl() {
    // get index of current week day 
    $dayofweek = (int) date('w', strtotime('now'));    
    // use 1 for monday, 7 for sunday
    if($dayofweek == 0) {
        $dayofweek = 7;
    }
    // assign TTL based on current day (17 backups)
    $ttl_map = [
        1 => 7,    // one week
        2 => 7,    // one week
        3 => 7,    // one week
        4 => 7,    // one week
        5 => 7,    // one week
        6 => 28,   // four weeks
        7 => 56    // two months        
    ];
    
    return $ttl_map[$dayofweek];
}

// retrieve TTL according to current day
$ttl = get_ttl();
// retrieve current date
$current_date = intval(date('Ymd'));

$file_local = "/home/".DOMAIN_NAME."/export/backup.tar";
$file_remote = FS_DIR."/".DOMAIN_NAME."_{$current_date}_{$ttl}.tar";


if (copy($file_local, $file_remote)) {
    echo "exported backup.tar\n";
}
else {
    echo "error: unable to export file\n";
}