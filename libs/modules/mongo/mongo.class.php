<?php

class MongoBase implements arrayaccess {
    public $_id, $rt_sync = false, $obj_type = 'MongoBase';
    protected $cl = array(), $data = array(), $obj_id = false;
    
    public function __construct($rec = null) {
        if (!isset($this->collection)) {
            throw new Exception('DEFINE A COLLECTION BEFORE EXTENDING MongoBase');
        }

        if(is_null($rec)){
            return;
        }

        if(is_string($rec) && $this->obj_id){
            $rec = new MongoId($rec);
        }

        if (is_array($rec)) {
            $this->load_from_record($rec);
        } elseif (is_object($rec)) {
            if (get_class($rec) == 'MongoId') {
                $this->_id = $rec;
                $this->load_from_id();
            } else {
                $this->load_from_object($rec);
            }
        } elseif (!empty($rec)) {
            $this->_id = (string)$rec;
            $this->load_from_id();
        }

        if (method_exists($this, 'load_postprocess')) {
            call_user_func(array(
                $this,
                'load_postprocess'
            ));
        }
    }

    function __get($var) {
       
        if (method_exists($this, 'get_' . $var)) {
            return call_user_func_array(array(
                $this,
                'get_' . $var
            ), array());
        }
    }

    function __toString() {
        return json_encode($this->data);
    }

    protected function load_from_id() {
        if ($exists = $this->exists()) {
            $this->data = $exists;
            if (method_exists($this, 'load_postprocess')) {
                call_user_func(array(
                    $this,
                    'load_postprocess'
                ));
            }
        }
    }
    //just a proxy for load_from_id
    public function refresh(){
        $this->load_from_id();
    }

    protected function load_from_record($rec) {

        $this->_id = $rec['_id'];
        $this->data = $rec;
        if (method_exists($this, 'load_postprocess')) {
            call_user_func(array(
                $this,
                'load_postprocess'
            ));
        }
    }

    public function exists($ret_obj = true) {
        if (!$this->_id) {
            $this->exists = false;
            return false;
        }
        $q = array('_id' => $this->_id);
        if ($ret_obj) {
            $rec =  mdb()->{$this->collection}->findOne($q);
        } else {
            $rec =  mdb()->{$this->collection}->count($q);
        }
        if ($rec) {
            $this->exists = true;
            return $rec;
        } else {
            $this->exists = false;
            return false;
        }
    }

    public function save() {
        if (!$this['_id'] && $this->_id) {
            $this['_id'] = $this->_id;
        }
        if (method_exists($this, 'save_preprocess')) {
            call_user_func(array(
                $this,
                'save_preprocess'
            ));
        }
        if ($this->exists(false)) {
            //var_dump($this);
                
            $set = array();
            uksort($this->cl,'sort_by_len');
            //var_dump($this->cl);
            foreach($this->cl as $field=>$op){
                if($field == '_id'){
                    continue;
                }
                $skip = false;
                $parts = explode('.', $field);
                $part = '';
                
                foreach($parts as $p){
                    $part = $part . $p;
                    //var_dump($part);
                    if(isset($set[$part]) || isset($unset[$part])){
                        $skip = true;
                    }
                    $part = $part . '.';
                    
                }
                //var_dump($skip);
                if($skip){
                    continue;
                }
                
                if($op){
                    $set[$field] = $this[$field];
                }else{
                    $unset[$field] = '';
                }
            }
            if(!empty($set) || !empty($unset)){
                $update = array();
                if(!empty($set)){$update['$set'] = $set;} 
                if(!empty($unset)){$update['$unset'] = $unset;} 
                if(!empty($update)){
                    mdb()->{$this->collection}->update(array('_id' => $this->_id), $update);
                }
            }
        } else {
            mdb()->{$this->collection}->insert($this->data);
            $this->_id = $this['_id'];
            $this->exists = true;
        }
        if (method_exists($this, 'save_postprocess')) {
            call_user_func(array(
                $this,
                'save_postprocess'
            ));
        }
    }

