<?php

/**
 * Copyright (c) Enalean, 2013. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */
require_once dirname(__FILE__).'/../../common.php';

class Planning_TopMilestoneTest extends TuleapTestCase {

    public function itThrowsAnExceptionIfNoPlanningsExistForProject() {
        $project = mock('Project');
        $user = mock('PFUser');
        $tracker_manager = mock('TrackerManager');
        $planning_factory = mock('PlanningFactory');

        $this->expectException('Planning_TopMilestoneNoPlanningsException');

        $milestone = new Planning_TopMilestone($project, $user, $tracker_manager, $planning_factory);
    }

    public function itCreatesNewPlanningWithValidBacklogAndPlanningTrackers() {
        $project = mock('Project');
        $user    = mock('PFUser');
        $tracker_manager  = mock('TrackerManager');
        $planning_factory = mock('PlanningFactory');

        $backlog_tracker  = mock('Tracker');
        $planning_tracker = mock('Tracker');

        stub($backlog_tracker)->getId()->returns(78);

        $my_planning = new Planning(null, null, null, null, null, 78, 45);
        $my_planning->setBacklogTracker($backlog_tracker)
                ->setPlanningTracker($planning_tracker);

        $project_plannings = array(
             $my_planning
        );
        stub($planning_factory)->getOrderedPlanningsWithBacklogTracker()->returns($project_plannings);
        stub($tracker_manager)->getTrackersByGroupId()->returns(
            array(
                45 => $backlog_tracker,
                78 => $planning_tracker
            )
        );
        stub($project)->getID()->returns(56);

        $milestone = new Planning_TopMilestone($project, $user, $tracker_manager, $planning_factory);

        $this->assertIsA($milestone->getPlanning(), 'Planning');
        $this->assertIsA($milestone->getPlanning()->getBacklogTracker(), 'Tracker');
        $this->assertIsA($milestone->getPlanning()->getPlanningTracker(), 'Tracker');
    }
}
?>
