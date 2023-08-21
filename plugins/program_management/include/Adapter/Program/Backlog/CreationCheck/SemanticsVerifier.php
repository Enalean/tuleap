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
use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\VerifySemanticsAreConfigured;
use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\VerifyStatusIsAligned;
use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\VerifyTimeframeIsAligned;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Source\SourceTrackerCollection;
use Tuleap\ProgramManagement\Domain\TrackerReference;

final class SemanticsVerifier implements VerifySemanticsAreConfigured
{
    public function __construct(
        private \Tracker_Semantic_TitleDao $semantic_title_dao,
        private \Tracker_Semantic_DescriptionDao $semantic_description_dao,
        private VerifyStatusIsAligned $status_verifier,
        private VerifyTimeframeIsAligned $timeframe_verifier,
    ) {
    }

    public function areTrackerSemanticsWellConfigured(
        TrackerReference $tracker,
        SourceTrackerCollection $source_tracker_collection,
        ConfigurationErrorsCollector $configuration_errors,
    ): bool {
        $tracker_ids = $source_tracker_collection->getSourceTrackerIds();

        $has_error = false;

        if ($this->semantic_title_dao->getNbOfTrackerWithoutSemanticTitleDefined($tracker_ids) > 0) {
            $this->buildSemanticError(
                $configuration_errors,
                $this->getProgramTrackersWithoutTitleDefined($source_tracker_collection),
                dgettext('tuleap-program_management', 'Title'),
                \Tracker_Semantic_Title::NAME
            );
            $has_error = true;
            if (! $configuration_errors->shouldCollectAllIssues()) {
                return false;
            }
        }
        if ($this->semantic_description_dao->getNbOfTrackerWithoutSemanticDescriptionDefined($tracker_ids) > 0) {
            $this->buildSemanticError(
                $configuration_errors,
                $this->getProgramTrackersWithoutDescriptionDefined($source_tracker_collection),
                dgettext('tuleap-program_management', 'Description'),
                \Tracker_Semantic_Description::NAME
            );
            $has_error = true;
            if (! $configuration_errors->shouldCollectAllIssues()) {
                return false;
            }
        }
        if (
            $this->timeframe_verifier->isTimeframeWellConfigured(
                $tracker,
                $source_tracker_collection,
                $configuration_errors
            ) === false
        ) {
            $has_error = true;
            if (! $configuration_errors->shouldCollectAllIssues()) {
                return false;
            }
        }
        if (
            $this->status_verifier->isStatusWellConfigured(
                $tracker,
                $source_tracker_collection,
                $configuration_errors
            ) === false
        ) {
            $has_error = true;
            if (! $configuration_errors->shouldCollectAllIssues()) {
                return false;
            }
        }

        return ! $has_error;
    }

    /**
     * @param TrackerReference[] $trackers
     */
    private function buildSemanticError(
        ConfigurationErrorsCollector $configuration_errors,
        array $trackers,
        string $semantic_name,
        string $semantic_shortname,
    ): void {
        $configuration_errors->addSemanticError($semantic_name, $semantic_shortname, $trackers);
    }

    private function getProgramTrackersWithoutTitleDefined(SourceTrackerCollection $source_tracker_collection): array
    {
        $trackers_ids_without_title = $this->semantic_title_dao->getTrackerIdsWithoutSemanticTitleDefined(
            $source_tracker_collection->getSourceTrackerIds()
        );

        if (count($trackers_ids_without_title) === 0) {
            return [];
        }

        return $this->getTrackersInError(
            $source_tracker_collection,
            $trackers_ids_without_title
        );
    }

    private function getProgramTrackersWithoutDescriptionDefined(SourceTrackerCollection $source_tracker_collection): array
    {
        $trackers_ids_without_title = $this->semantic_description_dao->getTrackerIdsWithoutSemanticDescriptionDefined(
            $source_tracker_collection->getSourceTrackerIds()
        );

        if (count($trackers_ids_without_title) === 0) {
            return [];
        }

        return $this->getTrackersInError(
            $source_tracker_collection,
            $trackers_ids_without_title
        );
    }

    private function getTrackersInError(SourceTrackerCollection $source_tracker_collection, array $tracker_ids): array
    {
        $mapping = [];
        foreach ($source_tracker_collection->getSourceTrackers() as $program_tracker) {
            $mapping[$program_tracker->getId()] = $program_tracker;
        }

        $trackers_in_error = [];
        foreach ($tracker_ids as $tracker_id) {
            if (array_key_exists($tracker_id, $mapping)) {
                $trackers_in_error[] = $mapping[$tracker_id];
            }
        }

        return $trackers_in_error;
    }
}
