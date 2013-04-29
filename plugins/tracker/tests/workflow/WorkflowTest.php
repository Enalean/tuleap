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

Mock::generate('Transition');

Mock::generatePartial('Workflow', 'WorkflowTestVersion', array('getTransitions'));
Mock::generate('Tracker');
Mock::generate('Tracker_FormElement_Field_List');
Mock::generate('Tracker_FormElement_Field_List_Value');

Mock::generate('Tracker_Artifact_Changeset');
Mock::generate('Tracker_Artifact');
Mock::generate('Tracker_Artifact_ChangesetValue_List');
Mock::generate('Tracker_Artifact_Changeset_Null');
Mock::generate('PFUser');

Mock::generate('PermissionsManager');

class WorkflowTest extends UnitTestCase {

    public function testEmptyWorkflow() {
        $workflow = new WorkflowTestVersion(1, 2, 3, 1);
        $workflow->setReturnValue('getTransitions', array());
        $this->assertNotNull($workflow->getTransitions());
        $this->assertEqual(count($workflow->getTransitions()),0);

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

        // workflow is empty, no transition exists
        $this->assertFalse($workflow->isTransitionExist($field_value_new, $field_value_analyzed));
        $this->assertFalse($workflow->hasTransitions());
    }

    public function testUseCaseBug() {
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

        $field_value_rejected = new MockTracker_FormElement_Field_List_Value();
        $field_value_rejected->setReturnValue('getId', 2069);
        //'old_id' => null,
        //'field_id' => 2707,
        //'value' => 'Rejected',
        //'description' => 'The bug is rejected',
        //'rank' => '40');

        $field_value_fixed = new MockTracker_FormElement_Field_List_Value();
        $field_value_fixed->setReturnValue('getId', 2070);
        //'old_id' => null,
        //'field_id' => 2707,
        //'value' => 'Fixed',
        //'description' => 'The bug was resolved',
        //'rank' => '50');

        $field_value_tested = new MockTracker_FormElement_Field_List_Value();
        $field_value_tested->setReturnValue('getId', 2071);
        //'old_id' => null,
        //'field_id' => 2707,
        //'value' => 'Tested',
        //'description' => 'The bug is tested',
        //'rank' => '60');

        $field_value_deployed = new MockTracker_FormElement_Field_List_Value();
        $field_value_deployed->setReturnValue('getId', 2072);
        //'old_id' => null,
        //'field_id' => 2707,
        //'value' => 'Deployed',
        //'description' => 'The bug is deployed',
        //'rank' => '70');


        $t_new_analyzed      = new Transition (1, 2, $field_value_new, $field_value_analyzed);
        $t_analyzed_accepted = new Transition (1, 2, $field_value_analyzed, $field_value_accepted);
        $t_analyzed_rejected = new Transition (1, 2, $field_value_analyzed, $field_value_rejected);
        $t_accepted_fixed    = new Transition (1, 2, $field_value_accepted, $field_value_fixed);
        $t_fixed_tested      = new Transition (1, 2, $field_value_fixed, $field_value_tested);
        $t_tested_deployed   = new Transition (1, 2, $field_value_tested, $field_value_deployed);

        $transitions = array($t_new_analyzed,
            $t_analyzed_accepted,
            $t_analyzed_rejected,
            $t_accepted_fixed,
            $t_fixed_tested,
            $t_tested_deployed);

        $workflow=new Workflow(1, 2, 3, 1, $transitions);

        $this->assertNotNull($workflow->getTransitions());
        $this->assertTrue($workflow->hasTransitions());
        // Test existing transition
        $this->assertTrue($workflow->isTransitionExist($field_value_new, $field_value_analyzed));
        $this->assertTrue($workflow->isTransitionExist($field_value_analyzed, $field_value_accepted));
        $this->assertTrue($workflow->isTransitionExist($field_value_analyzed, $field_value_rejected));
        $this->assertTrue($workflow->isTransitionExist($field_value_accepted, $field_value_fixed));
        $this->assertTrue($workflow->isTransitionExist($field_value_fixed, $field_value_tested));
        $this->assertTrue($workflow->isTransitionExist($field_value_tested, $field_value_deployed));

        $this->assertFalse($workflow->isTransitionExist($field_value_new, $field_value_tested));
        $this->assertFalse($workflow->isTransitionExist($field_value_new, $field_value_rejected));
        $this->assertFalse($workflow->isTransitionExist($field_value_analyzed, $field_value_new));
        $this->assertFalse($workflow->isTransitionExist($field_value_accepted, $field_value_rejected));

    }

