<?php

class TwitterList {
    private $cursor = -1, $members = array(), $ratelimit;
    function __construct($url) {
        
        $url = str_replace('https://twitter.com/', '', $url);
        list($this->user, $this->slug) = explode('/', $url, 2);
        $rate = twitter_ratelimit()->check('lists', '/lists/members');

        $this->ratelimit = $rate['remaining'];
        $this->run();
        $this->process();
    }

    function run() {
        $params = array(
            'owner_screen_name' => $this->user,
            'slug' => $this->slug,
            'skip_status' => 0,
            'include_entities' => 0,
            'cursor' => $this->cursor
        );
        if($this->ratelimit > 0){
            $list = twitter()->get('lists/members', $params);

            $this->ratelimit = twitter_ratelimit()->used('lists', '/lists/members');
            $this->members = array_merge($this->members, $list->users);
            if ($list->next_cursor_str != '0') {
                $this->cursor = $list->next_cursor_str - 1;
                $this->run();
            }
        }
    }
    
    function process(){
        message(count($this->members) . ' ready for processing');
        foreach($this->members as $key=>$member){
            $this->members[$key] = new TwitterUser($member);
        }
    }
    
    function get_ids(){
        $ret = array();
        foreach($this->members as $member){
            $ret[] = $member['_id'];
        }
        return $ret;
    }
    
    function __get($var) {
        if (method_exists($this, 'get_' . $var)) {
            return call_user_func_array(array(
                $this,
                'get_' . $var
            ), array());
        }
    }
    

}
