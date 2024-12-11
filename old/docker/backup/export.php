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
&& defined('EXPORT_TYPE')
) or die("missing mandatory constant: check config file\n");

$return_var = 0;
$output = '';

switch(EXPORT_TYPE) {
    case 'FTP':
        exec('/usr/bin/php /home/docker/backup/ftp/export.php '.DOMAIN_NAME, $output , $return_var);
        break;
    case 'FS':
        exec('/usr/bin/php /home/docker/backup/fs/export.php '.DOMAIN_NAME, $output , $return_var);
        break;
}