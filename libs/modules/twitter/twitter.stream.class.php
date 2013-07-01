<?php
define('STREAM_STOPPING', 'Stopping');
define('STREAM_STOPPED', 'Stopped');
define('STREAM_STARTED', 'Started');
define('STREAM_STARTING', 'Starting');
define('STREAM_RUNNING', 'Running');

class TwitterStream extends MongoBase {
    protected $collection = 'twitterStream';
    private $stream;

    function __construct($id = null, $streamless = false) {

        if ($id) {

            $this->_id = $id;
            $this->load_from_id(false);
            if(!$this->exists){
                return;
            }

            //stops recursion
            if (!$streamless) {
                $this->validate_predicates();
                $this->stream = new PhireStream($this['token'], $this['secret'], Phirehose::METHOD_FILTER);
                $this->stream->consumerKey = config('TWITTER_CONSUMER_KEY');
                $this->stream->consumerSecret = config('TWITTER_CONSUMER_SECRET');
                $this->stream->set_stream($id);
                
                //Set the various tracks for the stream, most of the time 2/3 will be empty arrays.
                $this->stream->setFollow($this['follow']);
                $this->stream->setLocations($this['location']);
                $this->stream->setTrack($this['track']);
            }

            //move stream to stopped if the process dies
            if ($this['pid'] && $this->status != STREAM_STOPPED && !isRunning($this['pid'])) {
                $this->set_status(STREAM_STOPPED);
            }
        }
    }

    function load_postprocess(){
        if(!isRunning($this['pid'])){
            $this->set_status('STOPPED');
        }
    }

    function validate_predicates() {
        if (!is_array($this['track'])) {
            $this['track'] = array();
        }
        if (!is_array($this['location'])) {
            $this['location'] = array();
        }
        if (!is_array($this['follow'])) {
            $this['follow'] = array();
        }

        foreach ($this['track'] as $key => $track) {
            if (is_null($track) || empty($track)) {
                unset($this->data['track'][$key]);
            }
        }

        foreach ($this['location'] as $key => $location) {
            //TODO: Validate locations
        }

        foreach ($this['follow'] as $key => $follow) {
            if (!is_numeric($follow)) {
                unset($this->data['follow'][$key]);
            }
        }
        $this->save();
    }

    function consume() {
        echo 'Stream: ' . $this->id . ' Started' . "\n";
        $this->set_status('Started');
        flush();
        $this->stream->consume();
    }

    function save_preprocess() {
        if (!isset($this['heartbeat'])) {
            $this['heartbeat'] = new MongoDate(0);
        }

    }

    function get_status($refresh = false) {
        if ($refresh) {
            $this->load_from_id();
        }
        return $this['status'];
    }

    function set_status($status) {
        //$this->load_from_id();
        if ($status != $this['status']) {
            switch($this['status']) {
                case STREAM_STOPPING :
                    if ($status == STREAM_STOPPED) {
                        $this['status'] = $status;
                        $this->save();
                    }
                    break;
                default :
                    $this['status'] = $status;
                    $this->save();
                    break;
            }

        }
    }

    function get_unprocessed_events_count($force = false) {
        //we can grap the count from the collection if its loaded.

        if(isset($this['unprocessed_events']) && !$force){
            return $this['unprocessed_events'];
        }

        if (isset($this->unprocessed_events)) {
            $this->unprocessed_events_count = $this->unprocessed_events->cnt;
        } else {
            $search = array(
                'stream_id' => (string)$this->_id,
                'processed' => array('$ne' => true)
            );
            $this->unprocessed_events_count =    mdb()->streamEvents->count($search);

        }

        $this['unprocessed_events'] = $this->unprocessed_events_count;
        $this->save();

        return $this->unprocessed_events_count;
    }

    function get_unprocessed_events() {

        $search = array(
            'stream_id' => (string)$this->_id,
            'processed' => array('$ne' => true)
        );
        $this->unprocessed_events = new StreamEventCollection($search);
        return $this->unprocessed_events;
    }

    function get_processed_events_count($force = false) {
        if(isset($this['processed_events']) && !$force){
            return $this['processed_events'];
        }
        //we can grap the count from the collection if its loaded.
        if (isset($this->processed_events)) {
            $this->processed_events_count = $this->processed_events->cnt;
        } else {
            $search = array(
                'stream_id' => (string)$this->_id,
                'processed' => true
            );
            $this->processed_events_count =    mdb()->streamEvents->count($search);
        }

        $this['processed_events'] = $this->processed_events_count;
        $this->save();

        return $this->processed_events_count;
    }

