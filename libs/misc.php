<?php
function debug($var) {
    print '<pre>' . print_r($var, true) . '</pre>';

}

function beginsWith($str, $sub) {
    return (strncmp($str, $sub, strlen($sub)) == 0);
}

function endsWith($str, $sub){
    return (strcmp(substr($str, -strlen($sub)), $sub) == 0);
}

function starts_with_upper($str) {
    $chr = mb_substr ($str, 0, 1, "UTF-8");
    return mb_strtolower($chr, "UTF-8") != $chr;
}

function is_common($word){
    $word = strtolower($word);
    $common = "'tis, 'twas, a, able, about, across, after, ain't, all, almost, also, am, among, an, and, any, are, aren't, as, at, be, because, been, but, by, can, can't, cannot, could, could've, couldn't, dear, did, didn't, do, does, doesn't, don't, either, else, ever, every, for, from, get, got, had, has, hasn't, have, he, he'd, he'll, he's, her, hers, him, his, how, how'd, how'll, how's, however, i, i'd, i'll, i'm, i've, if, in, into, is, isn't, it, it's, its, just, least, let, like, likely, may, me, might, might've, mightn't, most, must, must've, mustn't, my, neither, no, nor, not, of, off, often, on, only, or, other, our, own, rather, said, say, says, shan't, she, she'd, she'll, she's, should, should've, shouldn't, since, so, some, than, that, that'll, that's, the, their, them, then, there, there's, these, they, they'd, they'll, they're, they've, the, this, tis, to, too, twas, us, wants, was, wasn't, we, we'd, we'll, we're, were, weren't, what, what'd, what's, when, when, when'd, when'll, when's, where, where'd, where'll, where's, which, while, who, who'd, who'll, who's, whom, why, why'd, why'll, why's, will, with, won't, would, would've, wouldn't, yet, you, you'd, you'll, you're, you've, your";
    $common = explode(', ', $common);
    return in_array($word, $common);
}

function redirect($url, $code = '301', $root = true) {

    if ($root) {
        $url = get_url($url);
    }
    switch ($code) {
        default :
            header("HTTP/1.1 301 Moved Permanently");
            break;
    }

    $header = 'Location: ' . $url;
    header($header);
    die();
}

function module_get_path($module_name) {
    
    return get_path(config('PATH_TO_MODULES') . '/' .$module_name);
}

function get_data($url, $postfields = null, $include_headers = false) {
    global $ch;
    $ch = curl_init();
    $timeout = 15;
    curl_setopt($ch, CURLOPT_USERAGENT, "Social Cohorts v1.0");
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_ENCODING, "");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    # required for https urls
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);

    if(is_array($postfields)){
        curl_setopt($ch, CURLOPT_POST, 1); 
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
    }
    
    //curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    // Then, after your curl_exec call:
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = explode("\r\n",trim(substr($response, 0, $header_size)));
    $headers = array();
    foreach($header as $h){
        if(strpos($h, 'HTTP') !== false){
            $headers['code'] = $h;
        }elseif(!empty($h)){
        $arr = explode(': ', $h);
        $headers[$arr[0]] = $arr[1];
        }
    }
    $body = substr($response, $header_size);
    
    curl_close($ch);
    if($include_headers){
        return array($headers, $body);
    }else{
        return $body; 
    }
}

function var_get($name, $default = null) {
    
    $val = mdb()->vars->findOne(array('_id' => $name));
    if($val){
        return $val['value'];
    }else{
        return $default;
    }
}

function var_set($name, $value, $exp = 0) {
    if($cur = var_get($name)){
        if($cur !== $value){
            mdb()->vars->update(array('_id' => $name), array('value'=> $value));
        }
    }else{
        mdb()->vars->insert(array('_id' => $name, 'value' => $value));
    }
}

function between($haystack, $string1, $string2) {
    //echo ($haystack . "\n");
    $pos1 = strpos($haystack, $string1);
    if ($pos1 === false) {
        return null;
    }
    $pos1 = $pos1 + strlen($string1);
    $pos2 = strpos($haystack, $string2, $pos1);
    $val = substr($haystack, $pos1, $pos2 - $pos1);
    return $val;
}

function get_url($path, $full = false) {

    if (config()->site_root != '') {
        $path = '/' . config()->site_root . '/' . $path;
    } else {
        $path = '/' . $path;
    }

    //replace this with a substr splice
    //we are forcing urls to end with a slash to avoid silly rewrite by either .htaccess or bootstrap
    if (strpos($path, '?') !== false) {
        $path = explode('?', $path, 2);
        if(endsWith($path[0], '/')){
            $path = $path[0] . '?' . $path[1];
        }else{
            $path = $path[0] . '/?' . $path[1];
        }
    } elseif (strpos($path, '.') === false) {
        $path = $path . '/';
    }


    //lame but cleans the url
    while (strpos($path, '//') !== false) {
        $path = str_replace('//', '/', $path);
    }

    //if to be used externally
    if ($full) {
        if (!empty($_SERVER['HTTPS'])) {
            $path = 'https://' . HOST . $path;
        } else {
            $path = 'http://' . HOST . $path;
        }
    }

    return $path;
}

