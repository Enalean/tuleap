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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_Artifact;
use Tracker_FormElement_Field;
use Transition;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionRetriever;
use Tuleap\Tracker\Workflow\Transition\NoTransitionForStateException;

final class FrozenFieldDetectorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\MockInterface|TransitionRetriever
     */
    private $transition_retriever;
    /** @var FrozenFieldDetector */
    private $frozen_field_detector;
    /** @var Mockery\MockInterface */
    private $frozen_retriever;

    protected function setUp(): void
    {
        $this->transition_retriever  = Mockery::mock(TransitionRetriever::class);
        $this->frozen_retriever      = Mockery::mock(FrozenFieldsRetriever::class);
        $this->frozen_field_detector = new FrozenFieldDetector($this->transition_retriever, $this->frozen_retriever);
    }

    public function testIsFieldFrozenReturnsFalseWhenNoTransitionIsDefinedForCurrentState(): void
    {
        $this->transition_retriever->shouldReceive('getReferenceTransitionForCurrentState')
            ->andThrow(NoTransitionForStateException::class);

        $this->assertFalse(
            $this->frozen_field_detector->isFieldFrozen(
                Mockery::mock(Tracker_Artifact::class),
                Mockery::mock(Tracker_FormElement_Field::class)
            )
        );
    }

    public function testIsFieldFrozenReturnsFalseWhenNoFrozenFieldsPostAction(): void
    {
        $artifact = Mockery::mock(Tracker_Artifact::class);
        $field    = Mockery::mock(Tracker_FormElement_Field::class);

        $transition = Mockery::mock(\Transition::class);
        $this->transition_retriever->shouldReceive('getReferenceTransitionForCurrentState')
            ->andReturns($transition);
        $this->frozen_retriever
            ->shouldReceive('getFrozenFields')
            ->with($transition)
            ->andThrows(new NoFrozenFieldsPostActionException());

        $this->assertFalse(
            $this->frozen_field_detector->isFieldFrozen($artifact, $field)
        );
    }

    public function testIsFieldFrozenReturnsFalseWhenGivenFieldIsNotAmongFrozenFields(): void
    {
        $field    = Mockery::mock(Tracker_FormElement_Field::class);
        $artifact = $this->mockArtifactWithWorkflow();

        $this->transition_retriever->shouldReceive('getReferenceTransitionForCurrentState')
            ->andReturns(Mockery::mock(Transition::class));
        $this->mockFrozenFields(242, 566);
        $field->shouldReceive('getId')->andReturns('312');

        $this->assertFalse(
            $this->frozen_field_detector->isFieldFrozen($artifact, $field)
        );
    }

    public function testIsFieldFrozenReturnsTrueWhenGivenFieldIsReadOnly(): void
    {
        $field    = Mockery::mock(Tracker_FormElement_Field::class);
        $artifact = $this->mockArtifactWithWorkflow();

        $this->transition_retriever->shouldReceive('getReferenceTransitionForCurrentState')
            ->andReturns(Mockery::mock(Transition::class));
        $this->mockFrozenFields(242, 312, 566);
        $field->shouldReceive('getId')->andReturns('312');

        $this->assertTrue(
            $this->frozen_field_detector->isFieldFrozen($artifact, $field)
        );
    }

    private function mockSimpleModeWorkflow(): Mockery\MockInterface
    {
        $workflow = Mockery::mock(\Workflow::class);
        $workflow->shouldReceive('isUsed')->andReturnTrue();
        $workflow->shouldReceive('isAdvanced')->andReturnFalse();

        return $workflow;
    }

    private function mockArtifactWithWorkflow(): Mockery\MockInterface
    {
        $transition = Mockery::mock(\Transition::class);
        $artifact   = Mockery::mock(Tracker_Artifact::class);
        $workflow   = $this->mockSimpleModeWorkflow();
        $workflow->shouldReceive('getReferenceTransitionForCurrentState')
            ->andReturns($transition);
        $artifact->shouldReceive('getWorkflow')->andReturns($workflow);

        return $artifact;
    }

    private function mockFrozenFields(int ...$field_ids): void
    {
        $read_only_fields_post_action = Mockery::mock(FrozenFields::class);
        $this->frozen_retriever
            ->shouldReceive('getFrozenFields')
            ->andReturns($read_only_fields_post_action);
        $read_only_fields_post_action
            ->shouldReceive('getFieldIds')
            ->andReturns($field_ids);
    }
}
