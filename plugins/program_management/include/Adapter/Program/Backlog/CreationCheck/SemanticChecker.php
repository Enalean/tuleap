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

use Psr\Log\LoggerInterface;
use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\CheckSemantic;
use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\CheckStatus;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Source\SourceTrackerCollection;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao;

final class SemanticChecker implements CheckSemantic
{
    private \Tracker_Semantic_TitleDao $semantic_title_dao;
    private \Tracker_Semantic_DescriptionDao $semantic_description_dao;
    private SemanticTimeframeDao $semantic_timeframe_dao;
    private CheckStatus $semantic_status_checker;
    private LoggerInterface $logger;

    public function __construct(
        \Tracker_Semantic_TitleDao $semantic_title_dao,
        \Tracker_Semantic_DescriptionDao $semantic_description_dao,
        SemanticTimeframeDao $semantic_timeframe_dao,
        CheckStatus $semantic_status_checker,
        LoggerInterface $logger
    ) {
        $this->semantic_title_dao       = $semantic_title_dao;
        $this->semantic_description_dao = $semantic_description_dao;
        $this->semantic_timeframe_dao   = $semantic_timeframe_dao;
        $this->semantic_status_checker  = $semantic_status_checker;
        $this->logger                   = $logger;
    }

    public function areTrackerSemanticsWellConfigured(
        ProgramTracker $tracker,
        SourceTrackerCollection $source_tracker_collection
    ): bool {
        $tracker_ids = $source_tracker_collection->getSourceTrackerIds();
        if ($this->semantic_title_dao->getNbOfTrackerWithoutSemanticTitleDefined($tracker_ids) > 0) {
            $this->logger->error("Semantic 'Title' is not well configured. Please check semantic of timebox tracker and mirrored timebox trackers.");
            return false;
        }
        if ($this->semantic_description_dao->getNbOfTrackerWithoutSemanticDescriptionDefined($tracker_ids) > 0) {
            $this->logger->error("Semantic 'Description' is not well configured. Please check semantic of timebox tracker and mirrored timebox trackers.");
            return false;
        }
        if (! $this->areTimeFrameSemanticsAligned($tracker_ids)) {
            $this->logger->error("Semantic 'Timeframe' is not well configured. Please check semantic of timebox tracker and mirrored timebox trackers.");
            return false;
        }
        if (
            $this->semantic_status_checker->isStatusWellConfigured(
                $tracker,
                $source_tracker_collection
            ) === false
        ) {
            $this->logger->error("Semantic 'Status' is not well configured. Please check semantic of timebox tracker and mirrored timebox trackers.");
            return false;
        }

        return true;
    }

    /**
     * @param int[] $tracker_ids
     */
    private function areTimeFrameSemanticsAligned(array $tracker_ids): bool
    {
        if ($this->semantic_timeframe_dao->getNbOfTrackersWithoutTimeFrameSemanticDefined($tracker_ids) > 0) {
            return false;
        }
        if (! $this->semantic_timeframe_dao->areTimeFrameSemanticsUsingSameTypeOfField($tracker_ids)) {
            return false;
        }
        return true;
    }
}
