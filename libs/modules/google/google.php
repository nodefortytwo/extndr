<?php

function google_init(){
	require 'libs/google_php/Google_Client.php';
	require 'libs/google_php/contrib/Google_PlusService.php';
	require 'google.user.class.php';
}

function google_routes(){

	$paths = array();
	$paths['google/connect'] = array('callback' => 'google_connect');
    $paths['google/oauth2callback'] = array('callback' => 'google_callback');
    return $paths;
}

function google(){
	static $client;
	if(!isset($client)){
		$client = new Google_Client();
		$client->setApplicationName("extndr social wall");
		$plus = new Google_PlusService($client);
		if(session()->google_access_token !== null){
			$client->setAccessToken(session()->google_access_token);
		}
	}
	return $client;
}

function google_plus(){

	return new Google_PlusService(google());
}

function google_connect(){
	google()->authenticate();
}

function google_callback(){
	google()->authenticate();
	session(1)->google_access_token = google()->getAccessToken();
	$user = new User();
	if($user->exists){
		$guser = google_active_user();
		$user['google'] = $guser['_id'];
		$user->save();
	}
	message('Connected to Google+');
	redirect('/user/');
}

function google_active_user(){
	static $user;
    if(isset($user)){
        return $user;
    }
    if(session()->google_access_token){

    	if(session()->google_user_id){
    		$user = new GoogleUser(session()->google_user_id);
    	}else{
    		$user = new GoogleUser(google_plus()->people->get('me'));
    		session(1)->google_user_id = $user['_id'];
    	}

        return $user;
    }else{
        return false;
    }
}