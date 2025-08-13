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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\FormElement\Field\List\SelectboxField;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueListTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\SelectboxFieldBuilder;
use Tuleap\Tracker\Workflow\Transition\TransitionRetriever;
use Tuleap\Tracker\Workflow\WorkflowBackendLogger;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class WorkflowTest extends \Tuleap\Test\PHPUnit\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    private $transition_factory_test;
    private TransitionFactory&MockObject $transition_factory_instance;
    private Transition&MockObject $transition_null_to_open;
    private Transition&MockObject $transition_open_to_close;
    private Transition&MockObject $transition_close_to_open;
    private int $open_value_id  = 801;
    private int $close_value_id = 802;
    private Tracker_Workflow_Trigger_RulesManager&MockObject $trigger_rules_manager;
    private SelectboxField $status_field;
    private PFUser $current_user;
    private Workflow&MockObject $workflow;
    private Workflow&MockObject $unused_workflow;
    private Workflow&MockObject $unused_legacy_workflow;
    private \Tuleap\Tracker\Artifact\Artifact&MockObject $artifact;
    private EventManager&MockObject $event_manager;
    private Tracker_FormElement_Field_List_Bind_StaticValue $open_value;


    protected function setUp(): void
    {
        $this->transition_factory_instance = $this->createMock(TransitionFactory::class);
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

        $this->status_field = SelectboxFieldBuilder::aSelectboxField(103)->build();

        $this->open_value = ListStaticValueBuilder::aStaticValue('open')->withId($this->open_value_id)->build();

        $close_value = ListStaticValueBuilder::aStaticValue('closed')->withId($this->close_value_id)->build();

        $this->current_user = UserTestBuilder::anActiveUser()->build();

        $this->transition_null_to_open  = $this->createMock(\Transition::class);
        $this->transition_open_to_close = $this->createMock(\Transition::class);
        $this->transition_close_to_open = $this->createMock(\Transition::class);

        $this->transition_null_to_open->method('getFieldValueFrom')->willReturn(null);
        $this->transition_null_to_open->method('getFieldValueTo')->willReturn($this->open_value);
        $this->transition_open_to_close->method('getFieldValueFrom')->willReturn($this->open_value);
        $this->transition_open_to_close->method('getFieldValueTo')->willReturn($close_value);
        $this->transition_close_to_open->method('getFieldValueFrom')->willReturn($close_value);
        $this->transition_close_to_open->method('getFieldValueTo')->willReturn($this->open_value);

        $this->trigger_rules_manager = $this->createMock(\Tracker_Workflow_Trigger_RulesManager::class);
        $workflow_id                 = 1;
        $tracker_id                  = 2;
        $field_id                    = $this->status_field->getId();
        $is_used                     = 1;
        $is_legacy                   = 0;
        $is_advanced                 = 1;
        $transitions                 = [$this->transition_null_to_open, $this->transition_open_to_close];
        $logger                      = $this->createMock(\Psr\Log\LoggerInterface::class);
        $logger->method('debug');
        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->onlyMethods(['getTracker'])
            ->setConstructorArgs(
                [
                    $this->createMock(Tracker_RulesManager::class),
                    $this->trigger_rules_manager,
                    new WorkflowBackendLogger($logger, \Psr\Log\LogLevel::DEBUG),
                    $workflow_id,
                    $tracker_id,
                    $field_id,
                    $is_used,
                    $is_advanced,
                    $is_legacy,
                    $transitions,
                ]
            )->getMock();

        $this->unused_workflow = $this->getMockBuilder(Workflow::class)
            ->onlyMethods(['getTracker'])
            ->setConstructorArgs(
                [
                    $this->createMock(Tracker_RulesManager::class),
                    $this->trigger_rules_manager,
                    new WorkflowBackendLogger($logger, \Psr\Log\LogLevel::DEBUG),
                    $workflow_id,
                    $tracker_id,
                    $field_id,
                    false,
                    $is_advanced,
                    $is_legacy,
                    $transitions,
                ]
            )->getMock();

        $this->unused_legacy_workflow = $this->getMockBuilder(Workflow::class)
            ->onlyMethods(['getTracker'])
            ->setConstructorArgs(
                [
                    $this->createMock(Tracker_RulesManager::class),
                    $this->trigger_rules_manager,
                    new WorkflowBackendLogger($logger, \Psr\Log\LogLevel::DEBUG),
                    $workflow_id,
                    $tracker_id,
                    $field_id,
                    false,
                    $is_advanced,
                    true,
                    $transitions,
                ]
            )->getMock();

        $this->workflow->setField($this->status_field);
        $this->unused_workflow->setField($this->status_field);
        $this->unused_legacy_workflow->setField($this->status_field);

        $this->artifact = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->artifact->method('getId');

        $this->event_manager = $this->createMock(EventManager::class);
        EventManager::setInstance($this->event_manager);
    }

    protected function tearDown(): void
    {
        $this->transition_factory_test->clearInstance();
        EventManager::clearInstance();
    }

    public function testEmptyWorkflow(): void
    {
        $workflow = $this->createPartialMock(\Workflow::class, ['getTransitions']);
        $workflow->method('getTransitions')->willReturn([]);
        self::assertNotNull($workflow->getTransitions());
        self::assertEquals(count($workflow->getTransitions()), 0);

        $field_value_new      = $this->buildFieldValue(2066);
        $field_value_analyzed = $this->buildFieldValue(2067);

        // workflow is empty, no transition exists
        self::assertFalse($workflow->isTransitionExist($field_value_new, $field_value_analyzed));
        self::assertFalse($workflow->hasTransitions());
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
            $this->createMock(Tracker_RulesManager::class),
            $this->createMock(Tracker_Workflow_Trigger_RulesManager::class),
            new WorkflowBackendLogger($this->createMock(\Psr\Log\LoggerInterface::class), \Psr\Log\LogLevel::DEBUG),
            1,
            2,
            3,
            true,
            true,
            false,
            $transitions
        );

        self::assertNotNull($workflow->getTransitions());
        self::assertTrue($workflow->hasTransitions());
        // Test existing transition
        self::assertTrue($workflow->isTransitionExist($field_value_new, $field_value_analyzed));
        self::assertTrue($workflow->isTransitionExist($field_value_analyzed, $field_value_accepted));
        self::assertTrue($workflow->isTransitionExist($field_value_analyzed, $field_value_rejected));
        self::assertTrue($workflow->isTransitionExist($field_value_accepted, $field_value_fixed));
        self::assertTrue($workflow->isTransitionExist($field_value_fixed, $field_value_tested));
        self::assertTrue($workflow->isTransitionExist($field_value_tested, $field_value_deployed));

        self::assertFalse($workflow->isTransitionExist($field_value_new, $field_value_tested));
        self::assertFalse($workflow->isTransitionExist($field_value_new, $field_value_rejected));
        self::assertFalse($workflow->isTransitionExist($field_value_analyzed, $field_value_new));
        self::assertFalse($workflow->isTransitionExist($field_value_accepted, $field_value_rejected));
    }

    public function testExport(): void
    {
        $this->transition_factory_instance->method('getTransitions')->willReturn([]);

        $this->transition_null_to_open->method('exportToXml');
        $this->transition_open_to_close->method('exportToXml');
        $this->transition_close_to_open->method('exportToXml');

        $ugroups_transition = ['ugroup' => 'UGROUP_PROJECT_MEMBERS'];

        $global_rules_manager  = $this->createMock(\Tracker_RulesManager::class);
        $trigger_rules_manager = $this->createMock(\Tracker_Workflow_Trigger_RulesManager::class);
        $logger                = new WorkflowBackendLogger($this->createMock(\Psr\Log\LoggerInterface::class), \Psr\Log\LogLevel::DEBUG);

        $workflow = new \Workflow(
            $global_rules_manager,
            $trigger_rules_manager,
            $logger,
            1,
            2,
            103,
            1,
            false,
            false,
            [$this->transition_null_to_open, $this->transition_open_to_close, $this->transition_close_to_open],
        );

        $pm = $this->createMock(\PermissionsManager::class);
        $pm->method('getAuthorizedUgroups')->willReturn($ugroups_transition);

        $xml  = simplexml_load_string(file_get_contents(__DIR__ . '/_fixtures/importWorkflow.xml'));
        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');

        $array_xml_mapping = ['F32' => 103,
            'values' => [
                'F32-V0' => 806,
                'F32-V1' => 807,
            ],
        ];
        $workflow->exportToXML($root, $array_xml_mapping);

        self::assertEquals((string) $xml->field_id['REF'], (string) $root->field_id['REF']);
        self::assertEquals((int) $xml->is_used, (int) $root->is_used);
        self::assertEquals(count($xml->transitions), count($root->transitions));
    }

    public function testNonTransitionAlwaysExist(): void
    {
        $workflow = $this->createPartialMock(\Workflow::class, ['getTransitions']);
        $workflow->expects($this->never())->method('getTransitions');
        $field_value = [];
        self::assertTrue($workflow->isTransitionExist($field_value, $field_value));
    }

    private function buildFieldValue(int $id): \Tracker_FormElement_Field_List_Value
    {
        $uuid_factory = new DatabaseUUIDV7Factory();
        return new class ($uuid_factory->buildUUIDFromBytesData($uuid_factory->buildUUIDBytes()), $id) extends \Tracker_FormElement_Field_List_Value
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
        $changeset = ChangesetTestBuilder::aChangeset(101)->build();
        ChangesetValueListTestBuilder::aListOfValue(1, $changeset, $this->status_field)
            ->withValues([$this->open_value])
            ->build();

        $this->artifact->method('getLastChangeset')->willReturn($changeset);

        $fields_data = [
            '103' => "$this->close_value_id",
        ];
        $this->transition_null_to_open->expects($this->never())->method('before');
        $this->transition_open_to_close->expects($this->once())->method('before')->with($fields_data, $this->current_user);

        $this->event_manager->method('processEvent');

        $this->workflow->before($fields_data, $this->current_user, $this->artifact);
    }

    public function testBeforeShouldTriggerTransitionActionsForNewArtifact(): void
    {
        $changeset = new \Tracker_Artifact_Changeset_Null();
        $this->artifact->method('getLastChangeset')->willReturn($changeset);

        $fields_data = [
            '103' => "$this->open_value_id",
        ];
        $this->transition_null_to_open->expects($this->once())->method('before')->with($fields_data, $this->current_user);
        $this->transition_open_to_close->expects($this->never())->method('before');

        $this->event_manager->method('processEvent');

        $this->workflow->before($fields_data, $this->current_user, $this->artifact);
    }

    public function testBeforeShouldDoNothingButProcessTheEventIfWorkflowIsNotUsedAndIsNotLegacy(): void
    {
        $changeset = new \Tracker_Artifact_Changeset_Null();
        $this->artifact->method('getLastChangeset')->willReturn($changeset);

        $fields_data = [
            '103' => "$this->open_value_id",
        ];

        $this->transition_null_to_open->expects($this->never())->method('before');
        $this->transition_open_to_close->expects($this->never())->method('before');
        $this->event_manager->expects($this->once())->method('processEvent');

        $this->unused_workflow->before($fields_data, $this->current_user, $this->artifact);
    }

    public function testBeforeShouldProcessActionsIfWorkflowIsNotUsedAndIsLegacy(): void
    {
        $changeset = new \Tracker_Artifact_Changeset_Null();
        $this->artifact->method('getLastChangeset')->willReturn($changeset);

        $fields_data = [
            '103' => "$this->open_value_id",
        ];

        $this->transition_null_to_open->expects($this->once())->method('before');
        $this->event_manager->expects($this->once())->method('processEvent');

        $this->unused_legacy_workflow->before($fields_data, $this->current_user, $this->artifact);
    }

    public function testAfterShouldTriggerTransitionActions(): void
    {
        $previous_changeset = ChangesetTestBuilder::aChangeset(101)->build();
        ChangesetValueListTestBuilder::aListOfValue(1, $previous_changeset, $this->status_field)
            ->withValues([$this->open_value])
            ->build();

        $new_changeset = ChangesetTestBuilder::aChangeset(101)->build();

        $fields_data = [
            '103' => "$this->close_value_id",
        ];
        $this->transition_null_to_open->expects($this->never())->method('after');
        $this->transition_open_to_close->expects($this->once())->method('after')->with($new_changeset);

        $this->trigger_rules_manager->method('processTriggers');

        $this->workflow->after($fields_data, $new_changeset, $previous_changeset);
    }

    public function testAfterShouldTriggerTransitionActionsForNewArtifact(): void
    {
        $previous_changeset = null;
        $new_changeset      = ChangesetTestBuilder::aChangeset(101)->build();

        $fields_data = [
            '103' => "$this->open_value_id",
        ];
        $this->transition_null_to_open->expects($this->once())->method('after')->with($new_changeset);
        $this->transition_open_to_close->expects($this->never())->method('after');

        $this->trigger_rules_manager->method('processTriggers');

        $this->workflow->after($fields_data, $new_changeset, $previous_changeset);
    }

    public function testItShouldProcessTriggers(): void
    {
        $previous_changeset = null;
        $new_changeset      = ChangesetTestBuilder::aChangeset(101)->build();
        $fields_data        = [];

        $this->trigger_rules_manager->expects($this->once())->method('processTriggers')->with($new_changeset);

        $this->workflow->after($fields_data, $new_changeset, $previous_changeset);
    }

    public function testAfterShouldDoNothingButTriggersIfWorkflowIsNotUsedAndIsNotLegacy(): void
    {
        $previous_changeset = null;
        $new_changeset      = ChangesetTestBuilder::aChangeset(101)->build();

        $fields_data = [
            '103' => "$this->open_value_id",
        ];

        $this->transition_null_to_open->expects($this->never())->method('after');
        $this->transition_open_to_close->expects($this->never())->method('after');
        $this->event_manager->expects($this->never())->method('processEvent');
        $this->trigger_rules_manager->expects($this->once())->method('processTriggers');

        $this->unused_workflow->after($fields_data, $new_changeset, $previous_changeset);
    }

    public function testAfterShouldProcessActionsIfWorkflowIsNotUsedAndIsLegacy(): void
    {
        $previous_changeset = null;
        $new_changeset      = ChangesetTestBuilder::aChangeset(101)->build();

        $fields_data = [
            '103' => "$this->open_value_id",
        ];

        $this->transition_null_to_open->expects($this->once())->method('after');
        $this->trigger_rules_manager->expects($this->once())->method('processTriggers');

        $this->unused_legacy_workflow->after($fields_data, $new_changeset, $previous_changeset);
    }

    public function testItRaisesNoExceptionIfWorkflowIsNotEnabled(): void
    {
        $this->expectNotToPerformAssertions();
        $fields_data = [];
        $artifact    = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);

        $this->unused_workflow->validate($fields_data, $artifact, '', $this->current_user);
    }

    public function testItRaisesExceptionIfWorkflowIsEnabledAndTransitionNotValid(): void
    {
        $value_from = null;
        $value_to   = ListStaticValueBuilder::aStaticValue('open')->build();
        $transition = $this->createMock(\Transition::class);
        $transition->method('getFieldValueFrom')->willReturn($value_from);
        $transition->method('getFieldValueTo')->willReturn($value_to);
        $is_used  = 1;
        $field_id = 42;
        $workflow = $this->getMockBuilder(Workflow::class)
            ->onlyMethods(['getTransitionRetriever'])
            ->setConstructorArgs(
                [
                    $this->createMock(Tracker_RulesManager::class),
                    $this->trigger_rules_manager,
                    new WorkflowBackendLogger($this->createMock(\Psr\Log\LoggerInterface::class), \Psr\Log\LogLevel::DEBUG),
                    1,
                    2,
                    $field_id,
                    $is_used,
                    false,
                    false,
                    [$transition],
                ]
            )->getMock();

        $fields_data = [$field_id => 66];
        $artifact    = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->method('getLastChangeset');

        $retriever = $this->createMock(TransitionRetriever::class);
        $retriever->method('retrieveTransition')->willReturn($transition);


        $workflow->method('getTransitionRetriever')->willReturn($retriever);

        $transition->expects($this->once())->method('validate')->willReturn(false);
        $this->expectExceptionObject(new Tracker_Workflow_Transition_InvalidConditionForTransitionException($transition));

        $workflow->validate($fields_data, $artifact, '', $this->current_user);
    }

    public function testItDelegatesValidationToRulesManager(): void
    {
        $fields_data = [];

        $rules_manager = $this->createMock(\Tracker_RulesManager::class);
        $rules_manager->expects($this->once())->method('validate')->with(123, $fields_data)->willReturn(true);

        $workflow = new Workflow(
            $rules_manager,
            $this->trigger_rules_manager,
            new WorkflowBackendLogger($this->createMock(\Psr\Log\LoggerInterface::class), \Psr\Log\LogLevel::DEBUG),
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

        $transition = $this->createMock(\Transition::class);
        $transition->method('getFieldValueFrom')->willReturn(null);
        $transition->method('getFieldValueTo')->willReturn(ListStaticValueBuilder::aStaticValue('open')->build());
        $transition->method('validate')->willReturn(false);

        $workflow = $this->getMockBuilder(Workflow::class)
            ->onlyMethods(['getTransitionRetriever'])
            ->setConstructorArgs([
                $this->createMock(Tracker_RulesManager::class),
                $this->trigger_rules_manager,
                new WorkflowBackendLogger($this->createMock(\Psr\Log\LoggerInterface::class), \Psr\Log\LogLevel::DEBUG),
                1,
                2,
                42,
                true,
                false,
                false,
                [$transition],
            ])->getMock();

        $retriever = $this->createMock(TransitionRetriever::class);
        $retriever->method('retrieveTransition')->willReturn($transition);

        $workflow->method('getTransitionRetriever')->willReturn($retriever);

        $this->artifact->method('getLastChangeset');

        $this->expectExceptionObject(new Tracker_Workflow_Transition_InvalidConditionForTransitionException($transition));
        $workflow->validate($fields_data, $this->artifact, '', $this->current_user);
    }

    public function testItDisablesTheValidationOfTransitions(): void
    {
        $transition = $this->createMock(\Transition::class);
        $transition->method('getFieldValueFrom')->willReturn(null);
        $transition->method('getFieldValueTo')->willReturn(ListStaticValueBuilder::aStaticValue('open')->build());
        $transition->method('validate')->willReturn(false);
        $workflow = new Workflow(
            $this->createMock(Tracker_RulesManager::class),
            $this->trigger_rules_manager,
            new WorkflowBackendLogger($this->createMock(\Psr\Log\LoggerInterface::class), \Psr\Log\LogLevel::DEBUG),
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

        $transition->expects($this->never())->method('validate');

        $workflow->validate($fields_data, $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class), '', $this->current_user);
    }

    public function testItDisablesTheGlobalRulesValidation(): void
    {
        $this->expectNotToPerformAssertions();
        $fields_data = [];

        $rules_manager = $this->createMock(\Tracker_RulesManager::class);
        $rules_manager->method('validate')->willReturn(false);
        $workflow = new Workflow(
            $rules_manager,
            $this->trigger_rules_manager,
            new WorkflowBackendLogger($this->createMock(\Psr\Log\LoggerInterface::class), \Psr\Log\LogLevel::DEBUG),
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
        $this->transition_null_to_open->method('bypassPermissions')->willReturn(true);
        $this->transition_open_to_close->method('bypassPermissions')->willReturn(false);

        self::assertTrue($this->workflow->bypassPermissions($this->status_field));
    }

    public function testPermissionsMustBeApplied(): void
    {
        $this->transition_null_to_open->method('bypassPermissions')->willReturn(false);
        $this->transition_open_to_close->method('bypassPermissions')->willReturn(false);

        self::assertFalse($this->workflow->bypassPermissions($this->status_field));
    }
}