    function get_processed_events() {

        $search = array(
            'stream_id' => (string)$this->_id,
            'processed' => true
        );
        $this->processed_events = new StreamEventCollection($search);
        return $this->processed_events;
    }

    function process_events() {
        foreach ($this->unprocessed_events as $event) {
            $obj = json_decode($event['data']);
            if (is_object($obj)) {
                if(isset($obj->delete)){
                    //OMG a Delete event, how exciting.
                    if(isset($obj->delete->status)){
                        $tweet = new Tweet($obj->delete->status->id_str);
                        $tweet->delete();
                        
                        $event['processed'] = true;
                        $event->save();
                        $this['processed_events'] = $this['processed_events'] + 1;
                        $this['unprocessed_events'] = $this['unprocessed_events'] - 1;
                        $this->save();

                    }else{
                        //perhaps we will get user delete events?!? no idea
                        var_dump($obj);
                        die();

                    }
                }else{
                    if(isset($obj->id_str)){
                        $tweet = new Tweet($obj); 
                        $event['processed'] = true;
                        $event->save();
                        $this['processed_events'] = $this['processed_events'] + 1;
                        $this['unprocessed_events'] = $this['unprocessed_events'] - 1;
                        $this->save();
                    }
                }
            }
        }
    }

    public function heartbeat() {
        echo 'Stream: ' . $this->id . ' Heartbeat' . "\n";
        $this['heartbeat'] = new MongoDate(time());
        $this->save();
    }

    public function refresh() {
        if ($this->status == STREAM_STOPPING) {
            return;
        }

        $this->load_from_id();
    }

}

class PhireStream extends OauthPhirehose {
    public $stream, $error = false;
    public function set_stream($sid) {
        $this->id = $sid;
        $this->stream = new TwitterStream($sid, true);
    }

    public function enqueueStatus($status) {
        $rec = array(
            'stream_id' => $this->id,
            'data' => $status,
            'time' => new MongoDate(),
            'processed' => false
        );
        mdb()->streamEvents->insert($rec);

        $this->stream['unprocessed_events'] = $this->stream['unprocessed_events'] + 1;
        $this->stream->save();

        echo 'Stream: ' . $this->id . ' - Tweet added' . "\n";
        $status = $this->stream->status;
        if ($status == STREAM_STOPPING) {
            $this->dc();
        }
    }

    public function heartbeat() {
        $this->stream->heartbeat();
        $status = $this->stream->status;
        if ($status == STREAM_STOPPING) {
            $this->dc();
        }
        $this->stream->set_status(STREAM_RUNNING);
    }

    function dc() {
        echo 'Stream: ' . $this->id . ' - Stopping' . "\n";
        $this->disconnect();
        $this->stream->set_status(STREAM_STOPPED);
        die('dced');
    }

    protected function checkFilterPredicates() {
        $this->stream->refresh();
        $this->stream->validate_predicates();
        //follow
        $dif = array_diff($this->stream['follow'], $this->getFollow());
        //$this->log(print_r($dif, true));
        if (!empty($dif)) {
            $this->log('Updating Follow Predicate');
            $this->setFollow($this->stream['follow']);
        }

        //locations
        if (is_array($this->getLocations())) {
            $dif = array_diff($this->stream['location'], $this->getLocations());
            if (!empty($dif)) {
                $this->log('Updating Locations Predicate');
                $this->setLocations($this->stream['location']);
            }
        }

        //track
        $dif = array_diff($this->stream['track'], $this->getTrack());
        if (!empty($dif)) {
            $this->log('Updating Track Predicate');
            $this->setTrack($this->stream['track']);
        }

    }

    protected function statusUpdate() {
        $this->log('Consume rate: ' . $this->statusRate . ' status/sec (' . $this->statusCount . ' total), avg ' . 'enqueueStatus(): ' . $this->enqueueTimeMS . 'ms, avg checkFilterPredicates(): ' . $this->filterCheckTimeMS . 'ms (' . $this->filterCheckCount . ' total) over ' . $this->avgElapsed . ' seconds, max stream idle period: ' . $this->maxIdlePeriod . ' seconds.');

        $this->stream['status_rate'] = $this->statusRate;
        $this->stream['status_count'] = $this->statusCount;
        $this->stream->save();

        // Reset
        $this->statusCount = $this->filterCheckCount = $this->enqueueSpent = 0;
        $this->filterCheckSpent = $this->idlePeriod = $this->maxIdlePeriod = 0;

    }

