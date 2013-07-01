<?php
function session_init(){
    require('session.class.php');
}

function session_routes(){
    $paths = array();

    $paths['logout'] = array('callback' => 'session_kill');
    return $paths;
}

function session($state = 0){
    static $session;
    if(!$session){
        $session = new Session();
    }
    
    if($session->state() != $state){
        $session->state($state);
    }
    
    return $session;
}

function session_kill(){//session_destroy is already a function :(

    session()->destroy();
    redirect('/');
    
}
