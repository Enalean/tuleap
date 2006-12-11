<?php
require_once('SeekableIteratorTestCase.class');
require_once('common/collection/SeekableIterator.class');

class SeekableIteratorTestVersion extends SeekableIterator {
    var $_elements;
    var $_keys;
    var $_current;
    var $_nb;
    
    function SeekableIteratorTestVersion($array) {
        $this->_elements = $array;
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
    function key() {
        return $this->_keys[$this->_current];
    }
    function &current() {
        return $this->_elements[$this->key()];
    }
    function next() {
        $this->_current = $this->_current + 1;
    }
}

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id$
 *
 * Test the class SeekableIterator
 */
class SeekableIteratorTest extends SeekableIteratorTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function SeekableIteratorTest($name = 'SeekableIterator test') {
        $this->SeekableIteratorTestCase($name, 'SeekableIteratorTestVersion');
    }
}
?>
