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
use PlanningFactory;
use PlanningPermissionsManager;
use TrackerFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PlanningFactoryTestGetAvailablePlanningTrackersTest extends TestCase
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
            ->onlyMethods([
                'getPotentialPlanningTrackerIds',
                'getPlanningTrackerIdsByGroupId',
            ])
            ->getMock();
    }

    public function testItRetrievesAvailablePlanningTrackersIncludingTheCurrentPlanningTracker(): void
    {
        $group_id = 789;

        $this->partial_factory->method('getPotentialPlanningTrackerIds')->willReturn([1, 2, 3]);
        $this->partial_factory->method('getPlanningTrackerIdsByGroupId')->willReturn([1, 3]);

        $releases_tracker = $this->createMock(Tracker::class);
        $releases_tracker->method('userCanView')->willReturn(true);

        $this->tracker_factory->method('getTrackerById')->with(2)->willReturn($releases_tracker);

        $actual_trackers = $this->partial_factory->getAvailablePlanningTrackers(
            UserTestBuilder::buildWithDefaults(),
            $group_id
        );

        self::assertEquals([$releases_tracker], $actual_trackers);
    }

    public function testDoesNotRetrieveAvailablePlanningTrackersCurrentUserCannotSee(): void
    {
        $group_id = 789;

        $this->partial_factory->method('getPotentialPlanningTrackerIds')->willReturn([1, 2, 3]);
        $this->partial_factory->method('getPlanningTrackerIdsByGroupId')->willReturn([1, 3]);

        $releases_tracker = $this->createMock(Tracker::class);
        $releases_tracker->method('userCanView')->willReturn(false);

        $this->tracker_factory->method('getTrackerById')->with(2)->willReturn($releases_tracker);

        $actual_trackers = $this->partial_factory->getAvailablePlanningTrackers(
            UserTestBuilder::buildWithDefaults(),
            $group_id
        );

        self::assertEquals([], $actual_trackers);
    }
}
