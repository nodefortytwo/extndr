<?php
class Collection implements Iterator{
    protected $collection, $default_cols = array('ID' => '_id'), $class_name = null, $position = 0;
    private $cursor, $rand_used = array();
    public $search = array(), $random = false;

    function __construct($search = null, $limit = null, $sort = null, $fragment = null) {
        if(!$this->collection || !$this->class_name){
            throw new Exception('collection and class_name must be specified');
        }
        
        $this->search = $search;
        $this->limit = $limit;
        $this->sort = $sort;
        $this->fragment = $fragment;

        $this->find();
    }
    
    function __get($var) {
        if (method_exists($this, 'get_' . $var)) {
            return call_user_func_array(array(
                $this,
                'get_' . $var
            ), array());
        }
    }
    
    function find() {
        //don't run a null search.
        if (!is_array($this->search)) {
            return;
        }
        if($this->fragment){
            $this->cursor = mdb()->{$this->collection}->find($this->search, $this->fragment);
        }else{
            $this->cursor = mdb()->{$this->collection}->find($this->search);
        }

        if($this->sort){
            $this->cursor->sort($this->sort);
        }

        //don't limit random queries
        if($this->limit && !$this->random){
            $this->cursor->limit($this->limit);
        }

    }

    function render($style = 'table', $args = array()) {

        if(method_exists($this, 'render_' . $style)){
            return call_user_func_array(array(
                $this,
                'render_' . $style
            ), array($args));
        }else{
            throw new exception($style . ' not valid for this collection');
        }
    }


    function render_table($args = array()){
        list($headers, $rows) = $this->get_table_data($args);
        foreach($rows as $key=>$row){
            foreach($row as $ckey=>$col){
                if(is_object($col)){

                   switch(get_class($col)){
                       case 'MongoDate':
                           $row[$ckey] = template_date($col);
                           break;
                       default:
                           $col = (string) $col;
                   }
                    
                }
            }
            $rows[$key] = $row;
        }
        return template_table($headers, $rows, 'table-sortable');
    }
    
    function get_table_data($args = array()){
        if (!isset($args['cols'])) {
            $args['cols'] = $this->default_cols;
        }
        $headers = array_keys($args['cols']);
        if(isset($args['actions']) && $args['actions'] == true && isset($this->actions)){
            $headers[] = '';
        }
        $rows = array();
        foreach ($this as $object) {
            $row = $object->to_array($args['cols']);
            if(isset($args['actions']) && $args['actions'] == true && isset($this->actions)){
                $action_html = '<div class="btn-group">';

                foreach($this->actions as $aname=>$aurl){

                    $aurl = str_replace('{id}', $object['_id'], $aurl);

                    $action_html .= l($aname, $aurl, 'btn');
                }

                $row[] = $action_html . '</div>';
            }
            $rows[] = $row;
        }
        
        if (isset($args['sort'])) {
            $index = array_search($args['sort'], array_values($args['cols']));
            $col = array();
            foreach ($rows as $row) {
                $col[] = $row[$index];
            }
            array_multisort($col, SORT_DESC, $rows);
        }
        
        return array($headers, $rows);
    }

    function from_objects($objects) {
        //this is an experiment, a standard array should behave in almost the same way a mongo cursor so this should work.
        $this->cursor = new PseudoCursor($objects);
        //$this->cnt = $this->cursor->count();
    }
    
    function get_ids(){
        $this->ids = array();    
        foreach($this as $res){
            $this->ids[] = $res['_id'];
        }
        return $this->ids;
    }
    
    function get_cnt(){

        if($this->random && is_numeric($this->limit) && ($this->limit < $this->cursor->count())){
            return $this->limit;
        }else{
            return $this->cursor->count(true);   
        }

    }

    //iterator stuff
    function rewind() {

        $this->rand_used = array();
        $this->position = 0;
        $this->cursor->reset();
        $this->next();
    }

    function current() {
        if(is_object($this->cursor->current())){
            return $this->cursor->current();
        }
        if($this->cursor->current()){
            $classname = $this->class_name;
            return new $classname($this->cursor->current());
        }else{
            return null;
        }
    }

    function key() {
        return $this->cursor->key();
    }

    function next() {
        if($this->random == true){
            //move to zero
            $this->cursor->reset();
            //skip forward
            $this->cursor->skip($this->rand_skip());
            //move the cursor to the new item
            $this->cursor->next();
        }else{ 
            $this->cursor->next();
            $this->position++;
        }
    }

    function valid() {
        return $this->cursor->valid();
    }

    function rand_skip(){
        $total_records = $this->cursor->count();
        
        if(is_numeric($this->limit) && $this->limit < $total_records){
            $limit = $this->limit;
        }else{
            $limit = $total_records;
        }


        if(count($this->rand_used) >= $limit){
            //we have run out of things so return an invalid skip
            return $total_records;
        }

        $rand = rand(0, $total_records-1);
        while(in_array($rand, $this->rand_used)){
            $rand = rand(0, $total_records-1);
        }
        $this->rand_used[] = $rand;
        return $rand;
    }
}

//emulate mogo cursor behaviour on a standard array, associated arrays should work too but not tested.
class PseudoCursor{
    private $data, $position = -1, $keys = array();
    function __construct($data){
        $this->data = $data;
        $this->keys = array_keys($this->data);
    }

    function rewind() {
        $this->position = -1;
        //reset($this->data);
    }

    function reset() {
        $this->rewind();
    }

    function current() {
        return $this->data[$this->key()];
    }

    function key() {
        return $keys[$this->position];
    }

    function next() {
        $this->position++;
    }

    function valid() {
        return isset($this->data[$this->key()]);
    }

    function count(){
        return count($this->data);
    }

}