    function log($message, $level = 'notice') {
        if ($this->lastErrorNo && !$this->error) {
            $this->error = true;
            echo 'Stream: ' . $this->id . ' - Error:' . $this->lastErrorNo . "\n";
            $this->dc();
        }
        echo 'Stream: ' . $this->id . ' - ' . $message . "\n";
    }

}

class StreamEvent extends MongoBase {
    protected $collection = 'streamEvents';
}

class StreamEventCollection extends Collection {
    protected $collection = 'streamEvents', $class_name = 'StreamEvent';
}

function twitter_stream_add() {
    $stream = new TwitterStream();
    $stream['follow'] = array();
    $stream['token'] =   session()->oauth_token;
    $stream['secret'] =   session()->oauth_secret;
    //$stream['password'] = new MongoBinData(encrypt($args['password']));

    $stream->save();
    redirect('/twitter/streams/');
}

function twitter_streams($format = 'html') {

    $headers = array(
        'ID',
        'Rate (status/sec)',
        'Total (last 60s)',
        'Following',
        'Terms',
        'Heartbeat',
        'Status',
        'Events',
        'PID',
        ''
    );
    $rows = array();
    $streams =  mdb()->twitterStream->find();

    foreach ($streams as $stream) {

        $stream = new TwitterStream($stream['_id']);
        $actions = array(
            l('Start', get_url('/twitter/stream/start/~/' . $stream['_id']), 'btn btn-success'),
            l('Stop', get_url('/twitter/stream/stop/~/' . $stream['_id']), 'btn btn-danger'),
            l('Process', get_url('/twitter/stream/process/~/' . $stream['_id']), 'btn'),
            l('View Log', get_url('/twitter/stream/log/~/' . $stream['_id']), 'btn'),
            l('Delete', get_url('/twitter/stream/delete/~/' . $stream['_id']), 'btn btn-info')
        );
        $actions = '<div class="btn-group">' . implode('', $actions) . '</div>';

        $pid = $stream['pid'];
        if (isset($pid) && isRunning($pid)) {
            $pid = '<span style="color:green">' . $pid . '</span>';
        } else {
            $pid = '<span style="color:red">' . $pid . '</span>';
        }

        $rows[] = array(
            l($stream['_id'], get_url('/twitter/stream/~/' . $stream['_id'])),
            $stream['status_rate'],
            $stream['status_count'],
            count($stream['follow']),
            count($stream['track']),
            template_date($stream['heartbeat']),
            $stream->status,
            $stream->processed_events_count . '&nbsp;(' . $stream->unprocessed_events_count . ')',
            $pid,
            $actions
        );
    }

    if ($format == 'json') {
        return json_encode(array('html' => template_table($headers, $rows)));
    }
    $page = new Template();
    $page->add_js('js/twitter.stream.streams.js', 'twitter');
    $page->c('<div id="streams">' . template_table($headers, $rows) . '</div>');
    return $page->render();
}

function twitter_stream($sid) {
    $stream = new TwitterStream($sid);
    $pm = array();

    $interval_mins = 10;
    $length_hours = 48;

    //X min intervals
    $interval = 60 * $interval_mins;
    //X hours history
    $total_length = 60 * 60 * $length_hours;

    //calculate the start and end intervals
    $end = round_n(time(), $interval);
    $beg = $end - $total_length;
    //populate an empty array, so we have a value for every possible interval
    for ($i = $beg; $i <= $end; $i = $i + $interval) {
        $pm[$i] = 0;
    }
    //grab the events and put them in their intevals
    foreach ($stream->processed_events as $event) {
        $min = round_n($event['time']->sec, $interval);
        if (isset($pm[$min])) {
            $pm[$min]++;
        }
    }

    //create the series data for the chart
    $args = array();
    $series = array(
        'data' => array_values($pm),
        'pointInterval' => $interval * 1000,
        'pointStart' => $beg * 1000,
        'name' => 'Statues'
    );
    $args['series'] = array($series);
    $args['chart_title'] = $length_hours . ' hours of events (' . $interval_mins . ' min intervals)';
    $args['chart_subtitle'] = '';
    $args['y_title'] = 'Statues';
    $args['x_title'] = 'Time';
    $args['id'] = 'twiter_stream_history';

    //pass the details to chart renderer
    $chart = new Chart('line', $args);

    //chart render returns the javascript and the container
    list($script, $html) = $chart->render();

    $page = new Template();
    $page->add_js($script, null, 'inline');

    $page->c($html);
    return $page->render();
}

