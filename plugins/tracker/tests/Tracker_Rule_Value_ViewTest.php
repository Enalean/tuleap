<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 *
 *
 * Tests the class Tracker_Rule_List_View
 */

require_once('bootstrap.php');

class Tracker_Rule_List_ViewTest extends TuleapTestCase
{

    function testFetch()
    {
        $rule = mock('Tracker_Rule_List');
        $rule->id                = 'id';
        $rule->tracker_id        = 'tracker_id';
        $rule->source_field      = 'source_field';
        $rule->target_field      = 'target_field';
        $rule->source_value      = 'source_value_1';
        $rule->target_value      = 'target_value_2';

        $view = new Tracker_Rule_List_View($rule);
        $this->assertEqual($view->fetch(), '#id@tracker_id source_field(source_value_1) => target_field(target_value_2)');
    }
}
