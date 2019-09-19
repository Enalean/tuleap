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
require_once __DIR__.'/../../../bootstrap.php';

class AgileDashboard_Planning_NearestPlanningTrackerProviderTest extends TuleapTestCase
{


    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        /*
                       Epic
        Release  ----,-- Story
          Sprint ---'      Task
        */
        $this->epic_tracker    = aTracker()->withId('epic')->withParent(null)->build();
        $this->story_tracker   = aTracker()->withId('story')->withParent($this->epic_tracker)->build();
        $this->task_tracker    = aTracker()->withId('task')->withParent($this->story_tracker)->build();
        $this->release_tracker = aTracker()->withId('release')->withParent(null)->build();
        $this->sprint_tracker  = aTracker()->withId('sprint')->withParent($this->epic_tracker)->build();

        $release_planning = mockery_stub(\Planning::class)->getPlanningTracker()->returns($this->release_tracker);
        stub($release_planning)->getPlanningTrackerId()->returns('release');
        $sprint_planning  = mockery_stub(\Planning::class)->getPlanningTracker()->returns($this->sprint_tracker);
        stub($sprint_planning)->getPlanningTrackerId()->returns('sprint');

        $this->hierarchy         = \Mockery::spy(\Tracker_Hierarchy::class);
        stub($this->hierarchy)->sortTrackerIds()->returns(array('release', 'sprint'));
        $this->hierarchy_factory = \Mockery::spy(\Tracker_HierarchyFactory::class);
        stub($this->hierarchy_factory)->getHierarchy()->returns($this->hierarchy);

        $this->planning_factory  = \Mockery::spy(\PlanningFactory::class);
        stub($this->planning_factory)->getPlanningsByBacklogTracker($this->task_tracker)->returns(array());
        stub($this->planning_factory)->getPlanningsByBacklogTracker($this->story_tracker)->returns(array($sprint_planning, $release_planning));
        stub($this->planning_factory)->getPlanningsByBacklogTracker($this->epic_tracker)->returns(array());

        $this->provider = new AgileDashboard_Planning_NearestPlanningTrackerProvider($this->planning_factory);
    }

    public function itRetrievesTheNearestPlanningTracker()
    {
        $this->assertEqual($this->provider->getNearestPlanningTracker($this->task_tracker, $this->hierarchy_factory), $this->sprint_tracker);
    }

    public function itReturnsNullWhenNoPlanningMatches()
    {
        $this->assertEqual($this->provider->getNearestPlanningTracker($this->epic_tracker, $this->hierarchy_factory), null);
    }
}
