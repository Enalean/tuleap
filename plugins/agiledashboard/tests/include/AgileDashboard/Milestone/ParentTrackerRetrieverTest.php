<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

use PFUser;
use PlanningFactory;
use TuleapTestCase;

require_once __DIR__.'/../../../bootstrap.php';

class ParentTrackerRetrieverTest extends TuleapTestCase
{

    public function itRetrievesCreatableParentTrackersInTheLastPlanning()
    {
        $planning_factory = mock(PlanningFactory::class);
        $retriever        = new ParentTrackerRetriever($planning_factory);

        $epic_tracker       = aMockTracker()->withId(101)->build();
        $user_story_tracker = aMockTracker()->withId(102)->withParent($epic_tracker)->build();
        $bug_tracker        = aMockTracker()->withId(103)->build();
        $product_tracker    = aMockTracker()->withId(104)->build();
        $release_tracker    = aMockTracker()->withId(105)->withParent($product_tracker)->build();
        $sprint_tracker     = aMockTracker()->withId(106)->build();

        $release_planning = aPlanning()->withId(1)->withPlanningTracker($release_tracker)
            ->withBacklogTracker($epic_tracker)->build();
        $sprint_planning  = aPlanning()->withId(2)->withPlanningTracker($sprint_tracker)
            ->withBacklogTracker($user_story_tracker)
            ->withBacklogTracker($bug_tracker)
            ->build();

        $milestone                   = aMilestone()->withPlanning($sprint_planning)->build();
        $user                        = mock(PFUser::class);
        $descendant_backlog_trackers = [$user_story_tracker, $bug_tracker];

        stub($planning_factory)->getSubPlannings($sprint_planning, $user)->returns([]);

        $trackers = $retriever->getCreatableParentTrackers($milestone, $user, $descendant_backlog_trackers);

        $this->assertEqual($trackers[0], $epic_tracker);
    }

    public function itRetrievesCreatableParentTrackersInAPlanning()
    {
        $planning_factory = mock(PlanningFactory::class);
        $retriever        = new ParentTrackerRetriever($planning_factory);

        $theme_tracker      = aMockTracker()->withId(200)->build();
        $epic_tracker       = aMockTracker()->withId(101)->withParent($theme_tracker)->build();
        $user_story_tracker = aMockTracker()->withId(102)->withParent($epic_tracker)->build();
        $bug_tracker        = aMockTracker()->withId(103)->build();
        $product_tracker    = aMockTracker()->withId(104)->build();
        $release_tracker    = aMockTracker()->withId(105)->withParent($product_tracker)->build();
        $sprint_tracker     = aMockTracker()->withId(106)->build();
        $task_tracker       = aMockTracker()->withId(201)->withParent($user_story_tracker)->build();

        $product_planning = aPlanning()->withId(1)->withPlanningTracker($product_tracker)->build();
        $release_planning = aPlanning()->withId(2)->withPlanningTracker($release_tracker)
            ->withBacklogTracker($epic_tracker)->build();
        $sprint_planning  = aPlanning()->withId(3)->withPlanningTracker($sprint_tracker)
            ->withBacklogTracker($user_story_tracker)
            ->withBacklogTracker($bug_tracker)
            ->build();

        $milestone                   = aMilestone()->withPlanning($release_planning)->build();
        $user                        = mock(PFUser::class);
        $descendant_backlog_trackers = [$user_story_tracker, $bug_tracker];

        stub($planning_factory)->getSubPlannings($release_planning, $user)->returns([
            $sprint_planning
        ]);

        $trackers = $retriever->getCreatableParentTrackers($milestone, $user, $descendant_backlog_trackers);

        $this->assertEqual($trackers[0], $epic_tracker);
    }

