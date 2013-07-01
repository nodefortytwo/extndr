<?php
class Config{
    function __construct(){
        $setting_path = sys()->cwd . '/libs/settings/' . $_SERVER['HTTP_HOST'] . '.settings.php';
        if(file_exists($setting_path)){
            require($setting_path);
        }else{
            require(sys()->cwd . '/libs/settings/defaults.settings.php');
        }
    }
    
    function __get($var){
        $var = strtoupper($var);
        if(isset($_ENV[$var])){
            return $_ENV[$var];
        }elseif(defined($var)){
            return constant($var);
        }else{
            return null;
        }
    }
    
    function __set($var, $val){
        
    }
    
}

function config($key = null, $val = null){
    static $config;
    
    if(!$config){
        $config = new Config();
    }
    
    if($key){
        $ret = $config->$key;
        if(is_null($ret) && !is_null($val)){
            return $val;
        }elseif(!is_null($ret)){
            return $ret;
        }else{
            throw new exception($key . ' - Not Defined');               
        }
    }
    
    return $config;
}
?>