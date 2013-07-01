<?php

function template_init() {
    require ('template.class.php');
    $public_prefix = 'public';
    $public_paths = array('css', 'js', 'manifests', 'themes', 'gexf');
    if(!file_exists($public_prefix)){
        mkdir($public_prefix);
    }
    
    foreach($public_paths as $path){
        $path = $public_prefix  . '/' . $path;
        if(!file_exists($path)){
            mkdir($path);
        }
    }
    
    //template_clear_cache(false);
    
}

function template_routes() {
    $routes = array('theme/js/dynamic' => array('callback' => 'template_dynamic_js'));
    $routes['theme/change'] = array('callback'=>'template_change_theme');
    $routes['cache/clear'] = array('callback'=>'template_clear_cache');
    return $routes;
}

function template_global_css() {
    $css = array();
    $theme = var_get('current_theme', 'default');
    
    if($theme == 'default'){
        $css[] = 'theme/css/bootstrap.min.css';
    }else{
        template_download_theme();
        $css[] = '/'.config('UPLOAD_PATH') . '/themes/' . $theme . '.bootstrap.min.css'; 
    }
    $css[] = 'theme/css/bootstrap-responsive.min.css';
    $css[] = 'theme/css/daterangepicker.css';
    $css[] = 'theme/css/core.css';
    return $css;
}

function template_global_js() {
    $js = array();
    $js[] = '//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js';
    $js[] = 'theme/js/bootstrap.min.js';
    $js[] = 'theme/js/bootstrap-filestyle.min.js';
    $js[] = 'theme/js/jquery.tablesorter.min.js';
    $js[] = 'theme/js/highcharts.js';
    $js[] = 'theme/js/moment.min.js';
    $js[] = 'theme/js/daterangepicker.js';
    $js[] = 'theme/js/sigma.min.js';
    $js[] = 'theme/js/sigma.fisheye.js';
    $js[] = 'theme/js/sigma.forceatlas2.js';
    $js[] = 'theme/js/sigma.parseGexf.js';
    $js[] = 'theme/js/sigma.parseJson.js';

    $js[] = 'theme/js/core.js';
    return $js;
}

function template_global_less() {
    $less = array();
    return $less;
}

//this function is used to pass system variables to JS, makes it easier to format ajax call urls and other stuff
function template_dynamic_js() {
    $vars = array();
    $vars['HOST'] = HOST;
    $vars['SITE_ROOT'] = SITE_ROOT;
    $vars['PATH_TO_MODULES'] = PATH_TO_MODULES;
    $vars['BASE_PATH'] = '//' . HOST . '' . SITE_ROOT;

    $js_vars = json_encode($vars);

    //$user = new User();

    $return = 'var SYSTEM' . "\n";
    $return .= 'SYSTEM = eval(' . $js_vars . ')' . "\n";
    header('Content-type: text/javascript');
    return $return;
}

function template_change_theme($new_theme = 'default'){
    $current_theme = var_get('current_theme', 'default');
    $new_theme = strtolower($new_theme);
    if($new_theme != $current_theme){
        if($new_theme == 'default'){
            var_set('current_theme', 'default');
            message('current theme changed to: ' . $new_theme);
        }else{
            template_download_theme($new_theme);
        }   
    }else{
        message($new_theme . ' theme is already being used');
    }
    redirect('/');   
}

function template_download_theme($theme = null){
    if(is_null($theme)){
        $theme = var_get('current_theme', 'default');
    }
    $path = config('UPLOAD_PATH') . '/themes/' . $theme . '.bootstrap.min.css';          
    if(!file_exists($path)){
        $res = json_decode(get_data('http://api.bootswatch.com/'));
        $found = false;
        foreach($res->themes as $t){
            if(strtolower($t->name) == $theme){
                $found = true;
                $data = get_data($t->cssMin);
                file_put_contents($path, $data);
                var_set('current_theme', $theme);
                message('current theme changed to: ' . $theme);
            }
        }
        if(!$found){
            message($theme . ' Doesn\'t appear to be a valid theme');
        }
    }
}


//Theme functions (to be called by other modules)
function template_tabs($tabs = array(), $active = 0) {
    $content = '';
    $top = '';
    $i = 0;
    foreach ($tabs as $id => $tab) {
        if ($i == $active) {$class = 'active';
        }
        $top .= "\t" . '<li><a class="' . $class . '" href="#' . $id . '">' . $tab['title'] . '</a></li>' . "\n";
        $content .= "\t" . '<li id="' . $id . '" class="' . $class . '">' . $tab['content'] . '</li>' . "\n";
        $i++;
        $class = '';
    }
    $return = '<ul class="tabs">' . "\n" . $top . '</ul>' . "\n";
    $return .= '<ul class="tabs-content">' . "\n" . $content . '</ul>';
    return $return;

}

