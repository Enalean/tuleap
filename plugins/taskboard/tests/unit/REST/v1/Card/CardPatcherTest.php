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
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker;
use Tracker_FormElement_Field_Computed;
use Tracker_FormElement_Field_Float;
use Tracker_FormElement_Field_Integer;
use Tracker_FormElement_Field_Numeric;
use Tracker_FormElementFactory;
use Tracker_NoChangeException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\Artifact\ArtifactUpdater;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactValuesRepresentationBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class CardPatcherTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private MockObject&Tracker_FormElementFactory $factory;
    private MockObject&ArtifactUpdater $updater;
    private CardPatcher $patcher;
    private PFUser $user;
    private Artifact $artifact;
    private Tracker $tracker;
    private CardPatchRepresentation $payload;

    protected function setUp(): void
    {
        $this->user     = UserTestBuilder::aUser()->build();
        $this->tracker  = TrackerTestBuilder::aTracker()->build();
        $this->artifact = ArtifactTestBuilder::anArtifact(1)->inTracker($this->tracker)->build();

        $this->payload = CardPatchRepresentation::build(3.14);

        $this->factory = $this->createMock(Tracker_FormElementFactory::class);
        $this->updater = $this->createMock(ArtifactUpdater::class);

        $this->patcher = new CardPatcher($this->factory, $this->updater);
    }

    public function testItRaisesExceptionIfNoField(): void
    {
        $this->factory
            ->expects(self::once())
            ->method('getNumericFieldByNameForUser')
            ->with($this->tracker, $this->user, \Tracker::REMAINING_EFFORT_FIELD_NAME)
            ->willReturn(null);

        $this->expectException(RestException::class);

        $this->patcher->patchCard($this->artifact, $this->user, $this->payload);
    }

    public function testItRaisesExceptionIfFieldIsNotUpdatable(): void
    {
        $field = $this->createMock(Tracker_FormElement_Field_Float::class);
        $field->method('userCanUpdate')
              ->with($this->user)
              ->willReturn(false);

        $this->factory
            ->expects(self::once())
            ->method('getNumericFieldByNameForUser')
            ->with($this->tracker, $this->user, \Tracker::REMAINING_EFFORT_FIELD_NAME)
            ->willReturn($field);

        $this->expectException(RestException::class);

        $this->patcher->patchCard($this->artifact, $this->user, $this->payload);
    }

    public function testItUpdatesTheArtifactWithFormattedValueForFloatField(): void
    {
        $field = $this->createMock(Tracker_FormElement_Field_Float::class);

        $expected_value = ArtifactValuesRepresentationBuilder::aRepresentation(1001)->withValue(3.14)->build();

        self::assertUpdateIsCalledWithExpectedValue($field, $expected_value);
    }

    public function testItUpdatesTheArtifactWithFormattedValueForIntegerField(): void
    {
        $field = $this->createMock(Tracker_FormElement_Field_Integer::class);

        $expected_value = ArtifactValuesRepresentationBuilder::aRepresentation(1001)->withValue(3.14)->build();

        self::assertUpdateIsCalledWithExpectedValue($field, $expected_value);
    }

    public function testItUpdatesTheArtifactWithFormattedValueForComputedField(): void
    {
        $field = $this->createMock(Tracker_FormElement_Field_Computed::class);

        $expected_value = ArtifactValuesRepresentationBuilder::aRepresentation(1001)->withManualValue(3.14)->build();

        self::assertUpdateIsCalledWithExpectedValue($field, $expected_value);
    }

    public function testItDoesNotRaisesExceptionIfThereIsNoChange(): void
    {
        $field = $this->createMock(Tracker_FormElement_Field_Integer::class);

        $field->method('userCanUpdate')
              ->with($this->user)
              ->willReturn(true);
        $field->method('getId')
              ->willReturn("1001");

        $this->factory
            ->expects(self::once())
            ->method('getNumericFieldByNameForUser')
            ->with($this->tracker, $this->user, \Tracker::REMAINING_EFFORT_FIELD_NAME)
            ->willReturn($field);

        $this->updater
            ->method('update')
            ->willThrowException($this->createMock(Tracker_NoChangeException::class));

        $this->patcher->patchCard($this->artifact, $this->user, $this->payload);
    }

    private function assertUpdateIsCalledWithExpectedValue(
        MockObject&Tracker_FormElement_Field_Numeric $field,
        ArtifactValuesRepresentation $expected_value,
    ): void {
        $field->method('userCanUpdate')
              ->with($this->user)
              ->willReturn(true);
        $field->method('getId')
              ->willReturn("1001");

        $this->factory
            ->expects(self::once())
            ->method('getNumericFieldByNameForUser')
            ->with($this->tracker, $this->user, \Tracker::REMAINING_EFFORT_FIELD_NAME)
            ->willReturn($field);

        $this->updater
            ->expects(self::once())
            ->method('update')
            ->with($this->user, $this->artifact, [$expected_value]);

        $this->patcher->patchCard($this->artifact, $this->user, $this->payload);
    }
}
