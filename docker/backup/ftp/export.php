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
&& defined('FTP_HOST')
&& defined('FTP_USERNAME')
&& defined('FTP_PASSWORD')
&& defined('FTP_REMOTE_DIR')
) or die("missing mandatory constant: check config file\n");


function get_ttl() {
    // get index of current week day 
    $dayofweek = (int) date('w', strtotime('now'));    
    // use 1 for monday, 7 for sunday
    if($dayofweek == 0) {
        $dayofweek = 7;
    }
    // assign TTL based on current day (14 backups)
    $ttl_map = [
        1 => 7,    // one week
        2 => 14,   // two weeks
        3 => 21,   // three weeks
        4 => 28,   // one month
        5 => 56,   // two months
        6 => 84,   // three months
        7 => 112   // four months
    ];
    return $ttl_map[$dayofweek];
}

// retrieve TTL according to current day
$ttl = get_ttl();
// retrieve current date
$current_date = intval(date('Ymd'));

$file_local = "/home/".DOMAIN_NAME."/export/backup.tar";
$file_remote = DOMAIN_NAME."_{$current_date}_{$ttl}.tar";

// establish FTP connection
$conn_id = ftp_connect(FTP_HOST);
// log into the FTP host
if(!@ftp_login($conn_id, FTP_USERNAME, FTP_PASSWORD)) {
    die("error: login failed");
}
// switch to remote directory
if(!ftp_chdir($conn_id, FTP_REMOTE_DIR)) {
    die("error: unable to find remote backup dir");
}
// export backup (overwrite if a file by that name already exist)
if (ftp_put($conn_id, $file_remote, $file_local, FTP_BINARY)) {
    echo "uploaded backup.tar\n";
}
else {
    echo "error: unable to upload file\n";
}
// close FTP connection
ftp_close($conn_id);