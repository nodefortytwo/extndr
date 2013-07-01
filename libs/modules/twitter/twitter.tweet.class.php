<?php

class Tweet extends MongoBase {
    protected $collection = 'tweet';

    function load_from_object($object) {
        if (!isset($object->id_str)) {
            var_dump($object);
            die();
            throw new exception('invalid twitter object');
        }
        

        $this->_id = (string)$object->id_str;
        //create a user for this tweet if we don't already have one;
        $user = new TwitterUser($object->user);
        //if its a retweet add that tweet to the db;
        if(isset($object->retweeted_status)){
            $rt = new Tweet($object->retweeted_status);
        }
        //load if this is a replacement;
        $this->load_from_id();
        //convert the object into a ass array
        $this['tweet'] = object_to_array($object);
        //save it for laterz;
        $this->save();

    }

    function load_from_twitter() {
        die('no');
        $rates = twitter_ratelimit();

        $rate = $rates->check('statuses', '/statuses/show/:id');
        if ($rate['remaining'] > 0) {
            $params = array('id' => $this['_id']);
            $tweet = twitter()->get('statuses/show', $params);
            $this->load_from_object($tweet);
            $rates->used('statuses', '/statuses/show/:id');
        }
    }

    function load_postprocess(){
        $this['tweet.text'] = preg_replace('/[^a-zA-Z0-9\s\-=+\|!@#$%^&*()`~\[\]{};:\'",<.>\/?]/', '', $this['tweet.text']);
    }

    function save_preprocess() {
        if (!isset($this['_id'])) {
            $this['_id'] = $this->_id;
        }
        //if this is the first save we don't mess with it.
        if (is_object($this['tweet'])) {
            return;
        }

        //make sure that all the dates are mongoDates
        if (isset($this['tweet']['created_at']) && !is_object($this['tweet']['created_at'])) {
            $this['tweet.created_at'] = new MongoDate(strtotime($this['tweet']['created_at']));
        }

        //$this->get_opencalais(false);
        //$this->get_links(false);
    }

    function get_author() {
        $this->author = new TwitterUser($this['tweet']['user']['id_str']);
        return $this->author;
    }

    function get_opencalais($save = true) {
        if ($this['opencalais'] === null) {
            $this['opencalais'] = get_oc($this['tweet']['text']);
            if ($save) {
                $this->save();
            }
        }
        return $this['opencalais'];
    }

    function get_links($save = true) {
        if (!$this['links']) {
            if (isset($this['tweet']['entities']) && isset($this['tweet']['entities']['urls'])) {
                $this['links'] = $this['tweet']['entities']['urls'];
                foreach ($this['links'] as $key => $link) {
                    $this['links.' . $key] = expand_url($link['expanded_url']);
                }
                if ($save) {
                    $this->save();
                }
            }

        }
        return $this['links'];
    }

    function update_retweet_count() {
        $data = json_decode(get_data('https://twitter.com/i/expanded/batch/' . $this->_id . '?facepile_max=0&include%5B%5D=social_proof'));
        echo($this['_id'] . "\n");
        if(isset($data->social_proof)){
            $this['tweet.retweet_count'] = (int) between($data->social_proof, 'Retweeted', 'time');
            $this['tweet.favorite_count']  = (int) between($data->social_proof, 'Favorited', 'time');
            echo($this['tweet.retweet_count']. ' ' . $this['tweet.favorite_count']."\n");
            $this['retweet_update'] = new MongoDate(time());
            $this->save();
        } 
        if(isset($data->message)){
            echo($data->message . "\n");
            if($data->message == 'Tweet does not exist.'){
                $this->delete();
            }else{
                $this['retweet_update_message'] = $data->message;
                $this['retweet_update'] = new MongoDate(time());
                $this->save();    
            }
        }  
    }

    function __toString(){
        return $this['tweet.text'] . ' - ' . $this['tweet.user.screen_name'];
    }

}

class TweetCollection extends Collection {
    protected $collection = 'tweet', $class_name = 'Tweet';
    protected $default_cols = array(
        'ID' => '_id',
        'Author' => 'tweet.user.screen_name',
        '' => 'tweet.text',
        'Retweets' => 'tweet.retweet_count',
        'Favorites' => 'tweet.favorite_count',
        'retweet_update' => 'retweet_update',
        'Created' => 'tweet.created_at'
    );
}


