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
use TrackerFactory;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PlanningFactoryTestGetVirtualTopPlanningTest extends TestCase
{
    private const PROJECT_ID   = 101;
    private const SPRINT_ID    = 1000;
    private const STORIES_ID   = 1001;
    private const TOPSECRET_ID = 1002;

    private PlanningFactory&MockObject $partial_factory;
    private TrackerFactory&MockObject $tracker_factory;
    private PlanningDao&MockObject $planning_dao;
    private PlanningPermissionsManager&MockObject $planning_permissions_manager;

    protected function setUp(): void
    {
        $this->planning_dao                 = $this->createMock(PlanningDao::class);
        $this->tracker_factory              = $this->createMock(TrackerFactory::class);
        $this->planning_permissions_manager = $this->createMock(PlanningPermissionsManager::class);

        $this->partial_factory = $this->getMockBuilder(PlanningFactory::class)
            ->setConstructorArgs([$this->planning_dao, $this->tracker_factory, $this->planning_permissions_manager])
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
        $backlog_tracker  = TrackerTestBuilder::aTracker()->withId(78)->withUserCanView(true)->build();
        $planning_tracker = TrackerTestBuilder::aTracker()->withId(45)->withUserCanView(true)->build();

        $my_planning = PlanningBuilder::aPlanning(56)
            ->withBacklogTrackers($backlog_tracker)
            ->withMilestoneTracker($planning_tracker)
            ->build();

        $this->partial_factory->method('getRootPlanning')->willReturn($my_planning);
        $matcher = $this->exactly(2);
        $this->tracker_factory->expects($matcher)->method('getTrackerById')->willReturnCallback(function (...$parameters) use ($matcher, $backlog_tracker, $planning_tracker) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame(45, $parameters[0]);
                return $backlog_tracker;
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame(78, $parameters[0]);
                return $planning_tracker;
            }
        });

        $planning = $this->partial_factory->getVirtualTopPlanning(UserTestBuilder::buildWithDefaults(), 56);

        self::assertInstanceOf(Planning::class, $planning);
        self::assertInstanceOf(Tracker::class, $planning->getPlanningTracker());
        $backlog_trackers = $planning->getBacklogTrackers();
        self::assertInstanceOf(Tracker::class, $backlog_trackers[0]);
    }

    public function testGetVirtualTopPlanningExcludesBacklogTrackersUserCannotSeeSoItDoesNotProposeToAddThemInPV2(): void
    {
        $factory = new PlanningFactory(
            $this->planning_dao,
            $this->tracker_factory,
            $this->planning_permissions_manager,
        );

        $user = UserTestBuilder::buildWithDefaults();

        $sprint    = TrackerTestBuilder::aTracker()->withId(self::SPRINT_ID)->withUserCanView(true)->build();
        $stories   = TrackerTestBuilder::aTracker()->withId(self::STORIES_ID)->withUserCanView(true)->build();
        $topsecret = TrackerTestBuilder::aTracker()->withId(self::TOPSECRET_ID)->withUserCanView(false)->build();

        $this->tracker_factory->method('getTrackerById')->willReturnCallback(static fn($id) => match ($id) {
            self::SPRINT_ID    => $sprint,
            self::STORIES_ID   => $stories,
            self::TOPSECRET_ID => $topsecret,
        });
        $this->tracker_factory->method('getHierarchy')->willReturn(new \Tracker_Hierarchy());

        $this->planning_dao->method('searchByProjectId')->willReturn([[
            'id'                  => 1,
            'name'                => 'Sprint planning',
            'group_id'            => self::PROJECT_ID,
            'planning_tracker_id' => $sprint->id,
            'backlog_title'       => 'backlog',
            'plan_title'          => 'milestone',
        ],
        ]);
        $this->planning_dao->method('searchBacklogTrackersByPlanningId')->willReturn([
            ['planning_id' => 1, 'tracker_id' => self::STORIES_ID],
            ['planning_id' => 1, 'tracker_id' => self::TOPSECRET_ID],
        ]);

        $planning = $factory->getVirtualTopPlanning($user, self::PROJECT_ID);

        self::assertSame(
            [$stories],
            $planning->getBacklogTrackers(),
        );
    }
}