    public function itRetrievesParentTrackersIfItIsTrackerBacklogOfCurrentPlanning()
    {
        $planning_factory = mock(PlanningFactory::class);
        $retriever        = new ParentTrackerRetriever($planning_factory);

        $epic_tracker       = aMockTracker()->withId(101)->build();
        $user_story_tracker = aMockTracker()->withId(102)->withParent($epic_tracker)->build();
        $bug_tracker        = aMockTracker()->withId(103)->build();
        $release_tracker    = aMockTracker()->withId(105)->build();
        $sprint_tracker     = aMockTracker()->withId(106)->build();

        $release_planning = aPlanning()->withId(1)->withPlanningTracker($release_tracker)
            ->withBacklogTracker($epic_tracker)
            ->build();
        $sprint_planning  = aPlanning()->withId(2)->withPlanningTracker($sprint_tracker)
            ->withBacklogTracker($user_story_tracker)
            ->withBacklogTracker($bug_tracker)
            ->build();

        $milestone                   = aMilestone()->withPlanning($release_planning)->build();
        $user                        = mock(PFUser::class);
        $descendant_backlog_trackers = [$user_story_tracker, $bug_tracker];

        stub($planning_factory)->getSubPlannings($release_planning, $user)->returns([
            $sprint_planning
        ]);

        $trackers = $retriever->getCreatableParentTrackers($milestone, $user, $descendant_backlog_trackers);

        $this->assertEqual($trackers[0], $epic_tracker);
    }

    public function itDoesNotRetrieveParentTrackersIfItIsTrackerBacklogOfSubPlanning()
    {
        $planning_factory = mock(PlanningFactory::class);
        $retriever        = new ParentTrackerRetriever($planning_factory);

        $theme_tracker      = aMockTracker()->withId(200)->build();
        $epic_tracker       = aMockTracker()->withId(101)->withParent($theme_tracker)->build();
        $user_story_tracker = aMockTracker()->withId(102)->withParent($epic_tracker)->build();
        $bug_tracker        = aMockTracker()->withId(103)->build();
        $product_tracker    = aMockTracker()->withId(104)->build();
        $release_tracker    = aMockTracker()->withId(105)->withParent($product_tracker)->build();
        $sprint_tracker     = aMockTracker()->withId(106)->build();
        $task_tracker       = aMockTracker()->withId(201)->withParent($user_story_tracker)->build();
        $issue_tracker      = aMockTracker()->withId(202)->withParent($user_story_tracker)->build();

        $release_planning = aPlanning()->withId(1)->withPlanningTracker($release_tracker)
            ->withBacklogTracker($epic_tracker)->build();
        $sprint_planning  = aPlanning()->withId(2)->withPlanningTracker($sprint_tracker)
            ->withBacklogTracker($user_story_tracker)
            ->withBacklogTracker($bug_tracker)
            ->build();
        $sub_sprint_planning  = aPlanning()->withId(3)->withPlanningTracker($sprint_tracker)
            ->withBacklogTracker($epic_tracker)
            ->withBacklogTracker($issue_tracker)
            ->build();

        $milestone                   = aMilestone()->withPlanning($release_planning)->build();
        $user                        = mock(PFUser::class);
        $descendant_backlog_trackers = [$user_story_tracker, $bug_tracker];

        stub($planning_factory)->getSubPlannings($release_planning, $user)->returns([
            $sprint_planning,
            $sub_sprint_planning
        ]);

        $trackers = $retriever->getCreatableParentTrackers($milestone, $user, $descendant_backlog_trackers);

        $this->assertArrayEmpty($trackers);
    }

    /**
     * This test is in Scrum v2 setup where everything is possible
     */
    public function itDoesNotRetrieveParentTrackersIfItIsTrackerBacklogOfPlanning()
    {
        $planning_factory = mock(PlanningFactory::class);
        $retriever        = new ParentTrackerRetriever($planning_factory);

        $epic_tracker       = aMockTracker()->withId(101)->build();
        $user_story_tracker = aMockTracker()->withId(102)->withParent($epic_tracker)->build();
        $bug_tracker        = aMockTracker()->withId(103)->build();
        $sprint_tracker     = aMockTracker()->withId(106)->build();

        $sprint_planning  = aPlanning()->withId(1)->withPlanningTracker($sprint_tracker)
            ->withBacklogTracker($user_story_tracker)
            ->withBacklogTracker($bug_tracker)
            ->withBacklogTracker($epic_tracker)
            ->build();

        $milestone                   = aMilestone()->withPlanning($sprint_planning)->build();
        $user                        = mock(PFUser::class);
        $descendant_backlog_trackers = [$user_story_tracker, $bug_tracker, $epic_tracker];

        stub($planning_factory)->getSubPlannings($sprint_planning, $user)->returns([]);

        $trackers = $retriever->getCreatableParentTrackers($milestone, $user, $descendant_backlog_trackers);

        $this->assertArrayEmpty($trackers);
    }
}