function l($text, $url, $class = '', $root = false, $title = '') {
    if (empty($title)) {$title = trim(strip_tags($text));
    }

    if (!empty($title)) {$title = 'title="' . trim($title) . '"';
    }
    if (!empty($class)) {$class = 'class="' . trim($class) . '"';
    }

    $url = get_url($url);
    $return = '<a href="' . $url . '" ' . $class . ' ' . $title . '>' . $text . '</a>';
    return $return;
}

function template_list($array, $class = '') {
    $return = '<ul class="' . $class . '">';
    foreach ($array as $key => $item) {
        $class = '';
        if (is_array($item)) {
            if (array_key_exists('class', $item)) {
                $class = 'class="' . $item['class'] . '"';
            }
            if (array_key_exists('text', $item)) {
                $item = $item['text'];
            }
        }
        $return .= '<li id="' . $key . '" ' . $class . '>';
        $return .= trim($item);
        $return .= '</li>';
    }
    $return .= '</ul>';

    return $return;
}

function template_table($headers, $rows, $class = '') {
    $return = '';
    $return .= '<table class="table table-striped table-bordered ' . $class . '">';
    $return .= '<thead>';
    $return .= '<tr>';
    foreach ($headers as $header) {
        $return .= '<th>' . $header . '</th>';
    }
    $return .= '</tr>';
    $return .= '</thead>';
    $return .= '<tbody>';
    foreach ($rows as $key => $row) {
        $return .= '<tr id="' . $key . '">';
        foreach ($row as $col) {

            if(is_object($col)){
               switch(get_class($col)){
                   case 'MongoDate':
                       $col = template_date($col);
                       break;
                   default:
                       $col = (string) $col;
               }    
            }

            $return .= '<td>' . $col . '</td>';
        }
        $return .= '</tr>';
    }
    $return .= '</tbody>';
    $return .= '</table>';
    return $return;
}

function template_form_item($id, $name, $type, $default = '', $class = '', $options = array(), $description = '') {
    if ($type == 'search') {
        $class = 'span10';
    } elseif (empty($class)) {
        $class = 'span12';
    }
    $class .= ' input-large';
    $return = '';
    $ops = '';
    //$return .= '<div class="control-group ' . $type . ' ' . $width . ' columns">' . "\n";
    $return .= '<div class="control-group ' . '">';
    if ($type != 'submit') {
        $return .= "\t" . '<label class="control-label" for="' . $id . '">' . $name . '</label>' . "\n";
    }

    switch($type) {
        case 'select' :
            $return .= "\t" . '<select id="' . $id . '" name="' . $id . '">' . "\n";
            array_unshift($options, '-- select -- ');
            foreach ($options as $key => $opt) {
                $selected = '';
                if ($default == $key || $default == $opt) {
                    $selected = 'selected="selected"';
                }
                if (!is_numeric($key)) {
                    $return .= "\t\t" . '<option value="' . $key . '" ' . $selected . '>';
                } else {
                    $return .= "\t\t" . '<option ' . $selected . '>';
                }
                $return .= $opt . '</option>' . "\n";
            }
            $return .= "\t" . '</select>' . "\n";
            break;
        case 'submit' :
            $class .= ' btn btn-primary';
            $return .= "\t" . '<input type="' . $type . '" id="' . $id . '" name="' . $id . '" value="' . $name . '" class="' . $class . '"/>' . "\n";
            break;
        case 'location' :
            $return .= template_form_widget_location($id, $name, $default, $class, $options, $description);
            break;
        case 'html' :
        case 'textarea' :
            if (array_key_exists('rows', $options)) {

                $ops .= ' rows="' . $options['rows'] . '"';
            }
            $return .= "\t" . '<textarea id="' . $id . '" name="' . $id . '" placeholder="' . $default . '" class="' . $class . '" ' . $ops . '/>' . $default . '</textarea>' . "\n";
            break;
        case 'file' :
        case 'image' :
            $return .= file_upload_widget($id, $type, $class, $default);
            break;
        case 'typeahead' :
            if (isset($options['function'])) {
                $source = $options['function'];
            } else {
                $source = htmlentities(json_encode($options));
            }
            $ops = 'data-source="' . $source . '" data-provide="typeahead" data-items="4"';
            $return .= "\t" . '<input style="display:inline-block;" type="text" id="' . $id . '" name="' . $id . '" placeholder="' . $default . '" class="' . $class . ' typeahead" ' . $ops . ' value="' . $default . '"/>' . "\n";
            //die($return);
            break;
        case 'search' :
            $return .= '<div class="input-append"><div class="row-fluid"><div class="span9">';
            $class = ' span12';
        case 'password' :
        case 'text' :
        default :
            $ops = '';
            if (array_key_exists('readonly', $options)) {

                $ops .= ' readonly="readonly"';
            }
            $return .= "\t" . '<input style="display:inline-block;" type="' . $type . '" id="' . $id . '" name="' . $id . '" placeholder="' . $default . '" class="' . $class . '" ' . $ops . ' value="' . $default . '"/>' . "\n";
    }

    if ($type == 'search') {
        $return .= '</div><div class="span3"><button class="btn span12" id="' . $id . '_search">Search</button></div></div></div>';
    }

    //$return .= '</div>';
    $return .= '<div class="help-block">' . $description . '</div>';
    $return .= '</div>' . "\n";

    return $return;

}

