<?php

class File extends MongoBase{
	protected $handler, $collection = 'file', $obj_id = true;

	function __construct($rec = null){
       	parent::__construct($rec);
		$handler = 'FileSystem' . config('FS_HANDLER', 'Local');
		$this->handler = new $handler;
		
	}

	function create($data, $filename = null, $ext = null){
		if(is_null($filename) && !isset($this['filename'])){
			if(!isset($this['_id'])){
				$this['hash'] = (md5($data));
				$this->save();
			}
			$filename = $this['_id'];
		}elseif(isset($this['filename'])){
			$filename = $this['filename'];
		}

		if(!is_null($ext)){
			$filename .= '.' . $ext;
		}

		$saved = $this->handler->create($data, $filename);	
		if($saved){
			$this['filename'] = $filename;
			$this['created'] = new MongoDate();
			$this->save();
		}else{
			//save failed remove from db and throw an error
			$this->delete();
			throw new exception('failed to save ' , $filename);
		}
	}

	function get_contents(){
		return $this->handler->get_contents($this['filename']);
	}
}

interface FileSystem{
	public function create($data, $filename);
	public function save();
	public function delete();
	public function replace();
	public function move();
	public function copy();
	public function parse_upload();
	public function list_dir();
	public function exists();
	public function size();

}

class FileSystemLocal implements FileSystem{

	function __construct(){
		$this->public_dir = config('UPLOAD_PATH', 'public');
	}

	public function create($data, $filename){
		if($this->exists($filename)){
			throw new exception ($filename . ' already exists, use replace');
		}

		return file_put_contents($this->public_dir . '/' . $filename, $data) !== false;

	}
	public function save(){

	}
	public function delete(){

	}
	public function replace(){

	}
	public function move(){

	}
	public function copy(){

	}
	public function parse_upload(){

	}
	public function list_dir(){

	}
	public function get_contents($filename){
		return file_get_contents($this->public_dir . '/' . $filename);
	}
	public function exists($filename = null){
		if(!is_null($filename)){
			return file_exists($this->public_dir . '/' . $filename);
		}else{
			return false;
		}

	}
	public function size(){

	}
}