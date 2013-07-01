<?php
class User extends MongoBase{
	protected $collection = 'user', $id_obj = true;

	public function __construct($rec = null){
		parent::__construct($rec);
		
		if(is_null($this['_id'])){
			if(session()->user_id !== null){
				parent::__construct(session()->user_id);
				if($this->exists){
					return;
				}
			}


			$auth_method = config('PRIMARY_AUTH', 'twitter');
			//nothing loaded lets grab the active user from our choosen auth method and create a new user
			$func = $auth_method . '_active_user';
			if(function_exists($func)){
				$user = call_user_func_array($func, array());
			}

			//search for a user
			$indb = mdb()->{$this->collection}->findOne(array($auth_method => $user['_id']));
			if(is_null($indb)){
			$this[$auth_method] = $user['_id'];
			$this->save();
			session(1)->user_id = $this['_id'];
			}else{
				var_dump($indb);
				die();
			}
		}

	}

	public function get_walls(){
		return new WallCollection(array());
	}

	public function get_twitter(){
		if(isset($this['twitter'])){
			$this->twitter = new twitterUser($this['twitter']);
			return $this->twitter;
		}else{
			return null;
		}
	}

	public function render_profile_widget(){
		$auth_method = config('PRIMARY_AUTH', 'twitter');
		$user_obj = $this->$auth_method;
		return $user_obj->render('profile_widget');

	}

	public function render_connected_accounts(){
		$candidates = array('twitter', 'google', 'facebook', 'instagram');
		$html = '';
		foreach($candidates as $candidate){
			if(isset($this[$candidate])){
				$html .= $candidate . ' Connected <br/>';
			}else{
				$html .= l($candidate . ' Not Connected', '/user/connect/~/' . $candidate) .' <br/>';
			}
		}
		return $html;
	}

}
