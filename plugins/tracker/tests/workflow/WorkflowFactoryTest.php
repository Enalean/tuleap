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

require_once(dirname(__FILE__).'/../../include/workflow/WorkflowFactory.class.php');
require_once(dirname(__FILE__).'/../../include/Tracker/Tracker.class.php');
Mock::generate('Tracker');
Mock::generate('Workflow');
Mock::generate('Workflow_Dao');
Mock::generate('TransitionFactory');

require_once(dirname(__FILE__).'/../../include/Tracker/FormElement/Tracker_FormElement_Field_List.class.php');
Mock::generate('Tracker_FormElement_Field_List');

class WorkflowFactoryTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        PermissionsManager::setInstance(mock('PermissionsManager'));
    }

    public function tearDown() {
        PermissionsManager::clearInstance();
        parent::tearDown();
    }

     public function testImport() {
        $xml = simplexml_load_file(dirname(__FILE__) . '/_fixtures/importWorkflow.xml');
        
        $tracker = new MockTracker();
        
        $mapping = array(
                    'F1' => 110,
                    'F32'  => 111,
                    'F32-V0' => 801,
                    'F32-V1' => 802
                  );
        
        $condition_factory  = mock('Workflow_Transition_ConditionFactory');
        stub($condition_factory)->getAllInstancesFromXML()->returns(new Workflow_Transition_ConditionsCollection());
        $transition_factory = new TransitionFactory($condition_factory);
        $workflow_factory   = new WorkflowFactory($transition_factory);

        $workflow = $workflow_factory->getInstanceFromXML($xml, $mapping, $tracker);
        $this->assertEqual($workflow->getIsUsed(), 1);
        $this->assertEqual($workflow->getFieldId(), 111);
        $this->assertEqual(count($workflow->getTransitions()), 3);
        
        // Test post actions
        $transitions = $workflow->getTransitions();
        $this->assertEqual(count($transitions[0]->getPostActions()), 0);
        $this->assertEqual(count($transitions[1]->getPostActions()), 0);
        $this->assertEqual(count($transitions[2]->getPostActions()), 1);        
        
        // There is one post action on last transition
        $postactions = $transitions[2]->getPostActions();
        $this->assertEqual($postactions[0]->getField(), 110);
        $this->assertEqual($postactions[0]->getValueType(), 1);
        
        //TODO: test conditions
        $conditions = $transitions[2]->getConditions();
        $this->assertCount($conditions, 1);
        
    }
    
    public function testIsFieldUsedInWorkflow() {
        
        $tracker = new MockTracker();
        $tracker->setReturnValue('getId', 123);
        
        $field_status = new MockTracker_FormElement_Field_List();
        $field_status->setReturnReference('getTracker', $tracker);
        $field_status->setReturnValue('getId', 1001);
        
        $field_start_date = new MockTracker_FormElement_Field_List();
        $field_start_date->setReturnReference('getTracker', $tracker);
        $field_start_date->setReturnValue('getId', 1002);
        
        $field_close_date = new MockTracker_FormElement_Field_List();
        $field_close_date->setReturnReference('getTracker', $tracker);
        $field_close_date->setReturnValue('getId', 1003);
        
        $workflow = new MockWorkflow();
        $workflow->setReturnValue('getFieldId', $field_status->getId());
        
        $transition_factory = new MockTransitionFactory();
        $transition_factory->setReturnValue('isFieldUsedInTransitions', false, array($field_start_date));
        $transition_factory->setReturnValue('isFieldUsedInTransitions', true,  array($field_close_date));
        $transition_factory->expectCallCount('isFieldUsedInTransitions', 2);
        
        $wf = partial_mock('WorkflowFactory', array('getWorkflowByTrackerId'), array($transition_factory));
        $wf->setReturnReference('getWorkflowByTrackerId', $workflow, array($tracker->getId()));
        
        $this->assertTrue($wf->isFieldUsedInWorkflow($field_status));
        $this->assertFalse($wf->isFieldUsedInWorkflow($field_start_date));
        $this->assertTrue($wf->isFieldUsedInWorkflow($field_close_date));
    }
}

?>
