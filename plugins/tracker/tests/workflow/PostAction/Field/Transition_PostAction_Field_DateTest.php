<?php
/*
 * Copyright (c) Enalean, 2011. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once(dirname(__FILE__).'/../../../../include/workflow/PostAction/Field/Transition_PostAction_Field_Date.class.php');
Mock::generatePartial('Transition_PostAction_Field_Date', 'Transition_PostAction_Field_DateTestVersion', array('addFeedback', 'getFormElementFactory'));
Mock::generate('Transition');
Mock::generate('BaseLanguage');
Mock::generate('Tracker_FormElementFactory');
Mock::generate('Tracker_FormElement_Field_Date');

class Transition_PostAction_Field_DateTest extends UnitTestCase {
    
    public function setUp() {
        $GLOBALS['Language'] = new MockBaseLanguage();
        $GLOBALS['Language']->setReturnValue('getText', 'Y-m-d', array('system', 'datefmt_short'));
    }
    public function tearDown() {
        unset($GLOBALS['Language']);
    }
    public function testBeforeShouldSetTheDate() {
        $field = new MockTracker_FormElement_Field_Date();
        $field->setReturnValue('getId', 102);
        $field->setReturnValue('getLabel', 'Close Date');
        
        $factory = new MockTracker_FormElementFactory();
        $factory->setReturnReference('getFormElementById', $field, array($field->getId()));
        
        $expected    = date('Y-m-d', $_SERVER['REQUEST_TIME']);
        $fields_data = array('field_id' => 'value');
        $transition  = new MockTransition();
        $field_id    = $field->getId();
        $id          = 1;
        $value_type  = Transition_PostAction_Field_Date::FILL_CURRENT_TIME;
        
        $post_action = new Transition_PostAction_Field_DateTestVersion();
        $post_action->expectOnce('addFeedback', array('info', 'workflow_postaction', 'field_date_current_time', array($field->getLabel(), $expected)));
        $post_action->setReturnReference('getFormElementFactory', $factory);
        
        $post_action->__construct($transition, $id, $field_id, $value_type);
        $post_action->before($fields_data);
        $this->assertEqual($expected, $fields_data[$field_id]);
    }
    
    public function testBeforeShouldClearTheDate() {
        $field = new MockTracker_FormElement_Field_Date();
        $field->setReturnValue('getId', 102);
        $field->setReturnValue('getLabel', 'Close Date');
        
        $factory = new MockTracker_FormElementFactory();
        $factory->setReturnReference('getFormElementById', $field, array($field->getId()));
        
        $transition  = new MockTransition();
        $field_id    = $field->getId();
        $id          = 1;
        $fields_data = array(
            'field_id' => 'value',
            $field_id  => '1317817376',
        );
        $value_type = Transition_PostAction_Field_Date::CLEAR_DATE;
        
        $post_action = new Transition_PostAction_Field_DateTestVersion();
        $post_action->expectOnce('addFeedback', array('info', 'workflow_postaction', 'field_date_clear', array($field->getLabel())));
        $post_action->setReturnReference('getFormElementFactory', $factory);
        
        $post_action->__construct($transition, $id, $field_id, $value_type);
        $post_action->before($fields_data);
        $this->assertEqual('', $fields_data[$field_id]);
    }
}
?>
