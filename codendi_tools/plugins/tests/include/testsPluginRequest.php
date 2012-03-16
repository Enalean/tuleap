<?php
class testsPluginRequest {
    
    protected $cover_code   = false;
    protected $show_pass    = false;
    protected $order        = 'normal';
    protected $order_values = array('normal', 'random', 'invert');
    protected $tests_to_run = array();
    
    public function parse($request) {
        foreach($request as $property=> $value) {
            $setProperty = 'set'.ucfirst(preg_replace_callback('@[_](.)@', array($this, 'replaceUnderscore'), $property));
            if (method_exists($this, $setProperty)) {
                $this->$setProperty($value);
            }
        }
    }
    
    protected function replaceUnderscore($match) {
        return ucfirst($match[1]);
    }
    
    public function setCoverCode($cover_code) {
        $this->cover_code = (bool) $cover_code;
    }
    
    public function setShowPass($show_pass) {
        $this->show_pass = (bool) $show_pass;
    }
    
    public function setOrder($order) {
        $order = strtolower($order);
        if (in_array($order, $this->order_values)) {
            $this->order = $order;
        }
    }
    
    public function setTestsToRun( array $tests_to_run) {
        $this->tests_to_run = $tests_to_run;
    }
    
    
    public function getCoverCode() {
        return $this->cover_code;
    }
    
    public function getShowPass() {
        return $this->show_pass;
    }
    
    public function getOrder() {
        return $this->order;
    }
    
    public function getTestsToRun() {
        return $this->tests_to_run;
    }
    
}
?>