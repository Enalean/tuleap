<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Workflow;

use PermissionsManager;
use Project;
use Psr\Log\NullLogger;
use SimpleXMLElement;
use Tracker;
use Tracker_FormElementFactory;
use Tracker_Workflow_Trigger_RulesManager;
use TrackerFactory;
use Transition;
use Transition_PostAction_Field_Date;
use TransitionFactory;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;
use Tuleap\Tracker\Workflow\SimpleMode\SimpleWorkflowDao;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Workflow_Transition_ConditionFactory;
use Workflow_Transition_ConditionsCollection;
use WorkflowFactory;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class WorkflowFactoryImportTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $permission_manager = $this->createMock(PermissionsManager::class);
        PermissionsManager::setInstance($permission_manager);

        $this->project = ProjectTestBuilder::aProject()->build();
    }

    protected function tearDown(): void
    {
        PermissionsManager::clearInstance();

        parent::tearDown();
    }

    public function testImport(): void
    {
        $xml = simplexml_load_string(file_get_contents(__DIR__ . '/_fixtures/importWorkflow2.xml'), SimpleXMLElement::class, LIBXML_NOENT);

        $tracker = TrackerTestBuilder::aTracker()->build();

        $static_value_01 = ListStaticValueBuilder::aStaticValue('value 1')->withId(801)->build();
        $static_value_02 = ListStaticValueBuilder::aStaticValue('value 2')->withId(802)->build();

        $mapping = [
            'F1'     => ListFieldBuilder::aListField(110)->build(),
            'F32'    => ListFieldBuilder::aListField(111)->build(),
            'F32-V0' => $static_value_01,
            'F32-V1' => $static_value_02,
        ];

        $condition_factory = $this->createMock(Workflow_Transition_ConditionFactory::class);
        $condition_factory->method('getAllInstancesFromXML')
            ->willReturn(new Workflow_Transition_ConditionsCollection());

        $transition_factory = $this->createMock(TransitionFactory::class);

        $date_post_action = $this->createMock(Transition_PostAction_Field_Date::class);
        $date_post_action->method('getField')->willReturn(110);
        $date_post_action->method('getValueType')->willReturn(1);

        $third_transition = $this->createMock(Transition::class);
        $third_transition->method('getPostActions')->willReturn([$date_post_action]);

        $first_transition = $this->createMock(Transition::class);
        $first_transition->method('getPostActions')->willReturn([]);

        $second_transition = $this->createMock(Transition::class);
        $second_transition->method('getPostActions')->willReturn([]);

        $transition_factory->method('getInstanceFromXML')
            ->willReturnCallback(static fn (SimpleXMLElement $val) => match ((string) $val->from_id['REF']) {
                'null' => $first_transition,
                'F32-V0' => $second_transition,
                'F32-V1' => $third_transition,
            });

        $workflow_factory = new WorkflowFactory(
            $transition_factory,
            $this->createStub(TrackerFactory::class),
            $this->createStub(Tracker_FormElementFactory::class),
            $this->createStub(Tracker_Workflow_Trigger_RulesManager::class),
            new WorkflowBackendLogger($this->createStub(\Psr\Log\LoggerInterface::class), \Psr\Log\LogLevel::DEBUG),
            $this->createStub(FrozenFieldsDao::class),
            $this->createStub(StateFactory::class)
        );

        $workflow = $workflow_factory->getInstanceFromXML($xml, $mapping, $tracker, $this->project);

        self::assertSame('1', $workflow->isUsed());
        self::assertSame(111, $workflow->getFieldId());
        self::assertCount(3, $workflow->getTransitions());

        // Test post actions
        $transitions = $workflow->getTransitions();
        self::assertCount(0, $transitions[0]->getPostActions());
        self::assertCount(0, $transitions[1]->getPostActions());
        self::assertCount(1, $transitions[2]->getPostActions());

        // There is one post action on last transition
        $postactions = $transitions[2]->getPostActions();
        self::assertSame($postactions[0]->getField(), 110);
        self::assertSame($postactions[0]->getValueType(), 1);

        self::assertSame($third_transition, $transitions[2]);
    }

    public function testImportSimpleWorkflow(): void
    {
        $xml = simplexml_load_string(file_get_contents(__DIR__ . '/_fixtures/importSimpleWorkflow.xml'), SimpleXMLElement::class, LIBXML_NOENT);

        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(101);

        $static_value_01 = ListStaticValueBuilder::aStaticValue('value 1')->withId(801)->build();
        $static_value_02 = ListStaticValueBuilder::aStaticValue('value 2')->withId(802)->build();

        $mapping = [
            'F1'     => ListFieldBuilder::aListField(110)->build(),
            'F32'    => ListFieldBuilder::aListField(111)->build(),
            'F32-V0' => $static_value_01,
            'F32-V1' => $static_value_02,
        ];

        $date_post_action = $this->createMock(Transition_PostAction_Field_Date::class);
        $date_post_action->method('getField')->willReturn(110);
        $date_post_action->method('getValueType')->willReturn(1);

        $first_transition = $this->createMock(Transition::class);
        $first_transition->method('getPostActions')->willReturn([$date_post_action]);

        $second_transition = $this->createMock(Transition::class);
        $second_transition->method('getPostActions')->willReturn([$date_post_action]);

        $transition_factory = $this->createMock(TransitionFactory::class);
        $transition_factory->method('getInstancesFromStateXML')
            ->with(
                $this->callback(function (SimpleXMLElement $state) {
                    return (string) $state->to_id['REF'] === 'F32-V0';
                }),
                $mapping,
                $this->project,
                $static_value_01
            )
            ->willReturn([$first_transition, $second_transition]);

        $state_factory = new StateFactory(
            $transition_factory,
            $this->createStub(SimpleWorkflowDao::class)
        );

        $workflow_factory = new WorkflowFactory(
            $transition_factory,
            $this->createStub(TrackerFactory::class),
            $this->createStub(Tracker_FormElementFactory::class),
            $this->createStub(Tracker_Workflow_Trigger_RulesManager::class),
            new WorkflowBackendLogger($this->createStub(\Psr\Log\LoggerInterface::class), \Psr\Log\LogLevel::DEBUG),
            $this->createStub(FrozenFieldsDao::class),
            $state_factory
        );

        $workflow = $workflow_factory->getSimpleInstanceFromXML($xml, $mapping, $tracker, $this->project);

        self::assertSame('1', $workflow->isUsed());
        self::assertSame(111, $workflow->getFieldId());
        self::assertCount(2, $workflow->getTransitions());

        // Test post actions
        $transitions = $workflow->getTransitions();
        self::assertCount(1, $transitions[0]->getPostActions());
        self::assertCount(1, $transitions[1]->getPostActions());
    }

    public function testImportsSimpleWorkflowWithNoStates(): void
    {
        $xml = simplexml_load_string(file_get_contents(__DIR__ . '/_fixtures/importSimpleWorkflowNoStates.xml'), SimpleXMLElement::class);

        $transition_factory = $this->createStub(TransitionFactory::class);

        $workflow_factory = new WorkflowFactory(
            $transition_factory,
            $this->createStub(TrackerFactory::class),
            $this->createStub(Tracker_FormElementFactory::class),
            $this->createStub(Tracker_Workflow_Trigger_RulesManager::class),
            new WorkflowBackendLogger(new NullLogger(), \Psr\Log\LogLevel::DEBUG),
            $this->createStub(FrozenFieldsDao::class),
            $this->createStub(StateFactory::class)
        );

        $mapping  = [
            'F32' => ListFieldBuilder::aListField(32)->build(),
        ];
        $workflow = $workflow_factory->getSimpleInstanceFromXML($xml, $mapping, new \NullTracker(), $this->project);

        self::assertSame('1', $workflow->isUsed());
        self::assertSame(32, $workflow->getFieldId());
        self::assertEmpty($workflow->getTransitions());
    }
}
