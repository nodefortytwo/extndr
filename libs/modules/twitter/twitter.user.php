<?php
class TwitterUser extends MongoBase {
    protected $collection = 'twitterUser';
    public $screen_name;

    function __construct($id = null, $db_only = true) {
        $this->db_only = $db_only;
        
        if (is_numeric($id)) {
            //this is an id int
            $this->_id = (string)$id;
            $this->load_from_id();
        } elseif (is_string($id)) {
            $this->screen_name = str_replace('@', '', trim($id));
            $this->load_from_screen_name(true);
        } elseif (is_array($id)) {
            //mongo record
            $this->load_from_record($id);
        } elseif (is_object($id)) {
            //twitter api response
            $this->load_from_object($id);
        }

        $this->default_fields = array(
            'ID' => '_id',
            'Name' => 'user.name',
            'Screen Name' => 'user.screen_name',
            'Followers' => 'user.followers_count',
            'Friends' => 'user.friends_count',
            'Tweets' => 'user.statuses_count'
        );

    }

    function __toString() {
        if (isset($this['user']['screen_name'])) {
            return $this['user']['screen_name'];
        }
        return json_encode($this->data);
    }

    function load_from_screen_name($db_only = true) {
        $rec = mdb()->{$this->collection}->findOne(array('user.screen_name' => $this->screen_name));
        if ($rec) {
            $this->load_from_record($rec);
            $this->exists = true;
        } elseif (!$this->db_only) {
            $this->load_from_twitter(array('screen_name' => $this->screen_name));
        } else {
            $this->exists = false;
        }
    }

    function load_from_twitter($params) {
        $default_params = array();
        $params = array_merge($default_params, $params);
        $rates = twitter_ratelimit();

        $rate = $rates->check('users', '/users/show/:id');

        if ($rate['remaining'] > 0) {
            $data = twitter()->get('users/show', $params);
            $rates->used('users', '/users/show/:id');
            $this->load_from_object($data);
        }
    }

    function load_from_object($object) {
        if (isset($object->id_str)) {
            $this->_id = (string)$object->id_str;
            $this->data['_id'] = $this->_id;
            $this->data['user'] = (array)$object;
            $this->save();
            //load again to convert the object into a mongo array
            $this->load_from_id();
        }
    }

    
    function render_TableRow($args = array()) {
        $html = '<tr>';
        foreach ($args['cols'] as $col) {
            $html .= '<td>' . $this[$col] . '</td>';
        }

        $html .= '</tr>';
        return $html;
    }

    function render_profile_widget($args){

        $html = new Template(false);
        $html->load_template('templates/profile.widget.html', 'twitter');
        $vars = array();
        $vars['user_image'] = '<img src="'.$this['user.profile_image_url_https'].'" style="width:100%"/>';
        $vars['screen_name'] = $this['user.screen_name'];
        $vars['user_name'] = $this['user.name'];
        $html->add_variable($vars);


        return $html->render();
    }

    function update_stats(){
        $updates = exec_hook('user_update_stats', array($this));
        foreach($updates as $module => $update){
            foreach($update as $field=>$value){
                $this[$field] = $value;
            }
        }
   
    }

}

class TwitterUserCollection extends Collection{
    protected $collection = 'twitterUser', $class_name = 'TwitterUser';
    protected $default_cols = array(
            'ID' => 'user.id_str',
            'Name' => 'user.name',
            'Screen Name' => 'user.screen_name',
            'Followers' => 'user.followers_count',
            'Friends' => 'user.friends_count',
            'Tweets' => 'user.statuses_count'
        );
}

//fast external user exists function
function twitter_user_exists($uid){
    $count = mdb()->twitterUser->count(array('_id' => (string) $uid));
    if ($count === 0){
        return false;
    }
    return true;
}

