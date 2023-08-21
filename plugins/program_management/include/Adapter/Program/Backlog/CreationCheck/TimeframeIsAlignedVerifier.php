<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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
use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\VerifyTimeframeIsAligned;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Source\SourceTrackerCollection;
use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao;

final class TimeframeIsAlignedVerifier implements VerifyTimeframeIsAligned
{
    public function __construct(
        private readonly SemanticTimeframeDao $semantic_timeframe_dao,
    ) {
    }

    public function isTimeframeWellConfigured(
        TrackerReference $tracker,
        SourceTrackerCollection $source_tracker_collection,
        ConfigurationErrorsCollector $configuration_errors,
    ): bool {
        $tracker_ids = $source_tracker_collection->getSourceTrackerIds();

        if ($this->semantic_timeframe_dao->getNbOfTrackersWithoutTimeFrameSemanticDefined($tracker_ids) > 0) {
            $this->buildSemanticError(
                $configuration_errors,
                $source_tracker_collection->getSourceTrackers(),
            );
            return false;
        }
        if (! $this->semantic_timeframe_dao->areTimeFrameSemanticsUsingSameTypeOfField($tracker_ids)) {
            $this->buildSemanticError(
                $configuration_errors,
                $source_tracker_collection->getSourceTrackers(),
            );
            return false;
        }

        return true;
    }

    /**
     * @param TrackerReference[] $trackers
     */
    private function buildSemanticError(
        ConfigurationErrorsCollector $configuration_errors,
        array $trackers,
    ): void {
        $configuration_errors->addSemanticError(
            dgettext('tuleap-program_management', 'Timeframe'),
            SemanticTimeframe::NAME,
            $trackers
        );
    }
}
