<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

//Database
define('DB_NAME', 'cycle');

//Theme Stuff
define('HOST', 'localhost');
define('SITE_ROOT', 'platform');
define('PATH_TO_MODULES', 'libs/modules');
define('SITE_NAME', 'Platform');

define('UPLOAD_PATH', 'public');


//Dev / Live Settings
//any call to elog with a level >= what is defined below will be written to the database
define('DEBUG_LEVEL', 0);
define('TRACE', true);

//APIS

?>
