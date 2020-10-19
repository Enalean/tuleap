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
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use SimpleXMLElement;
use Tracker_FormElement_Field_List;
use Tracker_RulesManager;
use Tracker_Workflow_Trigger_RulesManager;
use TransitionFactory;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Workflow_Transition_ConditionFactory;
use Workflow_Transition_ConditionsCollection;
use Workflow_TransitionDao;

class TransitionFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var TransitionFactory */
    private $factory;

    /** @var Workflow_Transition_ConditionFactory */
    private $condition_factory;

    private $postaction_factory;
    private $project;
    private $to_value;
    private $xml_mapping;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Workflow_TransitionDao
     */
    private $transition_dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->condition_factory  = \Mockery::spy(\Workflow_Transition_ConditionFactory::class);
        $this->postaction_factory = \Mockery::spy(\Transition_PostActionFactory::class);
        $event_manager            = \Mockery::mock(\EventManager::class);
        $this->transition_dao     = \Mockery::mock(Workflow_TransitionDao::class);
        $this->factory            = \Mockery::mock(
            \TransitionFactory::class,
            [
                $this->condition_factory,
                $event_manager,
                new DBTransactionExecutorPassthrough(),
                $this->postaction_factory,
                $this->transition_dao,
            ]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->project = \Mockery::spy(\Project::class);

        $field             = Mockery::mock(Tracker_FormElement_Field_List::class);

        $from_value        = Mockery::spy(\Tracker_FormElement_Field_List_Value::class);
        $this->to_value    = Mockery::spy(\Tracker_FormElement_Field_List_Value::class);
        $this->xml_mapping = [
            'F32'    => $field,
            'F32-V1' => $from_value,
            'F32-V0' => $this->to_value
        ];
    }

    public function testItReconstitutesPostActions()
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

        $this->condition_factory->shouldReceive('getAllInstancesFromXML')
            ->once()
            ->andReturn(new Workflow_Transition_ConditionsCollection());

        $this->postaction_factory->shouldReceive('getInstanceFromXML')
            ->with(Mockery::any(), $this->xml_mapping, Mockery::any())
            ->once();

        $this->factory->getInstanceFromXML($xml, $this->xml_mapping, $this->project);
    }

    public function testItReconsititutesPermissions()
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

        $this->condition_factory->shouldReceive('getAllInstancesFromXML')
            ->once()
            ->andReturn(new Workflow_Transition_ConditionsCollection());

        $transition = $this->factory->getInstanceFromXML($xml, $this->xml_mapping, $this->project);

        $this->assertInstanceOf(Workflow_Transition_ConditionsCollection::class, $transition->getConditions());
    }

    public function testItReconsititutesTransitionsForState()
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

        $this->condition_factory->shouldReceive('getAllInstancesFromXML')
            ->andReturn(new Workflow_Transition_ConditionsCollection())
            ->times(2);

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
        $workflow = new \Workflow(
            Mockery::mock(Tracker_RulesManager::class),
            Mockery::mock(Tracker_Workflow_Trigger_RulesManager::class),
            new WorkflowBackendLogger(new NullLogger(), 0),
            '123',
            '444',
            '333',
            null,
            null
        );

        $this->transition_dao->shouldReceive('searchByWorkflow')->with('123')->andReturn([]);

        self::assertSame([], $this->factory->getTransitions($workflow));
    }
}
