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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Planning;
use Planning_NoPlanningsException;
use PlanningFactory;
use PlanningPermissionsManager;
use Tracker;
use TrackerFactory;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class PlanningFactoryTestGetVirtualTopPlanningTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\Mock | PlanningFactory
     */
    private $partial_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TrackerFactory
     */
    private $tracker_factory;

    protected function setUp(): void
    {
        $planning_dao                 = Mockery::spy(PlanningDao::class);
        $this->tracker_factory        = Mockery::spy(TrackerFactory::class);
        $planning_permissions_manager = Mockery::spy(PlanningPermissionsManager::class);

        $this->partial_factory = Mockery::mock(
            PlanningFactory::class,
            [$planning_dao, $this->tracker_factory, $planning_permissions_manager]
        )->makePartial()->shouldAllowMockingProtectedMethods();
    }

    public function testItThrowsAnExceptionIfNoPlanningsExistForProject(): void
    {
        $this->expectException(Planning_NoPlanningsException::class);

        $this->partial_factory->shouldReceive('getRootPlanning')->andReturn(false);
        $this->partial_factory->getVirtualTopPlanning(Mockery::mock(\PFUser::class), 112);
    }

    public function testItCreatesNewPlanningWithValidBacklogAndPlanningTrackers(): void
    {
        $backlog_tracker  = TrackerTestBuilder::aTracker()->withId(78)->build();
        $planning_tracker = TrackerTestBuilder::aTracker()->withId(45)->build();

        $my_planning = PlanningBuilder::aPlanning(56)
            ->withBacklogTrackers($backlog_tracker)
            ->withMilestoneTracker($planning_tracker)
            ->build();

        $this->partial_factory->shouldReceive('getRootPlanning')->andReturn($my_planning);
        $this->tracker_factory->shouldReceive('getTrackerById')->with(45)->andReturn($backlog_tracker);
        $this->tracker_factory->shouldReceive('getTrackerById')->with(78)->andReturn($planning_tracker);

        $planning = $this->partial_factory->getVirtualTopPlanning(Mockery::mock(\PFUser::class), 56);

        $this->assertInstanceOf(Planning::class, $planning);
        $this->assertInstanceOf(Tracker::class, $planning->getPlanningTracker());
        $backlog_trackers = $planning->getBacklogTrackers();
        $this->assertInstanceOf(Tracker::class, $backlog_trackers[0]);
    }
}
