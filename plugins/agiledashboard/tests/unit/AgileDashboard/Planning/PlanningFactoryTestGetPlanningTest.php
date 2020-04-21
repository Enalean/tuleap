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
use PFUser;
use PHPUnit\Framework\TestCase;
use Planning;
use PlanningDao;
use PlanningFactory;
use PlanningPermissionsManager;
use TestHelper;
use Tracker;
use Tracker_Hierarchy;
use TrackerFactory;

final class PlanningFactoryTestGetPlanningTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Planning
     */
    private $sprint_planning;
    /**
     * @var Planning
     */
    private $release_planning;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker
     */
    private $sprint_tracker;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker
     */
    private $release_tracker;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker
     */
    private $backlog_tracker;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker
     */
    private $planning_tracker;
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
        $this->planning_dao                 = Mockery::spy(PlanningDao::class);
        $this->tracker_factory              = Mockery::spy(TrackerFactory::class);
        $planning_permissions_manager = Mockery::spy(PlanningPermissionsManager::class);

        $this->planning_factory = new PlanningFactory(
            $this->planning_dao,
            $this->tracker_factory,
            $planning_permissions_manager
        );

        $this->planning_dao->shouldReceive('searchByPlanningTrackerId')->andReturn(
            TestHelper::arrayToDar(
                [
                    'id'                  => 1,
                    'name'                => 'Release Planning',
                    'group_id'            => 102,
                    'planning_tracker_id' => 103,
                    'backlog_title'       => 'Release Backlog',
                    'plan_title'          => 'Sprint Plan'
                ]
            )
        );
        $this->planning_dao->shouldReceive('searchBacklogTrackersById')->with(1)->andReturn(
            TestHelper::arrayToDar(
                [
                    'planning_id' => 1,
                    'tracker_id'  => 104,
                ]
            )
        );

        $this->user = Mockery::mock(PFUser::class);

        $this->planning_tracker = $this->mockTrackerWithId(103);
        $this->backlog_tracker  = $this->mockTrackerWithId(104);

        $epic_tracker          = $this->mockTrackerWithId(101);
        $story_tracker         = $this->mockTrackerWithId(100);
        $this->release_tracker = $this->mockTrackerWithId(107);
        $this->sprint_tracker  = $this->mockTrackerWithId(108);

        $this->tracker_factory->shouldReceive('getTrackerById')
            ->with(103)->andReturns($this->planning_tracker);
        $this->tracker_factory->shouldReceive('getTrackerById')
            ->with(104)->andReturns($this->backlog_tracker);
        $this->tracker_factory->shouldReceive('getTrackerById')
            ->with(100)->andReturns($story_tracker);
        $this->tracker_factory->shouldReceive('getTrackerById')
            ->with(101)->andReturns($epic_tracker);
        $this->tracker_factory->shouldReceive('getTrackerById')
            ->with(107)->andReturns($this->release_tracker);
        $this->tracker_factory->shouldReceive('getTrackerById')
            ->with(108)->andReturns($this->sprint_tracker);


        $this->tracker_factory->shouldReceive('setCachedInstances')->andReturn(
            [
                101 => $epic_tracker,
                100 => $story_tracker,
                107 => $this->release_tracker,
                108 => $this->sprint_tracker,
            ]
        );

        $this->createHierarchy();

        $this->planning_dao->shouldReceive('searchPlannings')->with(101)->andReturn(
            \TestHelper::arrayToDar(
                [
                    'id'                  => 1,
                    'name'                => 'Sprint Planning',
                    'group_id'            => 123,
                    'planning_tracker_id' => 108,
                    'backlog_title'       => 'Release Backlog',
                    'plan_title'          => 'Sprint Plan'
                ],
                [
                    'id'                  => 2,
                    'name'                => 'Release Planning',
                    'group_id'            => 123,
                    'planning_tracker_id' => 107,
                    'backlog_title'       => 'Product Backlog',
                    'plan_title'          => 'Release Plan'
                ]
            )
        );

        $this->release_planning = new Planning(2, 'Release Planning', 123, 'Product Backlog', 'Release Plan');
        $this->release_planning->setBacklogTrackers([$epic_tracker]);
        $this->release_planning->setPlanningTracker($this->release_tracker);
        $this->sprint_planning = new Planning(1, 'Sprint Planning', 123, 'Release Backlog', 'Sprint Plan');
        $this->sprint_planning->setBacklogTrackers([$this->backlog_tracker]);
        $this->sprint_planning->setPlanningTracker($this->sprint_tracker);
    }

    public function testItCanRetrieveBothAPlanningAndItsTrackers(): void
    {
        $group_id    = 42;
        $planning_id = 17;

        $planning_rows = TestHelper::arrayToDar(
            [
                'id'                  => $planning_id,
                'name'                => 'Foo',
                'group_id'            => $group_id,
                'planning_tracker_id' => 103,
                'backlog_title'       => 'Release Backlog',
                'plan_title'          => 'Sprint Plan'
            ]
        );

        $this->planning_dao->shouldReceive('searchById')->with($planning_id)->andReturns($planning_rows);

        $this->planning_dao->shouldReceive('searchBacklogTrackersById')->with($planning_id)->andReturns(
            \TestHelper::arrayToDar(['tracker_id' => 104])
        );

        $planning = $this->planning_factory->getPlanning($planning_id);

        $this->assertInstanceOf(Planning::class, $planning);
        $this->assertEquals($this->planning_tracker, $planning->getPlanningTracker());
        $this->assertEquals([$this->backlog_tracker], $planning->getBacklogTrackers());
    }

    public function testItReturnsAnEmptyArrayIfThereIsNoPlanningDefinedForAProject(): void
    {
        $this->assertEquals([], $this->planning_factory->getPlannings($this->user, 101));
    }

    public function testItReturnsAllDefinedPlanningsForAProjectInTheOrderDefinedByTheHierarchy(): void
    {
        $this->planning_dao->shouldReceive('searchBacklogTrackersById')->with(1)->andReturns(
            \TestHelper::arrayToDar(['tracker_id' => 100])
        );
        $this->planning_dao->shouldReceive('searchBacklogTrackersById')->with(2)->andReturns(
            \TestHelper::arrayToDar(['tracker_id' => 101])
        );


        $this->release_tracker->shouldReceive('userCanView')->with($this->user)->andReturn(true);
        $this->sprint_tracker->shouldReceive('userCanView')->with($this->user)->andReturn(true);

        $this->assertEquals(
            [$this->release_planning, $this->sprint_planning],
            $this->planning_factory->getPlannings($this->user, 101)
        );
    }

    public function testItReturnsOnlyPlanningsWhereTheUserCanViewTrackers(): void
    {
        $this->planning_dao->shouldReceive('searchBacklogTrackersById')->with(1)->andReturns(
            \TestHelper::arrayToDar(['tracker_id' => 100])
        );
        $this->planning_dao->shouldReceive('searchBacklogTrackersById')->with(2)->andReturns(
            \TestHelper::arrayToDar(['tracker_id' => 101])
        );


        $this->release_tracker->shouldReceive('userCanView')->with($this->user)->andReturn(false);
        $this->sprint_tracker->shouldReceive('userCanView')->with($this->user)->andReturn(true);

        $this->assertEquals(
            [$this->sprint_planning],
            $this->planning_factory->getPlannings($this->user, 101)
        );
    }

    /**
     * @return Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker
     */
    private function mockTrackerWithId(int $tracker_id)
    {
        $tracker = Mockery::spy(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn($tracker_id);

        return $tracker;
    }

    /**
     * backlog_tracker
     *    |_ planning_tracker
     *
     *  $this->release_tracker &&  $this->sprint_tracker can be added in both planning
     */
    protected function createHierarchy(): void
    {
        $hierarchy = new Tracker_Hierarchy();

        $this->tracker_factory->shouldReceive('getHierarchy')->andReturn($hierarchy);
        $hierarchy->addRelationship(104, 107);
        $hierarchy->addRelationship(104, 108);

        $hierarchy->addRelationship(103, 107);
        $hierarchy->addRelationship(103, 108);

        $hierarchy->addRelationship(107, 108);
    }
}
