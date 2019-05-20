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

namespace Tuleap\Tracker\Workflow\SimpleMode;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use Workflow;
use Workflow_Transition_ConditionsCollection;

class SimpleWorkflowXMLExporterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItExportsTheSimpleWorkflowInXML()
    {
        $xml       = new SimpleXMLElement('<simple_workflow/>');
        $dao       = Mockery::mock(SimpleWorkflowDao::class);
        $retriever = Mockery::mock(TransitionRetriever::class);
        $exporter  = new SimpleWorkflowXMLExporter($dao, $retriever);
        $workflow  = Mockery::mock(Workflow::class);

        $workflow->shouldReceive('getFieldId')->once()->andReturn(114);
        $workflow->shouldReceive('isUsed')->once()->andReturn(true);
        $workflow->shouldReceive('getId')->once()->andReturn('999');
        $dao->shouldReceive('searchStatesForWorkflow')->with(999)->once()->andReturn([
            ['to_id' => 200],
            ['to_id' => 201],
        ]);

        $dao->shouldReceive('searchTransitionsForState')->with(200)->once()->andReturn([
            ['from_id' => 0]
        ]);

        $dao->shouldReceive('searchTransitionsForState')->with(201)->once()->andReturn([
            ['from_id' => 0],
            ['from_id' => 410],
        ]);

        $transition_01 = Mockery::mock(\Transition::class);
        $transition_02 = Mockery::mock(\Transition::class);

        $post_action_01 = Mockery::mock(\Transition_PostAction_CIBuild::class);
        $post_action_02 = Mockery::mock(\Transition_PostAction_Field_Int::class);

        $transition_01->shouldReceive('getPostActions')->andReturn([]);
        $transition_02->shouldReceive('getPostActions')->andReturn([
            $post_action_01,
            $post_action_02,
        ]);

        $post_action_01->shouldReceive('exportToXML')->once();
        $post_action_02->shouldReceive('exportToXML')->once();

        $conditions_collection_01 = Mockery::mock(Workflow_Transition_ConditionsCollection::class);
        $conditions_collection_02 = Mockery::mock(Workflow_Transition_ConditionsCollection::class);

        $transition_01->shouldReceive('getConditions')->andReturn($conditions_collection_01);
        $transition_02->shouldReceive('getConditions')->andReturn($conditions_collection_02);

        $conditions_collection_01->shouldReceive('exportToXML')->once();
        $conditions_collection_02->shouldReceive('exportToXML')->once();

        $retriever->shouldReceive('getFirstTransitionForDestinationState')
            ->with($workflow, 200)
            ->once()
            ->andReturn($transition_01);

        $retriever->shouldReceive('getFirstTransitionForDestinationState')
            ->with($workflow, 201)
            ->once()
            ->andReturn($transition_02);

        $mapping = [
            'F114' => 114,
            'values' => [
                'V114-0' => 200,
                'V114-1' => 201,
                'V114-2' => 410,
            ]
        ];

        $exporter->exportToXML($workflow, $xml, $mapping);

        $this->assertEquals((string) $xml->field_id['REF'], 'F114');
        $this->assertEquals((string) $xml->is_used, '1');

        $this->assertTrue(isset($xml->states));
        $this->assertCount(2, $xml->states->state);

        $xml_state_01 = $xml->states->state[0];
        $xml_state_02 = $xml->states->state[1];

        $this->assertSame((string) $xml_state_01->to_id['REF'], 'V114-0');
        $this->assertSame((string) $xml_state_02->to_id['REF'], 'V114-1');

        $xml_state_01_transitions = $xml_state_01->transitions;
        $xml_state_02_transitions = $xml_state_02->transitions;

        $this->assertCount(1, $xml_state_01_transitions->transition);
        $this->assertCount(2, $xml_state_02_transitions->transition);

        $this->assertSame((string) $xml_state_01_transitions->transition->from_id['REF'], 'null');
        $this->assertSame((string) $xml_state_02_transitions->transition[0]->from_id['REF'], 'null');
        $this->assertSame((string) $xml_state_02_transitions->transition[1]->from_id['REF'], 'V114-2');
    }
}
