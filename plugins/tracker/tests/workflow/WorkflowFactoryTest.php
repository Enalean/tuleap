<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015-2018. All Rights Reserved.
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

require_once __DIR__.'/../bootstrap.php';

Mock::generate('Tracker');
Mock::generate('Workflow');
Mock::generate('Workflow_Dao');
Mock::generate('TransitionFactory');

Mock::generate('Tracker_FormElement_Field_List');

class WorkflowFactoryTest extends TuleapTestCase {

    /** @var XML_Security */
    private $xml_security;

    public function setUp() {
        parent::setUp();

        PermissionsManager::setInstance(mock('PermissionsManager'));
        $this->xml_security = new XML_Security();
        $this->xml_security->enableExternalLoadOfEntities();

        $this->project = mock('Project');
    }

    public function tearDown() {
        PermissionsManager::clearInstance();
        $this->xml_security->disableExternalLoadOfEntities();

        parent::tearDown();
    }

     public function testImport() {
        $xml = simplexml_load_file(dirname(__FILE__) . '/_fixtures/importWorkflow.xml');
        
        $tracker = new MockTracker();
        
        $mapping = array(
            'F1'     => aSelectBoxField()->withId(110)->build(),
            'F32'    => aSelectBoxField()->withId(111)->build(),
            'F32-V0' => 801,
            'F32-V1' => 802
        );
        
        $condition_factory  = mock('Workflow_Transition_ConditionFactory');
        stub($condition_factory)->getAllInstancesFromXML()->returns(new Workflow_Transition_ConditionsCollection());
        $transition_factory = mock('TransitionFactory');

        $third_transition = mock('Transition');
        $date_post_action = mock('Transition_PostAction_Field_Date');
        stub($date_post_action)->getField()->returns(110);
        stub($date_post_action)->getValueType()->returns(1);
        
        stub($third_transition)->getPostActions()->returns(array($date_post_action));

        $first_transition = Mockery::mock(Transition::class);
        $first_transition->shouldReceive('getPostActions')->andReturns([]);
        $second_transition = Mockery::mock(Transition::class);
        $second_transition->shouldReceive('getPostActions')->andReturns([]);

        stub($transition_factory)->getInstanceFromXML($xml->transitions->transition[0], $mapping, $this->project)->at(0)->returns($first_transition);
        stub($transition_factory)->getInstanceFromXML($xml->transitions->transition[1], $mapping, $this->project)->at(1)->returns($second_transition);
        stub($transition_factory)->getInstanceFromXML($xml->transitions->transition[2], $mapping, $this->project)->at(2)->returns($third_transition);

        $workflow_factory   = new WorkflowFactory(
            $transition_factory,
            mock('TrackerFactory'),
            mock('Tracker_FormElementFactory'),
            mock('Tracker_Workflow_Trigger_RulesManager'),
            mock('WorkflowBackendLogger')
        );

        $workflow = $workflow_factory->getInstanceFromXML($xml, $mapping, $tracker, $this->project);

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
        
        $this->assertEqual($third_transition, $transitions[2]);
        
    }
}
class WorkflowFactory_IsFieldUsedInWorkflowTest extends TuleapTestCase {

    /** @var Tracker_FormElement */
    private $field_status;

    /** @var Tracker_FormElement */
    private $field_start_date;

    /** @var Tracker_FormElement */
    private $field_close_date;

    /** @var Tracker_FormElement */
    private $field_due_date;

    /** @var WorkflowFactory */
    private $workflow_factory;

    /** @var TransitionFactory */
    private $transition_factory;

    public function setUp() {
        parent::setUp();
        $tracker = stub('Tracker')->getId()->returns(123);

        $this->field_status     = $this->setUpField($tracker, 1001);
        $this->field_start_date = $this->setUpField($tracker, 1002);
        $this->field_close_date = $this->setUpField($tracker, 1003);
        $this->field_due_date   = $this->setUpField($tracker, 1004);

        $workflow = mock('Workflow');
        stub($workflow)->getFieldId()->returns($this->field_status->getId());

        $this->transition_factory = mock('TransitionFactory');
        stub($this->transition_factory)->isFieldUsedInTransitions($this->field_start_date)->returns(false);
        stub($this->transition_factory)->isFieldUsedInTransitions($this->field_close_date)->returns(true);

        $this->workflow_factory = partial_mock(
            'WorkflowFactory',
            array('getWorkflowByTrackerId'),
            array(
                $this->transition_factory,
                mock('TrackerFactory'),
                mock('Tracker_FormElementFactory'),
                mock('Tracker_Workflow_Trigger_RulesManager'),
                mock('WorkflowBackendLogger')
            )
        );
        stub($this->workflow_factory)->getWorkflowByTrackerId($tracker->getId())->returns($workflow);
    }

    private function setUpField(Tracker $tracker, $id) {
        $field = mock('Tracker_FormElement_Field_List');
        stub($field)->getTracker()->returns($tracker);
        stub($field)->getId()->returns($id);
        return $field;
    }

    public function itReturnsTrueIfTheFieldIsUsedToDescribeTheStatesOfTheWorkflow() {
        expect($this->transition_factory)->isFieldUsedInTransitions()->never();
        $this->assertTrue($this->workflow_factory->isFieldUsedInWorkflow($this->field_status));
    }

    public function itReturnsTrueIfTheFieldIsUsedInAPostAction() {
        expect($this->transition_factory)->isFieldUsedInTransitions()->once();
        $this->assertTrue($this->workflow_factory->isFieldUsedInWorkflow($this->field_close_date));
    }

    public function itReturnsFalseIfTheFieldIsNotUsedByTheWorkflow() {
        expect($this->transition_factory)->isFieldUsedInTransitions()->once();
        $this->assertFalse($this->workflow_factory->isFieldUsedInWorkflow($this->field_start_date));
    }
}

class WorkflowFactory_CacheTest extends TuleapTestCase {

    /** @var WorkflowFactory */
    private $workflow_factory;

    public function setUp() {
        parent::setUp();
        $this->workflow_factory = partial_mock(
            'WorkflowFactory',
            array('getDao'),
            array(
                mock('TransitionFactory'),
                stub('TrackerFactory')->getTrackerById()->returns(aMockTracker()->build()),
                mock('Tracker_FormElementFactory'),
                mock('Tracker_Workflow_Trigger_RulesManager'),
                mock('WorkflowBackendLogger')
            )
        );
        $this->dao = mock('Workflow_Dao');
        stub($this->workflow_factory)->getDao()->returns($this->dao);
    }

    public function itReturnsSameObjectWhenUsingSameTrackerId() {
        stub($this->dao)->searchByTrackerId(112)->returnsDar(array('tracker_id' => 112, 'workflow_id' => 34, 'field_id' => 56, 'is_used' => 1));
        $this->assertIdentical(
            $this->workflow_factory->getWorkflowByTrackerId(112),
            $this->workflow_factory->getWorkflowByTrackerId(112)
        );
    }


}