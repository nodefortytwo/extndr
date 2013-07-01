<?php
function instagram_init(){
	require 'instagram.user.class.php';
	require 'instagram.media.class.php';
	require 'instagram.location.formitem.class.php';
}

function instagram_routes(){

	$paths = array();
	$paths['instagram/connect'] = array('callback' => 'instagram_connect');
    $paths['instagram/callback'] = array('callback' => 'instagram_callback');
    $paths['instagram/input/add'] = array('callback' => 'instagram_input_add');
    $paths['instagram/input/add/submit'] = array('callback' => 'instagram_input_add_submit');
    return $paths;
}

function instagram(){
	
}

function instagram_connect(){
	$url = 'http://' .  config('HOST') . '/instagram/callback/';
	redirect('https://api.instagram.com/oauth/authorize/?client_id=' .  config('INSTAGRAM_ID') . '&redirect_uri=' . $url . '&response_type=code', 301, false);
}

function instagram_callback(){
	if(!get('code')){
		redirect('/instagram/connect/');
	}

	$postfields = array(
		'client_id' => config('INSTAGRAM_ID'),
		'client_secret' => config('INSTAGRAM_SECRET'),
		'grant_type' => 'authorization_code', 
		'redirect_uri' =>  'http://' .  config('HOST') . '/instagram/callback/',
		'code' => get('code')
	);
	
	$url = 'https://api.instagram.com/oauth/access_token';

	$data = json_decode(get_data($url, $postfields));
	if(is_object($data) && isset($data->access_token)){
		session(1)->instagram = $data;

		$user = new User();
		if($user->exists){
			$iuser = instagram_active_user();
			if(is_object($iuser)){
				$user['instagram'] = $iuser['_id'];
				$user->save();
			}
		}

		redirect('/user');
	}else{
		message('Sorry there was an error connecting to Instagram, Please try again');
		redirect('/');
	}
}

function instagram_active_user(){
	if(!is_null(session()->instagram)){
		$user = new InstagramUser(session()->instagram['user']['id']);
		if(!$user->exists){
			$user->update_from_session();	
		}
		return $user;
	}else{
		return false;
	}
}

function instagram_input_add($wid){
	$page = new Template();
	$page->c('<h3>' . 'Instagram Input'  . '</h3>');
	$page->add_css('css/input.form.css', 'instagram');
	$page->add_js('js/input.form.js', 'instagram');

	$content = new Template(false);
	$content->load_template('templates/input.form.html', 'instagram');

	$form = new Form( array(
        'action' => '/instagram/input/add/submit/',
        'title' => 'Add Wall',
        'class' => 'form-horizontal',
        'id' => 'add_wall',
        'method' => 'POST'
    ));
	$options = array(
			'user' => 'Current User',
			'tags' => 'Tags',
			'location' => 'Location',
			'radius' => 'Radius'
		);

	$form->e(array(
		'type' => 'Hidden',
		'id' => 'wid',
		'default' => $wid
		));
	$form->row();
	$form->e(array(
		'type' => 'Select',
		'id' => 'type',
		'options' => $options,
		'label' => 'Input Type'
		));

	//all contextual rows are hidden until their input type is shown.
	$form->row(array('class' => 'tags'));
	$form->e(array(
		'type' => 'Text',
		'id' => 'tags',
		'label' => 'Tags',
		));

	//all contextual rows are hidden until their input type is shown.
	$form->row(array('class' => 'location contextual'));
	$form->e(array(
		'type' => 'InstagramLocation',
		'id' => 'location',
		'label' => 'Location',
		));

	//radius inputs
	$form->row(array('class' => 'radius contextual'));
	$form->e(array(
		'type' => 'Text',
		'id' => 'lat',
		'label' => 'Latitude',
		));
	$form->row(array('class' => 'radius contextual'));
	$form->e(array(
		'type' => 'Text',
		'id' => 'lon',
		'label' => 'Longitude',
		));
	$form->row(array('class' => 'radius contextual'));
	$form->e(array(
		'type' => 'Text',
		'id' => 'lat',
		'label' => 'Radius',
		'placeholder' => '10'
		));

	$form->row();
	$form->e(array(
		'type' => 'Submit',
		'text' => 'Add Input',
		'class' => 'pull-right'
		));

	

	$vars = array();
	$vars['form'] = $form->render();
	$content->add_variable($vars);
	$page->c($content->render());

	return $page;
}

function instagram_input_add_submit(){
	var_dump(get('type'));
	$type = get('type');
	switch (get('type')){
		default:
			die('default hit');
	}
}
