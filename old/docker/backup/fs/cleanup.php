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
&& defined('FS_DIR')
) or die("missing mandatory constant: check config file\n");


$current_time = time();

$files = glob(FS_DIR."/".DOMAIN_NAME."*");

if($files) {
    // loop amongst mathing files, if any
    foreach($files as $filepath) {
        $filename = basename($filepath);
        $matches = [];
        $escaped_domain = preg_quote(DOMAIN_NAME);
        $re = "/(${escaped_domain})_([0-9]{4}[0-9]{2}[0-9]{2})_([0-9]{1,3}).*/";
        if(preg_match($re, $filename, $matches)) {
            $target = intval($matches[1]);
            $date = intval($matches[2]);
            $ttl = intval($matches[3]);            
            list($year, $month, $day) = [substr($date, 0, 4), substr($date, 4, 2), substr($date, 6, 2)];
            $file_time = mktime(0, 0, 0, $month, $day, $year);
            $diff = round( ($current_time - $file_time) / (60*60*24) );
            if($ttl < $diff) {
                unlink($filepath);
                echo "removed $filename\n";
            }
        }    
    }
}

