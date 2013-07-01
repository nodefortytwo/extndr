<?php
//this class is a fairly half assed attempt to allow for smart session creation. only when the state is set to 1 will an actual session be used, the rest of the time it is simply a global value store
class Session{
    private $id, $state = 0, $data = array(), $persist = false, $pcookie_name = 'PLATFORM_PERSISTANT_SESSION';
    
    function __construct(){
        
        if(!empty($_COOKIE[$this->pcookie_name])){
            $this->id = $_COOKIE[$this->pcookie_name];
            session_id($this->id);
            session_start();
            $this->persist = true;
            $this->state = 1;
            $this->load_data();
            return true;
        }
        
        //if we have a session cookie we can just start up the session, and move our state to session writeable
        if(!empty($_COOKIE['PHPSESSID']) && !$this->id){
            $this->id = $_COOKIE['PHPSESSID'];
            session_id($this->id);
            session_start();
            $this->state = 1;
            $this->load_data();
        }
        
        
    }
    
    function __get($var){
        $this->load_data();
        if(isset($this->data[$var])){
            return $this->data[$var];
        }else{
            return null;
        }
    }
    
    function __set($var, $val){
        $this->load_data();
        if(!$this->id && $this->state > 0){
            //we don't have a session id and we want to store this value in a session
            $this->start();
        }
        $this->data[$var] = $val;
        $this->save_data();
    }
    
    function __unset($var){
        //die('unsetting' . $var);
        $this->load_data();
        if(isset($this->data[$var])){
            unset($this->data[$var]);
        }
        $this->save_data();
    }
    
    function state($state = null){
        if($state && $state > $this->state){
            $this->state = $state;
        }
        return $this->state;
    }
    
    function load_data(){
        //a session variable is being requested, lets check if a session has already been started.
        if(isset($_SESSION) && isset($_SESSION['data'])){
            $this->data = $_SESSION['data'];
        }
        
        if($this->persist && $data = $this->in_db()){
            $this->data = $data;
        }
    }
    
    function save_data(){
        if(isset($_SESSION)){
            $_SESSION['data'] = $this->data;
        }
        
        if($this->persist){
            if($this->in_db(false)){
                mdb()->session->update(array('_id'=>$this->id), $this->data);
            }else{
                $this->data['_id'] = $this->id;
                mdb()->session->insert($this->data);
            }
        }
    }
    
    function in_db($ret_obj = true){
        if(!$this->id){
            return false;
        }
        if($ret_obj){
            return mdb()->session->findOne(array('_id'=>$this->id));
        }else{
            return mdb()->session->count(array('_id'=>$this->id));
        }
    }
    
    function persist(){
        $this->persist = true;
        $this->save_data();
        setcookie($this->pcookie_name, $this->id, time()+60*60*24*30, '/');
    }
    
    public function start(){
        if(!sys()->cli){
            session_start();
            $this->id = session_id();
        }     
    }
    
    public function destroy(){
        setcookie ("PHPSESSID", "", time() - 3600, '/');
        setcookie ($this->pcookie_name, "", time() - 3600, '/');
        session_destroy();
        if($this->in_db(false)){
            return mdb()->session->remove(array('_id'=>$this->id));
        }
    }
}
