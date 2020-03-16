<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

use Tuleap\Tracker\Workflow\WorkflowBackendLogger;

require_once __DIR__ . '/../bootstrap.php';

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

class WorkflowTest extends TuleapTestCase
{

    /** @var XML_Security */
    private $xml_security;
    private $transition_factory_test;
    private $transition_factory_instance;

    public function setUp()
    {
        parent::setUp();

        $this->xml_security = new XML_Security();
        $this->xml_security->enableExternalLoadOfEntities();
        $this->transition_factory_instance = \Mockery::spy(TransitionFactory::class);
        $this->transition_factory_test     = new class ($this->transition_factory_instance) extends TransitionFactory
        {
            public function __construct($transition_factory_instance)
            {
                parent::$_instance = $transition_factory_instance;
            }

            public function clearInstance() : void
            {
                parent::$_instance = null;
            }
        };
    }

    public function tearDown()
    {
        $this->xml_security->disableExternalLoadOfEntities();
        $this->transition_factory_test->clearInstance();

        parent::tearDown();
    }

    public function testEmptyWorkflow()
    {
        $workflow = new WorkflowTestVersion(1, 2, 3, 1);
        $workflow->setReturnValue('getTransitions', array());
        $this->assertNotNull($workflow->getTransitions());
        $this->assertEqual(count($workflow->getTransitions()), 0);

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

    public function testUseCaseBug()
    {
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

        $t_new_analyzed      = new Transition(1, 2, $field_value_new, $field_value_analyzed);
        $t_analyzed_accepted = new Transition(1, 2, $field_value_analyzed, $field_value_accepted);
        $t_analyzed_rejected = new Transition(1, 2, $field_value_analyzed, $field_value_rejected);
        $t_accepted_fixed    = new Transition(1, 2, $field_value_accepted, $field_value_fixed);
        $t_fixed_tested      = new Transition(1, 2, $field_value_fixed, $field_value_tested);
        $t_tested_deployed   = new Transition(1, 2, $field_value_tested, $field_value_deployed);

        $transitions = array($t_new_analyzed,
            $t_analyzed_accepted,
            $t_analyzed_rejected,
            $t_accepted_fixed,
            $t_fixed_tested,
            $t_tested_deployed);

        $workflow = aWorkflow()->withTransitions($transitions)->build();

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

    public function testExport()
    {
        $this->transition_factory_instance->shouldReceive('getTransitions')->andReturn([]);

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

        $t1->setReturnValue('getFieldValueFrom', null);
        $t1->setReturnReference('getFieldValueTo', $ft1);
        $t1->setReturnValue('getTransitionId', 1);

        $t2->setReturnReference('getFieldValueFrom', $ff2);
        $t2->setReturnReference('getFieldValueTo', $ft2);
        $t2->setReturnValue('getTransitionId', 2);

        $t3->setReturnReference('getFieldValueFrom', $ff3);
        $t3->setReturnReference('getFieldValueTo', $ft3);
        $t3->setReturnValue('getTransitionId', 3);

        $transitions = array($t1, $t2, $t3);
        $ugroups_transition = array('ugroup' => 'UGROUP_PROJECT_MEMBERS');

        $global_rules_manager  = mock('Tracker_RulesManager');
        $trigger_rules_manager = mock('Tracker_Workflow_Trigger_RulesManager');
        $logger                = new WorkflowBackendLogger(Mockery::spy(\Psr\Log\LoggerInterface::class), \Psr\Log\LogLevel::DEBUG);

        $workflow = TestHelper::getPartialMock('Workflow', array('getPermissionsManager'));
        $workflow->__construct($global_rules_manager, $trigger_rules_manager, $logger, 1, 2, 103, 1, $transitions);

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

        $this->assertEqual((string) $xml->field_id['REF'], (string) $root->field_id['REF']);
        $this->assertEqual((int) $xml->is_used, (int) $root->is_used);
        $this->assertEqual(count($xml->transitions), count($root->transitions));
    }

    public function testNonTransitionAlwaysExist()
    {
        $workflow = new WorkflowTestVersion(1, 2, 3, 1);
        $workflow->expectNever('getTransitions');
        $field_value = array();
        $this->assertTrue($workflow->isTransitionExist($field_value, $field_value));
    }
}

class Workflow_BeforeAfterTest extends TuleapTestCase
{

    private $transition_null_to_open;
    private $transition_open_to_close;
    private $open_value_id = 801;
    private $close_value_id = 802;
    private $trigger_rules_manager;

    public function setUp()
    {
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

        $this->trigger_rules_manager = mock('Tracker_Workflow_Trigger_RulesManager');

        $workflow_id = 1;
        $tracker_id  = 2;
        $field_id    = 103;
        $is_used     = 1;
        $is_legacy   = 0;
        $is_advanced = 1;
        $transitions = array($this->transition_null_to_open, $this->transition_open_to_close);
        $this->workflow = partial_mock(
            'Workflow',
            array('getTracker'),
            array(
                mock('Tracker_RulesManager'),
                $this->trigger_rules_manager,
                new WorkflowBackendLogger(Mockery::spy(\Psr\Log\LoggerInterface::class), \Psr\Log\LogLevel::DEBUG),
                $workflow_id,
                $tracker_id,
                $field_id,
                $is_used,
                $is_advanced,
                $is_legacy,
                $transitions
            )
        );

        $this->unused_workflow = partial_mock(
            'Workflow',
            array('getTracker'),
            array(
                mock('Tracker_RulesManager'),
                $this->trigger_rules_manager,
                new WorkflowBackendLogger(Mockery::spy(\Psr\Log\LoggerInterface::class), \Psr\Log\LogLevel::DEBUG),
                $workflow_id,
                $tracker_id,
                $field_id,
                false,
                $is_advanced,
                $is_legacy,
                $transitions
            )
        );

        $this->unused_legacy_workflow = partial_mock(
            'Workflow',
            array('getTracker'),
            array(
                mock('Tracker_RulesManager'),
                $this->trigger_rules_manager,
                new WorkflowBackendLogger(Mockery::spy(\Psr\Log\LoggerInterface::class), \Psr\Log\LogLevel::DEBUG),
                $workflow_id,
                $tracker_id,
                $field_id,
                false,
                $is_advanced,
                true,
                $transitions
            )
        );

        $this->workflow->setField($this->status_field);
        $this->unused_workflow->setField($this->status_field);
        $this->unused_legacy_workflow->setField($this->status_field);

        $this->artifact = new MockTracker_Artifact();

        $this->event_manager = mock(EventManager::class);
        EventManager::setInstance($this->event_manager);
    }

    public function tearDown()
    {
        EventManager::clearInstance();

        parent::tearDown();
    }

    public function testBeforeShouldTriggerTransitionActions()
    {
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

    public function testBeforeShouldTriggerTransitionActionsForNewArtifact()
    {
        $changeset = new MockTracker_Artifact_Changeset_Null();
        $this->artifact->setReturnValue('getLastChangeset', $changeset);

        $fields_data = array(
            '103' => "$this->open_value_id",
        );
        $this->transition_null_to_open->expectOnce('before', array($fields_data, $this->current_user));
        $this->transition_open_to_close->expectNever('before');
        $this->workflow->before($fields_data, $this->current_user, $this->artifact);
    }

    public function testBeforeShouldDoNothingButProcessTheEventIfWorkflowIsNotUsedAndIsNotLegacy()
    {
        $changeset = new MockTracker_Artifact_Changeset_Null();
        $this->artifact->setReturnValue('getLastChangeset', $changeset);

        $fields_data = array(
            '103' => "$this->open_value_id",
        );

        expect($this->transition_null_to_open)->before()->never();
        expect($this->transition_open_to_close)->before()->never();
        expect($this->event_manager)->processEvent()->once();

        $this->unused_workflow->before($fields_data, $this->current_user, $this->artifact);
    }

    public function testBeforeShouldProcessActionsIfWorkflowIsNotUsedAndIsLegacy()
    {
        $changeset = new MockTracker_Artifact_Changeset_Null();
        $this->artifact->setReturnValue('getLastChangeset', $changeset);

        $fields_data = array(
            '103' => "$this->open_value_id",
        );

        expect($this->transition_null_to_open)->before()->once();
        expect($this->event_manager)->processEvent()->once();

        $this->unused_legacy_workflow->before($fields_data, $this->current_user, $this->artifact);
    }

    public function testAfterShouldTriggerTransitionActions()
    {
        $changeset_value_list = mock('Tracker_Artifact_ChangesetValue_List');
        stub($changeset_value_list)->getValue()->returns(array($this->open_value_id));

        $previous_changeset = mock('Tracker_Artifact_Changeset');
        stub($previous_changeset)->getValue($this->status_field)->returns($changeset_value_list);

        $new_changeset = mock('Tracker_Artifact_Changeset');
        stub($new_changeset)->getArtifact()->returns(mock('Tracker_Artifact'));

        $fields_data = array(
            '103' => "$this->close_value_id",
        );
        expect($this->transition_null_to_open)->after()->never();
        expect($this->transition_open_to_close)->after($new_changeset)->once();
        $this->workflow->after($fields_data, $new_changeset, $previous_changeset);
    }

    public function testAfterShouldTriggerTransitionActionsForNewArtifact()
    {
        $previous_changeset = null;
        $new_changeset      = mock('Tracker_Artifact_Changeset');
        stub($new_changeset)->getArtifact()->returns(mock('Tracker_Artifact'));

        $fields_data = array(
            '103' => "$this->open_value_id",
        );
        expect($this->transition_null_to_open)->after($new_changeset)->once();
        expect($this->transition_open_to_close)->after()->never();
        $this->workflow->after($fields_data, $new_changeset, $previous_changeset);
    }

    public function itShouldProcessTriggers()
    {
        $previous_changeset = null;
        $new_changeset      = mock('Tracker_Artifact_Changeset');
        stub($new_changeset)->getArtifact()->returns(mock('Tracker_Artifact'));
        $fields_data        = array();

        expect($this->trigger_rules_manager)->processTriggers($new_changeset)->once();

        $this->workflow->after($fields_data, $new_changeset, $previous_changeset);
    }

    public function testAfterShouldDoNothingButTriggersIfWorkflowIsNotUsedAndIsNotLegacy()
    {
        $previous_changeset = null;
        $new_changeset      = mock('Tracker_Artifact_Changeset');
        stub($new_changeset)->getArtifact()->returns(mock('Tracker_Artifact'));

        $fields_data = array(
            '103' => "$this->open_value_id",
        );

        expect($this->transition_null_to_open)->after()->never();
        expect($this->transition_open_to_close)->after()->never();
        expect($this->event_manager)->processEvent()->never();
        expect($this->trigger_rules_manager)->processTriggers()->once();

        $this->unused_workflow->after($fields_data, $new_changeset, $previous_changeset);
    }

    public function testAfterShouldProcessActionsIfWorkflowIsNotUsedAndIsLegacy()
    {
        $previous_changeset = null;
        $new_changeset      = mock('Tracker_Artifact_Changeset');
        stub($new_changeset)->getArtifact()->returns(mock('Tracker_Artifact'));

        $fields_data = array(
            '103' => "$this->open_value_id",
        );

        expect($this->transition_null_to_open)->after()->once();
        expect($this->trigger_rules_manager)->processTriggers()->once();

        $this->unused_legacy_workflow->after($fields_data, $new_changeset, $previous_changeset);
    }
}

class Workflow_validateTest extends TuleapTestCase
{

    public function itRaisesNoExceptionIfWorkflowIsNotEnabled()
    {
        $is_used     = 0;
        $workflow    = aWorkflow()->withIsUsed($is_used)->build();
        $fields_data = array();
        $artifact    = mock('Tracker_Artifact');

        try {
            $workflow->validate($fields_data, $artifact, '');
        } catch (Exception $e) {
            $this->fail('Should not receive an exception');
        }
    }

    public function itRaisesExceptionIfWorkflowIsEnabledAndTransitionNotValid()
    {
        $value_from  = null;
        $value_to    = stub('Tracker_FormElement_Field_List_Value')->getId()->returns(66);
        $transition  = mock('Transition');
        stub($transition)->getFieldValueFrom()->returns($value_from);
        stub($transition)->getFieldValueTo()->returns($value_to);
        $is_used     = 1;
        $field_id    = 42;
        $workflow    = aWorkflow()->withFieldId($field_id)->withTransitions(array($transition))->build();
        $fields_data = array($field_id => 66);
        $artifact    = mock('Tracker_Artifact');

        expect($transition)->validate()->once()->returns(false);
        $this->expectException(new Tracker_Workflow_Transition_InvalidConditionForTransitionException($transition));

        $workflow->validate($fields_data, $artifact, '');
    }
}

class Workflow_checkGlobalRulesTest extends TuleapTestCase
{

    private $tracker_id  = 123;

    public function setUp()
    {
        parent::setUp();
        $this->rules_manager = mock('Tracker_RulesManager');
    }

    public function itDelegatesValidationToRulesManager()
    {
        $fields_data = array();

        expect($this->rules_manager)->validate($this->tracker_id, $fields_data)->once()->returns(true);
        $workflow = aWorkflow()
            ->withGlobalRulesManager($this->rules_manager)
            ->withTrackerId($this->tracker_id)
            ->build();

        try {
            $workflow->checkGlobalRules($fields_data);
        } catch (Exception $e) {
            $this->fail('Should not throw an exception: ' . get_class($e));
        }
    }
}

class Workflow_DisableTest extends TuleapTestCase
{

    private $transition;
    private $field_id = 42;
    private $workflow;
    private $artifact;
    private $rules_manager;
    private $tracker_id  = 123;

    public function setUp()
    {
        parent::setUp();

        $value_from  = null;
        $value_to    = stub('Tracker_FormElement_Field_List_Value')->getId()->returns(66);
        $this->transition  = mock('Transition');
        stub($this->transition)->getFieldValueFrom()->returns($value_from);
        stub($this->transition)->getFieldValueTo()->returns($value_to);
        stub($this->transition)->validate()->returns(false);
        $this->workflow    = aWorkflow()->withFieldId($this->field_id)->withTransitions(array($this->transition))->build();
        $this->artifact    = mock('Tracker_Artifact');
        $this->rules_manager = mock('Tracker_RulesManager');
    }

    public function itIsNotValidWhenTheWOrkflowIsEnabled()
    {
        $fields_data = array($this->field_id => 66);

        $this->expectException(new Tracker_Workflow_Transition_InvalidConditionForTransitionException($this->transition));
        $this->workflow->validate($fields_data, $this->artifact, '');
    }

    public function itDisablesTheValidationOfTransitions()
    {
        $this->workflow->disable();

        $fields_data = array($this->field_id => 66);

        expect($this->transition)->validate()->never();

        $this->workflow->validate($fields_data, $this->artifact, '');
    }

    public function itDisablesTheGlobalRulesValidation()
    {
        $fields_data = array();

        stub($this->rules_manager)->validate()->returns(false);
        $workflow = aWorkflow()
            ->withGlobalRulesManager($this->rules_manager)
            ->withTrackerId($this->tracker_id)
            ->build();

        $workflow->disable();

        try {
            $workflow->checkGlobalRules($fields_data);
        } catch (Exception $e) {
            $this->fail('Should not throw an exception: ' . get_class($e));
        }
    }
}
