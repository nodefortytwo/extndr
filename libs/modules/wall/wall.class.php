<?php
//collects all the inputs and displays the wall

class Wall extends MongoBase{
	protected $collection = 'wall', $obj_id = true;


}

class WallCollection extends Collection{
	protected $collection = 'wall', $class_name = 'Wall';
	protected $default_cols = array('Name' => 'name');
	protected $actions = array(
		'Preview Feed' => 'wall/view/~/{id}',
		'Update Inputs' => 'wall/inputs/~/{id}',
		'Delete' => 'wall/delete/~/{id}'
		);

}