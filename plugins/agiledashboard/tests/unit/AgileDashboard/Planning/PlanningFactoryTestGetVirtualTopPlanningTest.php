<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\AgileDashboard\Planning;

use PHPUnit\Framework\MockObject\MockObject;
use Planning;
use Planning_NoPlanningsException;
use PlanningFactory;
use PlanningPermissionsManager;
use Tracker;
use TrackerFactory;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class PlanningFactoryTestGetVirtualTopPlanningTest extends TestCase
{
    private PlanningFactory&MockObject $partial_factory;
    private TrackerFactory&MockObject $tracker_factory;

    protected function setUp(): void
    {
        $planning_dao                 = $this->createMock(PlanningDao::class);
        $this->tracker_factory        = $this->createMock(TrackerFactory::class);
        $planning_permissions_manager = $this->createMock(PlanningPermissionsManager::class);

        $this->partial_factory = $this->getMockBuilder(PlanningFactory::class)
            ->setConstructorArgs([$planning_dao, $this->tracker_factory, $planning_permissions_manager])
            ->onlyMethods(['getRootPlanning'])
            ->getMock();
    }

    public function testItThrowsAnExceptionIfNoPlanningsExistForProject(): void
    {
        self::expectException(Planning_NoPlanningsException::class);

        $this->partial_factory->method('getRootPlanning')->willReturn(false);
        $this->partial_factory->getVirtualTopPlanning(UserTestBuilder::buildWithDefaults(), 112);
    }

    public function testItCreatesNewPlanningWithValidBacklogAndPlanningTrackers(): void
    {
        $backlog_tracker  = TrackerTestBuilder::aTracker()->withId(78)->build();
        $planning_tracker = TrackerTestBuilder::aTracker()->withId(45)->build();

        $my_planning = PlanningBuilder::aPlanning(56)
            ->withBacklogTrackers($backlog_tracker)
            ->withMilestoneTracker($planning_tracker)
            ->build();

        $this->partial_factory->method('getRootPlanning')->willReturn($my_planning);
        $this->tracker_factory->method('getTrackerById')
            ->withConsecutive([45], [78])
            ->willReturnOnConsecutiveCalls($backlog_tracker, $planning_tracker);

        $planning = $this->partial_factory->getVirtualTopPlanning(UserTestBuilder::buildWithDefaults(), 56);

        self::assertInstanceOf(Planning::class, $planning);
        self::assertInstanceOf(Tracker::class, $planning->getPlanningTracker());
        $backlog_trackers = $planning->getBacklogTrackers();
        self::assertInstanceOf(Tracker::class, $backlog_trackers[0]);
    }
}
