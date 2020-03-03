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

declare(strict_types = 1);

namespace Tuleap\AgileDashboard\Planning;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Planning;
use Planning_NoPlanningsException;
use PlanningDao;
use PlanningFactory;
use PlanningPermissionsManager;
use Tracker;
use TrackerFactory;

final class PlanningFactoryTestGetVirtualTopPlanningTest extends TestCase
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
        $planning_dao                       = Mockery::spy(PlanningDao::class);
        $this->tracker_factory              = Mockery::spy(TrackerFactory::class);
        $planning_permissions_manager = Mockery::spy(PlanningPermissionsManager::class);

        $this->partial_factory = Mockery::mock(
            PlanningFactory::class,
            [$planning_dao, $this->tracker_factory, $planning_permissions_manager]
        )->makePartial()->shouldAllowMockingProtectedMethods();
    }

    public function testItThrowsAnExceptionIfNoPlanningsExistForProject(): void
    {
        $this->expectException(Planning_NoPlanningsException::class);

        $this->partial_factory->shouldReceive('getRootPlanning')->andReturn(null);
        $this->partial_factory->getVirtualTopPlanning(Mockery::mock(\PFUser::class), 112);
    }

    public function testItCreatesNewPlanningWithValidBacklogAndPlanningTrackers(): void
    {
        $backlog_tracker  = Mockery::mock(Tracker::class);
        $planning_tracker = Mockery::mock(Tracker::class);

        $backlog_tracker->shouldReceive('getId')->andReturn(78);
        $planning_tracker->shouldReceive('getId')->andReturn(45);

        $my_planning = new Planning(null, null, null, null, null, array(78), 45);
        $my_planning->setBacklogTrackers(array($backlog_tracker))
            ->setPlanningTracker($planning_tracker);

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
