<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
require_once dirname(__FILE__).'/../../../bootstrap.php';

class AgileDashboard_Planning_ParentBacklogTrackerCollectionProviderTest extends TuleapTestCase {


    public function setUp() {
        /*
                        Theme
        Release  ------- Epic
          Sprint -------  Story
                           Task
                            TestCase
        */
        $this->theme_tracker   = aTracker()->withId('theme')->withParent(null)->build();
        $this->epic_tracker    = aTracker()->withId('epic')->withParent($this->theme_tracker)->build();
        $this->story_tracker   = aTracker()->withId('story')->withParent($this->epic_tracker)->build();
        $this->task_tracker    = aTracker()->withId('task')->withParent($this->story_tracker)->build();
        $this->test_tracker    = aTracker()->withId('test')->withParent($this->story_tracker)->build();
        $this->release_tracker = aTracker()->withId('release')->withParent(null)->build();
        $this->sprint_tracker  = aTracker()->withId('sprint')->withParent($this->epic_tracker)->build();

        $release_planning = aPlanning()
            ->withPlanningTracker($this->release_tracker)
            ->withBacklogTracker($this->epic_tracker)
            ->build();

        $this->milestone_release = aMilestone()->withPlanning($release_planning)->build();

        $this->provider = new AgileDashboard_Planning_ParentBacklogTrackerCollectionProvider();
    }

    public function itReturnsEmptyArrayWhenNoMatch() {
        $dummy_milestone = aMilestone()
            ->withPlanning(
                aPlanning()->withBacklogTracker($this->test_tracker)->build()
            )->build();

        $this->assertEqual(
            $this->provider->getParentBacklogTrackerCollection($this->task_tracker, $dummy_milestone),
            array()
        );
    }

    public function itReturnsEpicStoryAndTaskWhenBacklogTrackerIsTask() {
        $this->assertEqual(
            $this->provider->getParentBacklogTrackerCollection($this->task_tracker, $this->milestone_release),
            array($this->epic_tracker, $this->story_tracker, $this->task_tracker)
        );
    }

    public function itReturnsEpicAndStoryWhenBacklogTrackerIsStory() {
        $this->assertEqual(
            $this->provider->getParentBacklogTrackerCollection($this->story_tracker, $this->milestone_release),
            array($this->epic_tracker, $this->story_tracker)
        );
    }

    public function itReturnsEpicWhenBacklogTrackerIsEpic() {
        $this->assertEqual(
            $this->provider->getParentBacklogTrackerCollection($this->epic_tracker, $this->milestone_release),
            array($this->epic_tracker)
        );
    }
}
