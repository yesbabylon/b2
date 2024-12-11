<?php

define('EXPORT_TYPE' , 'FTP');      // 'FTP' or 'FS'

// FS mandatory options
define('FS_DIR',        '/mnt/backups');

// FTP mandatory options
define('FTP_HOST',      'IP or hostname');
define('FTP_USERNAME',  'username');
define('FTP_PASSWORD',  'password');
define('FTP_REMOTE_DIR','/path/to/filestore');
