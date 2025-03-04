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
use Tracker_Hierarchy;
use TrackerFactory;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PlanningFactoryGetNonLastLevelPlanningsTest extends TestCase
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
                'getPlannings',
            ])
            ->getMock();
    }

    public function testItReturnsAnEmptyArrayIfNoPlanningsExist(): void
    {
        $this->partial_factory->method('getPlannings')->willReturn([]);

        $plannings = $this->partial_factory->getNonLastLevelPlannings(UserTestBuilder::buildWithDefaults(), 14);

        self::assertCount(0, $plannings);
    }

    public function testItDoesNotReturnLastLevelPlannings(): void
    {
        $planning_1 = PlanningBuilder::aPlanning(14)
            ->withMilestoneTracker(TrackerTestBuilder::aTracker()->withId(11)->build())
            ->build();
        $planning_2 = PlanningBuilder::aPlanning(14)
            ->withMilestoneTracker(TrackerTestBuilder::aTracker()->withId(22)->build())
            ->build();
        $planning_3 = PlanningBuilder::aPlanning(14)
            ->withMilestoneTracker(TrackerTestBuilder::aTracker()->withId(33)->build())
            ->build();

        $this->partial_factory->method('getPlannings')->willReturn([$planning_3, $planning_2, $planning_1]);

        $hierarchy = $this->createMock(Tracker_Hierarchy::class);
        $hierarchy->method('getLastLevelTrackerIds')->willReturn([11]);
        $hierarchy->method('sortTrackerIds')->with([33, 22])->willReturn([22, 33]);
        $this->tracker_factory->method('getHierarchy')->willReturn($hierarchy);

        $plannings = $this->partial_factory->getNonLastLevelPlannings(UserTestBuilder::buildWithDefaults(), 14);

        self::assertCount(2, $plannings);

        self::assertEquals(22, $plannings[0]->getPlanningTrackerId());
        self::assertEquals(33, $plannings[1]->getPlanningTrackerId());
    }
}
