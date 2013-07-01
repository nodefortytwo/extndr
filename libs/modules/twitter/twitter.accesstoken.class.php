<?php

class AccessToken extends MongoBase {
    protected $collection = 'accessToken';
    function __construct($at) {
        if (is_array($at)) {
            $id = array();
            $id['oauth_token'] = $at['oauth_token'];
            $id['oauth_token_secret'] = $at['oauth_token_secret'];
            $id['user_id'] = $at['user_id'];
            $id['screen_name'] = $at['screen_name'];
            $this->_id = md5(json_encode($id));
            if(!$this->exists){
                foreach($at as $field=>$val){
                    $this[$field] = $val;
                }
                $this['_id'] = $this->_id;
                $this->save();
            }
            $this->load_from_id();
        }
    }

    function is_valid() {
        
        if(isset($this['validated'])){
            if((time() - $this['validated']->sec) < 3600 * 24){
                return true;
            }
        }
        $valid = twitter($this->data)->get('account/verify_credentials');
        if(isset($valid->errors)){
            foreach($valid->errors as $error){
                if($error->code == 89){
                    $this->delete();
                    return false;
                }
            }
        }
        $this['validated'] = new MongoDate(time());
        $this->save();
        return true;
    }

}

function twitter_validate_access_tokens() {
    $access_tokens = twitter_get_access_tokens();
    foreach($access_tokens as $key=>$token){
        if(!$token->is_valid()){
            unset($access_tokens[$key]);
        }   
    }
    return $access_tokens;
}

function twitter_get_access_tokens() {
    $tokens =  mdb()->accessToken->find();
    $ret = array();
    foreach ($tokens as $token) {
        $ret[] = new AccessToken($token);
    }
    
    return $ret;
}

function twitter_random_token(){
    $res = mdb()->accessToken->find();
    $ats = array();
    foreach($res as $r){
        $ats[] = $r;
    }
    $access_token = $ats[array_rand($ats)];
    return $access_token;
}
