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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PermissionsManager;
use Project;
use Psr\Log\NullLogger;
use SimpleXMLElement;
use Tracker;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tracker_FormElement_Field_Selectbox;
use Tracker_FormElementFactory;
use Tracker_Workflow_Trigger_RulesManager;
use TrackerFactory;
use Transition;
use Transition_PostAction_Field_Date;
use TransitionFactory;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;
use Tuleap\Tracker\Workflow\SimpleMode\SimpleWorkflowDao;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Workflow_Transition_ConditionFactory;
use Workflow_Transition_ConditionsCollection;
use WorkflowFactory;

final class WorkflowFactoryImportTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $permission_manager = Mockery::mock(PermissionsManager::class);
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

        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(101);

        $static_value_01 = Mockery::mock(Tracker_FormElement_Field_List_Bind_StaticValue::class);
        $static_value_02 = Mockery::mock(Tracker_FormElement_Field_List_Bind_StaticValue::class);

        $static_value_01->shouldReceive('getId')->andReturn(801);
        $static_value_02->shouldReceive('getId')->andReturn(802);

        $mapping = [
            'F1'     => Mockery::mock(Tracker_FormElement_Field_Selectbox::class)->shouldReceive('getId')->andReturn(110)->getMock(),
            'F32'    => Mockery::mock(Tracker_FormElement_Field_Selectbox::class)->shouldReceive('getId')->andReturn(111)->getMock(),
            'F32-V0' => $static_value_01,
            'F32-V1' => $static_value_02,
        ];

        $condition_factory = Mockery::mock(Workflow_Transition_ConditionFactory::class);
        $condition_factory->shouldReceive('getAllInstancesFromXML')
            ->andReturn(new Workflow_Transition_ConditionsCollection());

        $transition_factory = Mockery::mock(TransitionFactory::class);

        $date_post_action = Mockery::mock(Transition_PostAction_Field_Date::class);
        $date_post_action->shouldReceive('getField')->andReturn(110);
        $date_post_action->shouldReceive('getValueType')->andReturn(1);

        $third_transition = Mockery::mock(Transition::class);
        $third_transition->shouldReceive('getPostActions')->andReturn([$date_post_action]);

        $first_transition = Mockery::mock(Transition::class);
        $first_transition->shouldReceive('getPostActions')->andReturns([]);

        $second_transition = Mockery::mock(Transition::class);
        $second_transition->shouldReceive('getPostActions')->andReturns([]);

        $transition_factory->shouldReceive('getInstanceFromXML')
            ->with(
                Mockery::on(function (SimpleXMLElement $val) {
                    return (string) $val->from_id['REF'] === 'null';
                }),
                $mapping,
                $this->project
            )
            ->andReturn($first_transition);

        $transition_factory->shouldReceive('getInstanceFromXML')
            ->with(
                Mockery::on(function (SimpleXMLElement $val) {
                    return (string) $val->from_id['REF'] === 'F32-V0';
                }),
                $mapping,
                $this->project
            )
            ->andReturn($second_transition);

        $transition_factory->shouldReceive('getInstanceFromXML')
            ->with(
                Mockery::on(function (SimpleXMLElement $val) {
                    return (string) $val->from_id['REF'] === 'F32-V1';
                }),
                $mapping,
                $this->project
            )
            ->andReturn($third_transition);

        $workflow_factory = new WorkflowFactory(
            $transition_factory,
            Mockery::mock(TrackerFactory::class),
            Mockery::mock(Tracker_FormElementFactory::class),
            Mockery::mock(Tracker_Workflow_Trigger_RulesManager::class),
            new WorkflowBackendLogger(Mockery::spy(\Psr\Log\LoggerInterface::class), \Psr\Log\LogLevel::DEBUG),
            Mockery::mock(FrozenFieldsDao::class),
            Mockery::mock(StateFactory::class)
        );

        $workflow = $workflow_factory->getInstanceFromXML($xml, $mapping, $tracker, $this->project);

        $this->assertEquals($workflow->isUsed(), 1);
        $this->assertEquals($workflow->getFieldId(), 111);
        $this->assertEquals(count($workflow->getTransitions()), 3);

        // Test post actions
        $transitions = $workflow->getTransitions();
        $this->assertEquals(count($transitions[0]->getPostActions()), 0);
        $this->assertEquals(count($transitions[1]->getPostActions()), 0);
        $this->assertEquals(count($transitions[2]->getPostActions()), 1);

        // There is one post action on last transition
        $postactions = $transitions[2]->getPostActions();
        $this->assertEquals($postactions[0]->getField(), 110);
        $this->assertEquals($postactions[0]->getValueType(), 1);

        $this->assertEquals($third_transition, $transitions[2]);
    }

    public function testImportSimpleWorkflow(): void
    {
        $xml = simplexml_load_string(file_get_contents(__DIR__ . '/_fixtures/importSimpleWorkflow.xml'), SimpleXMLElement::class, LIBXML_NOENT);

        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(101);

        $static_value_01 = Mockery::mock(Tracker_FormElement_Field_List_Bind_StaticValue::class);
        $static_value_02 = Mockery::mock(Tracker_FormElement_Field_List_Bind_StaticValue::class);

        $static_value_01->shouldReceive('getId')->andReturn(801);
        $static_value_02->shouldReceive('getId')->andReturn(802);

        $mapping = [
            'F1'     => Mockery::mock(Tracker_FormElement_Field_Selectbox::class)->shouldReceive('getId')->andReturn(110)->getMock(),
            'F32'    => Mockery::mock(Tracker_FormElement_Field_Selectbox::class)->shouldReceive('getId')->andReturn(111)->getMock(),
            'F32-V0' => $static_value_01,
            'F32-V1' => $static_value_02,
        ];

        $date_post_action = Mockery::mock(Transition_PostAction_Field_Date::class);
        $date_post_action->shouldReceive('getField')->andReturn(110);
        $date_post_action->shouldReceive('getValueType')->andReturn(1);

        $first_transition = Mockery::mock(Transition::class);
        $first_transition->shouldReceive('getPostActions')->andReturns([$date_post_action]);

        $second_transition = Mockery::mock(Transition::class);
        $second_transition->shouldReceive('getPostActions')->andReturns([$date_post_action]);

        $transition_factory = Mockery::mock(TransitionFactory::class);
        $transition_factory->shouldReceive('getInstancesFromStateXML')
            ->with(
                Mockery::on(function (SimpleXMLElement $state) {
                    return (string) $state->to_id['REF'] === 'F32-V0';
                }),
                $mapping,
                $this->project,
                $static_value_01
            )
            ->andReturn([$first_transition, $second_transition]);

        $state_factory = new StateFactory(
            $transition_factory,
            Mockery::mock(SimpleWorkflowDao::class)
        );

        $workflow_factory = new WorkflowFactory(
            $transition_factory,
            Mockery::mock(TrackerFactory::class),
            Mockery::mock(Tracker_FormElementFactory::class),
            Mockery::mock(Tracker_Workflow_Trigger_RulesManager::class),
            new WorkflowBackendLogger(Mockery::spy(\Psr\Log\LoggerInterface::class), \Psr\Log\LogLevel::DEBUG),
            Mockery::mock(FrozenFieldsDao::class),
            $state_factory
        );

        $workflow = $workflow_factory->getSimpleInstanceFromXML($xml, $mapping, $tracker, $this->project);

        $this->assertEquals($workflow->isUsed(), 1);
        $this->assertEquals($workflow->getFieldId(), 111);
        $this->assertEquals(count($workflow->getTransitions()), 2);

        // Test post actions
        $transitions = $workflow->getTransitions();
        $this->assertEquals(count($transitions[0]->getPostActions()), 1);
        $this->assertEquals(count($transitions[1]->getPostActions()), 1);
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

        $field = $this->createStub(Tracker_FormElement_Field_Selectbox::class);
        $field->method('getId')->willReturn(32);
        $mapping  = [
            'F32' => $field,
        ];
        $workflow = $workflow_factory->getSimpleInstanceFromXML($xml, $mapping, new \NullTracker(), $this->project);

        $this->assertEquals(1, $workflow->isUsed());
        $this->assertEquals(32, $workflow->getFieldId());
        $this->assertEmpty($workflow->getTransitions());
    }
}
