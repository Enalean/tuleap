<?php
require_once('SeekableIteratorTestCase.class');
require_once('common/collection/ArrayIterator.class');


/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id$
 *
 * Test the class ArrayListIterator
 */
class ArrayIteratorTest extends SeekableIteratorTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function ArrayIteratorTest($name = 'ArrayIterator test') {
        $this->SeekableIteratorTestCase($name, 'ArrayIterator');
    }
}
?>
