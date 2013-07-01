<?php
class Error extends MongoBase{
	protected $collection = 'error', $obj_id = true;

	public function load_postprocess(){
		$this->get_file();
	}

	//throw is a restricted word :(
	public function toss($e){

		$this['message'] = $e->getMessage();
		$this['code'] = $e->getCode();
		$this['line'] = $e->getLine();
		$this['trace'] = object_to_array($e->getTrace());
		$this['file'] = $this->file;
		$this['time'] = new MongoDate(time());
		$this->save();
		return $this['_id'];
	}

	public function render(){

		$stack_html = '<pre>' . print_r($this['trace'], true) . '</pre>';
		$cp = $this->data;
		$cp['trace'] = $this->getExceptionTraceAsString();
		$cp['time'] = template_date($cp['time']);
		if(!isset($cp['file'])){
			$cp['file'] = $this->file;
		}
		$template = new Template(false);
		$template->load_template('templates/error.html', 'error');
		$template->add_variable($cp);
		return $template->render();
	}

	public function get_file(){
		foreach($this['trace'] as $t){
			if(isset($t['file'])){
				$this->file = $t['file'];
				break;
			}
		}
		if(!isset($this->file)){
			$this->file == 'Unknown';
		}
		$this['file'] = $this->file;
		return $this->file;
	}

	function getExceptionTraceAsString() {
	    $rtn = "";
	    $count = 0;
	    foreach ($this['trace'] as $frame) {
	        $args = "";
	        if (isset($frame['args'])) {
	            $args = array();
	            foreach ($frame['args'] as $arg) {
	                if (is_string($arg)) {
	                    $args[] = "'" . $arg . "'";
	                } elseif (is_array($arg)) {
	                    $args[] = "Array";
	                } elseif (is_null($arg)) {
	                    $args[] = 'NULL';
	                } elseif (is_bool($arg)) {
	                    $args[] = ($arg) ? "true" : "false";
	                } elseif (is_object($arg)) {
	                    $args[] = get_class($arg);
	                } elseif (is_resource($arg)) {
	                    $args[] = get_resource_type($arg);
	                } else {
	                    $args[] = $arg;
	                }   
	            }   
	            $args = join(", ", $args);
	        }
	        $rtn .= sprintf( "#%s %s(%s): %s(%s)\n",
	                                 $count,
	                                 isset($frame['file']) ? $frame['file'] : 'unknown file',
	                                 isset($frame['line']) ? $frame['line'] : 'unknown line',
	                                 (isset($frame['class']))  ? $frame['class'].$frame['type'].$frame['function'] : $frame['function'],
	                                 $args );
	        $count++;
	    }
	    return $rtn;
	}
}

class ErrorCollection extends Collection{
	protected $collection = 'error', $class_name = 'Error';
    protected $default_cols = array(
            'ID' => '_id',
            'Message' => 'message',
            'Time' => 'time',
            'File' => 'file',
            'line' => 'line'
        );
}