    public function delete() {
        if ($this->exists(false)) {
            mdb()->{$this->collection}->remove(array('_id' => $this->_id));
        }
    }

    public function get_exists() {
        $this->exists(false);
    }

    public function offsetSet($offset, $value) {
       
        if (is_null($offset)) {
            $this->data = $value;
        } else {
            $offsets = explode('.', $offset);
            $this->data = $this->recursive_set($this->data, $offsets, $value);
        }
        if(end($offsets) == '[]'){
            $parent = implode('.', array_slice($offsets, 0, count($offsets) - 1));
            $index = count($this[$parent]) - 1;
            $offset = $parent;
        }
         
        $this->cl[$offset] = true;
        if ($this->rt_sync) {
            $this->save();
        }
    }

    public function offsetExists($offset) {
        $offsets = explode('.', $offset);
        $value = $this->recursive_get($this->data, $offsets);
        return isset($value);
    }

    public function offsetUnset($offset) {
        $this->cl[$offset] = false;
        $offsets = explode('.', $offset);
        $this->data = $this->recursive_set($this->data, $offsets, null);
        if ($this->rt_sync) {
            $this->save();
        }
    }

    public function offsetGet($offset) {
        if (is_null($offset)) {
            return $this->data;
        } else {
            $offsets = explode('.', $offset);
            return $this->recursive_get($this->data, $offsets);
        }
    }

    private function recursive_set($data, $offset_array, $value, $i = 0) {
        $final = false;
        if (count($offset_array) - 1 == $i) {
            $final = true;
        }
        if ($final) {
            if ($offset_array[$i] == '[]') {
                if (!is_array($data)) {
                    $data = array($value);
                } else {
                    $data[] = $value;
                }
            } else {
                $data[$offset_array[$i]] = $value;
            }
        } else {
            if (!isset($data[$offset_array[$i]])) {
                $data[$offset_array[$i]] = array();
            }
            $data[$offset_array[$i]] = $this->recursive_set($data[$offset_array[$i]], $offset_array, $value, $i + 1);
        }
        return $data;
    }

    private function recursive_get($data, $offset_array, $i = 0) {

        $final = false;
        if (count($offset_array) - 1 == $i) {
            $final = true;
        }
        if (isset($data[$offset_array[$i]])) {
            if (is_array($data[$offset_array[$i]]) && !$final) {
                return $this->recursive_get($data[$offset_array[$i]], $offset_array, $i + 1);
            } else {

                return $data[$offset_array[$i]];
            }
        } else {
            return null;
        }
    }

    public function encode() {
        if (method_exists($this, 'encode_preprocess')) {
            call_user_func(array(
                $this,
                'encode_preprocess'
            ));
        }

        return json_encode($this->strip_mongo_objects($this->data));
    }

    private function strip_mongo_objects($data) {
        if (is_array($data) || is_object($data)) {
            if (is_a($data, 'MongoId')) {
                return (string)$data;
            }
            if (is_a($data, 'MongoDate')) {
                return (string)$data;
            }
            $result = array();
            foreach ($data as $key => $value) {
                $result[$key] = $this->strip_mongo_objects($value);
            }
            return $result;
        }
        return $data;
    }
    
    public function to_array($fields = null){
        
        if(isset($this->default_fields) && $fields === null){
            $fields = $this->default_fields;
        }elseif($fields === null){
            $fields = array('_id');
        }
        
        $ret = array();
        foreach($fields as $field){
            $ret[] = $this[$field];
        }
        return $ret;
    }

    function render($style = 'table', $args = array()) {
        if(method_exists($this, 'render_' . $style)){
            return call_user_func(array(
                $this,
                'render_' . $style
            ), $args);
        }else{
            throw new exception($style . ' not valid for this class');
        }
    }

}

function strip__ids($item, $key)
{

    var_dump($key);
}


