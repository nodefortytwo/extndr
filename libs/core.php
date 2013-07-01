<?php

//defined a global "modules" variable which contains an array of all detected modules
function registerModules(){

    //init the list of modules and include the {modulename}.php files
    $modules = array();
    $order = array('mongo', 'patterns', 'error','session', 'template');
    foreach (glob("libs/modules/*") as $filename)
    {
        $module = str_replace('libs/modules/', '', $filename);
        $path = 'libs/modules/' . $module . '/' . $module . '.php';
        $modules[$module] = array('path' => $path, 'weight' => 0);   
    }
    $morder = 1;
    foreach($order as $m){
        if(isset($modules[$m])){
            $modules[$m]['weight'] = $morder;
            $morder++;
        }
    }
    
    foreach($modules as $key=> $m){
        if($m['weight'] == 0){
            $modules[$key]['weight'] = $morder;
            $morder++;
        }
    }
    uasort($modules, 'module_weight_sort');
    $GLOBALS['modules'] = array_keys($modules);
    foreach($modules as $m){
        if(file_exists($m['path'])){
            require $m['path'];
        }
    }
    
    return $modules;
}

function module_weight_sort($a, $b){
    $a = $a['weight'];
    $b = $b['weight'];
    
    if ($a == $b) {
        return 0;
    }
    return ($a < $b) ? -1 : 1;
    
}



function exec_hook($hook, $args = array(), $module = null){
    
    global $modules;
    $results = array();
    if(!is_null($module)){
        if(function_exists($module.'_'.$hook)){
            return call_user_func_array($module.'_'.$hook, $args);
        }else{
            return null;
        }
    }else{
        foreach($modules as $module){
            if(function_exists($module.'_'.$hook)){
                $results[$module] = call_user_func_array($module.'_'.$hook, $args);
            }
        }
        return $results;
    }
    
}

function elog($text, $level = 'notice', $source = 'core'){
    if (!is_string($text)){$text = print_r($text, true);}
    $warning_levels = array('debug', 'notice', 'warning', 'error');
    $leveln = array_search($level, $warning_levels);
    if (is_null($leveln)){$leveln = 1;}
    //db()->query('INSERT INTO log (source, text, level, created) VALUES ("'.$source.'", "'.$text.'", '.$leveln.', '.time().')');
}

function message($text, $level = 'info'){
    if(sys()->cli){
        echo $text , "\n";
        return;
    }
    //sesh(1) tells the session class to start a session if one doesn't already exist;
    $messages = session(1)->messages;
    if(!$messages){
        $messages = array();
    }
    
    $messages[] = array(
        'text' => $text,
        'level' => $level
    );
    
    session()->messages = $messages;
}


function number_to_word($number){
    $numbers = array('zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen');
    return $numbers[$number];
}

function rand_str($length = 8){
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;

}

