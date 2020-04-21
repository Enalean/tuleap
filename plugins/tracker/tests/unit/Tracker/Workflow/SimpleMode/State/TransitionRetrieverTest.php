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

namespace Tuleap\Tracker\Workflow\SimpleMode\State;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_Artifact;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue;
use Tracker_FormElement_Field;
use Transition;
use Tuleap\Tracker\Workflow\Transition\NoTransitionForStateException;
use Workflow;

final class TransitionRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var TransitionRetriever */
    private $transition_retriever;

    /** @var Mockery\MockInterface */
    private $state_factory;

    /** @var Mockery\MockInterface */
    private $transition_extractor;

    protected function setUp(): void
    {
        $this->state_factory        = Mockery::mock(StateFactory::class);
        $this->transition_extractor = new TransitionExtractor();

        $this->transition_retriever = new TransitionRetriever($this->state_factory, $this->transition_extractor);
    }

    public function testTransitionForAnArtifactStateCanBeRetrieved(): void
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

        $expected_transition = Mockery::mock(Transition::class);

        $state = new State(1, [$expected_transition]);

        $this->state_factory->shouldReceive('getStateFromValueId')
            ->once()
            ->andReturn($state);

        $transition = $this->transition_retriever->getReferenceTransitionForCurrentState($artifact);
        $this->assertSame($expected_transition, $transition);
    }

    public function testFirstTransitionForAnArtifactStateDoesNotExistWhenThereIsNoWorkflow(): void
    {
        $artifact = Mockery::mock(Tracker_Artifact::class);
        $artifact->shouldReceive('getWorkflow')->andReturn(null);

        $this->expectException(NoTransitionForStateException::class);
        $this->transition_retriever->getReferenceTransitionForCurrentState($artifact);
    }

    public function testFirstTransitionForAnArtifactStateDoesNotExistWhenWorkflowIsNotUsed(): void
    {
        $artifact = Mockery::mock(Tracker_Artifact::class);

        $workflow = Mockery::mock(Workflow::class);
        $workflow->shouldReceive('isUsed')->andReturn(false);
        $artifact->shouldReceive('getWorkflow')->andReturn($workflow);

        $this->expectException(NoTransitionForStateException::class);
        $this->transition_retriever->getReferenceTransitionForCurrentState($artifact);
    }

    public function testFirstTransitionForAnArtifactStateCanNotBeFoundWhenWorkflowIsInAdvancedMode(): void
    {
        $artifact = Mockery::mock(Tracker_Artifact::class);

        $workflow = Mockery::mock(Workflow::class);
        $workflow->shouldReceive('isUsed')->andReturn(true);
        $workflow->shouldReceive('isAdvanced')->andReturn(true);
        $artifact->shouldReceive('getWorkflow')->andReturn($workflow);

        $this->expectException(NoTransitionForStateException::class);
        $this->transition_retriever->getReferenceTransitionForCurrentState($artifact);
    }

    public function testFirstTransitionCanNotBeFoundForAnArtifactStateWhenChangesetsAreMissing(): void
    {
        $artifact = Mockery::mock(Tracker_Artifact::class);

        $workflow = Mockery::mock(Workflow::class);
        $workflow->shouldReceive('isUsed')->andReturn(true);
        $workflow->shouldReceive('isAdvanced')->andReturn(false);
        $artifact->shouldReceive('getWorkflow')->andReturn($workflow);

        $artifact->shouldReceive('getLastChangeset')->andReturn(null);

        $this->expectException(NoTransitionForStateException::class);
        $this->transition_retriever->getReferenceTransitionForCurrentState($artifact);
    }

    public function testFirstTransitionCanNotBeFoundForAnArtifactStateWhenChangesetValueForAFieldDoesNotExist(): void
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
        $this->transition_retriever->getReferenceTransitionForCurrentState($artifact);
    }
}
