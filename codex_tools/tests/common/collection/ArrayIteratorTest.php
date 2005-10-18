<?php
if (! defined('CODEX_RUNNER')) {
    define('CODEX_RUNNER', __FILE__);
    require_once('tests/CodexReporter.class');
}

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

//We want to be able to run one test AND many tests
if (CODEX_RUNNER === __FILE__) {
    $test = &new ArrayIteratorTest();
    $test->run(new CodexReporter());
 }
?>
