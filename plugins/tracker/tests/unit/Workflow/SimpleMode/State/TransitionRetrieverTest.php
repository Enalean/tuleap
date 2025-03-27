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

use PHPUnit\Framework\MockObject\MockObject;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue;
use Tracker_FormElement_Field;
use Transition;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Workflow\Transition\NoTransitionForStateException;
use Workflow;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TransitionRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private TransitionRetriever $transition_retriever;

    private StateFactory&MockObject $state_factory;

    private TransitionExtractor $transition_extractor;

    protected function setUp(): void
    {
        $this->state_factory        = $this->createMock(StateFactory::class);
        $this->transition_extractor = new TransitionExtractor();

        $this->transition_retriever = new TransitionRetriever($this->state_factory, $this->transition_extractor);
    }

    public function testTransitionForAnArtifactStateCanBeRetrieved(): void
    {
        $artifact = $this->createMock(Artifact::class);

        $workflow_id = 963;
        $workflow    = $this->createMock(Workflow::class);
        $workflow->method('getId')->willReturn($workflow_id);
        $workflow->method('isUsed')->willReturn(true);
        $workflow->method('isAdvanced')->willReturn(false);
        $workflow->method('getField')->willReturn($this->createMock(Tracker_FormElement_Field::class));
        $artifact->method('getWorkflow')->willReturn($workflow);

        $changeset_value = $this->createMock(Tracker_Artifact_ChangesetValue::class);

        $last_changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $last_changeset->method('getValue')->willReturn($changeset_value);
        $artifact->method('getLastChangeset')->willReturn($last_changeset);

        $changeset_value->method('getValue')->willReturn(['147']);

        $expected_transition = $this->createMock(Transition::class);

        $state = new State(1, [$expected_transition]);

        $this->state_factory
            ->expects($this->once())
            ->method('getStateFromValueId')
            ->willReturn($state);

        $transition = $this->transition_retriever->getReferenceTransitionForCurrentState($artifact);
        self::assertSame($expected_transition, $transition);
    }

    public function testFirstTransitionForAnArtifactStateDoesNotExistWhenThereIsNoWorkflow(): void
    {
        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getWorkflow')->willReturn(null);

        $this->expectException(NoTransitionForStateException::class);
        $this->transition_retriever->getReferenceTransitionForCurrentState($artifact);
    }

    public function testFirstTransitionForAnArtifactStateDoesNotExistWhenWorkflowIsNotUsed(): void
    {
        $artifact = $this->createMock(Artifact::class);

        $workflow = $this->createMock(Workflow::class);
        $workflow->method('isUsed')->willReturn(false);
        $artifact->method('getWorkflow')->willReturn($workflow);

        $this->expectException(NoTransitionForStateException::class);
        $this->transition_retriever->getReferenceTransitionForCurrentState($artifact);
    }

    public function testFirstTransitionForAnArtifactStateCanNotBeFoundWhenWorkflowIsInAdvancedMode(): void
    {
        $artifact = $this->createMock(Artifact::class);

        $workflow = $this->createMock(Workflow::class);
        $workflow->method('isUsed')->willReturn(true);
        $workflow->method('isAdvanced')->willReturn(true);
        $artifact->method('getWorkflow')->willReturn($workflow);

        $this->expectException(NoTransitionForStateException::class);
        $this->transition_retriever->getReferenceTransitionForCurrentState($artifact);
    }

    public function testFirstTransitionCanNotBeFoundForAnArtifactStateWhenChangesetsAreMissing(): void
    {
        $artifact = $this->createMock(Artifact::class);

        $workflow = $this->createMock(Workflow::class);
        $workflow->method('isUsed')->willReturn(true);
        $workflow->method('isAdvanced')->willReturn(false);
        $artifact->method('getWorkflow')->willReturn($workflow);

        $artifact->method('getLastChangeset')->willReturn(null);

        $this->expectException(NoTransitionForStateException::class);
        $this->transition_retriever->getReferenceTransitionForCurrentState($artifact);
    }

    public function testFirstTransitionCanNotBeFoundForAnArtifactStateWhenChangesetValueForAFieldDoesNotExist(): void
    {
        $artifact = $this->createMock(Artifact::class);

        $workflow = $this->createMock(Workflow::class);
        $workflow->method('isUsed')->willReturn(true);
        $workflow->method('isAdvanced')->willReturn(false);
        $artifact->method('getWorkflow')->willReturn($workflow);
        $workflow->method('getField')->willReturn($this->createMock(Tracker_FormElement_Field::class));

        $last_changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $last_changeset->method('getValue')->willReturn(null);
        $artifact->method('getLastChangeset')->willReturn($last_changeset);

        $this->expectException(NoTransitionForStateException::class);
        $this->transition_retriever->getReferenceTransitionForCurrentState($artifact);
    }
}
