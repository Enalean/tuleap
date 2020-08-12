<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\MultiProjectBacklog\Aggregator\Milestone\CreationCheck;

use Planning_VirtualTopMilestone;
use Tracker_Semantic_StatusDao;
use Tracker_Semantic_StatusFactory;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\MilestoneTrackerCollection;

class StatusSemanticChecker
{
    /**
     * @var Tracker_Semantic_StatusDao
     */
    private $semantic_status_dao;

    /**
     * @var Tracker_Semantic_StatusFactory
     */
    private $semantic_status_factory;

    public function __construct(
        Tracker_Semantic_StatusDao $semantic_status_dao,
        Tracker_Semantic_StatusFactory $semantic_status_factory
    ) {
        $this->semantic_status_dao     = $semantic_status_dao;
        $this->semantic_status_factory = $semantic_status_factory;
    }

    public function areSemanticStatusWellConfigured(
        Planning_VirtualTopMilestone $top_milestone,
        MilestoneTrackerCollection $milestone_tracker_collection
    ): bool {
        $aggregator_top_milestone_tracker = $top_milestone->getPlanning()->getPlanningTracker();
        $aggregator_tracker_status_semantic = $this->semantic_status_factory->getByTracker($aggregator_top_milestone_tracker);

        if ($aggregator_tracker_status_semantic->getField() === null) {
            return false;
        }

        $nb_of_trackers_without_status = $this->semantic_status_dao->getNbOfTrackerWithoutSemanticStatusDefined(
            $milestone_tracker_collection->getTrackerIds()
        );
        if ($nb_of_trackers_without_status > 0) {
            return false;
        }

        $aggregator_open_values_labels = $aggregator_tracker_status_semantic->getOpenLabels();

        foreach ($milestone_tracker_collection->getMilestoneTrackers() as $tracker) {
            $status_semantic = $this->semantic_status_factory->getByTracker($tracker);
            if (count(array_diff($aggregator_open_values_labels, $status_semantic->getOpenLabels())) > 0) {
                return false;
            }
        }

        return true;
    }
}
