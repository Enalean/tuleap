<?php
require_once('MultiMapTestCase.class');
require_once('common/collection/PrioritizedMultiMap.class');

if (!class_exists("FakeValue")) {
    class FakeValue {}
}


/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id: PrioritizedMultiMapTest.php,v 1.1 2005/05/10 09:48:10 nterray Exp $
 *
 * Test the class PrioritizedMultiMap
 */
class PrioritizedMultiMapTest extends MultiMapTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function PrioritizedMultiMapTest($name = 'PrioritizedMultiMap test') {
        $this->MultiMapTestCase($name, 'PrioritizedMultiMap');
    }
    
    function testSimplePriority() {
        $m      =& new PrioritizedMultiMap();
        $value1 =& new FakeValue();
        $value2 =& new FakeValue();
        $key    =& new String('key');
        $m->put($key, $value1, -10);
        $m->put($key, $value2, 10);
        $col =& $m->get($key);
        $this->assertIsA($col, "PrioritizedList");
        $it =& $col->iterator();
        $element =& $it->current();
        $this->assertReference($element, $value2);
        $it->next();
        $element =& $it->current();
        $this->assertReference($element, $value1);
    }
}
?>
