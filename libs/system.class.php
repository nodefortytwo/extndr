<?php
class System {
    public $active_route = null, $status = 404;
    
    function __construct($cli) {
        $this->cli = $cli;
        require ('config.class.php');
        require ('core.php');
        require ('misc.php');
        $this->cwd = getcwd();
        $this->request_uri = $_SERVER['REQUEST_URI'];
        $this->http_host = $_SERVER['HTTP_HOST'];
        $this->get_request_path();
        registerModules();
        
    }

    function get_request_path() {
        // Extract the path from REQUEST_URI.
        $request_path = strtok($_SERVER['REQUEST_URI'], '?');
        //Force trailing slashes, this should be done in htaccess but this double checks :)
        $lastchar = substr($request_path, strlen($request_path) - 1);
        if ($lastchar != '/' && !$this->cli) {
            redirect($request_path . '/', 301, false);
        }

        if(!$this->cli){
            $base_path_len = strlen(rtrim(dirname($_SERVER['SCRIPT_NAME']), '\/'));
            $path = substr(urldecode($request_path), $base_path_len + 1);
            if ($path == basename($_SERVER['PHP_SELF'])) {
                $path = '';
            }
            if (empty($path)) {
                $path = 'home';
            }
        }else{
            $path = $this->request_uri;
        }
        //Find any args
        $split = explode('~', $path);
        $path = rtrim($split[0], "/");
        $args = array();
        if (!empty($split[1])) {
            $split[1] = trim($split[1], "/");
            $args = explode('/', $split[1]);
        }

        $this->path = $path;
        $this->args = $args;
        
    }

    function active_route() {
        if (isset($this->active_route)) {
            return $this->active_route;
        }

        $this->routes = exec_hook('routes');
        foreach ($this->routes as $module) {
            if (array_key_exists($this->path, $module)) {
                
                $callback = $module[$this->path];
                $callback['path'] = get_url($this->path);
                $this->active_route = $callback;
                $access = true;

                if (isset($callback['access_callback']) && function_exists($callback['access_callback'])) {
                    $access = call_user_func_array($callback['access_callback'], $this->args);
                }

                if ($access) {
                    if (isset($callback['callback']) && function_exists($callback['callback'])) {
                        $this->status = 200;
                        $this->returned = call_user_func_array($callback['callback'], $this->args);
                        print($this->returned);
                    } else {
                        $this->status = 500;
                        elog('either callback is not set or is invalid', 'error', 'bootstrap');
                    }
                } else {
                    $this->status = 403;
                }
            }
        }

        if(!$this->active_route){
            switch ($this->status){
                default:
                    $this->active_route = $this->routes['pages'][$this->status];
                    $this->active_route['path'] = get_url($this->status);
                    $this->returned = call_user_func_array($this->active_route['callback'], $this->args);
                    print($this->returned);
                    
            }
        }

        return $this->active_route;
    }

}

function sys($cli = false) {
    global $system;
    if (!isset($system)) {
        $system = new System($cli);
    }
    return $system;
}
