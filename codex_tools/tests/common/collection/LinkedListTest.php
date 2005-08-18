<?php
if (! defined('CODEX_RUNNER')) {
    define('CODEX_RUNNER', __FILE__);
    require_once('tests/CodexReporter.class');
}

require_once('LinkedListTestCase.class');
require_once('common/collection/LinkedList.class');

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

//We want to be able to run one test AND many tests
if (CODEX_RUNNER === __FILE__) {
    $test = &new LinkedListTest();
    $test->run(new CodexReporter());
 }
?>
