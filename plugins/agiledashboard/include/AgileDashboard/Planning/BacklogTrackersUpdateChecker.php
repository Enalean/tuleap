<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Planning;

use PlanningParameters;
use Psr\Log\LoggerInterface;
use Tracker_HierarchyFactory;
use TrackerFactory;

class BacklogTrackersUpdateChecker
{
    public function __construct(
        private Tracker_HierarchyFactory $tracker_hierarchy_factory,
        private TrackerFactory $tracker_factory,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws TrackersHaveAtLeastOneHierarchicalLinkException
     * @throws TrackersWithHierarchicalLinkDefinedNotFoundException
     */
    public function checkProvidedBacklogTrackersAreValid(PlanningParameters $updated_planning): void
    {
        $updated_backlog_trackers_ids = $updated_planning->backlog_tracker_ids;
        $hierarchy                    = $this->tracker_hierarchy_factory->getHierarchy($updated_backlog_trackers_ids);
        foreach ($updated_backlog_trackers_ids as $backlog_tracker_id) {
            $parent_id = $hierarchy->getParent($backlog_tracker_id);
            if ($parent_id === null) {
                continue;
            }

            if (in_array($parent_id, $updated_backlog_trackers_ids)) {
                $parent_tracker = $this->tracker_factory->getTrackerById($parent_id);
                if ($parent_tracker === null) {
                    $this->logger->error("Error with tracker #$parent_id: the tracker is not found but a hierarchy is defined.");
                    throw new TrackersWithHierarchicalLinkDefinedNotFoundException();
                }

                $child_tracker = $this->tracker_factory->getTrackerById($backlog_tracker_id);
                if ($child_tracker === null) {
                    $this->logger->error("Error with tracker #$backlog_tracker_id: the tracker is not found but a hierarchy is defined.");
                    throw new TrackersWithHierarchicalLinkDefinedNotFoundException();
                }

                throw new TrackersHaveAtLeastOneHierarchicalLinkException(
                    $parent_tracker->getName(),
                    $child_tracker->getName(),
                );
            }
        }
    }
}
