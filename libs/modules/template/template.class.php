<?php

class Template
{
    public $html = '', $close, $content, $title, $messages, $js_settings = array(), $css = array(), $js = array(), $less = array();
    private $template, $current_template = 'root', $vars, $fullpage = true;

    public function __construct($fullpage = true) {

        $this->fullpage = $fullpage;
        if ($this->fullpage) {
            $this->load_css();
            $this->load_js();
            $this->load_less();
        }
        $this->template = array();
    }

    public function __toString(){
        return $this->render();
    }

    public function load_default_wrappers() {
        $this->title = $this->title . ' | Instabanner.me';
        $this->html = $this->get_template(config('PATH_TO_MODULES') . '/' . 'template' . '/' . 'theme/wrapper.tpl.php');
    }

    private function get_template($path) {
        ob_start();
        include ($path);
        $ret = ob_get_contents();
        ob_end_clean();
        return $ret;
    }

    public function load_template($template, $module) {
        $this->template[] = array('file' => config('PATH_TO_MODULES') . '/' . $module . '/' . $template, 'vars' => array());
        $this->current_template = count($this->template) - 1;
    }

    public function add_variable($array) {
        $this->template[$this->current_template]['vars'] = array_merge($this->template[$this->current_template]['vars'], $array);
    }

    public function render() {
        if ($this->fullpage) {

        }
        
        //render templates
        foreach ($this->template as $template) {
            $this->vars = $template['vars'];
            $tmp = $this->get_template($template['file']);
            $ext = explode('.',$template['file']);
            $ext = array_pop($ext);
            
            switch($ext){
                case 'mkd':
                    $tmp = $this->mkd($tmp);
                break;
            }


            foreach ($template['vars'] as $variable => $value) {
                
                if (is_null($value) || empty($value)) {$value = '';
                }
                if (is_scalar($value)) {
                    $tmp = str_replace('{{' . $variable . '}}', $value, $tmp);
                }
            }
            
            $this->content .= $tmp;

            unset($tmp);
        }
        //var_dump($this->fullpage);
        //if we are rendering a full page we have to compile the js, css and less
        if ($this->fullpage) {
            
            //parse the css and js files to generate the mark-up
            $this->compile_less();
            $this->compile_css();
            $this->compile_js();
            
            
            //$this->compile_messages();
            //render things
            $this->render_css();
            $this->render_js();

            $this->generate_manifest();

            $this->get_messages();
            $this->compile_messages();
            //Load in the wrappers
            $this->load_default_wrappers();
            
            return $this->html;
        } else {
            return $this->content;
        }

    }

    private function load_css() {
        $this->css = array('global' => array());

        $files = exec_hook('global_css');
        foreach ($files as $module_name => $module) {
            foreach ($module as $file) {
                if (strpos($file, '//') !== false) {
                    $this->css['global'][] = $file;
                }elseif($file[0] == '/'){
                    $this->css['global'][] = get_path($file);
                } else {
                    $this->css['global'][] = get_path('/' . config('PATH_TO_MODULES') . '/' . $module_name . '/' . $file);
                }
            }
        }
    }

    public function add_css($file, $module_name) {
        if (!isset($this->css['local'])) {$this->css_compiled['local'] = array();
        }
        $this->css['local'][] = get_path('/' . config('PATH_TO_MODULES') . '/' . $module_name . '/' . $file);

    }

    private function compile_css() {

        $this->css_compiled = array();
        $css = '';
        foreach ($this->css as $key => $files) {
            foreach ($files as $file) {
                if (beginsWith($file, 'http://') || beginsWith($file, 'https://') || beginsWith($file, '//')) {
                    //$css .= file_get_contents($file);
                    $this->css_compiled[] = $file;
                } else {
                    $file = getcwd() . $file;
                    if (file_exists($file)) {
                        $css .= file_get_contents($file);
                    }
                }
                //$this->css_compiled .= "\t" . '<link rel="stylesheet" href="' . $file . '">' . "\n";
            }
            //require_once('libs/cssmin.php');
            //$css = CssMin::minify($css);
            $id = md5($css) . '_' . cache_key();
            $path = config('UPLOAD_PATH') . '/css/' . $key . '_' . $id . '.css';
            if (!file_exists($path)) {
                file_put_contents($path, $css);
            }
            $this->css_compiled[] = get_url('/' . $path);
        }
    }

