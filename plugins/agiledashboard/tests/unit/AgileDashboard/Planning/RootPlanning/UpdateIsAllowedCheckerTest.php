<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Planning\RootPlanning;

use PHPUnit\Framework\MockObject\MockObject;
use PlanningFactory;
use PlanningParameters;
use TrackerFactory;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Report\TrackerNotFoundException;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UpdateIsAllowedCheckerTest extends TestCase
{
    private UpdateIsAllowedChecker $checker;
    private PlanningFactory&MockObject $planning_factory;
    private BacklogTrackerRemovalChecker&MockObject $backlog_tracker_removal_checker;
    private TrackerFactory&MockObject $tracker_factory;

    protected function setUp(): void
    {
        $this->planning_factory                = $this->createMock(PlanningFactory::class);
        $this->backlog_tracker_removal_checker = $this->createMock(BacklogTrackerRemovalChecker::class);
        $this->tracker_factory                 = $this->createMock(TrackerFactory::class);
        $this->checker                         = new UpdateIsAllowedChecker(
            $this->planning_factory,
            $this->backlog_tracker_removal_checker,
            $this->tracker_factory
        );
    }

    public function testItReturnsIfNoRootPlanning(): void
    {
        $user     = UserTestBuilder::aUser()->build();
        $planning = PlanningBuilder::aPlanning(102)->withName('Not root planning')->build();
        $this->planning_factory->expects($this->once())->method('getRootPlanning')->willReturn(false);

        $this->checker->checkUpdateIsAllowed($planning, PlanningParameters::fromArray([]), $user);
    }

    public function testItReturnsIfNotARootPlanning(): void
    {
        $user     = UserTestBuilder::aUser()->build();
        $planning = PlanningBuilder::aPlanning(102)->withId(15)->withName('Not root planning')->build();
        $this->planning_factory->expects($this->once())->method('getRootPlanning')
            ->willReturn(PlanningBuilder::aPlanning(102)->withId(1)->withName('Root planning')->build());

        $this->checker->checkUpdateIsAllowed($planning, PlanningParameters::fromArray([]), $user);
    }

    public function testItThrowsWhenNewMilestoneTrackerIDIsNotAValidTrackerID(): void
    {
        $user     = UserTestBuilder::aUser()->build();
        $planning = PlanningBuilder::aPlanning(102)->withName('Not root planning')->build();
        $this->planning_factory->expects($this->once())->method('getRootPlanning')->willReturn($planning);
        $this->backlog_tracker_removal_checker->expects($this->once())->method('checkRemovedBacklogTrackersCanBeRemoved');
        $this->tracker_factory->expects($this->once())->method('getTrackerById')->willReturn(null);

        $this->expectException(TrackerNotFoundException::class);
        $this->checker->checkUpdateIsAllowed(
            $planning,
            PlanningParameters::fromArray(['planning_tracker_id' => '404']),
            $user
        );
    }

    public function testItThrowsWhenNewMilestoneTrackerIDIsInAnotherProject(): void
    {
        $user     = UserTestBuilder::aUser()->build();
        $planning = PlanningBuilder::aPlanning(102)->withName('Not root planning')->build();
        $this->planning_factory->expects($this->once())->method('getRootPlanning')->willReturn($planning);
        $this->backlog_tracker_removal_checker->expects($this->once())->method('checkRemovedBacklogTrackersCanBeRemoved');
        $tracker = TrackerTestBuilder::aTracker()
            ->withProject(ProjectTestBuilder::aProject()->withId(103)->build())
            ->build();
        $this->tracker_factory->expects($this->once())->method('getTrackerById')->willReturn($tracker);

        $this->expectException(TrackerNotFoundException::class);
        $this->checker->checkUpdateIsAllowed(
            $planning,
            PlanningParameters::fromArray(['planning_tracker_id' => '404']),
            $user
        );
    }

    public function testItReturnsWhenUpdateIsAllowed(): void
    {
        $user     = UserTestBuilder::aUser()->build();
        $planning = PlanningBuilder::aPlanning(102)->withName('Not root planning')->build();
        $this->planning_factory->expects($this->once())->method('getRootPlanning')->willReturn($planning);
        $this->backlog_tracker_removal_checker->expects($this->once())->method('checkRemovedBacklogTrackersCanBeRemoved');
        $tracker = TrackerTestBuilder::aTracker()
            ->withProject(ProjectTestBuilder::aProject()->withId(102)->build())
            ->build();
        $this->tracker_factory->expects($this->once())->method('getTrackerById')
            ->with(86)
            ->willReturn($tracker);

        $this->checker->checkUpdateIsAllowed(
            $planning,
            PlanningParameters::fromArray(['planning_tracker_id' => '86']),
            $user
        );
    }
}
