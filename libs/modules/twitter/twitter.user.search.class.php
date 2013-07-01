<?php
class TwitterUserSearch{
    private $page = 0, $results = array(), $ratelimit, $limit = 20;
    
    function __construct($keywords){
        $this->keywords = $keywords;
        $this->ratelimit = twitter_ratelimit()->check('users', '/users/search');
        $this->run();
        $this->process();
    }
    
    function run(){
        if($this->limit > 20){
            $count = 20;
        }else{
            $count = $this->limit;
        }
        
        $params = array(
            'page' => $this->page,
            'q' => $this->keywords,
            'count' => $count,
            'include_entities' => false
        );
        
        if($this->ratelimit['remaining'] > 0){
            $results = twitter()->get('users/search', $params);
            $this->results = array_merge($this->results, $results);
            
            if (count($this->results) < $this->limit){
                $this->page++;
                $this->run();
            }
        }
        
    }
    
    function process(){
        foreach($this->results as $key=>$res){
            $this->results[$key] = new TwitterUser($res);
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
        $collection = new TwitterUserCollection(null);
        $collection->from_objects($this->results);
        return $collection;
    }
    
}
