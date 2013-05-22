<?php
/**
 * Copyright Enalean (c) 2013. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

require_once dirname(__FILE__) .'/../../../../../common.php';
class AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinderTest extends TuleapTestCase {

    private $user_story_tracker_id  = 1;
    private $release_tracker_id     = 2;
    private $sprint_tracker_id      = 3;
    private $epic_tracker_id        = 4;
    private $theme_tracker_id       = 5;
    private $team_tracker_id        = 6;
    private $requirement_tracker_id = 7;

    public function setUp() {
        parent::setUp();

        $this->user_story_tracker  = stub('Tracker')->getId()->returns($this->user_story_tracker_id);
        $this->release_tracker     = stub('Tracker')->getId()->returns($this->release_tracker_id);
        $this->sprint_tracker      = stub('Tracker')->getId()->returns($this->sprint_tracker_id);
        $this->epic_tracker        = stub('Tracker')->getId()->returns($this->epic_tracker_id);
        $this->theme_tracker       = stub('Tracker')->getId()->returns($this->theme_tracker_id);
        $this->team_tracker        = stub('Tracker')->getId()->returns($this->team_tracker_id);
        $this->requirement_tracker = stub('Tracker')->getId()->returns($this->requirement_tracker_id);

        $this->sprint_planning      = stub('Planning')->getId()->returns(11);
        $this->release_planning     = stub('Planning')->getId()->returns(12);
        $this->requirement_planning = stub('Planning')->getId()->returns(13);

        $this->requirement_milestone = stub('Planning_Milestone')->getTrackerId()->returns($this->requirement_tracker_id);
        $this->release_milestone     = stub('Planning_Milestone')->getTrackerId()->returns($this->release_tracker_id);
        $this->sprint_milestone      = stub('Planning_Milestone')->getTrackerId()->returns($this->sprint_tracker_id);

        stub($this->requirement_milestone)->getPlanning()->returns($this->requirement_planning);
        stub($this->release_milestone)->getPlanning()->returns($this->release_planning);
        stub($this->sprint_milestone)->getPlanning()->returns($this->sprint_planning);

        $this->tracker_hierarchy_factory = mock('Tracker_HierarchyFactory');
        $this->planning_factory          = mock('PlanningFactory');

        $this->finder = new AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder(
            $this->tracker_hierarchy_factory,
            $this->planning_factory
        );
    }

    /**
     * user_story  ----> sprint*
     */
    public function itReturnsNullIfThereIsNoChildTracker() {
        stub($this->sprint_planning)->getBacklogTracker()->returns($this->user_story_tracker);
        stub($this->tracker_hierarchy_factory)->getChildren($this->sprint_tracker_id)->returns(array());

        $tracker = $this->finder->findSubmilestone($this->sprint_milestone);

        $this->assertNull($tracker);
    }

    /**
     * user_story  ----> release*
     *              `-->  ` sprint
     */
    public function itReturnsSprintWhenBothPlanningsHaveSameBacklogTracker() {
        stub($this->release_planning)->getBacklogTracker()->returns($this->user_story_tracker);
        stub($this->sprint_planning)->getBacklogTracker()->returns($this->user_story_tracker);
        stub($this->tracker_hierarchy_factory)->getChildren($this->release_tracker_id)->returns(array($this->sprint_tracker));
        stub($this->planning_factory)->getPlanningByPlanningTracker($this->sprint_tracker)->returns($this->sprint_planning);

        $tracker = $this->finder->findSubmilestone($this->release_milestone);

        $this->assertEqual($tracker, $this->sprint_tracker);
    }

    /**
     * user_story  ----> release*
     *                    ` sprint
     */
    public function itReturnsNullWhenChildHaveNoPlanning() {
        stub($this->release_planning)->getBacklogTracker()->returns($this->user_story_tracker);
        stub($this->tracker_hierarchy_factory)->getChildren($this->release_tracker_id)->returns(array($this->sprint_tracker));
        stub($this->planning_factory)->getPlanningByPlanningTracker($this->sprint_tracker)->returns(null);

        $tracker = $this->finder->findSubmilestone($this->release_milestone);

        $this->assertNull($tracker);
    }

    /**
     * epic          ----> release*
     *  ` user_story  `-->  ` sprint
     */
    public function itReturnsSprintWhenTheBacklogTrackerIsParent() {
        stub($this->release_planning)->getBacklogTracker()->returns($this->epic_tracker);
        stub($this->sprint_planning)->getBacklogTracker()->returns($this->user_story_tracker);
        stub($this->tracker_hierarchy_factory)->getChildren($this->release_tracker_id)->returns(array($this->sprint_tracker));
        stub($this->tracker_hierarchy_factory)->getAllParents($this->user_story_tracker)->returns(array($this->epic_tracker));
        stub($this->planning_factory)->getPlanningByPlanningTracker($this->sprint_tracker)->returns($this->sprint_planning);

        $tracker = $this->finder->findSubmilestone($this->release_milestone);

        $this->assertEqual($tracker, $this->sprint_tracker);
    }

    /**
     * epic  ----> release*
     *              ` requirement <---- team
     *
     * (no hierarchy between epic and team)
     */
    public function itReturnsNullWhenTheBacklogTrackerIsNotRelated() {
        stub($this->release_planning)->getBacklogTracker()->returns($this->epic_tracker);
        stub($this->requirement_planning)->getBacklogTracker()->returns($this->team_tracker);
        stub($this->tracker_hierarchy_factory)->getChildren($this->release_tracker_id)->returns(array($this->requirement_tracker));
        stub($this->tracker_hierarchy_factory)->getAllParents($this->team_tracker)->returns(array());
        stub($this->planning_factory)->getPlanningByPlanningTracker($this->requirement_tracker)->returns($this->requirement_planning);

        $tracker = $this->finder->findSubmilestone($this->release_milestone);

        $this->assertNull($tracker);
    }

    /**
     * theme            ----> release*
     *  ` epic            ,->  ` sprint
     *     ` user_story -'
      */
    public function itReturnsSprintWhenTheBacklogTrackerIsAncestor() {
        stub($this->release_planning)->getBacklogTracker()->returns($this->theme_tracker);
        stub($this->sprint_planning)->getBacklogTracker()->returns($this->user_story_tracker);
        stub($this->tracker_hierarchy_factory)->getChildren($this->release_tracker_id)->returns(array($this->sprint_tracker));
        stub($this->tracker_hierarchy_factory)->getAllParents($this->user_story_tracker)->returns(array($this->epic_tracker, $this->theme_tracker));
        stub($this->planning_factory)->getPlanningByPlanningTracker($this->sprint_tracker)->returns($this->sprint_planning);

        $tracker = $this->finder->findSubmilestone($this->release_milestone);

        $this->assertEqual($tracker, $this->sprint_tracker);
    }

    /**
     * user story  ----> release*
     *             \       + requirement <---- team
     *              '-->   ` sprint
     *
     * (no hierarchy between epic and team)
     * (requirement and sprint are siblings)
     */
    public function itReturnsSprintEvenIfThereIsSiblingWithoutMatchingBacklogTracker() {
        stub($this->release_planning)->getBacklogTracker()->returns($this->user_story_tracker);
        stub($this->requirement_planning)->getBacklogTracker()->returns($this->team_tracker);
        stub($this->sprint_planning)->getBacklogTracker()->returns($this->user_story_tracker);
        stub($this->tracker_hierarchy_factory)->getChildren($this->release_tracker_id)->returns(array($this->requirement_tracker, $this->sprint_tracker));
        stub($this->tracker_hierarchy_factory)->getAllParents($this->team_tracker)->returns(array());
        stub($this->tracker_hierarchy_factory)->getAllParents($this->user_story_tracker)->returns(array());
        stub($this->planning_factory)->getPlanningByPlanningTracker($this->requirement_tracker)->returns($this->requirement_planning);
        stub($this->planning_factory)->getPlanningByPlanningTracker($this->sprint_tracker)->returns($this->sprint_planning);

        $tracker = $this->finder->findSubmilestone($this->release_milestone);

        $this->assertEqual($tracker, $this->sprint_tracker);
    }

}
?>
