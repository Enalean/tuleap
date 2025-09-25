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
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class PlanningFactoryTestGetPlanningByPlanningTrackerTest extends TestCase
{
    private PlanningFactory $planning_factory;
    private TrackerFactory&MockObject $tracker_factory;
    private PlanningDao&MockObject $planning_dao;

    #[\Override]
    protected function setUp(): void
    {
        $this->planning_dao           = $this->createMock(PlanningDao::class);
        $this->tracker_factory        = $this->createMock(TrackerFactory::class);
        $planning_permissions_manager = $this->createMock(PlanningPermissionsManager::class);

        $this->planning_factory = new PlanningFactory(
            $this->planning_dao,
            $this->tracker_factory,
            $planning_permissions_manager
        );
    }

    public function testItReturnsNothingIfThereIsNoAssociatedPlanning(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->withId(99)->build();
        $this->planning_dao->method('searchByMilestoneTrackerId')->willReturn(null);

        self::assertNull($this->planning_factory->getPlanningByPlanningTracker(UserTestBuilder::buildWithDefaults(), $tracker));
    }

    public function testItReturnsAPlanningWithoutUnreadableTrackers(): void
    {
        $tracker           = TrackerTestBuilder::aTracker()->withId(99)->build();
        $planning_tracker  = TrackerTestBuilder::aTracker()->withId(1)->withUserCanView(true)->build();
        $backlog_tracker   = TrackerTestBuilder::aTracker()->withId(2)->withUserCanView(true)->build();
        $topsecret_tracker = TrackerTestBuilder::aTracker()->withId(3)->withUserCanView(false)->build();

        $planning = PlanningBuilder::aPlanning(102)
            ->withMilestoneTracker($planning_tracker)
            ->withBacklogTrackers($backlog_tracker)
            ->build();

        $this->tracker_factory
            ->method('getTrackerById')
            ->willReturnCallback(fn ($id) => match ($id) {
                1 => $planning_tracker,
                2 => $backlog_tracker,
                3 => $topsecret_tracker,
            });

        $rows = [
            'id'                  => 12,
            'name'                => 'Foo',
            'group_id'            => 102,
            'planning_tracker_id' => 1,
            'backlog_title'       => 'Release Backlog',
            'plan_title'          => 'Sprint Plan',
        ];

        $this->planning_dao->method('searchBacklogTrackersByPlanningId')->willReturn([['tracker_id' => 2]]);
        $this->planning_dao->method('searchByMilestoneTrackerId')->willReturn($rows);

        $retrieved_planning = $this->planning_factory->getPlanningByPlanningTracker(UserTestBuilder::buildWithDefaults(), $tracker);
        self::assertEquals($planning->getPlanningTracker(), $retrieved_planning->getPlanningTracker());
        self::assertEquals($planning->getBacklogTrackers(), $retrieved_planning->getBacklogTrackers());
    }
}
