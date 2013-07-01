<?php
class TwitterTweetSearch{
    private $page = 0, $results = array(), $ratelimit, $limit = 100, $max_id = -1;
    
    function __construct($keywords){
        $this->keywords = $keywords;
        $this->ratelimit = twitter_ratelimit()->check('search', '/search/tweets');
        $this->run();
        $this->process();
    }

    function __get($var) {
        if (method_exists($this, 'get_' . $var)) {
            return call_user_func_array(array(
                $this,
                'get_' . $var
            ), array());
        }
    }
    
    function run(){

        $params = array(
            'q' => $this->keywords,
            'count' => $this->limit,
            'include_entities' => true,
            'results_type' => 'recent',
            'max_id' => $this->max_id
        );
        
        if($this->ratelimit['remaining'] > 0){
            $results = twitter()->get('search/tweets', $params);
            $this->ratelimit['remaining'] = twitter_ratelimit()->used('search', '/search/tweets');


            $this->results = array_merge($this->results, $results->statuses);
            if(isset($this->search_metadata->max_id_str) && $this->search_metadata->max_id > 0){
                $this->max_id = $this->search_metadata->max_id_str;
                $this->run();
            }

        }
        
    }
    
    function process(){
        foreach($this->results as $key=>$res){
            $this->results[$key] = new Tweet($res);
            //make sure all users from the tweets are added to our db (Free Users!)
            $user = new TwitterUser($res->user);
        }
    }
    
    function ids(){
        $ids = array();
        foreach($this->results as $res){
            $ids[] = $res->_id;
        }
        return $ids;
    }
    
    function to_collection(){
        $collection = new TweetCollection(null);
        $collection->from_objects($this->results);
        return $collection;
    }

    function get_users(){
        $users = array();
        foreach($this->results as $tweet){
            $users[] = $tweet['tweet.user.id_str'];
        }
        $search = array('_id' => array('$in' => array_unique($users)));
        $this->users = new TwitterUserCollection($search);
        return $this->users;
    }

    function get_matching_tweet($uid){
        //there needs to be a better way of doing this
        foreach($this->results as $result){
            if($result['tweet.user.id_str'] == $uid){
                return $result;
            }
        }
    }
    
}
