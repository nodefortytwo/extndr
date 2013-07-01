<?php
function facebook_init(){
	require 'libs/facebook-php/facebook.php';
	require 'facebook.user.class.php';
}

function facebook_routes(){

	$paths = array();
	$paths['facebook/connect'] = array('callback' => 'facebook_connect');
    $paths['facebook/callback'] = array('callback' => 'facebook_callback');
    return $paths;
}

function facebook(){
	static $facebook;

	if(!isset($facebook)){
		$facebook = new Facebook(array(
		  'appId'  => config('FACEBOOK_ID'),
		  'secret' => config('FACEBOOK_SECRET'),
		));
	}
	if(session()->facebook_token){
		$facebook->setAccessToken(session()->facebook_token);
	}
	return $facebook;
	
}

function facebook_connect(){

	$params = array(
	  'scope' => '',
	  'redirect_uri' => 'http://' .  config('HOST') . '/facebook/callback/'
	);

	redirect(facebook()->getLoginUrl($params), 301, false);
}

function facebook_callback(){
	$url = "https://graph.facebook.com/oauth/access_token?";
    $params = array();
    $params[] = 'client_id=' . facebook()->getAppId();
    $params[] = 'redirect_uri=' . 'http://' . HOST . get_url('/facebook/callback/');
    $params[] = 'client_secret=' . facebook()->getApiSecret();
    $params[] = 'code=' . get('code');
    $url .= implode('&', $params);
	$data = get_data($url);
	
	$data = explode('&', $data);
	session(1)->facebook_token = str_replace('access_token=', '', $data[0]);

	$facebook = facebook();
	$fuser = $facebook->getUser();
	if($fuser > 0){
		$user = new User();
		$user['facebook'] = $fuser;
		$user->save();
	}
	redirect('/user/');
}

function facebook_active_user(){
	return false;
	if(!is_null(session()->facebook)){
		$user = new facebookUser(session()->facebook['user']['id']);
		if(!$user->exists){
			$user->update_from_session();	
		}
		return $user;
	}else{
		return false;
	}
}