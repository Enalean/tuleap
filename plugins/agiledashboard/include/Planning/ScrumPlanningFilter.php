<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Planning;

use AgileDashboard_HierarchyChecker;
use PFUser;
use Planning;
use Planning_TrackerPresenter;
use PlanningFactory;
use Tuleap\AgileDashboard\ScrumForMonoMilestoneChecker;

class ScrumPlanningFilter
{
    /**
     * @var AgileDashboard_HierarchyChecker
     */
    private $hierarchy_checker;
    /**
     * @var ScrumForMonoMilestoneChecker
     */
    private $scrum_mono_milestone_checker;
    /**
     * @var PlanningFactory
     */
    private $planning_factory;

    public function __construct(
        AgileDashboard_HierarchyChecker $hierarchy_checker,
        ScrumForMonoMilestoneChecker $scrum_mono_milestone_checker,
        PlanningFactory $planning_factory
    ) {
        $this->hierarchy_checker            = $hierarchy_checker;
        $this->scrum_mono_milestone_checker = $scrum_mono_milestone_checker;
        $this->planning_factory             = $planning_factory;
    }

    public function getBacklogTrackersFiltered(array $trackers, array $kanban_tracker_ids, Planning $planning)
    {
        $trackers_filtered = array();

        foreach ($this->getPlanningTrackerPresenters($trackers, $planning) as $tracker_presenter) {
            $trackers_filtered[] = array(
                'name'     => $tracker_presenter->getName(),
                'id'       => $tracker_presenter->getId(),
                'selected' => $tracker_presenter->selectedIfBacklogTracker(),
                'disabled' => in_array($tracker_presenter->getId(), $kanban_tracker_ids)
                    || $this->hierarchy_checker->isKanbanHierarchy($tracker_presenter->getTracker())
            );
        }

        return $trackers_filtered;
    }

    private function getPlanningTrackerPresenters(array $trackers, Planning $planning)
    {
        $tracker_presenters = array();

        foreach ($trackers as $tracker) {
            if ($tracker !== null) {
                $tracker_presenters[] = new Planning_TrackerPresenter($planning, $tracker);
            }
        }

        return $tracker_presenters;
    }

    /**
     * @param array $kanban_tracker_ids
     * @param Planning $planning
     * @param PFUser $user
     *
     * @return array
     */
    public function getPlanningTrackersFiltered(
        array $kanban_tracker_ids,
        Planning $planning,
        PFUser $user,
        $project_id
    ) {
        if ($this->scrum_mono_milestone_checker->isMonoMilestoneEnabled($project_id) === true) {
            $trackers_filtered = $this->getPlanningTrackerFilteredForMonoMilestone($user, $project_id);
        } else {
            $available_planning_trackers   = $this->planning_factory->getAvailablePlanningTrackers(
                $user,
                $project_id
            );
            $available_planning_trackers[] = $planning->getPlanningTracker();
            $trackers_filtered             = $this->getPlanningTrackerFilteredForMultiMilestone(
                $available_planning_trackers,
                $kanban_tracker_ids,
                $planning
            );
        }

        return $trackers_filtered;
    }

    /**
     * @param PFUser $user
     *
     * @return array
     */
    private function getPlanningTrackerFilteredForMonoMilestone(PFUser $user, $project_id)
    {
        $trackers_filtered = array();

        $available_trackers = $this->planning_factory->getAvailableBacklogTrackers($user, $project_id);

        foreach ($available_trackers as $tracker) {
            if (count($tracker->getChildren()) === 0 && count($tracker->getParent()) === 0) {
                $trackers_filtered[] = array(
                    'name'     => $tracker->getName(),
                    'id'       => $tracker->getId(),
                    'selected' => false,
                    'disabled' => false
                );
            }
        }

        return $trackers_filtered;
    }

    /**
     * @param array $trackers
     * @param array $kanban_tracker_ids
     * @param Planning $planning
     *
     * @return array
     */
    private function getPlanningTrackerFilteredForMultiMilestone(
        array $trackers,
        array $kanban_tracker_ids,
        Planning $planning
    ) {
        $trackers_filtered = array();

        foreach ($this->getPlanningTrackerPresenters($trackers, $planning) as $tracker_presenter) {
            $trackers_filtered[] = array(
                'name'     => $tracker_presenter->getName(),
                'id'       => $tracker_presenter->getId(),
                'selected' => $tracker_presenter->selectedIfPlanningTracker(),
                'disabled' => in_array($tracker_presenter->getId(), $kanban_tracker_ids)
                    || $this->hierarchy_checker->isKanbanHierarchy($tracker_presenter->getTracker())
            );
        }

        return $trackers_filtered;
    }
}
