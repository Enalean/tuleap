<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class CrossTrackerReportTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testTrackersAreMarkedAsInvalidWhenInNotActiveProjects()
    {
        $tracker_in_active_project = \Mockery::mock(\Tracker::class);
        $active_project            = \Mockery::mock(\Project::class);
        $active_project->shouldReceive('isActive')->andReturns(true);
        $tracker_in_active_project->shouldReceive('getProject')->andReturns($active_project);

        $tracker_in_suspended_project = \Mockery::mock(\Tracker::class);
        $suspended_project            = \Mockery::mock(\Project::class);
        $suspended_project->shouldReceive('isActive')->andReturns(false);
        $tracker_in_suspended_project->shouldReceive('getProject')->andReturns($suspended_project);

        $tracker_without_known_project = \Mockery::mock(\Tracker::class);
        $tracker_without_known_project->shouldReceive('getProject')->andReturns(null);

        $report = new CrossTrackerReport(
            1,
            '',
            [$tracker_in_active_project, $tracker_in_suspended_project, $tracker_without_known_project]
        );

        $this->assertSame([$tracker_in_active_project], $report->getTrackers());
        $this->assertSame([$tracker_in_suspended_project, $tracker_without_known_project], $report->getInvalidTrackers());
    }

    public function testDetermineInvalidTrackersIsComputedOnlyOnce()
    {
        $tracker = \Mockery::mock(\Tracker::class);
        $project = \Mockery::mock(\Project::class);
        $tracker->shouldReceive('getProject')->andReturns($project);
        $project->shouldReceive('isActive')->once()->andReturns(true);

        $report = new CrossTrackerReport(1, '', [$tracker]);

        $report->getTrackers();
        $report->getTrackers();
        $report->getInvalidTrackers();
        $report->getInvalidTrackers();
    }

    public function testReportWithoutTrackers()
    {
        $report = new CrossTrackerReport(1, '', []);
        $this->assertSame([], $report->getTrackerIds());
        $this->assertSame([], $report->getTrackers());
        $this->assertSame([], $report->getInvalidTrackers());
        $this->assertSame([], $report->getProjects());
        $this->assertSame([], $report->getColumnFields());
        $this->assertSame([], $report->getSearchFields());
    }
}