    public function testExport() {

        $ft1 = new MockTracker_FormElement_Field_List();
        $ff2 = new MockTracker_FormElement_Field_List();
        $ft2 = new MockTracker_FormElement_Field_List();
        $ff3 = new MockTracker_FormElement_Field_List();
        $ft3 = new MockTracker_FormElement_Field_List();

        $ft1->setReturnValue('getId', 806);
        $ff2->setReturnValue('getId', 806);
        $ft2->setReturnValue('getId', 807);
        $ff3->setReturnValue('getId', 807);
        $ft3->setReturnValue('getId', 806);

        $t1 = new MockTransition();
        $t2 = new MockTransition();
        $t3 = new MockTransition();

        $t1->setReturnValue('getFieldValueFrom',  null);
        $t1->setReturnReference('getFieldValueTo',  $ft1);
        $t1->setReturnValue('getTransitionId',  1);

        $t2->setReturnReference('getFieldValueFrom',  $ff2);
        $t2->setReturnReference('getFieldValueTo',  $ft2);
        $t2->setReturnValue('getTransitionId',  2);

        $t3->setReturnReference('getFieldValueFrom',  $ff3);
        $t3->setReturnReference('getFieldValueTo',  $ft3);
        $t3->setReturnValue('getTransitionId',  3);

        $transitions = array($t1, $t2, $t3);
        $ugroups_transition = array('ugroup' => 'UGROUP_PROJECT_MEMBERS');

        $workflow = TestHelper::getPartialMock('Workflow', array('getPermissionsManager'));
        $workflow->__construct(1, 2, 103, 1, $transitions);

        $pm = new MockPermissionsManager($this);
        $pm->setReturnValue('getAuthorizedUgroups', $ugroups_transition);

        $workflow->setReturnValue('getPermissionsManager', $pm);

        $xml = simplexml_load_file(dirname(__FILE__) . '/_fixtures/importWorkflow.xml');
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');

        $array_xml_mapping = array('F32' => 103,
                                   'values' => array(
                                       'F32-V0' => 806,
                                       'F32-V1' => 807)
                                   );
        $workflow->exportToXML($root, $array_xml_mapping);

        $this->assertEqual((string)$xml->field_id['REF'], (string)$root->field_id['REF']);
        $this->assertEqual((int)$xml->is_used, (int)$root->is_used);
        $this->assertEqual(count($xml->transitions), count($root->transitions));
    }

    function testNonTransitionAlwaysExist() {
        $workflow = new WorkflowTestVersion(1, 2, 3, 1);
        $workflow->expectNever('getTransitions');
        $field_value = array();
        $this->assertTrue($workflow->isTransitionExist($field_value, $field_value));
    }
}

class Workflow_BeforeAfterTest extends TuleapTestCase {

    private $transition_null_to_open;
    private $transition_open_to_close;
    private $open_value_id = 801;
    private $close_value_id = 802;
    public function setUp() {
        parent::setUp();

        $this->status_field = new MockTracker_FormElement_Field_List();
        stub($this->status_field)->getId()->returns(103);

        $open_value  = mock('Tracker_FormElement_Field_List_Value');
        $close_value = mock('Tracker_FormElement_Field_List_Value');

        stub($open_value)->getId()->returns($this->open_value_id);
        stub($close_value)->getId()->returns($this->close_value_id);
        $this->current_user = mock('PFUser');

        $this->transition_null_to_open  = mock('Transition');
        $this->transition_open_to_close = mock('Transition');

        stub($this->transition_null_to_open)->getFieldValueFrom()->returns(null);
        stub($this->transition_null_to_open)->getFieldValueTo()->returns($open_value);
        stub($this->transition_open_to_close)->getFieldValueFrom()->returns($open_value);
        stub($this->transition_open_to_close)->getFieldValueTo()->returns($close_value);

        $workflow_id = 1;
        $tracker_id  = 2;
        $field_id    = 103;
        $is_used     = 1;
        $transitions = array($this->transition_null_to_open, $this->transition_open_to_close);
        $this->workflow    = new Workflow($workflow_id, $tracker_id, $field_id, $is_used, $transitions);
        $this->workflow->setField($this->status_field);

        $this->artifact = new MockTracker_Artifact();
    }

