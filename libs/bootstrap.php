<?php
$cli = false;
if (defined('STDIN')) {
    $_SERVER['HTTP_HOST'] = $argv[1];
    $_SERVER['REQUEST_URI'] = $argv[2];
    print('STARTING CLI MODE' . "\n");
    $cli = true;
}
require('system.class.php');
sys($cli);
exec_hook('init');
if(!$cli){
    ob_start();
}
sys()->active_route();
if(!$cli){
    $contents = ob_get_contents();
    ob_end_clean();
    print($contents);
}