    public function render_css() {
        $this->css = '';
        foreach ($this->css_compiled as $file) {
            $this->css .= "\t" . ' <link rel="stylesheet" media="all" href="' . $file . '">' . "\n";
        }
    }

    private function load_js() {
        $this->js = array('global' => array());
        $files = exec_hook('global_js');
        foreach ($files as $module_name => $module) {
            foreach ($module as $file) {
                $this->add_js($file, $module_name, 'global');
            }
        }
    }

    public function add_js($file, $module_name = null, $scope = 'local') {
        if($scope == 'inline'){
            $this->js[$scope][] = $file;
        }else{
            if (beginsWith($file, 'http://') || beginsWith($file, 'https://') || beginsWith($file, '//')) {
                $this->js[$scope][] = $file;
            } else {
                $this->js[$scope][] = get_path('/' . config('PATH_TO_MODULES') . '/' . $module_name . '/' . $file);
            }
        }
    }

    private function compile_js() {

        $this->js_complied = array();
        
        foreach ($this->js as $key => $files) {
            $js = '';
            foreach ($files as $file) {
                if($key == 'inline'){
                    $js .= $file;                    
                }else{
                    if (beginsWith($file, 'http://') || beginsWith($file, 'https://') || beginsWith($file, '//')) {
                        $this->js_complied[] = $file;
                    } else {
                        $file = getcwd() . $file;
                        if (file_exists($file)) {
                            $js .= file_get_contents($file);
                        }else{
                            die($file . ' does\'t exist');
                        }
                    }
                }
            }

            $id = md5($js) . '_' . cache_key();
            $path = UPLOAD_PATH . '/js/' . $key . '_' . $id . '.js';
            if (!file_exists($path)) {
                file_put_contents($path, $js);
            }
            $this->js_complied[] = get_url('/' . $path);
        }
    }

    public function render_js() {
        $this->js = '';
        foreach ($this->js_complied as $file) {
            $this->js .= "\t" . ' <script src="' . $file . '"></script>' . "\n";
        }
    }

    //load less
    private function load_less() {
        $this->less = array();
        $files = exec_hook('global_less');
        foreach ($files as $module_name => $module) {
            foreach ($module as $file) {
                $this->less[] = config('PATH_TO_MODULES') . '/' . $module_name . '/' . $file;
            }
        }
    }

    public function add_less($file, $module_name) {

        $this->less[] = config('PATH_TO_MODULES') . '/' . $module_name . '/' . $file;

    }

    public function compile_less() {

        require_once ('libs/lessc.inc.php');
        $this->less_complied = '';
        $content = '';
        $id = '';
        foreach ($this->less as $file) {
            $id .= $file . filesize($file);
        }
        $id = md5($id) . '_' . cache_key();
        $path = UPLOAD_PATH . '/css/less_' . $id . '.css';

        if (!file_exists($path)) {
            $this->lessc = new lessc();
            foreach ($this->less as $file) {

                $content .= $this->lessc->compileFile($file);

                //$this->less_complied .= "\t" . '<link rel="stylesheet/less" type="text/css" href="' . $file . '">' . "\n";
            }
            //$id = md5($content);

            file_put_contents($path, $content);
        }
        $this->css['global'][] = get_url('/' . $path);
    }

    public function c($content, $clear = false) {
    	if(!is_string($content)){
    		$content = print_r($content, true);
    	}
        if ($clear) {
            $this->content = $content;
        } else {
            $this->content .= $content;
        }
    }