function template_form_widget_location($id, $name, $default = '', $width = 'span6', $options = array(), $description = '') {
    return location_form_widget($id, $name, $default, $width, $options, $description);
}

function template_date($date = null) {
    if (is_null($date)) {$date = time();
    }
    if(is_object($date)){
        $date = $date->sec;
    }
    if (!is_numeric($date)) {
        $date = strtotime($date);
    }
    $now = time();
    if (($now - $date) > 86400 || $date > time()) {
        return '<span class="date" data-timestamp="'.$date.'">'.date('dS M @ g:ia', $date) . '</span>';
    } else {
        return '<span class="date" data-timestamp="'.$date.'">'.template_time_ago($date) . ' ago</span>';
    }
}

function template_time_ago($tm, $rcs = 0) {
    $cur_tm = time();
    $dif = $cur_tm - $tm;
    $pds = array('second', 'minute', 'hour', 'day', 'week', 'month', 'year', 'decade');
    $lngh = array(1, 60, 3600, 86400, 604800, 2630880, 31570560, 315705600);
    for ($v = sizeof($lngh) - 1; ($v >= 0) && (($no = $dif / $lngh[$v]) <= 1); $v--);
    if ($v < 0)
        $v = 0;
    $_tm = $cur_tm - ($dif % $lngh[$v]);

    $no = floor($no);
    if ($no <> 1)
        $pds[$v] .= 's';
    $x = sprintf("%d %s ", $no, $pds[$v]);
    if (($rcs == 1) && ($v >= 1) && (($cur_tm - $_tm) > 0))
        $x .= time_ago($_tm);
    return $x;
}

function template_pagination($currentpage, $totalpages) {
    if($currentpage >= $totalpages){
        return '';
    }
    $range = 2;
    $items = array();
    
    if(($currentpage - $range) > $range){
        for($i=1; $i<$range+1; $i++){
             $items[] = $i;
        }
        $items[] = '...';
    }
    
    for ($x = ($currentpage - $range); $x < (($currentpage + $range) + 1); $x++) {
        // if it's a valid page number...
        if (($x > 0) && ($x <= $totalpages)) {
             $items[] = $x;
        }
    } 
    
    if($x < $totalpages){
        $items[] = '...';
        for($i=($totalpages-$range); $i<$totalpages + 1; $i++){
             $items[] = $i;
        }
    }
    
    $html = ' <div class="pagination pagination-centered">';
    $html .= '<ul>';
    $html .= '<li><a href="?page=1">First</a></li>';
    foreach($items as $i){
        if($i == $currentpage){
            $class = 'class="active"';
        }else{
            $class = '';
        }
        $html .= '<li '.$class.'><a href="?page='.$i.'">' . $i . '</a></li>';
    }
    $html .= '<li><a href="?page='.$totalpages.'">Last</a></li>';
    $html .= '</ul>';
    $html .= '</div>';
    
    return $html;
}


function template_clear_cache($redirect = true){
    $deleted = 0;
    $public_paths = array('css', 'js', 'manifests', 'gexf');
    foreach($public_paths as $path){
        $path = config('UPLOAD_PATH') . '/' . $path . '/*';
   
        foreach (glob($path) as $filename)
        {
            $deleted++;
            unlink($filename);
        }
    }
    if($redirect){
        message('Caches Cleared ' . $deleted . ' Files deleted');
        redirect('/');
    }
}
