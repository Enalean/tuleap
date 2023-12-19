<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

use PFUser;
use Planning;
use Planning_TrackerPresenter;
use PlanningFactory;

class ScrumPlanningFilter
{
    /**
     * @var PlanningFactory
     */
    private $planning_factory;

    public function __construct(
        PlanningFactory $planning_factory,
    ) {
        $this->planning_factory = $planning_factory;
    }

    public function getBacklogTrackersFiltered(array $trackers, Planning $planning)
    {
        $trackers_filtered = [];

        foreach ($this->getPlanningTrackerPresenters($trackers, $planning) as $tracker_presenter) {
            $trackers_filtered[] = [
                'name'     => $tracker_presenter->getName(),
                'id'       => $tracker_presenter->getId(),
                'selected' => $tracker_presenter->selectedIfBacklogTracker(),
            ];
        }

        return $trackers_filtered;
    }

    private function getPlanningTrackerPresenters(array $trackers, Planning $planning)
    {
        $tracker_presenters = [];

        foreach ($trackers as $tracker) {
            if ($tracker !== null) {
                $tracker_presenters[] = new Planning_TrackerPresenter($planning, $tracker);
            }
        }

        return $tracker_presenters;
    }

    public function getPlanningTrackersFiltered(
        Planning $planning,
        PFUser $user,
        $project_id,
    ): array {
        $available_planning_trackers   = $this->planning_factory->getAvailablePlanningTrackers(
            $user,
            $project_id
        );
        $available_planning_trackers[] = $planning->getPlanningTracker();

        $trackers_filtered = [];

        foreach ($this->getPlanningTrackerPresenters($available_planning_trackers, $planning) as $tracker_presenter) {
            $trackers_filtered[] = [
                'name'     => $tracker_presenter->getName(),
                'id'       => $tracker_presenter->getId(),
                'selected' => $tracker_presenter->selectedIfPlanningTracker(),
            ];
        }

        return $trackers_filtered;
    }
}
