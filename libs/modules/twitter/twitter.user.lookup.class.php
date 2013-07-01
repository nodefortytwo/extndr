<?php
class TwitterUserLookup {
    private $page = 0, $results = array(), $ratelimit, $user_ids = array(), $screen_names = array(), $found = array();
    public $stats = array();

    function __construct($users = array()) {
        $this->users = $users;
        $this->ratelimit = twitter_ratelimit()->check('users', '/users/lookup');
        $this->validate();
        $this->run();
        $this->process();
    }

    function validate() {
        $this->stats['found'] = 0;
        foreach ($this->users as $key => $screen_name) {
            $user = new TwitterUser($screen_name);
            if ($user->exists) {
                unset($this->users[$key]);
                $this->found[] = $user;
                $this->stats['found']++;
            } else {
                //doesn't exist so add it to our params
                if (is_numeric($screen_name)) {
                    $this->user_ids[] = $screen_name;
                } else {
                    $this->screen_names[] = $screen_name;
                }
            }
        }
    }

    function run() {
        if (count($this->users) > 100) {
            throw new exception('too many users to look-up');
        }
        if(empty($this->users)){
            return;
        }

        $screen_names = str_replace('@', '', implode(',', $this->screen_names));
        $user_ids = implode(',', $this->user_ids);
      
        $params = array(
            'screen_name' => $screen_names,
            'user_id' => $user_ids,
            'include_entities' => false
        );

        if ($this->ratelimit['remaining'] > 0) {
            $results = twitter()->get('users/lookup', $params);
            if(is_array($results)){
                $this->results = array_merge($this->results, $results);
            }else{
                print_r($results);
            }
        }

    }

    function process() {
        foreach ($this->results as $key => $res) {
            $this->results[$key] = new TwitterUser($res);
        }
        //merge our found records into the results set.
        foreach($this->found as $user){
            $this->results[] = $user;
        }
        $this->cnt = count($this->results);
        //we don't need found anymore;
        $this->found = array();
    }

    function ids() {
        $ids = array();
        foreach ($this->results as $res) {
            $ids[] = $res->_id;
        }
        return $ids;
    }

    function to_collection() {
        $collection = new TwitterUserCollection(null);
        $collection->from_objects($this->results);
        return $collection;
    }

    function fine_one(){
        $first = array_shift($this->results);
        return new TwitterUser($first);
    }
}
