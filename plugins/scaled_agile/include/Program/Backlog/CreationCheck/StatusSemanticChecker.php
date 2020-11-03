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

declare(strict_types=1);

namespace Tuleap\ScaledAgile\Program\Backlog\CreationCheck;

use Tracker_Semantic_StatusDao;
use Tracker_Semantic_StatusFactory;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\SourceTrackerCollection;
use Tuleap\ScaledAgile\Program\PlanningConfiguration\PlanningData;

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
        PlanningData $planning,
        SourceTrackerCollection $source_tracker_collection
    ): bool {
        $program_top_milestone_tracker   = $planning->getPlanningTrackerData();
        $program_tracker_status_semantic = $this->semantic_status_factory->getByTracker($program_top_milestone_tracker->getFullTracker());

        if ($program_tracker_status_semantic->getField() === null) {
            return false;
        }

        $nb_of_trackers_without_status = $this->semantic_status_dao->getNbOfTrackerWithoutSemanticStatusDefined(
            $source_tracker_collection->getTrackerIds()
        );
        if ($nb_of_trackers_without_status > 0) {
            return false;
        }

        $program_open_values_labels = $program_tracker_status_semantic->getOpenLabels();

        foreach ($source_tracker_collection->getSourceTrackers() as $tracker) {
            $status_semantic = $this->semantic_status_factory->getByTracker($tracker->getFullTracker());
            if (count(array_diff($program_open_values_labels, $status_semantic->getOpenLabels())) > 0) {
                return false;
            }
        }

        return true;
    }
}
