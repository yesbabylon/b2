<?php

/**
*
* import backup / download
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


$current_date = intval(date('Ymd'));

$file_local = "/home/".DOMAIN_NAME."/import/backup.tar";


$conn_id = ftp_connect(FTP_HOST);
// log into the FTP host
$login_result = ftp_login($conn_id, FTP_USERNAME, FTP_PASSWORD);

if(!ftp_chdir($conn_id, FTP_REMOTE_DIR)) {
 echo "error: unable to find remote backup dir";
}

// fetch files list from FTP host current directory
$files = ftp_nlist($conn_id, "./".DOMAIN_NAME."*");

$res = [];
if($files) {
    foreach($files as $filename) {
        $matches = [];
        $escaped_domain = preg_quote(DOMAIN_NAME);
        $re = "/(${escaped_domain})_([0-9]{4}[0-9]{2}[0-9]{2})_([0-9]{1,3}).*/";
        if(preg_match($re, $filename, $matches)) {
            $target = intval($matches[1]);
            $file_date = intval($matches[2]);
            $res[$file_date] = $filename;
        }
    }
}
if(!count($res)) {
        echo "no backup on storage\n";
        exit(0);
}
$latest = array_values(array_slice($res, -1))[0];

$file_remote = $latest;

if (!ftp_get($conn_id, $file_local, $file_remote, FTP_BINARY)) {
    print("retrieved $file_remote\n");
}
else {
    // error
}


ftp_close($conn_id);