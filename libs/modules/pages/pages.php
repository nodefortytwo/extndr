<?php

function pages_routes() {
    $paths = array();

    $paths['home'] = array('callback' => 'pages_homepage');
    
    $paths['offline'] = array('callback' => 'pages_offline');

    $paths['404'] = array('callback' => 'pages_404');
    $paths['403'] = array('callback' => 'pages_403');
    $paths['500'] = array('callback' => 'pages_500');
    $paths['204'] = array('callback' => 'pages_204');
    return $paths;
}

function pages_homepage() {

    $user = twitter_active_user();
    if($user !== false){
        redirect('/user/');
    }

    $page = new Template();

    $page->title = "Welcome";
    $page->load_template('templates/index.html', 'pages');
    $page->add_variable(array('twitter_connect_button' => twitter_connect_button()));
    
    return $page->render();
}

function pages_404() {
    header("HTTP/1.0 404 Not Found");
    $page = new Template();
    $page->title = "Sorry, page not found";
    $img = '/' . SITE_ROOT . '/' . PATH_TO_MODULES . '/pages/img/404.jpg';
    $page->c('<div class="span12">' . '<h1>404 - Page Not Found</h1>');
    $page->c('</div>');
    return $page->render();
}

function pages_204() {
    header("HTTP/1.0 204 No Content");
    return '';
}

function pages_403() {
    header("HTTP/1.0 403 Access Denied");
    $page = new Template();
    $page->title = "Access Denied";
    $img = '/' . SITE_ROOT . '/' . PATH_TO_MODULES . '/pages/img/404.jpg';
    $page->c('<div class="span12">' . '<h1>403 - Access Denied</h1>');
    $page->c('<h2>Move along, Nothing to see here</h2>');
    $page->c('</div>');
    return $page->render();
}

function pages_500($eid = null) {
    header("HTTP/1.0 500 Server Error");
    $page = new Template();
    $page->title = "Code Error";
    $page->c('<div class="span12">' . '<h1>500 - I appear to have broken something. Sent me a tweet STAT! @nodefortytwo</h1>');
    $page->c('</div>');

    if(class_exists('Error')){
        if(!$eid){
            $eid = get('eid');
        }
        $error = new Error($eid);
        $page->c($error->render());
    }
    return $page->render();
}

function pages_offline() {
    
    $page = new Template();
    $page->title = "Offline";
    $page->c('<div class="span12">' . '<h1>You appear to be offline!</h1>');
    $page->c('</div>');
    return $page->render();
}

function pages_user(){

}

