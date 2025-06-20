<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\AgileDashboard\Tracker;

use LogicException;
use PFUser;
use PlanningFactory;
use TrackerFactory;
use Tuleap\Tracker\Tracker;

class TrackerHierarchyUpdateChecker
{
    public function __construct(
        private PlanningFactory $planning_factory,
        private TrackerFactory $tracker_factory,
    ) {
    }

    /**
     * @throws TrackersCannotBeLinkedWithHierarchyException
     * @throws LogicException
     */
    public function canTrackersBeLinkedWithHierarchy(PFUser $user, Tracker $parent_tracker, array $children_trackers_ids): void
    {
        if (empty($children_trackers_ids)) {
            return;
        }

        $project_plannings = $this->planning_factory->getPlannings(
            $user,
            (int) $parent_tracker->getProject()->getID()
        );

        foreach ($project_plannings as $planning) {
            $backlog_trackers_ids = $planning->getBacklogTrackersIds();
            if (in_array($parent_tracker->getId(), $backlog_trackers_ids)) {
                foreach ($children_trackers_ids as $child_tracker_id) {
                    if (in_array($child_tracker_id, $backlog_trackers_ids)) {
                        $child_tracker = $this->tracker_factory->getTrackerById($child_tracker_id);
                        if ($child_tracker === null) {
                            throw new LogicException(
                                "Tracker #$child_tracker_id not found. This is not expected"
                            );
                        }

                        throw new TrackersCannotBeLinkedWithHierarchyException(
                            $planning,
                            $parent_tracker,
                            $child_tracker,
                        );
                    }
                }
            }
        }
    }
}
