<?php
/**
 * Copyright (c) Enalean, 2012 - present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Planning;

use AgileDashboard_Planning_NearestPlanningTrackerProvider;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use PlanningFactory;
use Tracker_Hierarchy;
use Tracker_HierarchyFactory;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class NearestPlanningTrackerProviderTest extends TestCase
{
    private Tracker $task_tracker;
    private Tracker $epic_tracker;
    private Tracker $sprint_tracker;
    private Tracker_HierarchyFactory&MockObject $hierarchy_factory;
    private AgileDashboard_Planning_NearestPlanningTrackerProvider $provider;

    protected function setUp(): void
    {
        $this->epic_tracker   = TrackerTestBuilder::aTracker()->withParent(null)->build();
        $this->sprint_tracker = TrackerTestBuilder::aTracker()->withParent($this->epic_tracker)->build();
        $story_tracker        = TrackerTestBuilder::aTracker()->withParent($this->epic_tracker)->build();
        $this->task_tracker   = TrackerTestBuilder::aTracker()->withParent($story_tracker)->build();

        $sprint_planning = PlanningBuilder::aPlanning(101)
            ->withMilestoneTracker($this->sprint_tracker)
            ->build();

        $hierarchy = $this->createMock(Tracker_Hierarchy::class);
        $hierarchy->method('sortTrackerIds')->willReturn(['release', 'sprint']);
        $this->hierarchy_factory = $this->createMock(Tracker_HierarchyFactory::class);
        $this->hierarchy_factory->method('getHierarchy')->willReturn($hierarchy);

        $planning_factory = $this->createMock(PlanningFactory::class);
        $planning_factory->method('getPlanningsByBacklogTracker')
            ->willReturnCallback(fn(\PFUser $user, Tracker $tracker) => match ($tracker) {
                $this->task_tracker,
                $this->epic_tracker => [],
                $story_tracker      => [$sprint_planning],
                default             => throw new LogicException("Should not be called with tracker #{$tracker->getId()}"),
            });

        $this->provider = new AgileDashboard_Planning_NearestPlanningTrackerProvider($planning_factory);
    }

    public function testItRetrievesTheNearestPlanningTracker(): void
    {
        self::assertEquals($this->sprint_tracker, $this->provider->getNearestPlanningTracker(UserTestBuilder::buildWithDefaults(), $this->task_tracker, $this->hierarchy_factory));
    }

    public function testItReturnsNullWhenNoPlanningMatches(): void
    {
        self::assertNull($this->provider->getNearestPlanningTracker(UserTestBuilder::buildWithDefaults(), $this->epic_tracker, $this->hierarchy_factory));
    }
}
