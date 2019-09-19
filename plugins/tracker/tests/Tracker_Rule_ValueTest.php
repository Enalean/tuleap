<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 *
 *
 * Tests the class Tracker_RuleValue
 */
require_once('bootstrap.php');
class Tracker_Rule_ListTest extends TuleapTestCase
{

    function testApplyTo()
    {
        $trv  = new Tracker_Rule_List();
        $trv->setSourceValue('source_value')
                ->setTargetValue('target_value')
                ->setId('id')
                ->setTrackerId('tracker_id')
                ->setSourceFieldId('source_field')
                ->setTargetFieldId('target_field');
        $this->assertTrue($trv->applyTo('tracker_id', 'source_field', 'source_value', 'target_field', 'target_value'));
        $this->assertFalse($trv->applyTo('tracker_id', 'source_field', 'source_value', 'target_field', 'false_target_value'));
        //$this->assertFalse($trv->applyTo('false_tracker_id', 'source_field',        'source_value',       'target_field',       'target_value'      ));
        $this->assertFalse($trv->applyTo('false_tracker_id', 'source_field', 'source_value', 'target_field', 'false_target_value'));
        $this->assertFalse($trv->applyTo('tracker_id', 'target_source_field', 'source_value', 'target_field', 'false_value'));
        $this->assertFalse($trv->applyTo('tracker_id', 'target_source_field', 'source_value', 'target_field', 'false_target_value'));
        $this->assertFalse($trv->applyTo('tracker_id', 'source_field', 'false_source_value', 'target_field', 'false_value'));
        $this->assertFalse($trv->applyTo('tracker_id', 'source_field', 'false_source_value', 'target_field', 'false_target_value'));
        $this->assertFalse($trv->applyTo('tracker_id', 'source_field', 'source_value', 'false_target_field', 'false_value'));
        $this->assertFalse($trv->applyTo('tracker_id', 'source_field', 'source_value', 'false_target_field', 'false_target_value'));
    }

    function testCanApplyTo()
    {
        $trv  = new Tracker_Rule_List();
        $trv->setSourceValue('source_value')
                ->setTargetValue('target_value')
                ->setId('id')
                ->setTrackerId('tracker_id')
                ->setSourceFieldId('source_field')
                ->setTargetFieldId('target_field');
        $this->assertTrue($trv->canApplyTo('tracker_id', 'source_field', 'source_value', 'target_field', 'target_value'));
        $this->assertTrue($trv->canApplyTo('tracker_id', 'source_field', 'source_value', 'target_field', 'false_target_value'));
        //$this->assertFalse($trv->canApplyTo('false_tracker_id', 'source_field',        'source_value',       'target_field',       'target_value'      ));
        //$this->assertFalse($trv->canApplyTo('false_tracker_id', 'source_field',        'source_value',       'target_field',       'false_target_value'));
        $this->assertFalse($trv->canApplyTo('tracker_id', 'target_source_field', 'source_value', 'target_field', 'false_value'));
        $this->assertFalse($trv->canApplyTo('tracker_id', 'target_source_field', 'source_value', 'target_field', 'false_target_value'));
        $this->assertFalse($trv->canApplyTo('tracker_id', 'source_field', 'false_source_value', 'target_field', 'false_value'));
        $this->assertFalse($trv->canApplyTo('tracker_id', 'source_field', 'false_source_value', 'target_field', 'false_target_value'));
        $this->assertFalse($trv->canApplyTo('tracker_id', 'source_field', 'source_value', 'false_target_field', 'false_value'));
        $this->assertFalse($trv->canApplyTo('tracker_id', 'source_field', 'source_value', 'false_target_field', 'false_target_value'));
    }
}
