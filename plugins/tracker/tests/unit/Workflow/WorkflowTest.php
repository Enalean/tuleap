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

declare(strict_types=1);

use Tuleap\Tracker\Workflow\Transition\TransitionRetriever;
use Tuleap\Tracker\Workflow\WorkflowBackendLogger;

final class WorkflowTest extends \Tuleap\Test\PHPUnit\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $transition_factory_test;
    private TransitionFactory|\Mockery\LegacyMockInterface|\Mockery\MockInterface $transition_factory_instance;
    private \Mockery\LegacyMockInterface|Transition|\Mockery\MockInterface $transition_null_to_open;
    private \Mockery\LegacyMockInterface|Transition|\Mockery\MockInterface $transition_open_to_close;
    private int $open_value_id  = 801;
    private int $close_value_id = 802;
    private \Mockery\LegacyMockInterface|Tracker_Workflow_Trigger_RulesManager|\Mockery\MockInterface $trigger_rules_manager;
    private Tracker_FormElement_Field_Selectbox $status_field;
    private PFUser $current_user;
    private Workflow|\Mockery\LegacyMockInterface|\Mockery\MockInterface $workflow;
    private Workflow|\Mockery\LegacyMockInterface|\Mockery\MockInterface $unused_workflow;
    private Workflow|\Mockery\LegacyMockInterface|\Mockery\MockInterface $unused_legacy_workflow;
    private \Tuleap\Tracker\Artifact\Artifact|\Mockery\MockInterface|\Mockery\LegacyMockInterface $artifact;
    private EventManager|\Mockery\LegacyMockInterface|\Mockery\MockInterface $event_manager;


    protected function setUp(): void
    {
        $this->transition_factory_instance = \Mockery::spy(TransitionFactory::class);
        $this->transition_factory_test     = new class ($this->transition_factory_instance) extends TransitionFactory {
            public function __construct($transition_factory_instance)
            {
                parent::$_instance = $transition_factory_instance;
            }

            public function clearInstance(): void
            {
                parent::$_instance = null;
            }
        };

        $this->status_field = \Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder::aListField(103)->build();

        $open_value  = \Mockery::spy(\Tracker_FormElement_Field_List_Value::class);
        $close_value = \Mockery::spy(\Tracker_FormElement_Field_List_Value::class);

        $open_value->shouldReceive('getId')->andReturns($this->open_value_id);
        $close_value->shouldReceive('getId')->andReturns($this->close_value_id);
        $this->current_user = \Tuleap\Test\Builders\UserTestBuilder::anActiveUser()->build();

        $this->transition_null_to_open  = \Mockery::spy(\Transition::class);
        $this->transition_open_to_close = \Mockery::spy(\Transition::class);

        $this->transition_null_to_open->shouldReceive('getFieldValueFrom')->andReturns(null);
        $this->transition_null_to_open->shouldReceive('getFieldValueTo')->andReturns($open_value);
        $this->transition_open_to_close->shouldReceive('getFieldValueFrom')->andReturns($open_value);
        $this->transition_open_to_close->shouldReceive('getFieldValueTo')->andReturns($close_value);

        $this->trigger_rules_manager = \Mockery::spy(\Tracker_Workflow_Trigger_RulesManager::class);
        $workflow_id                 = 1;
        $tracker_id                  = 2;
        $field_id                    = 103;
        $is_used                     = 1;
        $is_legacy                   = 0;
        $is_advanced                 = 1;
        $transitions                 = [$this->transition_null_to_open, $this->transition_open_to_close];
        $this->workflow              = Mockery::mock(
            Workflow::class . '[getTracker]',
            [
                Mockery::mock(Tracker_RulesManager::class),
                $this->trigger_rules_manager,
                new WorkflowBackendLogger(Mockery::spy(\Psr\Log\LoggerInterface::class), \Psr\Log\LogLevel::DEBUG),
                $workflow_id,
                $tracker_id,
                $field_id,
                $is_used,
                $is_advanced,
                $is_legacy,
                $transitions,
            ]
        );

        $this->unused_workflow = Mockery::mock(
            Workflow::class . '[getTracker]',
            [
                Mockery::mock(Tracker_RulesManager::class),
                $this->trigger_rules_manager,
                new WorkflowBackendLogger(Mockery::spy(\Psr\Log\LoggerInterface::class), \Psr\Log\LogLevel::DEBUG),
                $workflow_id,
                $tracker_id,
                $field_id,
                false,
                $is_advanced,
                $is_legacy,
                $transitions,
            ]
        );

        $this->unused_legacy_workflow = Mockery::mock(
            Workflow::class . '[getTracker]',
            [
                Mockery::mock(Tracker_RulesManager::class),
                $this->trigger_rules_manager,
                new WorkflowBackendLogger(Mockery::spy(\Psr\Log\LoggerInterface::class), \Psr\Log\LogLevel::DEBUG),
                $workflow_id,
                $tracker_id,
                $field_id,
                false,
                $is_advanced,
                true,
                $transitions,
            ]
        );

        $this->workflow->setField($this->status_field);
        $this->unused_workflow->setField($this->status_field);
        $this->unused_legacy_workflow->setField($this->status_field);

        $this->artifact = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);

        $this->event_manager = \Mockery::spy(EventManager::class);
        EventManager::setInstance($this->event_manager);
    }

    protected function tearDown(): void
    {
        $this->transition_factory_test->clearInstance();
        EventManager::clearInstance();
    }

    public function testEmptyWorkflow(): void
    {
        $workflow = \Mockery::mock(\Workflow::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $workflow->shouldReceive('getTransitions')->andReturns([]);
        $this->assertNotNull($workflow->getTransitions());
        $this->assertEquals(count($workflow->getTransitions()), 0);

        $field_value_new      = $this->buildFieldValue(2066);
        $field_value_analyzed = $this->buildFieldValue(2067);

        // workflow is empty, no transition exists
        $this->assertFalse($workflow->isTransitionExist($field_value_new, $field_value_analyzed));
        $this->assertFalse($workflow->hasTransitions());
    }

    public function testUseCaseBug(): void
    {
        $field_value_new      = $this->buildFieldValue(2066);
        $field_value_analyzed = $this->buildFieldValue(2067);
        $field_value_accepted = $this->buildFieldValue(2068);
        $field_value_rejected = $this->buildFieldValue(2069);
        $field_value_fixed    = $this->buildFieldValue(2070);
        $field_value_tested   = $this->buildFieldValue(2071);
        $field_value_deployed = $this->buildFieldValue(2072);

        $t_new_analyzed      = new Transition(1, 2, $field_value_new, $field_value_analyzed);
        $t_analyzed_accepted = new Transition(1, 2, $field_value_analyzed, $field_value_accepted);
        $t_analyzed_rejected = new Transition(1, 2, $field_value_analyzed, $field_value_rejected);
        $t_accepted_fixed    = new Transition(1, 2, $field_value_accepted, $field_value_fixed);
        $t_fixed_tested      = new Transition(1, 2, $field_value_fixed, $field_value_tested);
        $t_tested_deployed   = new Transition(1, 2, $field_value_tested, $field_value_deployed);

        $transitions = [$t_new_analyzed,
            $t_analyzed_accepted,
            $t_analyzed_rejected,
            $t_accepted_fixed,
            $t_fixed_tested,
            $t_tested_deployed,
        ];

        $workflow = new Workflow(
            Mockery::spy(Tracker_RulesManager::class),
            Mockery::spy(Tracker_Workflow_Trigger_RulesManager::class),
            new WorkflowBackendLogger(Mockery::spy(\Psr\Log\LoggerInterface::class), \Psr\Log\LogLevel::DEBUG),
            1,
            2,
            3,
            true,
            true,
            false,
            $transitions
        );

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

    public function testExport(): void
    {
        $this->transition_factory_instance->shouldReceive('getTransitions')->andReturn([]);

        $ft1 = \Mockery::spy(\Tracker_FormElement_Field_List::class);
        $ff2 = \Mockery::spy(\Tracker_FormElement_Field_List::class);
        $ft2 = \Mockery::spy(\Tracker_FormElement_Field_List::class);
        $ff3 = \Mockery::spy(\Tracker_FormElement_Field_List::class);
        $ft3 = \Mockery::spy(\Tracker_FormElement_Field_List::class);

        $ft1->shouldReceive('getId')->andReturns(806);
        $ff2->shouldReceive('getId')->andReturns(806);
        $ft2->shouldReceive('getId')->andReturns(807);
        $ff3->shouldReceive('getId')->andReturns(807);
        $ft3->shouldReceive('getId')->andReturns(806);

        $t1 = \Mockery::spy(\Transition::class);
        $t2 = \Mockery::spy(\Transition::class);
        $t3 = \Mockery::spy(\Transition::class);

        $t1->shouldReceive('getFieldValueFrom')->andReturns(null);
        $t1->shouldReceive('getFieldValueTo')->andReturns($ft1);
        $t1->shouldReceive('getTransitionId')->andReturns(1);

        $t2->shouldReceive('getFieldValueFrom')->andReturns($ff2);
        $t2->shouldReceive('getFieldValueTo')->andReturns($ft2);
        $t2->shouldReceive('getTransitionId')->andReturns(2);

        $t3->shouldReceive('getFieldValueFrom')->andReturns($ff3);
        $t3->shouldReceive('getFieldValueTo')->andReturns($ft3);
        $t3->shouldReceive('getTransitionId')->andReturns(3);

        $transitions        = [$t1, $t2, $t3];
        $ugroups_transition = ['ugroup' => 'UGROUP_PROJECT_MEMBERS'];

        $global_rules_manager  = \Mockery::spy(\Tracker_RulesManager::class);
        $trigger_rules_manager = \Mockery::spy(\Tracker_Workflow_Trigger_RulesManager::class);
        $logger                = new WorkflowBackendLogger(Mockery::spy(\Psr\Log\LoggerInterface::class), \Psr\Log\LogLevel::DEBUG);

        $workflow = \Mockery::mock(\Workflow::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $workflow->__construct($global_rules_manager, $trigger_rules_manager, $logger, 1, 2, 103, 1, false, false, $transitions);

        $pm = \Mockery::spy(\PermissionsManager::class);
        $pm->shouldReceive('getAuthorizedUgroups')->andReturns($ugroups_transition);

        $workflow->shouldReceive('getPermissionsManager')->andReturns($pm);

        $xml  = simplexml_load_string(file_get_contents(__DIR__ . '/_fixtures/importWorkflow.xml'));
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');

        $array_xml_mapping = ['F32' => 103,
            'values' => [
                'F32-V0' => 806,
                'F32-V1' => 807,
            ],
        ];
        $workflow->exportToXML($root, $array_xml_mapping);

        $this->assertEquals((string) $xml->field_id['REF'], (string) $root->field_id['REF']);
        $this->assertEquals((int) $xml->is_used, (int) $root->is_used);
        $this->assertEquals(count($xml->transitions), count($root->transitions));
    }

    public function testNonTransitionAlwaysExist(): void
    {
        $workflow = \Mockery::mock(\Workflow::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $workflow->shouldReceive('getTransitions')->never();
        $field_value = [];
        $this->assertTrue($workflow->isTransitionExist($field_value, $field_value));
    }

    private function buildFieldValue(int $id): \Tracker_FormElement_Field_List_Value
    {
        return new class ($id) extends \Tracker_FormElement_Field_List_Value
        {
            public function getJsonId(): string
            {
                return 'test';
            }

            public function __toString(): string
            {
                return 'test';
            }

            public function getLabel(): string
            {
                return 'test';
            }
        };
    }

    public function testBeforeShouldTriggerTransitionActions(): void
    {
        $changeset_value_list = \Mockery::spy(\Tracker_Artifact_ChangesetValue_List::class);
        $changeset_value_list->shouldReceive('getValue')->andReturns([$this->open_value_id]);

        $changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive('getValue')->with($this->status_field)->andReturns($changeset_value_list);

        $this->artifact->shouldReceive('getLastChangeset')->andReturns($changeset);

        $fields_data = [
            '103' => "$this->close_value_id",
        ];
        $this->transition_null_to_open->shouldReceive('before')->never();
        $this->transition_open_to_close->shouldReceive('before')->with($fields_data, $this->current_user)->once();
        $this->workflow->before($fields_data, $this->current_user, $this->artifact);
    }

    public function testBeforeShouldTriggerTransitionActionsForNewArtifact(): void
    {
        $changeset = \Mockery::spy(\Tracker_Artifact_Changeset_Null::class);
        $this->artifact->shouldReceive('getLastChangeset')->andReturns($changeset);

        $fields_data = [
            '103' => "$this->open_value_id",
        ];
        $this->transition_null_to_open->shouldReceive('before')->with($fields_data, $this->current_user)->once();
        $this->transition_open_to_close->shouldReceive('before')->never();
        $this->workflow->before($fields_data, $this->current_user, $this->artifact);
    }

    public function testBeforeShouldDoNothingButProcessTheEventIfWorkflowIsNotUsedAndIsNotLegacy(): void
    {
        $changeset = \Mockery::spy(\Tracker_Artifact_Changeset_Null::class);
        $this->artifact->shouldReceive('getLastChangeset')->andReturns($changeset);

        $fields_data = [
            '103' => "$this->open_value_id",
        ];

        $this->transition_null_to_open->shouldReceive('before')->never();
        $this->transition_open_to_close->shouldReceive('before')->never();
        $this->event_manager->shouldReceive('processEvent')->once();

        $this->unused_workflow->before($fields_data, $this->current_user, $this->artifact);
    }

    public function testBeforeShouldProcessActionsIfWorkflowIsNotUsedAndIsLegacy(): void
    {
        $changeset = \Mockery::spy(\Tracker_Artifact_Changeset_Null::class);
        $this->artifact->shouldReceive('getLastChangeset')->andReturns($changeset);

        $fields_data = [
            '103' => "$this->open_value_id",
        ];

        $this->transition_null_to_open->shouldReceive('before')->once();
        $this->event_manager->shouldReceive('processEvent')->once();

        $this->unused_legacy_workflow->before($fields_data, $this->current_user, $this->artifact);
    }

    public function testAfterShouldTriggerTransitionActions(): void
    {
        $changeset_value_list = \Mockery::spy(\Tracker_Artifact_ChangesetValue_List::class);
        $changeset_value_list->shouldReceive('getValue')->andReturns([$this->open_value_id]);

        $previous_changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $previous_changeset->shouldReceive('getValue')->with($this->status_field)->andReturns($changeset_value_list);

        $new_changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $new_changeset->shouldReceive('getArtifact')->andReturns(\Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class));

        $fields_data = [
            '103' => "$this->close_value_id",
        ];
        $this->transition_null_to_open->shouldReceive('after')->never();
        $this->transition_open_to_close->shouldReceive('after')->with($new_changeset)->once();
        $this->workflow->after($fields_data, $new_changeset, $previous_changeset);
    }

    public function testAfterShouldTriggerTransitionActionsForNewArtifact(): void
    {
        $previous_changeset = null;
        $new_changeset      = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $new_changeset->shouldReceive('getArtifact')->andReturns(\Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class));

        $fields_data = [
            '103' => "$this->open_value_id",
        ];
        $this->transition_null_to_open->shouldReceive('after')->with($new_changeset)->once();
        $this->transition_open_to_close->shouldReceive('after')->never();
        $this->workflow->after($fields_data, $new_changeset, $previous_changeset);
    }

    public function testItShouldProcessTriggers(): void
    {
        $previous_changeset = null;
        $new_changeset      = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $new_changeset->shouldReceive('getArtifact')->andReturns(\Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class));
        $fields_data = [];

        $this->trigger_rules_manager->shouldReceive('processTriggers')->with($new_changeset)->once();

        $this->workflow->after($fields_data, $new_changeset, $previous_changeset);
    }

    public function testAfterShouldDoNothingButTriggersIfWorkflowIsNotUsedAndIsNotLegacy(): void
    {
        $previous_changeset = null;
        $new_changeset      = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $new_changeset->shouldReceive('getArtifact')->andReturns(\Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class));

        $fields_data = [
            '103' => "$this->open_value_id",
        ];

        $this->transition_null_to_open->shouldReceive('after')->never();
        $this->transition_open_to_close->shouldReceive('after')->never();
        $this->event_manager->shouldReceive('processEvent')->never();
        $this->trigger_rules_manager->shouldReceive('processTriggers')->once();

        $this->unused_workflow->after($fields_data, $new_changeset, $previous_changeset);
    }

    public function testAfterShouldProcessActionsIfWorkflowIsNotUsedAndIsLegacy(): void
    {
        $previous_changeset = null;
        $new_changeset      = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $new_changeset->shouldReceive('getArtifact')->andReturns(\Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class));

        $fields_data = [
            '103' => "$this->open_value_id",
        ];

        $this->transition_null_to_open->shouldReceive('after')->once();
        $this->trigger_rules_manager->shouldReceive('processTriggers')->once();

        $this->unused_legacy_workflow->after($fields_data, $new_changeset, $previous_changeset);
    }

    public function testItRaisesNoExceptionIfWorkflowIsNotEnabled(): void
    {
        $this->expectNotToPerformAssertions();
        $fields_data = [];
        $artifact    = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);

        $this->unused_workflow->validate($fields_data, $artifact, '', $this->current_user);
    }

    public function testItRaisesExceptionIfWorkflowIsEnabledAndTransitionNotValid(): void
    {
        $value_from = null;
        $value_to   = \Mockery::spy(\Tracker_FormElement_Field_List_Value::class)->shouldReceive('getId')->andReturns(66)->getMock();
        $transition = \Mockery::spy(\Transition::class);
        $transition->shouldReceive('getFieldValueFrom')->andReturns($value_from);
        $transition->shouldReceive('getFieldValueTo')->andReturns($value_to);
        $is_used  = 1;
        $field_id = 42;
        $workflow = Mockery::mock(
            Workflow::class,
            [
                Mockery::mock(Tracker_RulesManager::class),
                $this->trigger_rules_manager,
                new WorkflowBackendLogger(Mockery::spy(\Psr\Log\LoggerInterface::class), \Psr\Log\LogLevel::DEBUG),
                1,
                2,
                $field_id,
                $is_used,
                false,
                false,
                [$transition],
            ]
        )->makePartial()->shouldAllowMockingProtectedMethods();

        $fields_data = [$field_id => 66];
        $artifact    = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);

        $retriever = Mockery::mock(TransitionRetriever::class);
        $retriever->shouldReceive('retrieveTransition')->andReturn($transition);


        $workflow->shouldReceive('getTransitionRetriever')->andReturn($retriever);

        $transition->shouldReceive('validate')->once()->andReturns(false);
        $this->expectExceptionObject(new Tracker_Workflow_Transition_InvalidConditionForTransitionException($transition));

        $workflow->validate($fields_data, $artifact, '', $this->current_user);
    }

    public function testItDelegatesValidationToRulesManager(): void
    {
        $fields_data = [];

        $rules_manager = \Mockery::spy(\Tracker_RulesManager::class);
        $rules_manager->shouldReceive('validate')->with(123, $fields_data)->once()->andReturns(true);

        $workflow = new Workflow(
            $rules_manager,
            $this->trigger_rules_manager,
            new WorkflowBackendLogger(Mockery::spy(\Psr\Log\LoggerInterface::class), \Psr\Log\LogLevel::DEBUG),
            1,
            123,
            3,
            true,
            false,
            false,
            []
        );

        $workflow->checkGlobalRules($fields_data);
    }

    public function testItIsNotValidWhenTheWorkflowIsEnabled(): void
    {
        $fields_data = [42 => 66];

        $transition = \Mockery::spy(\Transition::class);
        $transition->shouldReceive('getFieldValueFrom')->andReturns(null);
        $transition->shouldReceive('getFieldValueTo')->andReturns(\Mockery::spy(\Tracker_FormElement_Field_List_Value::class)->shouldReceive('getId')->andReturns(66)->getMock());
        $transition->shouldReceive('validate')->andReturns(false);

        $workflow = Mockery::mock(
            Workflow::class,
            [
                Mockery::mock(Tracker_RulesManager::class),
                $this->trigger_rules_manager,
                new WorkflowBackendLogger(Mockery::spy(\Psr\Log\LoggerInterface::class), \Psr\Log\LogLevel::DEBUG),
                1,
                2,
                42,
                true,
                false,
                false,
                [$transition],
            ]
        )->makePartial()->shouldAllowMockingProtectedMethods();

        $retriever = Mockery::mock(TransitionRetriever::class);
        $retriever->shouldReceive('retrieveTransition')->andReturn($transition);

        $workflow->shouldReceive('getTransitionRetriever')->andReturn($retriever);

        $this->expectExceptionObject(new Tracker_Workflow_Transition_InvalidConditionForTransitionException($transition));
        $workflow->validate($fields_data, $this->artifact, '', $this->current_user);
    }

    public function testItDisablesTheValidationOfTransitions(): void
    {
        $transition = \Mockery::spy(\Transition::class);
        $transition->shouldReceive('getFieldValueFrom')->andReturns(null);
        $transition->shouldReceive('getFieldValueTo')->andReturns(\Mockery::spy(\Tracker_FormElement_Field_List_Value::class)->shouldReceive('getId')->andReturns(66)->getMock());
        $transition->shouldReceive('validate')->andReturns(false);
        $workflow = new Workflow(
            Mockery::mock(Tracker_RulesManager::class),
            $this->trigger_rules_manager,
            new WorkflowBackendLogger(Mockery::spy(\Psr\Log\LoggerInterface::class), \Psr\Log\LogLevel::DEBUG),
            1,
            2,
            42,
            true,
            false,
            false,
            [$transition]
        );
        $workflow->disable();

        $fields_data = [42 => 66];

        $transition->shouldReceive('validate')->never();

        $workflow->validate($fields_data, \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class), '', $this->current_user);
    }

    public function testItDisablesTheGlobalRulesValidation(): void
    {
        $this->expectNotToPerformAssertions();
        $fields_data = [];

        $rules_manager = \Mockery::spy(\Tracker_RulesManager::class);
        $rules_manager->shouldReceive('validate')->andReturns(false);
        $workflow = new Workflow(
            $rules_manager,
            $this->trigger_rules_manager,
            new WorkflowBackendLogger(Mockery::spy(\Psr\Log\LoggerInterface::class), \Psr\Log\LogLevel::DEBUG),
            1,
            123,
            42,
            true,
            false,
            false,
            []
        );

        $workflow->disable();

        $workflow->checkGlobalRules($fields_data);
    }

    public function testPermissionsAreByPassedWhenWorkflowIsDisabled(): void
    {
        $rules_manager = $this->createStub(\Tracker_RulesManager::class);
        $workflow      = new Workflow(
            $rules_manager,
            $this->trigger_rules_manager,
            new WorkflowBackendLogger($this->createStub(\Psr\Log\LoggerInterface::class), \Psr\Log\LogLevel::DEBUG),
            1,
            123,
            42,
            true,
            false,
            false,
            []
        );

        $workflow->disable();

        self::assertTrue($workflow->bypassPermissions($this->status_field));
    }

    public function testPermissionsAreByPassedWhenTransitionIsMarkedAsByPassed(): void
    {
        $this->workflow->shouldReceive('getTransitions')->andReturns([
            $this->transition_null_to_open,
        ]);
        $this->transition_null_to_open->shouldReceive("bypassPermissions")->andReturnTrue();

        self::assertTrue($this->workflow->bypassPermissions($this->status_field));
    }

    public function testPermissionsMustBeApplied(): void
    {
        $this->workflow->shouldReceive('getTransitions')->andReturns([
            $this->transition_null_to_open,
        ]);
        $this->transition_null_to_open->shouldReceive("bypassPermissions")->andReturnFalse();

        self::assertFalse($this->workflow->bypassPermissions($this->status_field));
    }
}
