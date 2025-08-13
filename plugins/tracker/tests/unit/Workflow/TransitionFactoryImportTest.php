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

use EventManager;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Psr\Log\NullLogger;
use SimpleXMLElement;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tracker_RulesManager;
use Tracker_Workflow_Trigger_RulesManager;
use Transition_PostActionFactory;
use TransitionFactory;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\SelectboxFieldBuilder;
use Workflow;
use Workflow_Transition_ConditionFactory;
use Workflow_Transition_ConditionsCollection;
use Workflow_TransitionDao;

#[DisableReturnValueGenerationForTestDoubles]
final class TransitionFactoryImportTest extends TestCase
{
    private TransitionFactory $factory;

    private Workflow_Transition_ConditionFactory&MockObject $condition_factory;

    private Transition_PostActionFactory&MockObject $postaction_factory;
    private Project $project;
    private Tracker_FormElement_Field_List_Bind_StaticValue $to_value;
    private array $xml_mapping;

    private Workflow_TransitionDao&MockObject $transition_dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->condition_factory  = $this->createMock(Workflow_Transition_ConditionFactory::class);
        $this->postaction_factory = $this->createMock(Transition_PostActionFactory::class);
        $this->postaction_factory->method('warmUpCacheForWorkflow');

        $event_manager        = $this->createMock(EventManager::class);
        $this->transition_dao = $this->createMock(Workflow_TransitionDao::class);
        $this->factory        = new TransitionFactory(
            $this->condition_factory,
            $event_manager,
            new DBTransactionExecutorPassthrough(),
            $this->postaction_factory,
            $this->transition_dao,
        );

        $this->project = ProjectTestBuilder::aProject()->build();

        $field = SelectboxFieldBuilder::aSelectboxField(101)->build();

        $from_value        = ListStaticValueBuilder::aStaticValue('Todo')->build();
        $this->to_value    = ListStaticValueBuilder::aStaticValue('Done')->build();
        $this->xml_mapping = [
            'F32'    => $field,
            'F32-V1' => $from_value,
            'F32-V0' => $this->to_value,
        ];
    }

    public function testItReconstitutesPostActions(): void
    {
        $xml = new SimpleXMLElement('
            <transition>
                <from_id REF="F32-V1"/>
                <to_id REF="F32-V0"/>
                <postactions>
                    <postaction_field_date valuetype="1">
                        <field_id REF="F1"/>
                    </postaction_field_date>
                </postactions>
            </transition>
        ');

        $this->condition_factory->expects($this->once())->method('getAllInstancesFromXML')
            ->willReturn(new Workflow_Transition_ConditionsCollection());

        $this->postaction_factory->expects($this->once())->method('getInstanceFromXML')
            ->with($this->anything(), $this->xml_mapping, $this->anything());

        $this->factory->getInstanceFromXML($xml, $this->xml_mapping, $this->project);
    }

    public function testItReconsititutesPermissions(): void
    {
        $xml = new SimpleXMLElement('
            <transition>
                <from_id REF="F32-V1"/>
                <to_id REF="F32-V0"/>
                <permissions>
                    <permission ugroup="UGROUP_PROJECT_MEMBERS"/>
                    <permission ugroup="UGROUP_PROJECT_ADMIN"/>
                </permissions>
            </transition>
        ');

        $this->condition_factory->expects($this->once())->method('getAllInstancesFromXML')
            ->willReturn(new Workflow_Transition_ConditionsCollection());

        $transition = $this->factory->getInstanceFromXML($xml, $this->xml_mapping, $this->project);

        $this->assertInstanceOf(Workflow_Transition_ConditionsCollection::class, $transition->getConditions());
    }

    public function testItReconsititutesTransitionsForState(): void
    {
        $xml = new SimpleXMLElement('
            <state>
                <to_id REF="F32-V0"/>
                <transitions>
                    <transition>
                        <from_id REF="null"/>
                    </transition>
                    <transition>
                        <from_id REF="F32-V1"/>
                    </transition>
                </transitions>
            </state>
        ');

        $this->condition_factory->expects($this->exactly(2))->method('getAllInstancesFromXML')
            ->willReturn(new Workflow_Transition_ConditionsCollection());

        $transitions = $this->factory->getInstancesFromStateXML(
            $xml,
            $this->xml_mapping,
            $this->project,
            $this->to_value
        );

        $this->assertCount(2, $transitions);
    }

    public function testGetTransitionsWhenNoTransitionsDefined(): void
    {
        $workflow = new Workflow(
            $this->createMock(Tracker_RulesManager::class),
            $this->createMock(Tracker_Workflow_Trigger_RulesManager::class),
            new WorkflowBackendLogger(new NullLogger(), 0),
            '123',
            444,
            '333',
            null,
            null
        );

        $this->transition_dao->method('searchByWorkflow')->with('123')->willReturn([]);

        self::assertSame([], $this->factory->getTransitions($workflow));
    }
}
