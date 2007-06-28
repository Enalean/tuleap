<?php
require_once('PropertyDescriptor.class.php');
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * PropertyDescriptor
 */
class PropertyDescriptor {
    
    var $name;
    var $value;
    
    function PropertyDescriptor(&$name, $value) {
        $this->name =& $name;
        $this->setValue($value);
    }
    
    function &getName() { 
        return $this->name; 
    }
    function getValue() { 
        return $this->value; 
    }
    function setValue($value) {
        $this->value = $value;
    }
}
?>