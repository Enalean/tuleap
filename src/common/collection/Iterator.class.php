<?php
if (phpversion() < 5) {

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * Iterator
 * @see http://www.php.net/~helly/php/ext/spl/interfaceIterator.html
 */
/* interface */ class Iterator {
    
    /**
     * Return the current element.
     */
    function &current() {
        trigger_error(get_class($this).'::current => Not yet implemented', E_USER_ERROR);
    }
    
    /**
     * Return the key of the current element.
     */
    function key() {
        trigger_error(get_class($this).'::key => Not yet implemented', E_USER_ERROR);
    }
    
    /**
     * Move forward to next element.
     */
    function next() {
        trigger_error(get_class($this).'::next => Not yet implemented', E_USER_ERROR);
    }
    
    /**
     * Rewind the Iterator to the first element.
     * The iterator must be rewinded during initialisation
     */
    function rewind() {
        trigger_error(get_class($this).'::rewind => Not yet implemented', E_USER_ERROR);
    }
    
    /**
     * Check if there is a current element after calls to rewind() or next().
     */
    function valid() {
        trigger_error(get_class($this).'::valid => Not yet implemented', E_USER_ERROR);
    }
}

}
?>