function get_path($path, $full=false){
    $path = '/' . $path;

    //replace this with a substr splice
    //we are forcing urls to end with a slash to avoid silly rewrite by either .htaccess or bootstrap
    if (strpos($path, '?') !== false) {
        $path = explode('?', $path, 2);
        if(endsWith($path[0], '/')){
            $path = $path[0] . '?' . $path[1];
        }else{
            $path = $path[0] . '/?' . $path[1];
        }
    } elseif (strpos($path, '.') === false) {
        $path = $path . '/';
    }


    //lame but cleans the url
    while (strpos($path, '//') !== false) {
        $path = str_replace('//', '/', $path);
    }

    //if to be used externally
    if ($full) {
        if (!empty($_SERVER['HTTPS'])) {
            $path = 'https://' . HOST . $path;
        } else {
            $path = 'http://' . HOST . $path;
        }
    }

    return $path;
}

//check post, get and var table for a value;
function get($param, $default = false) {
    if (isset($_POST[$param])) {
        return $_POST[$param];
    } elseif (isset($_GET[$param])) {
        return $_GET[$param];
    } else {
        return $default;
    }
}

function cache_key() {
    $key = var_get('CACHE_KEY', false);
    if (!$key) {
        $key = md5(time());
        var_set('CACHE_KEY', $key);
    }
    return $key;
}

function pretty_json($string = ''){
 $pattern = array(',"', '{', '}');
 $replacement = array(",\n\t\"", "{\n\t", "\n}");
 return str_replace($pattern, $replacement, $string);
}

function isRunning($pid){
    try{
        $result = shell_exec(sprintf("ps %d", $pid));
        if( count(preg_split("/\n/", $result)) > 2){
            return true;
        }
    }catch(Exception $e){}

    return false;
}

//opposite of abs, turns any number into a negative version, useful for splicing
function neg($n){
    if(!is_numeric($n)){
        //we could throw an error but meh
        return $n;
    }
    return -abs($n);
}

function round_n($n, $int = 10, $dir = 'down'){
    if($dir == 'down'){
        $n = floor($n / $int);
    }else{
        $n = round($n / $int);    
    }
    $n = $int * $n;
    return $n;
}

function intervals(){
    global $steps;
    if(!$steps){
        $steps = array();
    }
    $steps[] = explode(' ', microtime());
    $cnt = count($steps);
    if($cnt > 1){
        $b = $steps[$cnt-2];
        $a = $steps[$cnt -1];
        $r = array();
        $r[0] = $a[0] - $b[0];
        $r[1] = $a[1] - $b[1];
        $r = $r[0] + $r[1];
       
        
      return $r . 's';
    } else{
        return 0;
    } 
}

function sort_by_len($a,$b){
    return strlen($a)-strlen($b);
}

function object_to_array($data) {
    if (is_array($data) || is_object($data)) {
        $result = array();
        foreach ($data as $key => $value) {
            $result[$key] = object_to_array($value);
        }
        return $result;
    }
    return $data;
}

function array_merge_unique(array $array1 /* [, array $...] */) {
  $result = array_flip(array_flip($array1));
  foreach (array_slice(func_get_args(),1) as $arg) { 
    $result = 
      array_flip(
        array_flip(
          array_merge($result,$arg)));
  } 
  return $result;
}

function normalise($array){
    $top = $array;
    rsort($top);
    $top = $top[0];

    foreach($array as $key=>$val){
        $array[$key] = $val / $top * 100;
    }
    return $array;
}

function find_factors($n){
    $factors = array();
    for ($i = 1; $i <= $n; $i++){
        
        if(($n % $i) == 0){
            $factors[] = $i;
        }elseif (($n / $i) == 1){
            $factors[] = $i;
        }
        
    }
    
    return $factors;
}

function common_factors($x, $y){
    $x = find_factors($x);
    $y = find_factors($y);
    return array_values(array_intersect($x, $y));
}

function find_nearest($target, $options){
    $match_id = null;
    $dif = 0;
    foreach($options as $key => $f){
        $d = abs($f - $target);
        if($d < $dif || $dif == 0){
            $match_id = $key;
            $dif = $d;
        }
    }
    return $match_id;
}
?>