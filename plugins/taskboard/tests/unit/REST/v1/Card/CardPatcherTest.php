<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\REST\v1\Card;

use Luracast\Restler\RestException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tracker_FormElement_Field_Computed;
use Tracker_FormElement_Field_Float;
use Tracker_FormElement_Field_Integer;
use Tracker_FormElement_Field_Numeric;
use Tracker_FormElementFactory;
use Tracker_NoChangeException;
use Tracker_REST_Artifact_ArtifactUpdater;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;

class CardPatcherTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_FormElementFactory
     */
    private $factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_REST_Artifact_ArtifactUpdater
     */
    private $updater;
    /**
     * @var CardPatcher
     */
    private $patcher;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Artifact
     */
    private $artifact;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker
     */
    private $tracker;
    /**
     * @var CardPatchRepresentation
     */
    private $payload;

    protected function setUp(): void
    {
        $this->user     = Mockery::mock(PFUser::class);
        $this->artifact = Mockery::mock(Artifact::class);
        $this->tracker  = Mockery::mock(Tracker::class);

        $this->artifact->shouldReceive('getTracker')->andReturn($this->tracker);

        $this->payload                   = new CardPatchRepresentation();
        $this->payload->remaining_effort = 3.14;

        $this->factory = Mockery::mock(Tracker_FormElementFactory::class);
        $this->updater = Mockery::mock(Tracker_REST_Artifact_ArtifactUpdater::class);

        $this->patcher = new CardPatcher($this->factory, $this->updater);
    }

    public function testItRaisesExceptionIfNoField(): void
    {
        $this->factory
            ->shouldReceive('getNumericFieldByNameForUser')
            ->with($this->tracker, $this->user, \Tracker::REMAINING_EFFORT_FIELD_NAME)
            ->once()
            ->andReturn(null);

        $this->expectException(RestException::class);

        $this->patcher->patchCard($this->artifact, $this->user, $this->payload);
    }

    public function testItRaisesExceptionIfFieldIsNotUpdatable(): void
    {
        $field = Mockery::mock(Tracker_FormElement_Field_Float::class);
        $field->shouldReceive('userCanUpdate')
              ->with($this->user)
              ->andReturn(false);

        $this->factory
            ->shouldReceive('getNumericFieldByNameForUser')
            ->with($this->tracker, $this->user, \Tracker::REMAINING_EFFORT_FIELD_NAME)
            ->once()
            ->andReturn($field);

        $this->expectException(RestException::class);

        $this->patcher->patchCard($this->artifact, $this->user, $this->payload);
    }

    public function testItUpdatesTheArtifactWithFormattedValueForFloatField(): void
    {
        $field = Mockery::mock(Tracker_FormElement_Field_Float::class);

        $expected_value           = new ArtifactValuesRepresentation();
        $expected_value->field_id = 1001;
        $expected_value->value    = 3.14;

        $this->assertUpdateIsCalledWithExpectedValue($field, $expected_value);
    }

    public function testItUpdatesTheArtifactWithFormattedValueForIntegerField(): void
    {
        $field = Mockery::mock(Tracker_FormElement_Field_Integer::class);

        $expected_value           = new ArtifactValuesRepresentation();
        $expected_value->field_id = 1001;
        $expected_value->value    = 3.14;

        $this->assertUpdateIsCalledWithExpectedValue($field, $expected_value);
    }

    public function testItUpdatesTheArtifactWithFormattedValueForComputedField(): void
    {
        $field = Mockery::mock(Tracker_FormElement_Field_Computed::class);

        $expected_value                  = new ArtifactValuesRepresentation();
        $expected_value->field_id        = 1001;
        $expected_value->manual_value    = 3.14;
        $expected_value->is_autocomputed = false;

        $this->assertUpdateIsCalledWithExpectedValue($field, $expected_value);
    }

    public function testItDoesNotRaisesExceptionIfThereIsNoChange(): void
    {
        $field = Mockery::mock(Tracker_FormElement_Field_Integer::class);

        $field->shouldReceive('userCanUpdate')
              ->with($this->user)
              ->andReturn(true);
        $field->shouldReceive('getId')
              ->andReturn("1001");

        $this->factory
            ->shouldReceive('getNumericFieldByNameForUser')
            ->with($this->tracker, $this->user, \Tracker::REMAINING_EFFORT_FIELD_NAME)
            ->once()
            ->andReturn($field);

        $this->updater
            ->shouldReceive('update')
            ->andThrow(Mockery::mock(Tracker_NoChangeException::class));

        $this->patcher->patchCard($this->artifact, $this->user, $this->payload);
    }

    private function assertUpdateIsCalledWithExpectedValue(Tracker_FormElement_Field_Numeric $field, ArtifactValuesRepresentation $expected_value): void
    {
        $field->shouldReceive('userCanUpdate')
              ->with($this->user)
              ->andReturn(true);
        $field->shouldReceive('getId')
              ->andReturn("1001");

        $this->factory
            ->shouldReceive('getNumericFieldByNameForUser')
            ->with($this->tracker, $this->user, \Tracker::REMAINING_EFFORT_FIELD_NAME)
            ->once()
            ->andReturn($field);

        $this->updater
            ->shouldReceive('update')
            ->with($this->user, $this->artifact, [$expected_value])
            ->once();

        $this->patcher->patchCard($this->artifact, $this->user, $this->payload);
    }
}
