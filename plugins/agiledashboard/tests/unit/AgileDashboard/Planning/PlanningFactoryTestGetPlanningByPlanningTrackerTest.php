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
use PlanningFactory;
use PlanningPermissionsManager;
use TrackerFactory;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

class PlanningFactoryTestGetPlanningByPlanningTrackerTest extends \Tuleap\Test\PHPUnit\TestCase
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
        $tracker = TrackerTestBuilder::aTracker()->withId(99)->build();
        $this->planning_dao->shouldReceive('searchByMilestoneTrackerId')
            ->andReturnNull();

        $this->assertNull($this->planning_factory->getPlanningByPlanningTracker($tracker));
    }

    public function testItReturnsAPlanning(): void
    {
        $tracker          = TrackerTestBuilder::aTracker()->withId(99)->build();
        $planning_tracker = TrackerTestBuilder::aTracker()->withId(1)->build();
        $backlog_tracker  = TrackerTestBuilder::aTracker()->withId(2)->build();

        $planning = PlanningBuilder::aPlanning(102)
            ->withMilestoneTracker($planning_tracker)
            ->withBacklogTrackers($backlog_tracker)
            ->build();

        $this->tracker_factory->shouldReceive('getTrackerById')->with(1)->once()->andReturn($planning_tracker);
        $this->tracker_factory->shouldReceive('getTrackerById')->with(2)->once()->andReturn($backlog_tracker);

        $rows = [
            'id'                  => 12,
            'name'                => 'Foo',
            'group_id'            => 102,
            'planning_tracker_id' => 1,
            'backlog_title'       => 'Release Backlog',
            'plan_title'          => 'Sprint Plan',
        ];

        $this->planning_dao->shouldReceive('searchBacklogTrackersByPlanningId')->andReturn([['tracker_id' => 2]]);
        $this->planning_dao->shouldReceive('searchByMilestoneTrackerId')
            ->andReturns($rows);

        $retrieved_planning = $this->planning_factory->getPlanningByPlanningTracker($tracker);
        $this->assertEquals($planning->getPlanningTracker(), $retrieved_planning->getPlanningTracker());
        $this->assertEquals($planning->getBacklogTrackers(), $retrieved_planning->getBacklogTrackers());
    }
}
