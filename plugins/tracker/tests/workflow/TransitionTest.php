<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */
require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';
Mock::generate('Tracker_FormElement_Field_List_Value');
Mock::generate('Transition_PostAction');
Mock::generate('User');

class Transition_baseTest extends TuleapTestCase {

    protected $id          = 1;
    protected $workflow_id = 2;
    protected $from;
    protected $to;

    public function setUp() {
        parent::setUp();
        $this->from = aFieldListStaticValue()->withId(123)->build();
        $this->to   = aFieldListStaticValue()->withId(456)->build();
        PermissionsManager::setInstance(mock('PermissionsManager'));
    }

    public function tearDown() {
        PermissionsManager::clearInstance();
        parent::tearDown();
    }
}

class Transition_equalsTest extends Transition_baseTest {

    public function testEquals() {

        $field_value_new = new MockTracker_FormElement_Field_List_Value();
        $field_value_new->setReturnValue('getId', 2066);
        //'old_id' => null,
        //'field_id' => 2707,
        //'value' => 'New',
        //'description' => 'The bug has been submitted',
        //'rank' => '10');


        $field_value_analyzed = new MockTracker_FormElement_Field_List_Value();
        $field_value_analyzed->setReturnValue('getId', 2067);
        //'old_id' => null,
        //'field_id' => 2707,
        //'value' => 'Analyzed',
        //'description' => 'The bug is analyzed',
        //'rank' => '20');

        $field_value_accepted = new MockTracker_FormElement_Field_List_Value();
        $field_value_accepted->setReturnValue('getId', 2068);
        //'old_id' => null,
        //'field_id' => 2707,
        //'value' => 'Accepted',
        //'description' => 'The bug is accepted',
        //'rank' => '30');

        $t1  = new Transition(1, 2, $field_value_new, $field_value_analyzed);
        $t2  = new Transition(1, 2, $field_value_analyzed, $field_value_accepted);
        $t3  = new Transition(1, 2, $field_value_analyzed, $field_value_new);
        $t4  = new Transition(1, 2, $field_value_new, $field_value_analyzed); // equals $t1
        $t5  = new Transition(1, 2, null, $field_value_analyzed);
        $t6  = new Transition(1, 2, null, $field_value_analyzed);

        $this->assertTrue($t1->equals($t1));
        $this->assertTrue($t2->equals($t2));
        $this->assertTrue($t3->equals($t3));
        $this->assertTrue($t4->equals($t1));
        $this->assertTrue($t5->equals($t6));

        $this->assertFalse($t1->equals($t2));
        $this->assertFalse($t2->equals($t1));
        $this->assertFalse($t2->equals($t3));
        $this->assertFalse($t4->equals($t5));
    }
}

class Transition_beforeTest extends Transition_baseTest {

    function testBeforeShouldTriggerActions() {
        $current_user = new MockUser();

        $field_value_new = new MockTracker_FormElement_Field_List_Value();
        $field_value_new->setReturnValue('getId', 2066);
        //'old_id' => null,
        //'field_id' => 2707,
        //'value' => 'New',
        //'description' => 'The bug has been submitted',
        //'rank' => '10');


        $field_value_analyzed = new MockTracker_FormElement_Field_List_Value();
        $field_value_analyzed->setReturnValue('getId', 2067);
        //'old_id' => null,
        //'field_id' => 2707,
        //'value' => 'Analyzed',
        //'description' => 'The bug is analyzed',
        //'rank' => '20');

        $fields_data = array('field_id' => 'value');

        $t1 = new Transition(1, 2, $field_value_new, $field_value_analyzed);
        $t1->setConditions(new Workflow_Transition_ConditionsCollection());

        $a1 = new MockTransition_PostAction();
        $a2 = new MockTransition_PostAction();

        $t1->setPostActions(array($a1, $a2));

        $a1->expectOnce('before', array($fields_data, $current_user));
        $a2->expectOnce('before', array($fields_data, $current_user));

        $t1->before($fields_data, $current_user);
    }
}

class Transition_exportToSOAPTest extends Transition_baseTest {

    public function itExportsTheFromAndToAttributes() {
        $transition = new Transition($this->id, $this->workflow_id, $this->from, $this->to);
        $this->assertEqual($transition->exportToSOAP(), array('from_id' => 123, 'to_id' => 456));
    }

    public function itExportsEmptyStringWhenFromIsNull() {
        $transition = new Transition($this->id, $this->workflow_id, null, $this->to);
        $this->assertEqual($transition->exportToSOAP(), array('from_id' => '', 'to_id' => 456));
    }

    public function itExportsEmptyStringWhenToIsNull() {
        $transition = new Transition($this->id, $this->workflow_id, $this->from, null);
        $this->assertEqual($transition->exportToSOAP(), array('from_id' => 123, 'to_id' => ''));
    }
}

class Transition_validateTest extends Transition_baseTest {

    public function itReturnsTrueWhenConditionsAreValid() {
        $transition  = new Transition($this->id, $this->workflow_id, $this->from, $this->to);
        $fields_data = array();
        $artifact    = mock('Tracker_Artifact');
        $conditions  = stub('Workflow_Transition_ConditionsCollection')->validate()->returns(true);
        $transition->setConditions($conditions);
        $this->assertTrue($transition->validate($fields_data, $artifact));
    }

    public function itReturnsFalseWhenConditionsAreNotValid() {
        $transition  = new Transition($this->id, $this->workflow_id, $this->from, $this->to);
        $fields_data = array();
        $artifact    = mock('Tracker_Artifact');
        $conditions  = stub('Workflow_Transition_ConditionsCollection')->validate()->returns(false);
        $transition->setConditions($conditions);
        $this->assertFalse($transition->validate($fields_data, $artifact));
    }
}
?>