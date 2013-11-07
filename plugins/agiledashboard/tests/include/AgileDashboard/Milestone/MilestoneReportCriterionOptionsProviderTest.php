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

class AgileDashboard_Milestone_MilestoneReportCriterionOptionsProviderTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        /*
        Product          Epic
          Release  ----,-- Story
            Sprint ---'      Task
        */

        $this->release_tracker_id = 101;
        $this->sprint_tracker_id  = 1001;

        $this->epic_tracker    = aTracker()->withId('epic')->withParent(null)->build();
        $this->story_tracker   = aTracker()->withId('story')->withParent($this->epic_tracker)->build();
        $this->task_tracker    = aTracker()->withId('task')->withParent($this->story_tracker)->build();
        $this->product_tracker = aTracker()->withId('product')->withParent(null)->build();
        $this->release_tracker = aTracker()->withId($this->release_tracker_id)->withParent($this->product_tracker)->build();
        $this->sprint_tracker  = aTracker()->withId($this->sprint_tracker_id)->withParent($this->epic_tracker)->build();

        $release_planning = stub('Planning')->getPlanningTracker()->returns($this->release_tracker);
        $sprint_planning  = stub('Planning')->getPlanningTracker()->returns($this->sprint_tracker);

        $this->planning_factory = mock('PlanningFactory');
        stub($this->planning_factory)->getPlanningByPlanningTracker($this->product_tracker)->returns(null);
        stub($this->planning_factory)->getPlanningByPlanningTracker($this->release_tracker)->returns($release_planning);
        stub($this->planning_factory)->getPlanningByPlanningTracker($this->sprint_tracker)->returns($sprint_planning);

        $this->hierarchy_factory = mock('Tracker_HierarchyFactory');
        stub($this->hierarchy_factory)->getAllParents($this->sprint_tracker)->returns(array($this->release_tracker, $this->product_tracker));

        $this->dao = mock('AgileDashboard_Milestone_MilestoneDao');
        stub($this->dao)->getAllMilestoneByTrackers(array($this->release_tracker_id, $this->sprint_tracker_id))->returnsDar(
            array(
                'm101_id'     => '123',
                'm101_title'  => 'Tuleap 6.5',
                'm1001_id'    => '1231',
                'm1001_title' => 'Sprint 31',
            ),
            array(
                'm101_id'     => '123',
                'm101_title'  => 'Tuleap 6.5',
                'm1001_id'    => '1232',
                'm1001_title' => 'Sprint 32',
            ),
            array(
                'm101_id'     => '124',
                'm101_title'  => 'Tuleap 6.6',
                'm1001_id'    => '1241',
                'm1001_title' => 'Sprint 33',
            )
        );
        $this->nearest_planning_tracker_provider = mock('AgileDashboard_Planning_NearestPlanningTrackerProvider');
        $this->provider = new AgileDashboard_Milestone_MilestoneReportCriterionOptionsProvider(
            $this->nearest_planning_tracker_provider,
            $this->dao,
            $this->hierarchy_factory,
            $this->planning_factory
        );
    }

    public function itReturnsEmptyArrayWhenNoNearestPlanningTracker() {
        stub($this->nearest_planning_tracker_provider)->getNearestPlanningTracker($this->task_tracker, $this->hierarchy_factory)->returns(null);

        $this->assertEqual($this->provider->getSelectboxOptions($this->task_tracker, '*'), array());
    }

    public function itDoesNotSearchOnProductTrackerSinceThereIsNoPlanning() {
        stub($this->nearest_planning_tracker_provider)->getNearestPlanningTracker($this->task_tracker, $this->hierarchy_factory)->returns($this->sprint_tracker);

        expect($this->dao)->getAllMilestoneByTrackers(array($this->release_tracker_id, $this->sprint_tracker_id))->once();

        $this->provider->getSelectboxOptions($this->task_tracker, '*');
    }

    public function _itReturnsTheListOfOptions() {
        stub($this->nearest_planning_tracker_provider)->getNearestPlanningTracker($this->task_tracker)->returns($this->sprint_tracker);
        $this->assertEqual(
            $this->provider->getSelectboxOptions($this->task_tracker, 124),
            array(
                '<option value="123" > Tuleap 6.5</option>',
                '<option value="1231" >- Sprint 31</option>',
                '<option value="1232" >- Sprint 32</option>',
                '<option value="124" selected="selected"> Tuleap 6.6</option>',
                '<option value="1241" >- Sprint 33</option>',
            )
        );
    }
}
