<?php
require_once('MultiMapTestCase.class.php');
require_once('common/collection/PrioritizedMultiMap.class.php');

if (!class_exists("FakeValue")) {
    class FakeValue {}
}


/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * 
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
        $m      = new PrioritizedMultiMap();
        $value1 = new FakeValue();
        $value2 = new FakeValue();
        $key    = 'key';
        $m->put($key, $value1, -10);
        $m->put($key, $value2, 10);
        $col = $m->get($key);
        $this->assertIsA($col, "PrioritizedList");
        $it = $col->iterator();
        $element = $it->current();
        $this->assertReference($element, $value2);
        $it->next();
        $element = $it->current();
        $this->assertReference($element, $value1);
    }
}
?>
