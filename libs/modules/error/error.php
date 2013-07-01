<?php

function error_init(){
	require "error.class.php";

	set_error_handler('error_error');
	set_exception_handler('error_exception');
}

function error_routes(){
	$routes = array();

	$routes['error/logs'] = array('callback' => 'error_logs');

	return $routes;
}

function error_exception($e){

	$error = new Error();
	$eid = (string) $error->toss($e);
	//take them to the error 500 page.

	echo pages_500($eid);
	die();

	redirect('/500?eid=' . $eid);
}

function error_error($errno, $errstr, $errfile, $errline){
	//convert errors into exceptions, we run a clean ship and want every error to crash
	throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
	return true;
}

function error_logs(){
	$errors = new ErrorCollection(array(), null,  array('time' => -1));
	$page = new template();
	$page->c($errors->render());
	return $page->render();
}