<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Roadmap\REST\v1;

use Psr\Log\NullLogger;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Semantic\Progress\IComputeProgression;
use Tuleap\Tracker\Semantic\Progress\SemanticProgress;
use Tuleap\Tracker\Semantic\Progress\SemanticProgressBuilder;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeNotConfigured;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeWithDuration;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeWithEndDate;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;

final class TaskRepresentationBuilderForTrackerCacheTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private TaskRepresentationBuilderForTrackerCache $cache;
    private \PHPUnit\Framework\MockObject\MockObject&SemanticTimeframeBuilder $semantic_timeframe_builder;
    private \PHPUnit\Framework\MockObject\MockObject&IRetrieveDependencies $dependencies_retriever;
    private \PHPUnit\Framework\MockObject\MockObject&SemanticProgressBuilder $progress_builder;
    private \PFUser $user;

    protected function setUp(): void
    {
        $this->semantic_timeframe_builder = $this->createMock(SemanticTimeframeBuilder::class);
        $this->dependencies_retriever     = $this->createMock(IRetrieveDependencies::class);
        $this->progress_builder           = $this->createMock(SemanticProgressBuilder::class);

        $this->cache = new TaskRepresentationBuilderForTrackerCache(
            $this->semantic_timeframe_builder,
            $this->dependencies_retriever,
            $this->progress_builder,
            new NullLogger()
        );

        $this->user = UserTestBuilder::aUser()->build();
    }

    public function testItReturnsNullWhenNoTitleField(): void
    {
        $tracker = $this->createMock(\Tracker::class);
        $tracker->method('getId')->willReturn(101);
        $tracker->method('getTitleField')->willReturn(null);

        self::assertNull($this->cache->getRepresentationBuilderForTracker($tracker, $this->user));
    }

    public function testItReturnsNullWhenTitleFieldIsNotReadable(): void
    {
        $title_field = $this->createMock(\Tracker_FormElement_Field_String::class);
        $title_field->method('userCanRead')->willReturn(false);

        $tracker = $this->createMock(\Tracker::class);
        $tracker->method('getId')->willReturn(101);
        $tracker->method('getTitleField')->willReturn($title_field);

        self::assertNull($this->cache->getRepresentationBuilderForTracker($tracker, $this->user));
    }

    public function testItReturnsNullWhenTimeframeIsNotDefined(): void
    {
        $title_field = $this->createMock(\Tracker_FormElement_Field_String::class);
        $title_field->method('userCanRead')->willReturn(true);

        $tracker = $this->createMock(\Tracker::class);
        $tracker->method('getId')->willReturn(101);
        $tracker->method('getTitleField')->willReturn($title_field);

        $this->semantic_timeframe_builder->method('getSemantic')->willReturn(
            new SemanticTimeframe($tracker, new TimeframeNotConfigured())
        );

        self::assertNull($this->cache->getRepresentationBuilderForTracker($tracker, $this->user));
    }

    public function testItReturnsNullWhenStartDateIsNotReadable(): void
    {
        $title_field = $this->createMock(\Tracker_FormElement_Field_String::class);
        $title_field->method('userCanRead')->willReturn(true);

        $tracker = $this->createMock(\Tracker::class);
        $tracker->method('getId')->willReturn(101);
        $tracker->method('getTitleField')->willReturn($title_field);

        $start_date = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $start_date->method('userCanRead')->willReturn(false);
        $end_date = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $end_date->method('userCanRead')->willReturn(true);

        $this->semantic_timeframe_builder->method('getSemantic')->willReturn(
            new SemanticTimeframe($tracker, new TimeframeWithEndDate($start_date, $end_date))
        );

        self::assertNull($this->cache->getRepresentationBuilderForTracker($tracker, $this->user));
    }

    public function testItReturnsNullWhenEndDateIsNotReadable(): void
    {
        $title_field = $this->createMock(\Tracker_FormElement_Field_String::class);
        $title_field->method('userCanRead')->willReturn(true);

        $tracker = $this->createMock(\Tracker::class);
        $tracker->method('getId')->willReturn(101);
        $tracker->method('getTitleField')->willReturn($title_field);

        $start_date = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $start_date->method('userCanRead')->willReturn(true);
        $end_date = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $end_date->method('userCanRead')->willReturn(false);

        $this->semantic_timeframe_builder->method('getSemantic')->willReturn(
            new SemanticTimeframe($tracker, new TimeframeWithEndDate($start_date, $end_date))
        );

        self::assertNull($this->cache->getRepresentationBuilderForTracker($tracker, $this->user));
    }

    public function testItReturnsNullWhenDurationIsNotReadable(): void
    {
        $title_field = $this->createMock(\Tracker_FormElement_Field_String::class);
        $title_field->method('userCanRead')->willReturn(true);

        $tracker = $this->createMock(\Tracker::class);
        $tracker->method('getId')->willReturn(101);
        $tracker->method('getTitleField')->willReturn($title_field);

        $start_date = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $start_date->method('userCanRead')->willReturn(true);
        $duration = $this->createMock(\Tracker_FormElement_Field_Numeric::class);
        $duration->method('userCanRead')->willReturn(false);

        $this->semantic_timeframe_builder->method('getSemantic')->willReturn(
            new SemanticTimeframe($tracker, new TimeframeWithDuration($start_date, $duration))
        );

        self::assertNull($this->cache->getRepresentationBuilderForTracker($tracker, $this->user));
    }

    public function testItReturnsABuilderInstance(): void
    {
        $semantic = $this->createMock(SemanticProgress::class);
        $semantic->method('getComputationMethod')->willReturn($this->createMock(IComputeProgression::class));
        $this->progress_builder->method('getSemantic')->willReturn($semantic);

        $title_field = $this->createMock(\Tracker_FormElement_Field_String::class);
        $title_field->method('userCanRead')->willReturn(true);

        $tracker = $this->createMock(\Tracker::class);
        $tracker->method('getId')->willReturn(101);
        $tracker->method('getTitleField')->willReturn($title_field);

        $start_date = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $start_date->method('userCanRead')->willReturn(true);
        $duration = $this->createMock(\Tracker_FormElement_Field_Numeric::class);
        $duration->method('userCanRead')->willReturn(true);

        $this->semantic_timeframe_builder->method('getSemantic')->willReturn(
            new SemanticTimeframe($tracker, new TimeframeWithDuration($start_date, $duration))
        );

        self::assertInstanceOf(
            TaskRepresentationBuilderForTracker::class,
            $this->cache->getRepresentationBuilderForTracker($tracker, $this->user)
        );
    }

    public function testItInstantiateOnlyOnceABuilderForAGivenTracker(): void
    {
        $semantic = $this->createMock(SemanticProgress::class);
        $semantic->method('getComputationMethod')->willReturn($this->createMock(IComputeProgression::class));
        $this->progress_builder
            ->expects(self::once())
            ->method('getSemantic')
            ->willReturn($semantic);

        $title_field = $this->createMock(\Tracker_FormElement_Field_String::class);
        $title_field->method('userCanRead')->willReturn(true);

        $tracker = $this->createMock(\Tracker::class);
        $tracker->method('getId')->willReturn(101);
        $tracker->method('getTitleField')->willReturn($title_field);

        $start_date = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $start_date->method('userCanRead')->willReturn(true);
        $duration = $this->createMock(\Tracker_FormElement_Field_Numeric::class);
        $duration->method('userCanRead')->willReturn(true);

        $this->semantic_timeframe_builder
            ->expects(self::once())
            ->method('getSemantic')
            ->willReturn(new SemanticTimeframe($tracker, new TimeframeWithDuration($start_date, $duration)));

        self::assertEquals(
            $this->cache->getRepresentationBuilderForTracker($tracker, $this->user),
            $this->cache->getRepresentationBuilderForTracker($tracker, $this->user),
        );
    }
}
