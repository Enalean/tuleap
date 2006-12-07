<?php
require_once('LinkedListTestCase.class.php');
require_once('common/collection/LinkedList.class.php');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id: LinkedListTest.php,v 1.1 2005/05/10 09:48:10 nterray Exp $
 *
 * Test the class LinkedList
 */
class LinkedListTest extends LinkedListTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function LinkedListTest($name = 'LinkedList test') {
        $this->LinkedListTestCase($name, 'LinkedList');
    }
    
}
?>
