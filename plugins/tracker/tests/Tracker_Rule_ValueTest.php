<?php

require_once(dirname(__FILE__).'/../include/Tracker_Rule_Value.class.php');

/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * 
 *
 * Tests the class Tracker_RuleValue
 */
class Tracker_Rule_ValueTest extends UnitTestCase {

    function testApplyTo() {
        $trv = new Tracker_Rule_Value('id', 'tracker_id', 'source_field', 'source_value', 'target_field', 'target_value');
        
        $this->assertTrue( $trv->applyTo('tracker_id',       'source_field',        'source_value',       'target_field',       'target_value'      ));
        $this->assertFalse($trv->applyTo('tracker_id',       'source_field',        'source_value',       'target_field',       'false_target_value'));
        //$this->assertFalse($trv->applyTo('false_tracker_id', 'source_field',        'source_value',       'target_field',       'target_value'      ));
        $this->assertFalse($trv->applyTo('false_tracker_id', 'source_field',        'source_value',       'target_field',       'false_target_value'));
        $this->assertFalse($trv->applyTo('tracker_id',       'target_source_field', 'source_value',       'target_field',       'false_value'       ));
        $this->assertFalse($trv->applyTo('tracker_id',       'target_source_field', 'source_value',       'target_field',       'false_target_value'));
        $this->assertFalse($trv->applyTo('tracker_id',       'source_field',        'false_source_value', 'target_field',       'false_value'       ));
        $this->assertFalse($trv->applyTo('tracker_id',       'source_field',        'false_source_value', 'target_field',       'false_target_value'));
        $this->assertFalse($trv->applyTo('tracker_id',       'source_field',        'source_value',       'false_target_field', 'false_value'       ));
        $this->assertFalse($trv->applyTo('tracker_id',       'source_field',        'source_value',       'false_target_field', 'false_target_value'));
    }
    
    function testCanApplyTo() {
        $trv = new Tracker_Rule_Value('id', 'tracker_id', 'source_field', 'source_value', 'target_field', 'target_value');
        
        $this->assertTrue( $trv->canApplyTo('tracker_id',       'source_field',        'source_value',       'target_field',       'target_value'      ));
        $this->assertTrue( $trv->canApplyTo('tracker_id',       'source_field',        'source_value',       'target_field',       'false_target_value'));
        //$this->assertFalse($trv->canApplyTo('false_tracker_id', 'source_field',        'source_value',       'target_field',       'target_value'      ));
        //$this->assertFalse($trv->canApplyTo('false_tracker_id', 'source_field',        'source_value',       'target_field',       'false_target_value'));
        $this->assertFalse($trv->canApplyTo('tracker_id',       'target_source_field', 'source_value',       'target_field',       'false_value'       ));
        $this->assertFalse($trv->canApplyTo('tracker_id',       'target_source_field', 'source_value',       'target_field',       'false_target_value'));
        $this->assertFalse($trv->canApplyTo('tracker_id',       'source_field',        'false_source_value', 'target_field',       'false_value'       ));
        $this->assertFalse($trv->canApplyTo('tracker_id',       'source_field',        'false_source_value', 'target_field',       'false_target_value'));
        $this->assertFalse($trv->canApplyTo('tracker_id',       'source_field',        'source_value',       'false_target_field', 'false_value'       ));
        $this->assertFalse($trv->canApplyTo('tracker_id',       'source_field',        'source_value',       'false_target_field', 'false_target_value'));
    }
        
}
?>
