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

class AgileDashboard_Planning_NearestPlanningTrackerProviderTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker
     */
    private $task_tracker;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker
     */
    private $epic_tracker;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker
     */
    private $sprint_tracker;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_HierarchyFactory
     */
    private $hierarchy_factory;
    /**
     * @var AgileDashboard_Planning_NearestPlanningTrackerProvider
     */
    private $provider;


    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PlanningFactory
     */
    private $planning_factory;

    protected function setUp(): void
    {
        $this->epic_tracker  = Mockery::mock(Tracker::class);
        $this->epic_tracker->shouldReceive('getParent')->andReturn(null);

        $this->sprint_tracker  = Mockery::mock(Tracker::class);
        $this->sprint_tracker->shouldReceive('getParent')->andReturn($this->epic_tracker);

        $story_tracker  = Mockery::mock(Tracker::class);
        $story_tracker->shouldReceive('getParent')->andReturn($this->epic_tracker);

        $this->task_tracker  = Mockery::mock(Tracker::class);
        $this->task_tracker->shouldReceive('getParent')->andReturn($story_tracker);

        $sprint_planning = Mockery::mock(Planning::class);
        $sprint_planning->shouldReceive('getPlanningTrackerId')->andReturns('sprint');
        $sprint_planning->shouldReceive('getPlanningTracker')->andReturns($this->sprint_tracker);

        $hierarchy         = \Mockery::spy(\Tracker_Hierarchy::class);
        $hierarchy->shouldReceive('sortTrackerIds')->andReturns(array('release', 'sprint'));
        $this->hierarchy_factory = \Mockery::spy(\Tracker_HierarchyFactory::class);
        $this->hierarchy_factory->shouldReceive('getHierarchy')->andReturns($hierarchy);

        $this->planning_factory  = \Mockery::spy(\PlanningFactory::class);
        $this->planning_factory->shouldReceive('getPlanningsByBacklogTracker')->with($this->task_tracker)->andReturns(array());
        $this->planning_factory->shouldReceive('getPlanningsByBacklogTracker')->with($story_tracker)->andReturns(array($sprint_planning));
        $this->planning_factory->shouldReceive('getPlanningsByBacklogTracker')->with($this->epic_tracker)->andReturns(array());

        $this->provider = new AgileDashboard_Planning_NearestPlanningTrackerProvider($this->planning_factory);
    }

    public function testItRetrievesTheNearestPlanningTracker(): void
    {
        $this->assertEquals($this->sprint_tracker, $this->provider->getNearestPlanningTracker($this->task_tracker, $this->hierarchy_factory));
    }

    public function testItReturnsNullWhenNoPlanningMatches(): void
    {
        $this->assertNull($this->provider->getNearestPlanningTracker($this->epic_tracker, $this->hierarchy_factory));
    }
}
