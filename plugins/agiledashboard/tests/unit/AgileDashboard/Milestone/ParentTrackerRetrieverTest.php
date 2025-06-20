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

declare(strict_types=1);

namespace Tuleap\AgileDashboard\Milestone;

use PHPUnit\Framework\MockObject\MockObject;
use Planning_VirtualTopMilestone;
use PlanningFactory;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ParentTrackerRetrieverTest extends TestCase
{
    private ParentTrackerRetriever $retriever;
    private PlanningFactory&MockObject $planning_factory;

    protected function setUp(): void
    {
        $this->planning_factory = $this->createMock(PlanningFactory::class);
        $this->retriever        = new ParentTrackerRetriever($this->planning_factory);
    }

    private function getTracker(int $id, ?Tracker $parent): Tracker
    {
        return TrackerTestBuilder::aTracker()
            ->withId($id)
            ->withParent($parent)
            ->build();
    }

    public function testItRetrievesCreatableParentTrackersInTheLastPlanning(): void
    {
        $epic_tracker       = $this->getTracker(101, null);
        $user_story_tracker = $this->getTracker(102, $epic_tracker);
        $bug_tracker        = $this->getTracker(103, null);

        $sprint_planning = PlanningBuilder::aPlanning(101)->build();
        $milestone       = new Planning_VirtualTopMilestone(
            ProjectTestBuilder::aProject()->build(),
            $sprint_planning,
        );

        $user                        = UserTestBuilder::buildWithDefaults();
        $descendant_backlog_trackers = [$user_story_tracker, $bug_tracker];

        $this->planning_factory->method('getSubPlannings')->with($sprint_planning, $user)->willReturn([]);

        $trackers = $this->retriever->getCreatableParentTrackers($milestone, $user, $descendant_backlog_trackers);

        self::assertEquals($epic_tracker, $trackers[0]);
    }

    public function testItRetrievesCreatableParentTrackersInAPlanning(): void
    {
        $theme_tracker      = $this->getTracker(200, null);
        $epic_tracker       = $this->getTracker(101, $theme_tracker);
        $user_story_tracker = $this->getTracker(102, $epic_tracker);
        $bug_tracker        = $this->getTracker(103, null);

        $release_planning = PlanningBuilder::aPlanning(101)->build();
        $sprint_planning  = PlanningBuilder::aPlanning(101)->withBacklogTrackers($user_story_tracker, $bug_tracker)->build();

        $milestone                   = new Planning_VirtualTopMilestone(
            ProjectTestBuilder::aProject()->build(),
            $release_planning,
        );
        $user                        = UserTestBuilder::buildWithDefaults();
        $descendant_backlog_trackers = [$user_story_tracker, $bug_tracker];

        $this->planning_factory->method('getSubPlannings')->with($release_planning, $user)->willReturn([$sprint_planning]);

        $trackers = $this->retriever->getCreatableParentTrackers($milestone, $user, $descendant_backlog_trackers);

        self::assertEquals($epic_tracker, $trackers[0]);
    }

    public function testItRetrievesParentTrackersIfItIsTrackerBacklogOfCurrentPlanning(): void
    {
        $epic_tracker       = $this->getTracker(101, null);
        $user_story_tracker = $this->getTracker(102, $epic_tracker);
        $bug_tracker        = $this->getTracker(103, null);

        $release_planning = PlanningBuilder::aPlanning(101)->build();
        $sprint_planning  = PlanningBuilder::aPlanning(101)->withBacklogTrackers($user_story_tracker, $bug_tracker)->build();
        $milestone        = new Planning_VirtualTopMilestone(
            ProjectTestBuilder::aProject()->build(),
            $release_planning,
        );

        $user                        = UserTestBuilder::buildWithDefaults();
        $descendant_backlog_trackers = [$user_story_tracker, $bug_tracker];

        $this->planning_factory->method('getSubPlannings')->with($release_planning, $user)->willReturn([$sprint_planning]);

        $trackers = $this->retriever->getCreatableParentTrackers($milestone, $user, $descendant_backlog_trackers);

        self::assertEquals($epic_tracker, $trackers[0]);
    }

    public function testItDoesNotRetrieveParentTrackersIfItIsTrackerBacklogOfSubPlanning(): void
    {
        $theme_tracker      = $this->getTracker(200, null);
        $epic_tracker       = $this->getTracker(101, $theme_tracker);
        $user_story_tracker = $this->getTracker(102, $epic_tracker);
        $bug_tracker        = $this->getTracker(103, null);

        $release_planning    = PlanningBuilder::aPlanning(101)->build();
        $sprint_planning     = PlanningBuilder::aPlanning(101)->withBacklogTrackers($user_story_tracker, $bug_tracker)->build();
        $sub_sprint_planning = PlanningBuilder::aPlanning(101)->withBacklogTrackers($epic_tracker)->build();
        $milestone           = new Planning_VirtualTopMilestone(
            ProjectTestBuilder::aProject()->build(),
            $release_planning,
        );

        $user                        = UserTestBuilder::buildWithDefaults();
        $descendant_backlog_trackers = [$user_story_tracker, $bug_tracker];

        $this->planning_factory->method('getSubPlannings')->with($release_planning, $user)->willReturn([
            $sprint_planning,
            $sub_sprint_planning,
        ]);

        $trackers = $this->retriever->getCreatableParentTrackers($milestone, $user, $descendant_backlog_trackers);

        self::assertEmpty($trackers);
    }

    public function testItDoesNotRetrieveParentTrackersIfItIsTrackerBacklogOfPlanning(): void
    {
        $epic_tracker       = $this->getTracker(101, null);
        $user_story_tracker = $this->getTracker(102, $epic_tracker);
        $bug_tracker        = $this->getTracker(103, null);

        $sprint_planning = PlanningBuilder::aPlanning(101)
            ->withBacklogTrackers($epic_tracker, $user_story_tracker, $bug_tracker)
            ->build();
        $milestone       = new Planning_VirtualTopMilestone(
            ProjectTestBuilder::aProject()->build(),
            $sprint_planning,
        );

        $user                        = UserTestBuilder::buildWithDefaults();
        $descendant_backlog_trackers = [$user_story_tracker, $bug_tracker, $epic_tracker];

        $this->planning_factory->method('getSubPlannings')->with($sprint_planning, $user)->willReturn([]);

        $trackers = $this->retriever->getCreatableParentTrackers($milestone, $user, $descendant_backlog_trackers);

        self::assertEmpty($trackers);
    }

    public function testItGetsUniqueParentTrackerOfPlanning(): void
    {
        $epic_tracker       = $this->getTracker(101, null);
        $release_tracker    = $this->getTracker(102, null);
        $user_story_tracker = $this->getTracker(103, $epic_tracker);
        $bug_tracker        = $this->getTracker(104, $epic_tracker);
        $activity_tracker   = $this->getTracker(105, $release_tracker);

        $descendant_backlog_trackers = [$user_story_tracker, $bug_tracker, $activity_tracker];

        $milestone = new Planning_VirtualTopMilestone(
            ProjectTestBuilder::aProject()->build(),
            PlanningBuilder::aPlanning(101)->build(),
        );
        $user      = UserTestBuilder::aUser()->build();

        $this->planning_factory->method('getSubPlannings')->willReturn([]);

        $trackers = $this->retriever->getCreatableParentTrackers($milestone, $user, $descendant_backlog_trackers);

        self::assertCount(2, $trackers);
        self::assertSame(101, $trackers[0]->getId());
        self::assertSame(102, $trackers[1]->getId());
    }
}
