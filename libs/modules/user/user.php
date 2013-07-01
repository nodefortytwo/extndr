<?php
function user_init(){
	require 'user.class.php';
}
function user_routes(){
	$paths = array();
	$paths['user'] = array('callback' => 'user_homepage', 'access_callback' => 'twitter_auth_callback');
	$paths['user/connect'] = array('callback' => 'user_connect', 'access_callback' => 'twitter_auth_callback');
	return $paths;
}

function user_homepage(){
	google_active_user();
	$user = new User();
	$page = new Template();
    $content = new Template(false);
    $content->load_template('templates/user.html', 'user');

    $vars = array();
    $vars['user_widget'] = $user->render('profile_widget');
    $vars['connected_accounts'] = $user->render('connected_accounts');
    $vars['wall_add_form'] = wall_add_form();
    $vars['walls'] = user_active()->walls->render('table', array('actions' => true));
    $content->add_variable($vars);
    $page->c($content->render());


    return $page->render();

}

function user_active(){
	return new User();
}

function user_connect($type = null){
	if(is_null($type)){
		message('No account type specified');
		redirect('/user/');
	}

	$func = 'user_connect_' . $type;

	if(function_exists($func)){
		$return = call_user_func_array($func, array());
	}else{
		message('account type not supported');
		redirect('/user/');
	}
}

function user_connect_facebook(){
	$fuser = facebook_active_user();
	if($fuser){
		$user = new User();
		$user['facebook'] = $fuser['_id'];
		$user->save();
		redirect('/user/');
	}else{
		redirect('/facebook/connect/');
	}
}

function user_connect_google(){
	$guser = google_active_user();
	if($guser){
		$user = new User();
		$user['google'] = $guser['_id'];
		$user->save();
		redirect('/user/');
	}else{
		redirect('/google/connect/');
	}
}

function user_connect_instagram(){
	$iuser = instagram_active_user();
	if($iuser){
		$user = new User();
		$user['instagram'] = $iuser['_id'];
		$user->save();
		redirect('/user/');
	}else{
		redirect('/instagram/connect/');
	}
}

