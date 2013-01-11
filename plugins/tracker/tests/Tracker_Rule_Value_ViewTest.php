<?php

require_once(dirname(__FILE__).'/../include/Tracker/Rule/List/View.class.php');
require_once(dirname(__FILE__).'/../include/Tracker/Rule/List.class.php');
Mock::generate('Tracker_Rule_List');

/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * 
 *
 * Tests the class Tracker_Rule_List_View
 */
class Tracker_Rule_List_ViewTest extends UnitTestCase {

    function testFetch() {
        $rule =& new MockTracker_Rule_List($this);
        $rule->id                = 'id';
        $rule->tracker_id        = 'tracker_id';
        $rule->source_field      = 'source_field';
        $rule->target_field      = 'target_field';
        $rule->source_value      = 'source_value_1';
        $rule->target_value      = 'target_value_2';

        $view =& new Tracker_Rule_List_View($rule);
        $this->assertEqual($view->fetch(), '#id@tracker_id source_field(source_value_1) => target_field(target_value_2)');
    }
}
?>
