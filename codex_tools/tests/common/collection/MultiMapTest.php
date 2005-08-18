<?php
//We want to be able to run one test AND many tests
if (! defined('CODEX_RUNNER')) {
    define('CODEX_RUNNER', __FILE__);
    require_once('tests/CodexReporter.class');
}

require_once('MultiMapTestCase.class');
require_once('common/collection/MultiMap.class');

if (!class_exists("FakeValue")) {
    class FakeValue {}
}


/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id: MultiMapTest.php,v 1.1 2005/05/10 09:48:10 nterray Exp $
 *
 * Test the class MultiMap
 */
class MultiMapTest extends MultiMapTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function MultiMapTest($name = 'MultiMap test') {
        $this->MultiMapTestCase($name, 'MultiMap');
    }
}

if (CODEX_RUNNER === __FILE__) {
    $test = &new MultiMapTest();
    $test->run(new CodexReporter());
 }
?>
