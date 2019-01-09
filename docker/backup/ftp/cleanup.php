<?php

/**
*
* Clean up backup storage: keep only those with acceptable TTL
*/



if(!isset($argv) || !isset($argv[1])) {
    die('missing mandatory argument');
}

define('DOMAIN_NAME', $argv[1]);

require_once('/home/'.DOMAIN_NAME.'/status/backup/config.inc.php');

(defined('DOMAIN_NAME')
&& defined('FTP_HOST')
&& defined('FTP_USERNAME')
&& defined('FTP_PASSWORD')
&& defined('FTP_REMOTE_DIR')
) or die('missing mandatory constant: check config file');


$current_date = intval(date('Ymd'));
// establish FTP connection
$conn_id = ftp_connect(FTP_HOST);
// log into the FTP host
$login_result = ftp_login($conn_id, FTP_USERNAME, FTP_PASSWORD);
// switch to remote directory
if(!ftp_chdir($conn_id, FTP_REMOTE_DIR)) {
    die("error: unable to find remote backup dir");
}
// fetch files list from FTP host current directory
$files = ftp_nlist($conn_id, "./".DOMAIN_NAME."*");

if($files) {
    // loop amongst mathing files, if any
    foreach($files as $filename) {
        $matches = [];
        $escaped_domain = preg_quote(DOMAIN_NAME);
        $re = "/(${escaped_domain})_([0-9]{4}[0-9]{2}[0-9]{2})_([0-9]{1,3}).*/";
        if(preg_match($re, $filename, $matches)) {
            $target = intval($matches[1]);
            $file_date = intval($matches[2]);
            $ttl = intval($matches[3]);
            $diff = $current_date - $file_date;
            if($ttl < $diff) {
                ftp_delete($conn_id, $filename);
                echo "removed $filename\n";
            }
        }    
    }
}
// close FTP connection
ftp_close($conn_id);
