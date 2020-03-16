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
use PHPUnit\Framework\TestCase;
use Project;
use SimpleXMLElement;
use Tracker;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tracker_FormElementFactory;
use Tracker_Workflow_Trigger_RulesManager;
use TrackerFactory;
use Transition;
use Transition_PostAction_Field_Date;
use TransitionFactory;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;
use Tuleap\Tracker\Workflow\SimpleMode\SimpleWorkflowDao;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Workflow_Transition_ConditionFactory;
use Workflow_Transition_ConditionsCollection;
use WorkflowFactory;
use XML_Security;

class WorkflowFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var XML_Security */
    private $xml_security;

    protected function setUp() : void
    {
        parent::setUp();

        $permission_manager = Mockery::mock(PermissionsManager::class);
        PermissionsManager::setInstance($permission_manager);

        $this->xml_security = new XML_Security();
        $this->xml_security->enableExternalLoadOfEntities();

        $this->project = Mockery::mock(Project::class);
    }

    protected function tearDown() : void
    {
        PermissionsManager::clearInstance();
        $this->xml_security->disableExternalLoadOfEntities();

        parent::tearDown();
    }

    public function testImport()
    {
        $xml = simplexml_load_file(__DIR__ . '/_fixtures/importWorkflow.xml');

        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(101);

        $static_value_01 = Mockery::mock(Tracker_FormElement_Field_List_Bind_StaticValue::class);
        $static_value_02 = Mockery::mock(Tracker_FormElement_Field_List_Bind_StaticValue::class);

        $static_value_01->shouldReceive('getId')->andReturn(801);
        $static_value_02->shouldReceive('getId')->andReturn(802);

        $mapping = array(
            'F1'     => aSelectBoxField()->withId(110)->build(),
            'F32'    => aSelectBoxField()->withId(111)->build(),
            'F32-V0' => $static_value_01,
            'F32-V1' => $static_value_02
        );

        $condition_factory = Mockery::mock(Workflow_Transition_ConditionFactory::class);
        $condition_factory->shouldReceive('getAllInstancesFromXML')
            ->andReturn(new Workflow_Transition_ConditionsCollection());

        $transition_factory = Mockery::mock(TransitionFactory::class);

        $date_post_action = Mockery::mock(Transition_PostAction_Field_Date::class);
        $date_post_action->shouldReceive('getField')->andReturn(110);
        $date_post_action->shouldReceive('getValueType')->andReturn(1);

        $third_transition = Mockery::mock(Transition::class);
        $third_transition->shouldReceive('getPostActions')->andReturn(array($date_post_action));

        $first_transition = Mockery::mock(Transition::class);
        $first_transition->shouldReceive('getPostActions')->andReturns([]);

        $second_transition = Mockery::mock(Transition::class);
        $second_transition->shouldReceive('getPostActions')->andReturns([]);

        $transition_factory->shouldReceive('getInstanceFromXML')
            ->with(
                Mockery::on(function (SimpleXMLElement $val) {
                    return (string) $val->from_id['REF'] === "null";
                }),
                $mapping,
                $this->project
            )
            ->andReturn($first_transition);

        $transition_factory->shouldReceive('getInstanceFromXML')
            ->with(
                Mockery::on(function (SimpleXMLElement $val) {
                    return (string) $val->from_id['REF'] === "F32-V0";
                }),
                $mapping,
                $this->project
            )
            ->andReturn($second_transition);

        $transition_factory->shouldReceive('getInstanceFromXML')
            ->with(
                Mockery::on(function (SimpleXMLElement $val) {
                    return (string) $val->from_id['REF'] === "F32-V1";
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

    public function testImportSimpleWorkflow()
    {
        $xml = simplexml_load_file(__DIR__ . '/_fixtures/importSimpleWorkflow.xml');

        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(101);

        $static_value_01 = Mockery::mock(Tracker_FormElement_Field_List_Bind_StaticValue::class);
        $static_value_02 = Mockery::mock(Tracker_FormElement_Field_List_Bind_StaticValue::class);

        $static_value_01->shouldReceive('getId')->andReturn(801);
        $static_value_02->shouldReceive('getId')->andReturn(802);

        $mapping = array(
            'F1'     => aSelectBoxField()->withId(110)->build(),
            'F32'    => aSelectBoxField()->withId(111)->build(),
            'F32-V0' => $static_value_01,
            'F32-V1' => $static_value_02
        );

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
                    return (string) $state->to_id['REF'] === "F32-V0";
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
}
