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
use PHPUnit\Framework\TestCase;
use Planning;
use PlanningDao;
use PlanningFactory;
use PlanningPermissionsManager;
use TestHelper;
use Tracker;
use TrackerFactory;

class PlanningFactoryTestGetPlanningByPlanningTrackerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var PlanningFactory
     */
    private $planning_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TrackerFactory
     */
    private $tracker_factory;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlanningDao
     */
    private $planning_dao;

    protected function setUp(): void
    {
        $this->planning_dao           = Mockery::spy(PlanningDao::class);
        $this->tracker_factory        = Mockery::spy(TrackerFactory::class);
        $planning_permissions_manager = Mockery::spy(PlanningPermissionsManager::class);

        $this->planning_factory = new PlanningFactory(
            $this->planning_dao,
            $this->tracker_factory,
            $planning_permissions_manager
        );
    }

    public function testItReturnsNothingIfThereIsNoAssociatedPlanning(): void
    {
        $tracker = $this->mockTrackerWithId(99);
        $this->planning_dao->shouldReceive('searchByPlanningTrackerId')
            ->andReturns(TestHelper::arrayToDar());

        $this->assertNull($this->planning_factory->getPlanningByPlanningTracker($tracker));
    }

    public function testItReturnsAPlanning(): void
    {
        $tracker          = $this->mockTrackerWithId(99);
        $planning_tracker = $this->mockTrackerWithId(1);
        $backlog_tracker  = $this->mockTrackerWithId(2);

        $planning = new Planning(1, 'Release Planning', 102, 'Release Backlog', 'Sprint Plan', []);
        $planning->setPlanningTracker($planning_tracker);
        $planning->setBacklogTrackers([$backlog_tracker]);

        $this->tracker_factory->shouldReceive('getTrackerById')->with(1)->once()->andReturn($planning_tracker);
        $this->tracker_factory->shouldReceive('getTrackerById')->with(2)->once()->andReturn($backlog_tracker);

        $rows = TestHelper::arrayToDar(
            [
                'id'                  => 12,
                'name'                => 'Foo',
                'group_id'            => 102,
                'planning_tracker_id' => 1,
                'backlog_title'       => 'Release Backlog',
                'plan_title'          => 'Sprint Plan'
            ]
        );

        $this->planning_dao->shouldReceive('searchBacklogTrackersById')->andReturn([['tracker_id' => 2]]);
        $this->planning_dao->shouldReceive('searchByPlanningTrackerId')
            ->andReturns($rows);

        $retrieved_planning = $this->planning_factory->getPlanningByPlanningTracker($tracker);
        $this->assertEquals($planning->getPlanningTracker(), $retrieved_planning->getPlanningTracker());
        $this->assertEquals($planning->getBacklogTrackers(), $retrieved_planning->getBacklogTrackers());
    }

    /**
     * @return \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker
     */
    private function mockTrackerWithId(int $tracker_id)
    {
        $tracker = Mockery::spy(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn($tracker_id);

        return $tracker;
    }
}
