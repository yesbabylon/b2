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
&& defined('FS_DIR')
) or die("missing mandatory constant: check config file\n");


$current_date = intval(date('Ymd'));

$file_local = "/home/".DOMAIN_NAME."/import/backup.tar";


$files = glob(FS_DIR."/".DOMAIN_NAME."*");

$res = [];
if($files) {
    foreach($files as $filepath) {
        $filename = basename($filepath);
        $matches = [];
        $escaped_domain = preg_quote(DOMAIN_NAME);
        $re = "/(${escaped_domain})_([0-9]{4}[0-9]{2}[0-9]{2})_([0-9]{1,3}).*/";
        if(preg_match($re, $filename, $matches)) {
            $target = intval($matches[1]);
            $file_date = intval($matches[2]);
            $res[$file_date] = $filepath;
        }
    }
}
if(!count($res)) {
        echo "no backup on storage\n";
        exit(0);
}
$latest = array_values(array_slice($res, -1))[0];

$file_remote = $latest;

if (copy($file_remote, $file_local)) {
    echo "retrieved $file_remote\n";
}
else {
    echo "error: unable to import file\n";
}