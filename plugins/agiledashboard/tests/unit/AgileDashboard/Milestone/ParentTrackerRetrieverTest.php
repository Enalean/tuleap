<?php
/**
 * Copyright (c) Enalean, 2018 - present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Milestone;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use Planning;
use Planning_Milestone;
use PlanningFactory;
use Tracker;
use Tuleap\Test\Builders\UserTestBuilder;

final class ParentTrackerRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var ParentTrackerRetriever
     */
    private $retriever;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlanningFactory
     */
    private $planning_factory;

    protected function setUp(): void
    {
        $this->planning_factory = Mockery::spy(PlanningFactory::class);
        $this->retriever        = new ParentTrackerRetriever($this->planning_factory);
    }

    private function getMockedTracker(int $id, ?Tracker $parent): Tracker
    {
        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn($id);
        $tracker->shouldReceive('getParent')->andReturn($parent);

        return $tracker;
    }

    public function testItRetrievesCreatableParentTrackersInTheLastPlanning(): void
    {
        $epic_tracker       = $this->getMockedTracker(101, null);
        $user_story_tracker = $this->getMockedTracker(102, $epic_tracker);
        $bug_tracker        = $this->getMockedTracker(103, null);

        $sprint_planning = Mockery::mock(Planning::class);

        $milestone = Mockery::mock(Planning_Milestone::class);
        $milestone->shouldReceive('getPlanning')->andReturn($sprint_planning);

        $user                        = Mockery::spy(PFUser::class);
        $descendant_backlog_trackers = [$user_story_tracker, $bug_tracker];

        $this->planning_factory->shouldReceive('getSubPlannings')->with($sprint_planning, $user)->andReturns([]);

        $trackers = $this->retriever->getCreatableParentTrackers($milestone, $user, $descendant_backlog_trackers);

        $this->assertEquals($epic_tracker, $trackers[0]);
    }

    public function testItRetrievesCreatableParentTrackersInAPlanning(): void
    {
        $theme_tracker      = $this->getMockedTracker(200, null);
        $epic_tracker       = $this->getMockedTracker(101, $theme_tracker);
        $user_story_tracker = $this->getMockedTracker(102, $epic_tracker);
        $bug_tracker        = $this->getMockedTracker(103, null);

        $release_planning = Mockery::mock(Planning::class);
        $sprint_planning  = Mockery::mock(Planning::class);
        $sprint_planning->shouldReceive('getBacklogTrackersIds')->andReturn([102, 103]);

        $milestone = Mockery::mock(Planning_Milestone::class);
        $milestone->shouldReceive('getPlanning')->andReturn($release_planning);
        $user                        = Mockery::spy(PFUser::class);
        $descendant_backlog_trackers = [$user_story_tracker, $bug_tracker];

        $this->planning_factory->shouldReceive('getSubPlannings')->with($release_planning, $user)->andReturns(
            [
                $sprint_planning,
            ]
        );

        $trackers = $this->retriever->getCreatableParentTrackers($milestone, $user, $descendant_backlog_trackers);

        $this->assertEquals($epic_tracker, $trackers[0]);
    }

    public function testItRetrievesParentTrackersIfItIsTrackerBacklogOfCurrentPlanning(): void
    {
        $epic_tracker       = $this->getMockedTracker(101, null);
        $user_story_tracker = $this->getMockedTracker(102, $epic_tracker);
        $bug_tracker        = $this->getMockedTracker(103, null);

        $release_planning = Mockery::mock(Planning::class);
        $sprint_planning  = Mockery::mock(Planning::class);
        $sprint_planning->shouldReceive('getBacklogTrackersIds')->andReturn([102, 103]);

        $milestone = Mockery::mock(Planning_Milestone::class);
        $milestone->shouldReceive('getPlanning')->andReturn($release_planning);

        $user                        = Mockery::spy(PFUser::class);
        $descendant_backlog_trackers = [$user_story_tracker, $bug_tracker];

        $this->planning_factory->shouldReceive('getSubPlannings')->with($release_planning, $user)->andReturns(
            [
                $sprint_planning,
            ]
        );

        $trackers = $this->retriever->getCreatableParentTrackers($milestone, $user, $descendant_backlog_trackers);

        $this->assertEquals($epic_tracker, $trackers[0]);
    }

    public function testItDoesNotRetrieveParentTrackersIfItIsTrackerBacklogOfSubPlanning(): void
    {
        $theme_tracker      = $this->getMockedTracker(200, null);
        $epic_tracker       = $this->getMockedTracker(101, $theme_tracker);
        $user_story_tracker = $this->getMockedTracker(102, $epic_tracker);
        $bug_tracker        = $this->getMockedTracker(103, null);

        $release_planning = Mockery::mock(Planning::class);

        $sprint_planning = Mockery::mock(Planning::class);
        $sprint_planning->shouldReceive('getBacklogTrackersIds')->andReturn([102, 103]);

        $sub_sprint_planning = Mockery::mock(Planning::class);
        $sub_sprint_planning->shouldReceive('getBacklogTrackersIds')->andReturn([101]);

        $milestone = Mockery::mock(Planning_Milestone::class);
        $milestone->shouldReceive('getPlanning')->andReturn($release_planning);

        $user                        = Mockery::spy(PFUser::class);
        $descendant_backlog_trackers = [$user_story_tracker, $bug_tracker];

        $this->planning_factory->shouldReceive('getSubPlannings')->with($release_planning, $user)->andReturns(
            [
                $sprint_planning,
                $sub_sprint_planning,
            ]
        );

        $trackers = $this->retriever->getCreatableParentTrackers($milestone, $user, $descendant_backlog_trackers);

        $this->assertEmpty($trackers);
    }

    public function testItDoesNotRetrieveParentTrackersIfItIsTrackerBacklogOfPlanning(): void
    {
        $epic_tracker       = $this->getMockedTracker(101, null);
        $user_story_tracker = $this->getMockedTracker(102, $epic_tracker);
        $bug_tracker        = $this->getMockedTracker(103, null);

        $sprint_planning =  Mockery::mock(Planning::class);
        $sprint_planning->shouldReceive('getBacklogTrackersIds')->andReturn([101, 102, 103]);

        $milestone = Mockery::mock(Planning_Milestone::class);
        $milestone->shouldReceive('getPlanning')->andReturn($sprint_planning);

        $user                        = Mockery::spy(PFUser::class);
        $descendant_backlog_trackers = [$user_story_tracker, $bug_tracker, $epic_tracker];

        $this->planning_factory->shouldReceive('getSubPlannings')->with($sprint_planning, $user)->andReturns([]);

        $trackers = $this->retriever->getCreatableParentTrackers($milestone, $user, $descendant_backlog_trackers);

        $this->assertEmpty($trackers);
    }

    public function testItGetsUniqueParentTrackerOfPlanning(): void
    {
        $epic_tracker       = $this->getMockedTracker(101, null);
        $release_tracker    = $this->getMockedTracker(102, null);
        $user_story_tracker = $this->getMockedTracker(103, $epic_tracker);
        $bug_tracker        = $this->getMockedTracker(104, $epic_tracker);
        $activity_tracker   = $this->getMockedTracker(105, $release_tracker);

        $descendant_backlog_trackers = [$user_story_tracker, $bug_tracker, $activity_tracker];

        $milestone = $this->createMock(Planning_Milestone::class);
        $milestone->method('getPlanning')->willReturn($this->createMock(Planning::class));
        $user = UserTestBuilder::aUser()->build();

        $this->planning_factory->shouldReceive('getSubPlannings')->andReturns([]);

        $trackers = $this->retriever->getCreatableParentTrackers($milestone, $user, $descendant_backlog_trackers);

        self::assertCount(2, $trackers);
        self::assertSame(101, $trackers[0]->getId());
        self::assertSame(102, $trackers[1]->getId());
    }
}
