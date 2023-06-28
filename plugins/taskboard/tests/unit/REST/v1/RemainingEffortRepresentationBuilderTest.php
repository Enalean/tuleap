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

namespace Tuleap\Taskboard\REST\v1;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\AgileDashboard\RemainingEffortValueRetriever;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class RemainingEffortRepresentationBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private MockObject&\Tracker_FormElementFactory $factory;
    private MockObject&RemainingEffortValueRetriever $retriever;
    private RemainingEffortRepresentationBuilder $builder;
    private \PFUser $user;
    private \Tuleap\Tracker\Artifact\Artifact $artifact;
    private \Tracker $tracker;

    protected function setUp(): void
    {
        $this->user     = UserTestBuilder::aUser()->build();
        $this->tracker  = TrackerTestBuilder::aTracker()->build();
        $this->artifact = ArtifactTestBuilder::anArtifact(1)->inTracker($this->tracker)->build();

        $this->factory   = $this->createMock(\Tracker_FormElementFactory::class);
        $this->retriever = $this->createMock(RemainingEffortValueRetriever::class);

        $this->builder = new RemainingEffortRepresentationBuilder($this->retriever, $this->factory);
    }

    public function testItReturnsNullIfNoFieldIsDefined(): void
    {
        $this->factory
            ->expects(self::once())
            ->method('getNumericFieldByNameForUser')
            ->with($this->tracker, $this->user, \Tracker::REMAINING_EFFORT_FIELD_NAME)
            ->willReturn(null);

        self::assertNull($this->builder->getRemainingEffort($this->user, $this->artifact));
    }

    public function testItTellsIfUserCannotUpdateTheField(): void
    {
        $field = $this->createMock(\Tracker_FormElement_Field_Float::class);
        $field->method('userCanUpdate')
              ->with($this->user)
              ->willReturn(false);

        $this->factory
            ->expects(self::once())
            ->method('getNumericFieldByNameForUser')
            ->with($this->tracker, $this->user, \Tracker::REMAINING_EFFORT_FIELD_NAME)
            ->willReturn($field);

        $this->retriever
            ->method('getRemainingEffortValue');

        $representation = $this->builder->getRemainingEffort($this->user, $this->artifact);
        self::assertNotNull($representation);
        self::assertFalse($representation->can_update);
    }

    public function testItTellsIfUserCanUpdateTheField(): void
    {
        $field = $this->createMock(\Tracker_FormElement_Field_Float::class);
        $field->expects(self::once())
            ->method('userCanUpdate')
            ->with($this->user)
            ->willReturn(true);

        $this->factory
            ->expects(self::once())
            ->method('getNumericFieldByNameForUser')
            ->with($this->tracker, $this->user, \Tracker::REMAINING_EFFORT_FIELD_NAME)
            ->willReturn($field);

        $this->retriever
            ->method('getRemainingEffortValue');

        $representation = $this->builder->getRemainingEffort($this->user, $this->artifact);
        self::assertNotNull($representation);
        self::assertTrue($representation->can_update);
    }

    public function testItGivesTheFloatValueOfTheField(): void
    {
        $field = $this->createMock(\Tracker_FormElement_Field_Float::class);
        $field->method('userCanUpdate')
              ->with($this->user)
              ->willReturn(true);

        $this->factory
            ->expects(self::once())
            ->method('getNumericFieldByNameForUser')
            ->with($this->tracker, $this->user, \Tracker::REMAINING_EFFORT_FIELD_NAME)
            ->willReturn($field);

        $this->retriever
            ->expects(self::once())
            ->method('getRemainingEffortValue')
            ->with($this->user, $this->artifact)
            ->willReturn('3.14');

        $representation = $this->builder->getRemainingEffort($this->user, $this->artifact);
        self::assertNotNull($representation);
        self::assertEquals(3.14, $representation->value);
    }

    public function testItGivesANullValueIfValueIsNotNumeric(): void
    {
        $field = $this->createMock(\Tracker_FormElement_Field_Float::class);
        $field->expects(self::once())
            ->method('userCanUpdate')
            ->with($this->user)
            ->willReturn(true);

        $this->factory
            ->expects(self::once())
            ->method('getNumericFieldByNameForUser')
            ->with($this->tracker, $this->user, \Tracker::REMAINING_EFFORT_FIELD_NAME)
            ->willReturn($field);

        $this->retriever
            ->expects(self::once())
            ->method('getRemainingEffortValue')
            ->with($this->user, $this->artifact)
            ->willReturn('whatedver');

        $representation = $this->builder->getRemainingEffort($this->user, $this->artifact);
        self::assertNotNull($representation);
        self::assertNull($representation->value);
    }
}
