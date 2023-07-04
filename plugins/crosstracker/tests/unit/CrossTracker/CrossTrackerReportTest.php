<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker;

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class CrossTrackerReportTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testTrackersAreMarkedAsInvalidWhenInNotActiveProjects(): void
    {
        $active_project            = ProjectTestBuilder::aProject()->withStatusActive()->build();
        $tracker_in_active_project = TrackerTestBuilder::aTracker()->withProject($active_project)->build();

        $suspended_project            = ProjectTestBuilder::aProject()->withStatusSuspended()->build();
        $tracker_in_suspended_project = TrackerTestBuilder::aTracker()->withProject($suspended_project)->build();

        $tracker_without_known_project = $this->createMock(\Tracker::class);
        $tracker_without_known_project->method('getProject')->willReturn(null);

        $report = new CrossTrackerReport(
            1,
            '',
            [$tracker_in_active_project, $tracker_in_suspended_project, $tracker_without_known_project]
        );

        self::assertSame([$tracker_in_active_project], $report->getTrackers());
        self::assertSame([$tracker_in_suspended_project, $tracker_without_known_project], $report->getInvalidTrackers());
    }

    public function testDetermineInvalidTrackersIsComputedOnlyOnce(): void
    {
        $project = $this->createMock(\Project::class);
        $project->expects(self::once())->method('isActive')->willReturn(true);
        $project->method('getID')->willReturn(101);

        $tracker = TrackerTestBuilder::aTracker()->withProject($project)->build();

        $report = new CrossTrackerReport(1, '', [$tracker]);

        $report->getTrackers();
        $report->getTrackers();
        $report->getInvalidTrackers();
        $report->getInvalidTrackers();
    }

    public function testReportWithoutTrackers(): void
    {
        $report = new CrossTrackerReport(1, '', []);
        self::assertSame([], $report->getTrackerIds());
        self::assertSame([], $report->getTrackers());
        self::assertSame([], $report->getInvalidTrackers());
        self::assertSame([], $report->getProjects());
        self::assertSame([], $report->getColumnFields());
        self::assertSame([], $report->getSearchFields());
    }
}
