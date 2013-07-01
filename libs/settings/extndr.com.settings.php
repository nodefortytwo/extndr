<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

//Database
//This site is hosted on OpenShift so we have to use OpenShift style db connection settings
define('OPENSHIFT_MONGODB_DB_HOST', 'localhost');
define('OPENSHIFT_MONGODB_DB_PORT', '27017');
define('OPENSHIFT_APP_NAME', 'extndr');
define('OPENSHIFT_MONGODB_DB_USERNAME', '');
define('OPENSHIFT_MONGODB_DB_PASSWORD', '');

//Theme Stuff
define('HOST', 'extndr.com');
define('SITE_ROOT', '');
define('PATH_TO_MODULES', 'libs/modules');
define('SITE_NAME', 'extndr');
define('DEFAULT_STYLE', 'cosmo');

define('UPLOAD_PATH', 'public');


define('PRIMARY_AUTH', 'twitter');

//Dev / Live Settings
//any call to elog with a level >= what is defined below will be written to the database
define('DEBUG_LEVEL', 0);
define('TRACE', true);

//APIS
define('INSTAGRAM_ID', '2a08b842d07d4bf39fe2550ae35f01f2');
define('INSTAGRAM_SECRET', 'f59b279c2ddf44e4a12264e65cc73b9f');

define('TWITTER_CONSUMER_KEY', 'vaQ0XmX8BEwq8uDP4hyBgg');
define('TWITTER_CONSUMER_SECRET', 'mlmU8i8g62BQpkaxmvjquCVkooeH9GRNETqMQ6tlSg');
define('TWITTER_OAUTH_CALLBACK', 'http://local.extndr.com/twitter/callback/');

define('FACEBOOK_ID', '578882105485840');
define('FACEBOOK_SECRET', 'cec416abb78924dd6ec645cfec0c62dd');

define('GOOGLE_ID', '202722879933.apps.googleusercontent.com');
define('GOOGLE_SECRET', 'RS_Dk2zAk6UdKMtf91-RFje3');
define('GOOGLE_OAUTH_CALLBACK', 'http://local.extndr.com/google/oauth2callback/');


?>
