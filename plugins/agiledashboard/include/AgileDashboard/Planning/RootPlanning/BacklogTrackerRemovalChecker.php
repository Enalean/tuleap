<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Planning\RootPlanning;

use Planning;
use PlanningParameters;
use Tuleap\AgileDashboard\Planning\TrackerHaveAtLeastOneAddToTopBacklogPostActionException;
use Tuleap\AgileDashboard\Workflow\AddToTopBacklogPostActionDao;

class BacklogTrackerRemovalChecker
{
    /**
     * @var AddToTopBacklogPostActionDao
     */
    private $add_to_top_backlog_post_action_dao;

    public function __construct(AddToTopBacklogPostActionDao $add_to_top_backlog_post_action_dao)
    {
        $this->add_to_top_backlog_post_action_dao = $add_to_top_backlog_post_action_dao;
    }

    /**
     * @throws TrackerHaveAtLeastOneAddToTopBacklogPostActionException
     */
    public function checkRemovedBacklogTrackersCanBeRemoved(
        Planning $planning,
        PlanningParameters $planning_parameters
    ): void {
        $removed_backlog_tracker_ids = $this->getRemovedTrackerIds($planning, $planning_parameters);

        if (count($removed_backlog_tracker_ids) === 0) {
            return;
        }

        $trackers_in_error = $this->add_to_top_backlog_post_action_dao->getTrackersThatHaveAtLeastOneAddToTopBacklogPostAction(
            $removed_backlog_tracker_ids
        );

        if (count($trackers_in_error) > 0) {
            throw new TrackerHaveAtLeastOneAddToTopBacklogPostActionException(
                $this->getTrackerNames($trackers_in_error)
            );
        }
    }

    private function getTrackerNames(array $trackers_in_error): array
    {
        $tracker_names = [];
        foreach ($trackers_in_error as $tracker_in_error) {
            $tracker_names[] = (string) $tracker_in_error['name'];
        }

        return $tracker_names;
    }

    private function getRemovedTrackerIds(Planning $planning, PlanningParameters $planning_parameters): array
    {
        $current_backlog_tracker_ids = $planning->getBacklogTrackersIds();
        $removed_backlog_tracker_ids = array_diff(
            $current_backlog_tracker_ids,
            $planning_parameters->backlog_tracker_ids
        );

        return array_values($removed_backlog_tracker_ids);
    }
}
