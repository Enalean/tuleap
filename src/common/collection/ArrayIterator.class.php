<?php

require_once("SeekableIterator.class.php");

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * ArrayIterator
 * @see http://www.php.net/~helly/php/ext/spl/interfaceIterator.html
 */
class ArrayIterator extends SeekableIterator {
    var $_elements;
    var $_keys;
    var $_current;
    var $_nb;
    
    function ArrayIterator(&$array) {
        $this->_elements =& $array;
        /*$this->_keys = array();
        foreach($this->_elements as $key => $value) {
            $this->_keys[] = $key;
        }*/
        $this->_keys = array_keys($array);
        $this->_nb = count($this->_elements);
        $this->rewind();
    }
    
    function rewind() {
        $this->_current = 0;
    }
    function valid() {
        return $this->_current < $this->_nb;
    }
    function &key() {
        return $this->_keys[$this->_current];
    }
    function &current() {
        return $this->_elements[$this->key()];
    }
    function next() {
        $this->_current = $this->_current + 1;
    }
    function count() {
        return $this->_nb;
    }
}

?>