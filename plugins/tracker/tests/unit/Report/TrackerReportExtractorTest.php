<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

namespace Tuleap\Tracker\Report;

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerReportExtractorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const TRACKER_ID = 1;

    public function testItDoesNotExtractTrackerUserCanNotView(): void
    {
        $project = ProjectTestBuilder::aProject()
            ->withStatusActive()
            ->build();
        $tracker = TrackerTestBuilder::aTracker()
            ->withProject($project)
            ->withId(self::TRACKER_ID)
            ->withUserCanView(false)
            ->build();

        $tracker_factory = $this->createMock(\TrackerFactory::class);
        $tracker_factory->method('getTrackerById')->with(self::TRACKER_ID)->willReturn($tracker);

        $extractor = new TrackerReportExtractor($tracker_factory);

        $expected_result = [];
        self::assertEquals(
            $expected_result,
            $extractor->extractTrackers([self::TRACKER_ID])
        );
    }

    public function testItDoesNotExtractDeletedTrackers(): void
    {
        $project = ProjectTestBuilder::aProject()
            ->withStatusActive()
            ->build();
        $tracker = TrackerTestBuilder::aTracker()
            ->withProject($project)
            ->withId(self::TRACKER_ID)
            ->withUserCanView(true)
            ->withDeletionDate(1234567890)
            ->build();

        $tracker_factory = $this->createMock(\TrackerFactory::class);
        $tracker_factory->method('getTrackerById')->with(self::TRACKER_ID)->willReturn($tracker);

        $extractor = new TrackerReportExtractor($tracker_factory);

        $expected_result = [];
        self::assertEquals(
            $expected_result,
            $extractor->extractTrackers([self::TRACKER_ID])
        );
    }

    public function testItDoesNotExtractTrackerOfNonActiveProjects(): void
    {
        $project = ProjectTestBuilder::aProject()
            ->withStatusDeleted()
            ->build();
        $tracker = TrackerTestBuilder::aTracker()
            ->withProject($project)
            ->withId(self::TRACKER_ID)
            ->withUserCanView(true)
            ->build();

        $tracker_factory = $this->createMock(\TrackerFactory::class);
        $tracker_factory->method('getTrackerById')->with(self::TRACKER_ID)->willReturn($tracker);

        $extractor = new TrackerReportExtractor($tracker_factory);

        $expected_result = [];
        self::assertEquals(
            $expected_result,
            $extractor->extractTrackers([self::TRACKER_ID])
        );
    }

    public function testItThrowAnExceptionWhenTrackerIsNotFound(): void
    {
        $tracker_factory = $this->createMock(\TrackerFactory::class);
        $tracker_factory->method('getTrackerById')->with(self::TRACKER_ID)->willReturn(null);

        $this->expectException('Tuleap\Tracker\Report\TrackerNotFoundException');

        $extractor = new TrackerReportExtractor($tracker_factory);
        $extractor->extractTrackers([self::TRACKER_ID]);
    }

    public function testItExtractsTrackers(): void
    {
        $project = ProjectTestBuilder::aProject()
            ->withStatusActive()
            ->build();
        $tracker = TrackerTestBuilder::aTracker()
            ->withProject($project)
            ->withId(self::TRACKER_ID)
            ->withUserCanView(true)
            ->build();

        $tracker_factory = $this->createMock(\TrackerFactory::class);
        $tracker_factory->method('getTrackerById')->with(self::TRACKER_ID)->willReturn($tracker);
        $extractor = new TrackerReportExtractor($tracker_factory);

        $expected_result = [$tracker];
        self::assertEquals(
            $expected_result,
            $extractor->extractTrackers([self::TRACKER_ID])
        );
    }
}
