<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * String
 */
class String {
    
    var $str;
    
    function String($str = '') {
        $this->str = (string)$str;
    }
    
    function getInternalString() {
        return $this->str;
    }
    
    function equals($obj) {
        if (is_a($obj, 'String')) {
            $comp = $obj->getInternalString();
        } else if (!is_object($obj)) {
            $comp = $obj;
        } else {
            return false;
        }
        return $this->str == (string)$comp;
    }
    
    function hashCode() {
        return sha1($this->getInternalString());
    }
    
    function compareTo(&$obj) {
        if (is_a($obj, 'String')) {
            $comp = $obj->getInternalString();
        } else if (!is_object($obj)) {
            $comp = $obj;
        } else {
            return false;
        }
        if ($this->str === (string)$comp) {
            return 0;
        } else if ($this->str < (string)$comp) {
            return -1;
        } else {
            return 1;
        }
    }
}
?>