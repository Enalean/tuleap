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
use Tracker_Artifact;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue;
use Tracker_FormElement_Field;
use Transition;
use Tuleap\Tracker\Workflow\Transition\NoSiblingTransitionException;
use Tuleap\Tracker\Workflow\Transition\NoTransitionForStateException;
use Workflow;

final class TransitionRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var TransitionRetriever */
    private $transition_retriever;
    /** @var Mockery\MockInterface */
    private $transition_dao;
    /** @var Mockery\MockInterface */
    private $transition_factory;

    protected function setUp(): void
    {
        $this->transition_dao       = Mockery::mock(\Workflow_TransitionDao::class);
        $this->transition_factory   = Mockery::mock(\TransitionFactory::class);
        $this->transition_retriever = new TransitionRetriever($this->transition_dao, $this->transition_factory);
    }

    public function testGetSiblingTransitionsReturnsTransitionCollection() : void
    {
        $workflow_id       = 38;
        $transition_id     = 81;
        $to_id             = 239;
        $first_sibling_id  = 95;
        $second_sibling_id = 105;

        $first_row = ['transition_id' => $first_sibling_id];
        $second_row = ['transition_id' => $second_sibling_id];
        $this->transition_dao
            ->shouldReceive('searchSiblings')
            ->with($workflow_id, $to_id, $transition_id)
            ->andReturn([$first_row, $second_row]);

        $first_sibling  = $this->buildTransition($first_sibling_id, $workflow_id);
        $second_sibling = $this->buildTransition($second_sibling_id, $workflow_id);
        $this->transition_factory
            ->shouldReceive('getInstanceFromRow')
            ->with($first_row)
            ->andReturn($first_sibling);
        $this->transition_factory
            ->shouldReceive('getInstanceFromRow')
            ->with($second_row)
            ->andReturn($second_sibling);

        $transition = $this->buildTransitionWithToId($transition_id, $workflow_id, $to_id);

        $result = $this->transition_retriever->getSiblingTransitions($transition);

        $expected = new TransitionCollection($first_sibling, $second_sibling);
        $this->assertEquals($expected, $result);
    }

    public function testGetSiblingTransitionsThrowsWhenNoSibling() : void
    {
        $this->transition_dao->shouldReceive('searchSiblings')->andReturnFalse();
        $transition = $this->buildTransitionWithToId(19, 53, 483);

        $this->expectException(NoSiblingTransitionException::class);

        $this->transition_retriever->getSiblingTransitions($transition);
    }

    public function testGetFirstSiblingTransitionChoosesFirstTransitionThatIsNotFromNewArtifact() : void
    {
        $workflow_id           = 45;
        $transition_id         = 79;
        $to_id                 = 656;
        $sibling_transition_id = 76;
        $sibling_row           = ['transition_id' => $sibling_transition_id, 'from_id' => 871, 'to_id' => $to_id];
        $sibling_from_new_row  = ['transition_id' => 81, 'from_id' => '0', 'to_id' => $to_id];
        $this->transition_dao
            ->shouldReceive('searchSiblings')
            ->with($workflow_id, $to_id, $transition_id)
            ->andReturn([$sibling_from_new_row, $sibling_row]);

        $sibling_transition = $this->buildTransition($sibling_transition_id, $workflow_id);
        $this->transition_factory
            ->shouldReceive('getInstanceFromRow')
            ->with($sibling_row)
            ->andReturn($sibling_transition);

        $transition = $this->buildTransitionWithToId($transition_id, $workflow_id, $to_id);

        $result = $this->transition_retriever->getFirstSiblingTransition($transition);

        $this->assertEquals($sibling_transition, $result);
    }

    public function testGetFirstSiblingTransitionChoosesTransitionFromNewArtifactWhenItsTheOnlyOne() : void
    {
        $workflow_id           = 45;
        $transition_id         = 79;
        $to_id                 = 656;
        $sibling_transition_id = 81;
        $sibling_from_new_row  = ['transition_id' => $sibling_transition_id, 'from_id' => '0', 'to_id' => $to_id];
        $this->transition_dao
            ->shouldReceive('searchSiblings')
            ->with($workflow_id, $to_id, $transition_id)
            ->andReturn([$sibling_from_new_row]);

        $sibling_transition = $this->buildTransition($sibling_transition_id, $workflow_id);
        $this->transition_factory
            ->shouldReceive('getInstanceFromRow')
            ->with($sibling_from_new_row)
            ->andReturn($sibling_transition);

        $transition = $this->buildTransitionWithToId($transition_id, $workflow_id, $to_id);

        $result = $this->transition_retriever->getFirstSiblingTransition($transition);

        $this->assertEquals($sibling_transition, $result);
    }

    public function testGetFirstSiblingTransitionThrowsWhenNoSiblingTransition() : void
    {
        $this->transition_dao->shouldReceive('searchSiblings')->andReturn([]);
        $transition = $this->buildTransitionWithToId(16, 17, 163);

        $this->expectException(NoSiblingTransitionException::class);

        $this->transition_retriever->getFirstSiblingTransition($transition);
    }

    public function testGetFirstSiblingTransitionThrowsWhenDBError() : void
    {
        $this->transition_dao->shouldReceive('searchSiblings')->andReturnFalse();
        $transition = $this->buildTransitionWithToId(29, 52, 748);

        $this->expectException(NoSiblingTransitionException::class);

        $this->transition_retriever->getFirstSiblingTransition($transition);
    }

    private function buildTransition(int $transition_id, int $workflow_id): Transition
    {
        $from = Mockery::mock(\Tracker_FormElement_Field_List_Value::class);
        $to   = Mockery::mock(\Tracker_FormElement_Field_List_Value::class);
        return new Transition($transition_id, $workflow_id, $from, $to);
    }

    private function buildTransitionWithToId(int $transition_id, int $workflow_id, int $to_id): Transition
    {
        $from = Mockery::mock(\Tracker_FormElement_Field_List_Value::class);
        $to   = Mockery::mock(\Tracker_FormElement_Field_List_Value::class)
            ->shouldReceive('getId')
            ->andReturn($to_id)
            ->getMock();
        return new Transition($transition_id, $workflow_id, $from, $to);
    }

    public function testFirstTransitionForAnArtifactStateCanBeRetrieved() : void
    {
        $artifact = Mockery::mock(Tracker_Artifact::class);

        $workflow_id = 963;
        $workflow    = Mockery::mock(Workflow::class);
        $workflow->shouldReceive('getId')->andReturn($workflow_id);
        $workflow->shouldReceive('isUsed')->andReturn(true);
        $workflow->shouldReceive('isAdvanced')->andReturn(false);
        $workflow->shouldReceive('getField')->andReturn(Mockery::mock(Tracker_FormElement_Field::class));
        $artifact->shouldReceive('getWorkflow')->andReturn($workflow);

        $changeset_value = Mockery::mock(Tracker_Artifact_ChangesetValue::class);

        $last_changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $last_changeset->shouldReceive('getValue')->andReturn($changeset_value);
        $artifact->shouldReceive('getLastChangeset')->andReturn($last_changeset);

        $changeset_value->shouldReceive('getValue')->andReturn(['147']);

        $this->transition_dao->shouldReceive('searchFirstTransition')->andReturn(['row']);
        $expected_transition = Mockery::mock(Transition::class);
        $this->transition_factory->shouldReceive('getInstanceFromRow')->andReturn($expected_transition);

        $transition = $this->transition_retriever->getFirstTransitionForCurrentState($artifact);
        $this->assertSame($expected_transition, $transition);
    }

    public function testFirstTransitionForAnArtifactStateDoesNotExistWhenThereIsNoWorkflow() : void
    {
        $artifact = Mockery::mock(Tracker_Artifact::class);
        $artifact->shouldReceive('getWorkflow')->andReturn(null);

        $this->expectException(NoTransitionForStateException::class);
        $this->transition_retriever->getFirstTransitionForCurrentState($artifact);
    }

    public function testFirstTransitionForAnArtifactStateDoesNotExistWhenWorkflowIsNotUsed() : void
    {
        $artifact = Mockery::mock(Tracker_Artifact::class);

        $workflow = Mockery::mock(Workflow::class);
        $workflow->shouldReceive('isUsed')->andReturn(false);
        $artifact->shouldReceive('getWorkflow')->andReturn($workflow);

        $this->expectException(NoTransitionForStateException::class);
        $this->transition_retriever->getFirstTransitionForCurrentState($artifact);
    }

    public function testFirstTransitionForAnArtifactStateCanNotBeFoundWhenWorkflowIsInAdvancedMode() : void
    {
        $artifact = Mockery::mock(Tracker_Artifact::class);

        $workflow = Mockery::mock(Workflow::class);
        $workflow->shouldReceive('isUsed')->andReturn(true);
        $workflow->shouldReceive('isAdvanced')->andReturn(true);
        $artifact->shouldReceive('getWorkflow')->andReturn($workflow);

        $this->expectException(NoTransitionForStateException::class);
        $this->transition_retriever->getFirstTransitionForCurrentState($artifact);
    }

    public function testFirstTransitionCanNotBeFoundForAnArtifactStateWhenChangesetsAreMissing() : void
    {
        $artifact = Mockery::mock(Tracker_Artifact::class);

        $workflow = Mockery::mock(Workflow::class);
        $workflow->shouldReceive('isUsed')->andReturn(true);
        $workflow->shouldReceive('isAdvanced')->andReturn(false);
        $artifact->shouldReceive('getWorkflow')->andReturn($workflow);

        $artifact->shouldReceive('getLastChangeset')->andReturn(null);

        $this->expectException(NoTransitionForStateException::class);
        $this->transition_retriever->getFirstTransitionForCurrentState($artifact);
    }


    public function testFirstTransitionCanNotBeFoundForAnArtifactStateWhenChangesetValueForAFieldDoesNotExist() : void
    {
        $artifact = Mockery::mock(Tracker_Artifact::class);

        $workflow = Mockery::mock(Workflow::class);
        $workflow->shouldReceive('isUsed')->andReturn(true);
        $workflow->shouldReceive('isAdvanced')->andReturn(false);
        $artifact->shouldReceive('getWorkflow')->andReturn($workflow);
        $workflow->shouldReceive('getField')->andReturn(Mockery::mock(Tracker_FormElement_Field::class));

        $last_changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $last_changeset->shouldReceive('getValue')->andReturn(null);
        $artifact->shouldReceive('getLastChangeset')->andReturn($last_changeset);

        $this->expectException(NoTransitionForStateException::class);
        $this->transition_retriever->getFirstTransitionForCurrentState($artifact);
    }

    public function testFirstTransitionCanNotBeFoundForAnArtifactStateWhenItCannotBeFoundInDB() : void
    {
        $artifact = Mockery::mock(Tracker_Artifact::class);

        $workflow_id = 963;
        $workflow    = Mockery::mock(Workflow::class);
        $workflow->shouldReceive('getId')->andReturn($workflow_id);
        $workflow->shouldReceive('isUsed')->andReturn(true);
        $workflow->shouldReceive('isAdvanced')->andReturn(false);
        $workflow->shouldReceive('getField')->andReturn(Mockery::mock(Tracker_FormElement_Field::class));
        $artifact->shouldReceive('getWorkflow')->andReturn($workflow);

        $changeset_value = Mockery::mock(Tracker_Artifact_ChangesetValue::class);

        $last_changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $last_changeset->shouldReceive('getValue')->andReturn($changeset_value);
        $artifact->shouldReceive('getLastChangeset')->andReturn($last_changeset);

        $changeset_value->shouldReceive('getValue')->andReturn(['147']);

        $this->transition_dao->shouldReceive('searchFirstTransition')->andReturn(false);

        $this->expectException(NoTransitionForStateException::class);
        $this->transition_retriever->getFirstTransitionForCurrentState($artifact);
    }
}
