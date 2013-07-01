<?php

function wall_init(){
	require 'wall.class.php';
	require 'input.class.php';
}

function wall_routes(){
	$paths = array();
	$paths['wall/add'] = array('callback' => 'wall_add');
	$paths['wall/inputs'] = array('callback' => 'wall_inputs');
	$paths['wall/input/add'] = array('callback' => 'wall_input_add');
	return $paths;
}

function wall_add(){
	$owner = user_active();
	$wall = new Wall();
	$wall['name'] = get('wall_name');
	$wall['owner'] = $owner['_id'];
	$wall->save();

	redirect('/user/');
}

function wall_add_form(){
	$add_form = new Form( array(
        'action' => '/wall/add/',
        'title' => 'Add Wall',
        'class' => 'form-horizontal',
        'id' => 'add_wall',
        'method' => 'POST'
    ));
    $add_form->row();
    $add_form->e(array(
        'type' => 'Text',
        'id' => 'wall_name',
        'placeholder' => 'Wall Name',
        'class' => 'span8'
    ));
    $add_form->e(array(
        'type' => 'Submit',
        'class' => 'span4',
        'text' => 'Add Wall!',
        'style' => 'btn-primary'
    ));
    return '<div class="row-fluid"><div class="span6">' . $add_form->render() . '</div></div>';
}

function wall_inputs($wid){
	$wall = new Wall($wid);	
	$page = new Template();
	$page->c('<h1>' . $wall['name'] . '</h1>');

	if(count($wall['inputs']) > 0){

	}else{
		$page->c('<p>This wall has no input streams, '.l('add one', '/wall/input/add/~/' . $wid) . ' to get started!<p>');
	}

	return $page->render();
}

function wall_input_add($wid){
	$page = new Template();
	$content = new Template(false);
	$content->load_template('templates/input.sources.html', 'wall');
	$vars = array();
	$vars['wid'] = $wid;
	$content->add_variable($vars);
	$page->c($content->render());
	return $page->render();
}