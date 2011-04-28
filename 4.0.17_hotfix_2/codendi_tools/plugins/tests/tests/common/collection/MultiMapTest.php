<?php
require_once('MultiMapTestCase.class.php');
require_once('common/collection/MultiMap.class.php');

if (!class_exists("FakeValue")) {
    class FakeValue {}
}


/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * 
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
?>