function twitter_stream_consume($sid) {
    $stream = new TwitterStream($sid);
    $stream->consume();
}

function twitter_stream_start($sid) {
    $stream = new TwitterStream($sid);
    $stream->set_status(STREAM_STARTING);

    $outputfile = $_SERVER['DOCUMENT_ROOT'] . '/' . config('UPLOAD_PATH') . '/' . $sid . '-output.txt';
    $pidfile = $_SERVER['DOCUMENT_ROOT'] . '/' . config('UPLOAD_PATH') . '/' . $sid . '-pid.txt';
    $stream['outputfile'] = $outputfile;
    $stream['pidfile'] = $pidfile;

    if (file_exists($pidfile)) {
        unlink($pidfile);
    }

    $cmd = PHP_BINDIR . "/php " . $_SERVER['DOCUMENT_ROOT'] . "/index.php " . $_SERVER['SERVER_NAME'] . " twitter/stream/consume/~/" . $sid;
    $cmd = sprintf("%s > %s 2>&1 & echo $! >> %s", $cmd, $outputfile, $pidfile);
    exec($cmd);

    $pid = trim(file_get_contents($pidfile));
    $stream['pid'] = $pid;
    $stream->save();

    redirect('/twitter/streams/');
}

function twitter_stream_stop($sid) {
    $stream = new TwitterStream($sid);
    $stream->set_status(STREAM_STOPPING);
    redirect('/twitter/streams/');
}

function twitter_stream_log($sid) {

    $page = new Template();
    $stream = new TwitterStream($sid);

    if (file_exists($stream['outputfile'])) {
        $log_contents = file_get_contents($stream['outputfile']);
        $page->c('<pre id="log_contents">' . $log_contents . '</pre>');
    }
    return $page->render();
}

function twitter_stream_delete($sid) {
    $stream = new TwitterStream($sid);
    if(!$stream->status != 'STOPPED'){
        message('Please stop the stream before deleting');
        redirect('/twitter/streams');
    }
    $stream->delete();
    redirect('/twitter/streams/');
}

function twitter_stream_process($sid, $redirect = true) {
    $stream = new TwitterStream($sid);
    $stream->process_events();
    if($redirect){
        redirect('/twitter/streams/');
    }
}

function twitter_stream_process_job($process, $sid){
    $stream = new TwitterStream($sid);
    $stream->process_events();
    $stream->get_unprocessed_events_count(true);
    $stream->get_processed_events_count(true);
}

function twitter_stream_update_stream_pool(){
    $max_size = 5000;
    $res = mdb()->accessToken->find();
    $ats = array();
    foreach($res as $rec){
        $ats[] = $rec;
    }

    $rt_uids = new TwitterUserCollection(array('in_cohort' => true));
    $rt_uids = $rt_uids->ids;

    $pool = array_chunk($rt_uids, $max_size);
    $cnt = 0;
    foreach($ats as $at){
        if(!isset($pool[$cnt])){
            //more access tokens than required;
            break;
        }
        $id = 'realtime_pool_stream_' . $cnt;
        $stream = new TwitterStream($id);
        $stream['follow'] = $pool[$cnt];
        $stream['token'] = $at['oauth_token'];
        $stream['secret'] = $at['oauth_token_secret'];
        $stream->save();
        $cnt++;
    }

    if(($cnt+1) < count($pool)){
        //TODO: we need a way to alert people that we don't have enough access tokens to support the number of users.
    }
}

function twitter_stream_jobs() {
    $jobs = array();

    $streams =   mdb()->twitterStream->find();
    foreach ($streams as $stream) {
        $jobs['stream_process_' . $stream['_id']] = array(
            'frequency' => 10,
            'function' => 'twitter_stream_process_job',
            'args' => array(
                'sid' => $stream['_id']
            )
        );
    }

    $jobs['update_stream_predicates'] = array(
            'frequency' => 60 * 60,
            'function' => 'twitter_stream_update_stream_pool'
        );

    return $jobs;
}