    public function mkd($content, $module=null){
        require 'libs/Michelf/Markdown.php';
        if($module){
            $c = file_get_contents(sys()->cwd . module_get_path($module) . '' . $content);
            $this->c(\Michelf\Markdown::defaultTransform($c));
        }else{
            return (\Michelf\Markdown::defaultTransform($content));
        }

    }

    private function render_nav($nav) {
        global $system_routes;
        $menu = array();
        $n = 0;
        if (true) {
            $menu['divider' . $n] = array('text' => '', 'class' => 'divider-vertical');
        }
        $active_route = sys()->active_route();
        $active_path = $active_route['path'];
        foreach (sys()->routes as $module) {
            foreach ($module as $path => $item) {
                $class = '';
                if (array_key_exists('nav', $item) && array_search($nav, $item['nav']) !== false) {
                    $display = true;
                    if (array_key_exists('menu_callback', $item)) {
                        $display = call_user_func_array($item['menu_callback'], array($path));
                    }
                    if ($display) {

                        if ($item['menu_title']) {
                            $title = $item['menu_title'];
                        } else {
                            $title = $path;
                        }

                        $icon = '';
                        if (isset($item['menu_icon'])) {
                            $icon = '<i class="' . $item['menu_icon'] . ' icon-white"></i> ';
                        }

                        if ($nav == 'phone') {
                            $title = '<i class="' . $item['menu_icon'] . ' icon-white"></i> ';
                        } else {
                            $title = $icon . $title;
                        }
                        
                        if ($path == str_replace('/', '', $active_path)){
                            $class .= ' active';
                        }
                        
                        $t = l($title, '/' . $path, '', false, $item['menu_title']);
                        $menu[$path] = array('text' => $t, 'class' => $class);

                        if ($nav == 'phone') {
                            $n++;
                            $menu['divider' . $n] = array('text' => '', 'class' => 'divider-vertical');
                        }
                    }
                }
            }
        }
        return template_list($menu, 'nav');
    }

    private function get_messages() {
        global $messages;
        $this->messages = session()->messages;
    }

    private function compile_messages() {
        $this->compiled_messages = '';
        if (is_array($this->messages)) {
            foreach ($this->messages as $message) {
                $this->compiled_messages .= '<div class="alert alert-' . $message['level'] . '">' . $message['text'] . '</div>';
            }
        }
        if (!empty($this->compiled_messages)) {
            $this->compiled_messages = '<div class="fluid-row"><div class="span12"><div class="alerts span12">' . $this->compiled_messages . '</div></div></div>';
        }
        //print 'called';
        unset(session()->messages);
    }

    private function generate_manifest() {
        $active_route = sys()->active_route();
        $id = md5($active_route['path']) . '_' . cache_key() . '.appcache';
        $path = (config('UPLOAD_PATH').'/manifests/' . $id);
        
        $content = 'CACHE MANIFEST' . "\n\n";
        $content .= '#Key:' . cache_key() . "\n";
        $content .= '#Date:' . date('') . "\n\n";
        //$content .= $active_route['path'] . "\n";

        $content .= "\n" . 'CACHE:' . "\n";
        foreach ($this->js_complied as $file) {
            if (!beginsWith($file, 'http://') && !beginsWith($file, 'https://')) {
                $content .= $file . "\n";
            }
        }
        foreach ($this->css_compiled as $file) {
            if (!beginsWith($file, 'http://') && !beginsWith($file, 'https://')) {
                $content .= $file . "\n";
            }
        }

        $content .= "\n" . 'NETWORK:' . "\n";
        $content .= '*' . "\n";
        $content .= "\n" . 'FALLBACK:' . "\n";
        $content .= get_url('/offline');
        
        file_put_contents($path, $content);
        $this->manifest_path = get_url($path);
    }

}
