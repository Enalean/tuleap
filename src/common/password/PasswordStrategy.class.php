<?php 
/**
* PasswordStrategy
* 
* TODO: description
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
*/
class PasswordStrategy {
    
    var $validators;
    var $errors;
    
    /**
    * Constructor
    */
    function PasswordStrategy() {
        $this->validators = array();
        $this->errors     = array();
    }
    
    /**
    * validate
    * 
    * validate a password with the help of validators
    *
    * @param  pwd  
    */
    function validate($pwd) {
        $valid = true;
        foreach($this->validators as $key => $nop) {
            if (!$this->validators[$key]->validate($pwd)) {
                $valid = false;
                $this->errors[$key] = $this->validators[$key]->description();
            }
        }
        return $valid;
    }
    
    /**
    * add
    * 
    * @param  v  
    */
    function add(&$v) {
        $this->validators[] =& $v;
    }
    
}
?>
