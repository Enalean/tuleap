<?php
require_once('PropertyDescriptor.class.php');
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id:PropertyDescriptor.class.php 4446 2006-12-08 16:18:48 +0000 (Fri, 08 Dec 2006) ahardyau $
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