    function testBeforeShouldTriggerTransitionActions() {
        $changeset_value_list = new MockTracker_Artifact_ChangesetValue_List();
        $changeset_value_list->setReturnValue('getValue', array($this->open_value_id));

        $changeset = new MockTracker_Artifact_Changeset();
        $changeset->setReturnValue('getValue', $changeset_value_list, array($this->status_field));

        $this->artifact->setReturnValue('getLastChangeset', $changeset);

        $fields_data = array(
            '103' => "$this->close_value_id",
        );
        $this->transition_null_to_open->expectNever('before');
        $this->transition_open_to_close->expectOnce('before', array($fields_data, $this->current_user));
        $this->workflow->before($fields_data, $this->current_user, $this->artifact);
    }

    function testBeforeShouldTriggerTransitionActionsForNewArtifact() {
        $changeset = new MockTracker_Artifact_Changeset_Null();
        $this->artifact->setReturnValue('getLastChangeset', $changeset);

        $fields_data = array(
            '103' => "$this->open_value_id",
        );
        $this->transition_null_to_open->expectOnce('before', array($fields_data, $this->current_user));
        $this->transition_open_to_close->expectNever('before');
        $this->workflow->before($fields_data, $this->current_user, $this->artifact);
    }

    public function testAfterShouldTriggerTransitionActions() {
        $changeset_value_list = mock('Tracker_Artifact_ChangesetValue_List');
        stub($changeset_value_list)->getValue()->returns(array($this->open_value_id));

        $previous_changeset = mock('Tracker_Artifact_Changeset');
        stub($previous_changeset)->getValue($this->status_field)->returns($changeset_value_list);

        $new_changeset = mock('Tracker_Artifact_Changeset');

        $fields_data = array(
            '103' => "$this->close_value_id",
        );
        expect($this->transition_null_to_open)->after()->never();
        expect($this->transition_open_to_close)->after($new_changeset)->once();
        $this->workflow->after($fields_data, $new_changeset, $previous_changeset);
    }

    function testAfterShouldTriggerTransitionActionsForNewArtifact() {
        $previous_changeset = null;
        $new_changeset      = mock('Tracker_Artifact_Changeset');

        $fields_data = array(
            '103' => "$this->open_value_id",
        );
        expect($this->transition_null_to_open)->after($new_changeset)->once();
        expect($this->transition_open_to_close)->after()->never();
        $this->workflow->after($fields_data, $new_changeset, $previous_changeset);
    }
}

class Workflow_ExportToSOAP_BaseTest extends TuleapTestCase {

    protected $tracker_id = 123;
    protected $tracker;
    protected $workflow;
    protected $result;

    public function setUp() {
        parent::setUp();

        $this->tracker   = stub('Tracker')->getId()->returns($this->tracker_id);
        $tracker_factory = mock('TrackerFactory');
        TrackerFactory::setInstance($tracker_factory);
        stub($tracker_factory)->getTrackerById($this->tracker_id)->returns($this->tracker);

        $this->rules_manager = mock('Tracker_RulesManager');
        stub($this->rules_manager)->exportToSOAP()->returns('rules in soap format');
        stub($this->tracker)->getRulesManager()->returns($this->rules_manager);

        $field_list_value1 = aFieldListStaticValue()->withId(0)->build();
        $field_list_value2 = aFieldListStaticValue()->withId(11)->build();
        $field_list_value3 = aFieldListStaticValue()->withId(4)->build();
        $field_list_value4 = aFieldListStaticValue()->withId(5)->build();
        $field_list_value5 = aFieldListStaticValue()->withId(6)->build();
        $field_list_value6 = aFieldListStaticValue()->withId(89)->build();

        $transition1 = new Transition(0,1, $field_list_value1, $field_list_value2);
        $transition2 = new Transition(0,1, $field_list_value3, $field_list_value4);
        $transition3 = new Transition(0,1, $field_list_value5, $field_list_value6);

        $this->workflow = new Workflow(1, $this->tracker_id, 1, 1, array($transition1, $transition2, $transition3));
    }

