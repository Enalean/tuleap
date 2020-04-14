<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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
class AgileDashboard_Milestone_MilestoneReportCriterionOptionsProvider
{
    public const TOP_BACKLOG_IDENTIFIER   = "0";
    public const TOP_BACKLOG_OPTION_ENTRY = "Top Backlog";

    /** @var AgileDashboard_Planning_NearestPlanningTrackerProvider */
    private $nearest_planning_tracker_provider;

    /** @var AgileDashboard_Milestone_MilestoneDao */
    private $dao;

    /** @var Tracker_HierarchyFactory */
    private $hierarchy_factory;

    /** @var PlanningFactory */
    private $planning_factory;

    /** @var TrackerFactory */
    private $tracker_factory;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    public function __construct(
        AgileDashboard_Planning_NearestPlanningTrackerProvider $nearest_planning_tracker_provider,
        AgileDashboard_Milestone_MilestoneDao $dao,
        Tracker_HierarchyFactory $hierarchy_factory,
        PlanningFactory $planning_factory,
        TrackerFactory $tracker_factory,
        Tracker_ArtifactFactory $artifact_factory
    ) {
        $this->nearest_planning_tracker_provider = $nearest_planning_tracker_provider;
        $this->hierarchy_factory                 = $hierarchy_factory;
        $this->dao                               = $dao;
        $this->planning_factory                  = $planning_factory;
        $this->tracker_factory                   = $tracker_factory;
        $this->artifact_factory                  = $artifact_factory;
    }

    /**
     * Returns array of <option>
     *
     * @return string[]
     */
    public function getSelectboxOptions(Tracker $backlog_tracker, $selected_milestone_id, PFUser $user)
    {
        $nearest_planning_tracker = $this->nearest_planning_tracker_provider->getNearestPlanningTracker($backlog_tracker, $this->hierarchy_factory);
        if (! $nearest_planning_tracker) {
            return array();
        }

        $planning_trackers_ids = $this->getPlanningTrackersIds($nearest_planning_tracker);

        return $this->formatAllMilestonesAsSelectboxOptions($planning_trackers_ids, $selected_milestone_id, $backlog_tracker, $user);
    }

    /** @return string[] */
    private function formatAllMilestonesAsSelectboxOptions(array $planning_trackers_ids, $selected_milestone_id, Tracker $backlog_tracker, PFUser $user)
    {
        $hp = Codendi_HTMLPurifier::instance();
        $options = array();
        $current_milestone = array();

        $options[] = $this->addTopBacklogPlanningEntry($selected_milestone_id, $backlog_tracker, $user);

        foreach ($planning_trackers_ids as $id) {
            $current_milestone[$id] = null;
        }

        foreach ($this->dao->getAllMilestoneByTrackers($planning_trackers_ids) as $row) {
            foreach ($planning_trackers_ids as $index => $id) {
                $tracker = $this->tracker_factory->getTrackerById($id);
                if ($tracker->userCanView($user)) {
                    $milestone_id    = $row['m' . $id . '_id'];
                    $milestone_title = $row['m' . $id . '_title'];

                    if (! $milestone_id) {
                        continue;
                    }

                    if ($current_milestone[$id] === $milestone_id) {
                        continue;
                    }

                    $milestone               = $this->artifact_factory->getArtifactById((int) $milestone_id);
                    $user_can_view_milestone = $milestone !== null && $milestone->userCanView($user);
                    if ($user_can_view_milestone) {
                        $content                = str_pad('', $index, '-') . ' ' . $hp->purify($milestone_title);
                        $options[]              = $this->getOptionForSelectBox($selected_milestone_id, $milestone_id, $content);
                        $current_milestone[$id] = $milestone_id;
                    }
                }
            }
        }

        return $options;
    }

    private function getOptionForSelectBox($selected_milestone_id, $milestone_id, $content)
    {
        $selected = '';

        if ($selected_milestone_id == $milestone_id) {
            $selected = 'selected="selected"';
        }

        $option  = '<option value="' . $milestone_id . '" ' . $selected . '>';
        $option .= $content;
        $option .= '</option>';

        return $option;
    }

    private function addTopBacklogPlanningEntry($selected_milestone_id, Tracker $backlog_tracker, PFUser $user)
    {
        try {
            $top_planning  = $this->planning_factory->getVirtualTopPlanning($user, $backlog_tracker->getGroupId());
        } catch (Planning_NoPlanningsException $exception) {
            return;
        }

        if ($top_planning) {
            $backlog_trackers_ids = $top_planning->getBacklogTrackersIds();

            if (in_array($backlog_tracker->getId(), $backlog_trackers_ids)) {
                return $this->getOptionForSelectBox($selected_milestone_id, self::TOP_BACKLOG_IDENTIFIER, self::TOP_BACKLOG_OPTION_ENTRY);
            }
        }
        return;
    }

    /** @return int[] */
    private function getPlanningTrackersIds(Tracker $nearest_planning_tracker)
    {
        $parents = $this->getParentsWithPlanningAndOrderedFromTopToBottom($nearest_planning_tracker);

        return array_map(
            static function (Tracker $tracker) {
                return $tracker->getId();
            },
            $parents
        );
    }

    /** @return Tracker[] */
    private function keepsTrackersUntilThereIsNoPlanning(array $list_of_trackers)
    {
        $trackers = array();
        foreach ($list_of_trackers as $tracker) {
            if (! $this->planning_factory->getPlanningByPlanningTracker($tracker)) {
                break;
            }
            $trackers[] = $tracker;
        }
        return $trackers;
    }

    /** @return Tracker[] */
    private function getParentsWithPlanningAndOrderedFromTopToBottom(Tracker $nearest_planning_tracker)
    {
        $parents = $this->hierarchy_factory->getAllParents($nearest_planning_tracker);
        $parents = $this->keepsTrackersUntilThereIsNoPlanning($parents);
        $parents = array_reverse($parents);
        $parents[] = $nearest_planning_tracker;

        return $parents;
    }
}
