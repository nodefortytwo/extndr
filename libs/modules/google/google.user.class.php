<?php
class GoogleUser extends MongoBase{
	protected $collection = 'googleUser';

	public function __construct($rec = null){

		if (is_numeric($rec)) {
            //this is an id int
            $this->_id = (string)$rec;
            $this->load_from_id();
        } elseif (is_array($rec)) {

        	if(isset($rec['kind'])){
        		$this->load_from_google_record($rec);
        	}else{
		        //mongo record
		        $this->load_from_record($rec);
        	}
        }


	}

	function load_from_google_record($rec = array()){
		$this->__construct($rec['id']);
		if(!$this->exists){
			$this['user'] = $rec;
			$this->save();
		}
	}
}