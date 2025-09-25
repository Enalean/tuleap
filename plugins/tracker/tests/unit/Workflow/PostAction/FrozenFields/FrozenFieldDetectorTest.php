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

namespace Tuleap\Tracker\Workflow\PostAction\FrozenFields;

use PHPUnit\Framework\MockObject\MockObject;
use Transition;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionRetriever;
use Tuleap\Tracker\Workflow\Transition\NoTransitionForStateException;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FrozenFieldDetectorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private TransitionRetriever&MockObject $transition_retriever;
    private FrozenFieldDetector $frozen_field_detector;
    private FrozenFieldsRetriever&MockObject $frozen_retriever;

    #[\Override]
    protected function setUp(): void
    {
        $this->transition_retriever  = $this->createMock(TransitionRetriever::class);
        $this->frozen_retriever      = $this->createMock(FrozenFieldsRetriever::class);
        $this->frozen_field_detector = new FrozenFieldDetector($this->transition_retriever, $this->frozen_retriever);
    }

    public function testIsFieldFrozenReturnsFalseWhenNoTransitionIsDefinedForCurrentState(): void
    {
        $this->transition_retriever->method('getReferenceTransitionForCurrentState')
            ->willThrowException(new NoTransitionForStateException());

        $this->assertFalse(
            $this->frozen_field_detector->isFieldFrozen(
                ArtifactTestBuilder::anArtifact(101)->build(),
                StringFieldBuilder::aStringField(312)->build(),
            )
        );
    }

    public function testIsFieldFrozenReturnsFalseWhenNoFrozenFieldsPostAction(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(101)->build();
        $field    = StringFieldBuilder::aStringField(312)->build();

        $transition = $this->createMock(\Transition::class);
        $this->transition_retriever->method('getReferenceTransitionForCurrentState')
            ->willReturn($transition);
        $this->frozen_retriever
            ->method('getFrozenFields')
            ->with($transition)
            ->willThrowException(new NoFrozenFieldsPostActionException());

        $this->assertFalse(
            $this->frozen_field_detector->isFieldFrozen($artifact, $field)
        );
    }

    public function testIsFieldFrozenReturnsFalseWhenGivenFieldIsNotAmongFrozenFields(): void
    {
        $field    = StringFieldBuilder::aStringField(312)->build();
        $artifact = $this->mockArtifactWithWorkflow();

        $this->transition_retriever->method('getReferenceTransitionForCurrentState')
            ->willReturn($this->createMock(Transition::class));
        $this->mockFrozenFields(242, 566);

        $this->assertFalse(
            $this->frozen_field_detector->isFieldFrozen($artifact, $field)
        );
    }

    public function testIsFieldFrozenReturnsTrueWhenGivenFieldIsReadOnly(): void
    {
        $field    = StringFieldBuilder::aStringField(312)->build();
        $artifact = $this->mockArtifactWithWorkflow();

        $this->transition_retriever->method('getReferenceTransitionForCurrentState')
            ->willReturn($this->createMock(Transition::class));
        $this->mockFrozenFields(242, 312, 566);

        $this->assertTrue(
            $this->frozen_field_detector->isFieldFrozen($artifact, $field)
        );
    }

    private function mockSimpleModeWorkflow(): \Workflow&MockObject
    {
        $workflow = $this->createMock(\Workflow::class);
        $workflow->method('isUsed')->willReturn(true);
        $workflow->method('isAdvanced')->willReturn(false);

        return $workflow;
    }

    private function mockArtifactWithWorkflow(): Artifact&MockObject
    {
        $artifact = $this->createMock(Artifact::class);
        $workflow = $this->mockSimpleModeWorkflow();

        $artifact->method('getWorkflow')->willReturn($workflow);

        return $artifact;
    }

    private function mockFrozenFields(int ...$field_ids): void
    {
        $read_only_fields_post_action = $this->createMock(FrozenFields::class);
        $this->frozen_retriever
            ->method('getFrozenFields')
            ->willReturn($read_only_fields_post_action);
        $read_only_fields_post_action
            ->method('getFieldIds')
            ->willReturn($field_ids);
    }
}
