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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\CreationCheck;

use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\VerifyStatusIsAligned;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Source\SourceTrackerCollection;
use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\Tracker\Semantic\Status\TrackerSemanticStatusDao;
use Tuleap\Tracker\Semantic\Status\TrackerSemanticStatusFactory;

final class StatusIsAlignedVerifier implements VerifyStatusIsAligned
{
    private TrackerSemanticStatusDao $semantic_status_dao;
    private TrackerSemanticStatusFactory $semantic_status_factory;
    private \TrackerFactory $tracker_factory;

    public function __construct(
        TrackerSemanticStatusDao $semantic_status_dao,
        TrackerSemanticStatusFactory $semantic_status_factory,
        \TrackerFactory $tracker_factory,
    ) {
        $this->semantic_status_dao     = $semantic_status_dao;
        $this->semantic_status_factory = $semantic_status_factory;
        $this->tracker_factory         = $tracker_factory;
    }

    public function isStatusWellConfigured(
        TrackerReference $tracker,
        SourceTrackerCollection $source_tracker_collection,
        ConfigurationErrorsCollector $configuration_errors,
    ): bool {
        $full_tracker = $this->tracker_factory->getTrackerById($tracker->getId());
        if (! $full_tracker) {
            throw new \RuntimeException('Tracker with id #' . $tracker->getId() . ' is not found.');
        }
        $program_tracker_status_semantic = $this->semantic_status_factory->getByTracker($full_tracker);

        if ($program_tracker_status_semantic->getField() === null) {
            $configuration_errors->addSemanticNoStatusFieldError($tracker);
            return false;
        }

        $trackers_in_error = $this->getTrackersWithoutStatusDefined(
            $source_tracker_collection
        );

        if (count($trackers_in_error) > 0) {
            $configuration_errors->addMissingSemanticInTeamErrors($trackers_in_error);
            return false;
        }

        $program_open_values_labels = $program_tracker_status_semantic->getOpenLabels();

        foreach ($source_tracker_collection->getSourceTrackers() as $source_tracker) {
            $source_full_tracker = $this->tracker_factory->getTrackerById($source_tracker->getId());
            if (! $source_full_tracker) {
                throw new \RuntimeException('Tracker with id #' . $source_tracker->getId() . ' is not found.');
            }
            $status_semantic = $this->semantic_status_factory->getByTracker($source_full_tracker);
            $array_diff      = array_diff($program_open_values_labels, $status_semantic->getOpenLabels());
            if (count($array_diff) > 0) {
                $configuration_errors->addMissingValueInSemantic($array_diff, $source_tracker_collection->getSourceTrackers());
                return false;
            }
        }

        return true;
    }

    /**
     * @return TrackerReference[]
     */
    private function getTrackersWithoutStatusDefined(SourceTrackerCollection $source_tracker_collection): array
    {
        $trackers_ids_without_status = $this->semantic_status_dao->getTrackerIdsWithoutSemanticStatusDefined(
            $source_tracker_collection->getSourceTrackerIds()
        );

        if (count($trackers_ids_without_status) === 0) {
            return [];
        }

        $mapping = [];
        foreach ($source_tracker_collection->getSourceTrackers() as $program_tracker) {
            $mapping[$program_tracker->getId()] = $program_tracker;
        }

        $trackers_in_error = [];
        foreach ($trackers_ids_without_status as $tracker_id) {
            if (array_key_exists($tracker_id, $mapping)) {
                $trackers_in_error[] = $mapping[$tracker_id];
            }
        }

        return $trackers_in_error;
    }
}
