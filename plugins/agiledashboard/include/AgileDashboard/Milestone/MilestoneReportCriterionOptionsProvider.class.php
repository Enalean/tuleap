<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

/**
 * I am a helper to build selectbox options of all milestones of a given tracker
 */
class AgileDashboard_Milestone_MilestoneReportCriterionOptionsProvider extends DataAccessObject {

    /** @var AgileDashboard_Planning_NearestPlanningTrackerProvider */
    private $nearest_planning_tracker_provider;

    /** @var AgileDashboard_Milestone_MilestoneDao */
    private $dao;

    /** @var Tracker_HierarchyFactory */
    private $hierarchy_factory;

    public function __construct(
        AgileDashboard_Planning_NearestPlanningTrackerProvider $nearest_planning_tracker_provider,
        AgileDashboard_Milestone_MilestoneDao $dao,
        Tracker_HierarchyFactory $hierarchy_factory
    ) {
        $this->nearest_planning_tracker_provider = $nearest_planning_tracker_provider;
        $this->hierarchy_factory                 = $hierarchy_factory;
        $this->dao                               = $dao;
    }

    /**
     * Returns array of <option>
     *
     * @return string[]
     */
    public function getSelectboxOptions(Tracker $backlog_tracker, $selected_milestone_id) {
        $nearest_planning_tracker = $this->nearest_planning_tracker_provider->getNearestPlanningTracker($backlog_tracker);
        if (! $nearest_planning_tracker) {
            return array();
        }

        $planning_trackers_ids = $this->getPlanningTrackersIds($nearest_planning_tracker);

        return $this->formatAllMilestonesAsSelectboxOptions($planning_trackers_ids, $selected_milestone_id);
    }

    /** @return string[] */
    private function formatAllMilestonesAsSelectboxOptions(array $planning_trackers_ids, $selected_milestone_id) {
        $hp = Codendi_HTMLPurifier::instance();
        $options = array();
        $current_milestone = array();
        foreach ($planning_trackers_ids as $id) {
            $current_milestone[$id] = null;
        }

        foreach ($this->dao->getAllMilestoneByTrackers($planning_trackers_ids) as $row) {
            foreach ($planning_trackers_ids as $index => $id) {
                $milestone_id    = $row['m'. $id .'_id'];
                $milestone_title = $row['m'. $id .'_title'];
                if (! $milestone_id) {
                    continue;
                }
                if ($current_milestone[$id] === $milestone_id) {
                    continue;
                }

                $selected = '';
                if ($milestone_id == $selected_milestone_id) {
                    $selected = 'selected="selected"';
                }
                $option  = '<option value="'. $milestone_id .'" '. $selected .'>';
                $option .= str_pad('', $index, '-') .' '. $hp->purify($milestone_title);
                $option .= '</option>';
                $options[] = $option;
                $current_milestone[$id] = $milestone_id;
            }
        }

        return $options;
    }

    /** @return Tracker[] */
    private function getPlanningTrackersIds(Tracker $nearest_planning_tracker) {
        $parents = $this->getParentsOrderedFromTopToBottom($nearest_planning_tracker);

        return array_map(array($this, 'extractTrackerId'), $parents);
    }

    /** @return Tracker[] */
    private function getParentsOrderedFromTopToBottom(Tracker $nearest_planning_tracker) {
        $parents = $this->hierarchy_factory->getAllParents($nearest_planning_tracker);
        $parents = array_reverse($parents);
        $parents[] = $nearest_planning_tracker;

        return $parents;
    }

    /** @return int */
    private function extractTrackerId(Tracker $tracker) {
        return $tracker->getId();
    }
}
