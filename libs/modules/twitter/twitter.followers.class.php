<?php
class TwitterFollowers{
	public $complete = false, $ids = array(), $cursor = -1;
	protected $ratelimit, $id;

	function __construct($id, $cursor = null, $last_update = null){

		$this->id = $id;
		$this->ratelimit = twitter_get_ratelimit('followers', '/followers/ids');
		if($cursor){
			$this->cursor = $cursor;
		}
		if($last_update){
			$this->last_update = $last_update;
		}else{
			$this->last_update = new MongoDate(0);
		}

		if($this->id){
			$this->get_followers();
		}
			
	}

	private function get_followers(){
		//check we have requests remaining
		if($this->ratelimit['remaining'] <= 0){
			return $this->ids;
		}

		$params = array(
			'id' => $this->id,
			'cursor' => $this->cursor,
			'stringify_ids' => true,
			'count' => 5000
			);

		$response = twitter()->get('followers/ids', $params);
		if(!is_object($response) || !isset($response->ids)){
			return $this->ids;
		}
		$this->ratelimit['remaining'] = twitter_ratelimit()->used('followers', '/followers/ids');
		$this->last_update = new MongoDate(time());

		$this->ids = array_merge($this->ids, $response->ids);

		if(isset($response->next_cursor_str) && $response->next_cursor_str != "0"){
			$this->cursor = $response->next_cursor_str;
			$this->complete = false;
			$this->get_followers();
		}else{
			$this->cursor = '-1';
			$this->complete = true;
			return $this->ids;
		}
	}

	public function to_array(){
		$array = array();
		$array['complete'] = $this->complete;
		$array['next_cursor'] = $this->cursor;
		$array['last_update'] = $this->last_update;
		$array['cnt'] = count($this->ids);
		$array['ids'] = $this->ids;
		return $array;
	}
}