    public function tearDown() {
        TrackerFactory::clearInstance();
        parent::tearDown();
    }
}

class Workflow_ExportToSOAPTest extends Workflow_ExportToSOAP_BaseTest {

    public function itExportsTheFieldId() {
        $result = $this->workflow->exportToSOAP();
        $expected_field_id = 1;
        $this->assertEqual($result['field_id'], $expected_field_id);
    }

    public function itExportsTheIsUsedValue() {
        $result = $this->workflow->exportToSOAP();
        $expected_is_used = 1;
        $this->assertEqual($result['is_used'], $expected_is_used);
    }
}

class Workflow_ExportToSOAP_transitionsTest extends Workflow_ExportToSOAP_BaseTest {

    public function itExportsAllTheTransitions() {
        $result = $this->workflow->exportToSOAP();
        $expected_transisitions = array(
            '0' => array ('from_id' => 0, 'to_id'=> 11),
            '1' => array ('from_id' => 4, 'to_id'=> 5),
            '2' => array ('from_id' => 6, 'to_id'=> 89)
        );

        $this->assertEqual($result['transitions'], $expected_transisitions);
    }

    public function itExportsEmptyTransitionWhenWorkflowDoesntHaveTransition() {
        $workflow = new Workflow(1, $this->tracker_id, 1, 1, array());
        $result   = $workflow->exportToSOAP();
        $this->assertArrayEmpty($result['transitions']);
    }
}

class Workflow_ExportToSOAP_rulesTest extends Workflow_ExportToSOAP_BaseTest {

    public function itExportsRules() {
        $result = $this->workflow->exportToSOAP();

        $this->assertEqual($result['rules'], 'rules in soap format');
    }
}

class Workflow_validateTest extends TuleapTestCase {

    public function itReturnsTrueIfWorkflowIsNotEnabled() {
        $is_used     = 0;
        $workflow    = new Workflow(1,1,1,$is_used, array());
        $fields_data = array();
        $artifact    = mock('Tracker_Artifact');

        $this->assertTrue($workflow->validate($fields_data, $artifact));
    }

    public function itReturnsFalseIfWorkflowIsEnabledAndTransitionNotValid() {
        $value_from  = null;
        $value_to    = stub('Tracker_FormElement_Field_List_Value')->getId()->returns(66);
        $transition  = mock('Transition');
        stub($transition)->getFieldValueFrom()->returns($value_from);
        stub($transition)->getFieldValueTo()->returns($value_to);
        $is_used     = 1;
        $field_id    = 42;
        $workflow    = new Workflow(1, 1, $field_id, $is_used, array($transition));
        $fields_data = array($field_id => 66);
        $artifact    = mock('Tracker_Artifact');

        expect($transition)->validate()->once()->returns(false);
        $this->assertFalse($workflow->validate($fields_data, $artifact));
    }
}

class Workflow_validateGlobalRulesTest extends TuleapTestCase {

    private $tracker_id  = 123;

    public function setUp() {
        parent::setUp();

        $tracker         = mock('Tracker');
        $tracker_factory = mock('TrackerFactory');
        TrackerFactory::setInstance($tracker_factory);
        stub($tracker_factory)->getTrackerByid($this->tracker_id)->returns($tracker);

        $this->rules_manager = mock ('Tracker_RulesManager');
        stub($tracker)->getRulesManager()->returns($this->rules_manager);
    }

    public function tearDown() {
        TrackerFactory::clearInstance();
        parent::tearDown();
    }

    public function itDelegatesValidationToRulesManager() {
        $fields_data = array();

        expect($this->rules_manager)->validate($this->tracker_id, $fields_data)->once()->returns(true);
        $workflow = new workflow(1, $this->tracker_id, 1, 1, array());
        $this->assertTrue($workflow->validateGlobalRules($fields_data));
    }
}
?>