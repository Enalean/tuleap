<?php
if (phpversion() < 5) {

require_once("Iterator.class.php");

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * SeekableIterator
 * @see http://www.php.net/~helly/php/ext/spl/interfaceIterator.html
 */
/* abstract */ class SeekableIterator extends Iterator {
    
    /**
     * Seek to an absolute position.
     * @param $index position to seek to
     * trigger error OutOfBoundsException if the seek position is not valid
     */
    function seek($index) {
        $this->rewind();
        $position = 0;
        while($position < $index && $this->valid()) {
            $this->next();
            $position++;
        }
        if (!$this->valid()) {
            trigger_error(get_class($this).'::seek => OutOfBoundsException. Invalid seek position', E_USER_ERROR);
        }
    }
}

}
?>