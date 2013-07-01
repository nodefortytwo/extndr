<?php

function file_init(){
	require 'file.class.php';
}

function file_routes(){
	$routes = array();

	$routes['file/test'] = array('callback' => 'file_test');

	return $routes;
}

function file_test(){
	$file = new File('51b8d46a635e6f7f60000001');
	var_dump($file);
	die();
	//$file->create('bob');

	return 'bob';
}