<?php
class TwitterRatelimit extends MongoBase {
    protected $collection = 'ratelimit';
    private $oldest, $checked = false;

    function get_oldest() {
        $ar = $this['limits'];
        array_walk_recursive($ar, array(
            $this,
            'walk_limits'
        ));
        return $this->oldest;
    }

    function walk_limits($value, $key) {
        if ($key == 'reset' && (!isset($this->oldest) || $value < $this->oldest)) {
            $this->oldest = $value;
        }
    }

    function refresh() {
        if(!isset($this['last_checked'])){
            $this['last_checked'] = new MongoDate(0);
        }
        $since_last_check = time() - $this['last_checked']->sec;
        
        if($since_last_check > 60 * 15){
            $this->checked = false;
        }
        
        if(!$this->checked){
            $rl = twitter()->get('application/rate_limit_status');
            if(isset($rl->resources)){
                $this['limits'] = $rl->resources;
                $this->save();
                $this->load_from_id();
                $this->checked = true;
            }else{
                var_dump($rl);
                die();
            }
            $this['last_checked'] = new MongoDate(time());
            $this->save();
        }
    }

    function used($collection, $endpoint, $times = 1) {
        if (isset($this['limits'][$collection]) && isset($this['limits'][$collection][$endpoint])) {
            //$l = $this['limits'];
            //until php 5.4 we can't modify overloaded elements via ArrayAccess
            $this->data['limits'][$collection][$endpoint]['remaining'] = $this['limits'][$collection][$endpoint]['remaining'] - $times;
            //$this['limits'] = $l;
            $this->save();
            return $this['limits'][$collection][$endpoint]['remaining'];
        } else {
            return 0;
        }
    }

    function check($collection, $endpoint, $mode = 'php') {
        if (isset($this['limits'][$collection]) && isset($this['limits'][$collection][$endpoint])) {
            $rt = $this['limits'][$collection][$endpoint];
        } else {
            $rt = array(
                'limit' => 0,
                'remaining' => 0,
                'reset' => 0
            );
        }
        
        $rt['title'] = 'Endpoint: ' . $endpoint;
        //$rt['html'] = $this->render($rt);
        if($rt['remaining'] == 0){
            message($rt['title'] . ' Exhuasted');
        }
        elog('Ratelimit ' . $rt['title'] . ' checked, ' . $rt['remaining'] . ' Left, Resets on ' . template_date($rt['reset']), 'notice', 'twitter.ratelimit');
        switch ($mode) {
            case 'json' :
                return json_encode($rt);
                break;
            default :
                return $rt;
        }
    }
    

}

function twitter_ratelimit() {
    global $rates;
    //just make sure we have an access token;
    $conn = twitter();
    if(!isset($rates)){
        $at = $conn->token->key;
        $rates = new TwitterRatelimit($at);
    }
    if (!isset($rates['limits']) || time() > $rates->oldest) {
        //either one of the endpoint needs to be refreshed or we don't have ratelimits for this access token
        $rates->refresh();
    }else{
         
    }
    
    
    
    return $rates;
}

function twitter_get_ratelimit($section = null, $path = null, $format = 'php') {
    $path = str_replace('-', '/', $path);
    $rl = twitter_ratelimit();
    if ($section && $path) {
        $rl = $rl->check($section, $path, $format);
        return $rl;
    } else {
        return json_encode($rl['limits']);
    }
}

function twitter_ratelimit_table(){
    $page = new template();
    $rates = twitter_ratelimit();
    $rows = array();
    $dates = array();
    foreach($rates->data['limits'] as $section => $limits){
        foreach($limits as $limit=>$values){
            $rows[] = array(
                $section,
                $limit,
                $values['limit'],
                $values['remaining'],
                round($values['remaining'] / $values['limit'] * 100, 2),
                template_date($values['reset'])
            );
            $dates[] = $values['reset'];
            $pers[] =round($values['remaining'] / $values['limit'] * 100, 2);
        }
    }
    
    array_multisort($pers, SORT_ASC, $dates, SORT_ASC, $rows);
    
    $headers = array('section', 'path', 'limit', 'remaining', '% remaining', 'resets');
    $table = template_table($headers, $rows, 'table-sortable');
    $page->c($table);
    return $page->render();
}
