<?php
class TwitterQuery extends MongoBase {
    protected $collection = 'query';

    function __construct($id = null) {
        if ($id) {
            $this->_id = new MongoId($id);
            $this->load_from_id(false);
        }
    }
    

    function get_tweet_count($refresh = false) {
        if (!isset($this['tweet_count']) || $this['tweet_count'] == 0 || $refresh) {
            if (!isset($this['tweets'])) {
                $this['tweet_count'] = 0;
            } else {
                $this['tweet_count'] = count($this['tweets']);
                $this->save();
            }
        }
        return $this['tweet_count'];
    }

    function run() {
        $this->refresh_stats();
         $this['backfill'] = true;
        $this->backfill_tweets();
        $this['last_run'] = new MongoDate(time());
        $this->save();
        redirect('/twitter/queries');
        die();
        $params = array(
            'q' => $this['query'],
            'count' => 100
        );
        $rl = twitter_ratelimit();
        $remaining = $rl->check('search', '/search/tweets');
        $remaining = $remaining['remaining'];
        $this['backfill'] = true;
        while($remaining > 1){
            //we always backfill first as its time sensitive
            if($this['backfill']){
                if(isset($response->search_metadata) && isset($response->search_metadata->max_id_str)){
                    $params['max_id'] = $response->search_metadata->max_id_str;
                }elseif($this['oldest'] != "0"){
                    $params['max_id'] = $this['oldest'];
                }   
            }else{
                if(isset($response->search_metadata) && isset($response->search_metadata->since_id_str)){
                    $params['since_id'] = $response->search_metadata->since_id_str;
                }elseif($this['newest'] != "0"){
                    $params['since_id'] = $this['newest'];
                }
               
            }
            elog('Running search/tweets', 'notice', 'twitter.query.php', $params, $remaining);
            
            $response = twitter()->get('search/tweets', $params);
            $remaining = $rl->used('search', '/search/tweets');
            foreach($response->statuses as $t){
                $t = new Tweet($t);
                $this['tweets.' . $t->_id] = $t->_id;
            }
            $this['last_run'] = new MongoDate(time());
            $this->save();
            elog('finished', 'notice', 'twitter.query.php', $response->search_metadata->max_id_str);
            if(count($response->statuses) < 10 || $response->search_metadata->max_id_str = '0'){
                $this['backfill'] = false;
                $this->save();
                break;
            } 
        }   
       
    }

    function backfill_tweets(){

        if(!$this['backfill']){
            return;
        }
        
        $params = array(
            'q' => $this['query'],
            'count' => 100
        );
        if((int) $this['oldest'] > 0){
            $params['max_id'] = $this['oldest'];
        }
        
        $remaining = twitter_ratelimit()->check('search', '/search/tweets');
        $remaining = $remaining['remaining'];
        $continue = true;
        while($remaining > 1 && $continue){
            
            $response = twitter()->get('search/tweets', $params);
            $remaining = twitter_ratelimit()->used('search', '/search/tweets');
            
            if(!isset($response->statuses)){
                var_dump($response);
                die();
            }
            
            foreach($response->statuses as $t){
                $t = new Tweet($t);
                $this['tweets.' . $t->_id] = $t->_id;
            }
            $this->save();
            if(isset($response->search_metadata->max_id_str) && $response->search_metadata->max_id_str != $params['max_id']){
                $params['max_id'] = $response->search_metadata->max_id_str;
            }else{
                $this['backfill'] = false;
                break;
            }
            if(count($response->statuses) < 1){
                $this['backfill'] = false;
                $continue = false;
                break;
            }
            
        }  
        $this->refresh_stats();
        $this->save();
    }


    function refresh_stats(){
        $ts = array();
        if(!is_array($this['tweets'])){$this['tweets'] = array();}
        foreach($this['tweets'] as $t){
            $ts[] = (int) $t;
        }
        rsort($ts);
        $this['oldest'] = (string) array_pop($ts);
        $this['newest'] = (string) array_shift($ts);
        $this->tweet_count;
    }
    
    function get_tweets(){
        $this->tweets = array();
        foreach($this['tweets'] as $tweet){
            $this->tweets[$tweet] = new Tweet($tweet);
        }
        return $this->tweets;
    }

}


function twitter_query_add() {

    if (isset($_POST['query'])) {
        $query_str = $_POST['query'];
    }
    $query = new TwitterQuery();
    $query['query'] = $query_str;
    $query['last_run'] = new MongoDate(0);
    $query['oldest'] = 0;
    $query['newest'] = 0;
    $query['backfill'] = true;
    $query->save();
    redirect('/twitter/');
}

function twitter_get_queries($query = null) {
    if ($query) {
        $result =  mdb()->query->find($query);
    } else {
        $result =  mdb()->query->find();
    }
    $queries = array();
    foreach ($result as $query) {
        $queries[] = new TwitterQuery($query['_id']);
    }
    return $queries;
}

function twitter_queries() {

    $page = new Template();
    $form = '<form method="post" action="' . get_url('/twitter/query/add') . '">';
    $form .= '<p>See Search Operators in the Twitter <a href="https://dev.twitter.com/docs/using-search">Search API</a></p>';
    $form .= '<label>Query</label><input type="text" name="query" id="query" value="add query"/>';
    $form .= '<input type="submit"/>';
    $form .= '</form>';
    $page->add_js('js/twitter.queries.js', 'twitter');
    $page->c($form);
    $page->c('<div id="info"></div>');
    $queries = twitter_get_queries();
    $rows = array();
    $headers = array(
        'ID',
        'Query',
        'Last Run',
        'Tweet Count',
        ''
    );
    foreach ($queries as $q) {
        $row = array();
        $row['id'] = (string)$q['_id'];
        $row['query'] = l($q['query'], get_url('/twitter/query/~/' . $row['id']));
        $row['last_run'] = template_date($q['last_run']);
        $row['tweet_count'] = $q->tweet_count;
        $row['actions'] = array();
        $row['actions'][] = l('Run Now!', get_url('/twitter/query/run/~/' . $row['id']), 'btn');
        $row['actions'][] = l('Download Data', get_url('/twitter/query/download/~/' . $row['id']), 'btn');
        $row['actions'][] = l('Delete', get_url('/twitter/query/delete/~/' . $row['id']), 'btn btn-danger');
        $row['actions'] = '<div class="btn-group">' . implode('', $row['actions']) . '</div>';
        $rows[] = $row;
    }
    $page->c(template_table($headers, $rows));
    return $page->render();
}

function twitter_query($qid){
    $query = new TwitterQuery($qid);
    $rows = array();
    $headers = array(
        'ID',
        'Author',
        'Text',
        'Created at',
        'RTs',
        'URL'
    );
    
    foreach($query->tweets as $tweet){
        $row = array(
                $tweet->_id,
                l($tweet['tweet']['user']['screen_name'], '/twitter/user/~/' . $tweet->author->_id),
                $tweet['tweet']['text'],
                template_date($tweet['tweet']['created_at']),
                $tweet['tweet']['retweet_count'],
                'https://twitter.com/' . $tweet['tweet']['user']['screen_name'] . '/status/' . $tweet->_id
            );
        $rows[] = $row;
    }
    $page = new Template();
    $page->c(template_table($headers, $rows));
    return $page->render();
}

function twitter_query_run($qid) {
    $query = new TwitterQuery($qid);   
    $query->run();
    
}

function twitter_query_delete($qid){
    $query = new TwitterQuery($qid);
    $query->delete();
    redirect(get_url('/twitter/'));
}
