<?php
require_once('MultiMapTestCase.class.php');
require_once('common/collection/MultiMap.class.php');

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
?>
