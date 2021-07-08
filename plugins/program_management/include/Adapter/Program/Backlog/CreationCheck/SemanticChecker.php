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
use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\CheckSemantic;
use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\CheckStatus;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Source\SourceTrackerCollection;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao;

final class SemanticChecker implements CheckSemantic
{
    private \Tracker_Semantic_TitleDao $semantic_title_dao;
    private \Tracker_Semantic_DescriptionDao $semantic_description_dao;
    private SemanticTimeframeDao $semantic_timeframe_dao;
    private CheckStatus $semantic_status_checker;

    public function __construct(
        \Tracker_Semantic_TitleDao $semantic_title_dao,
        \Tracker_Semantic_DescriptionDao $semantic_description_dao,
        SemanticTimeframeDao $semantic_timeframe_dao,
        CheckStatus $semantic_status_checker
    ) {
        $this->semantic_title_dao       = $semantic_title_dao;
        $this->semantic_description_dao = $semantic_description_dao;
        $this->semantic_timeframe_dao   = $semantic_timeframe_dao;
        $this->semantic_status_checker  = $semantic_status_checker;
    }

    public function areTrackerSemanticsWellConfigured(
        ProgramTracker $tracker,
        SourceTrackerCollection $source_tracker_collection,
        ConfigurationErrorsCollector $configuration_errors
    ): bool {
        $tracker_ids = $source_tracker_collection->getSourceTrackerIds();

        $has_error = false;

        if ($this->semantic_title_dao->getNbOfTrackerWithoutSemanticTitleDefined($tracker_ids) > 0) {
            $this->buildSemanticError(
                $configuration_errors,
                $tracker_ids,
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
                $tracker_ids,
                dgettext('tuleap-program_management', 'Description'),
                \Tracker_Semantic_Description::NAME
            );
            $has_error = true;
            if (! $configuration_errors->shouldCollectAllIssues()) {
                return false;
            }
        }
        if (! $this->areTimeFrameSemanticsAligned($tracker_ids)) {
            $this->buildSemanticError(
                $configuration_errors,
                $tracker_ids,
                dgettext('tuleap-program_management', 'Timeframe'),
                SemanticTimeframe::NAME
            );
            $has_error = true;
            if (! $configuration_errors->shouldCollectAllIssues()) {
                return false;
            }
        }
        if (
            $this->semantic_status_checker->isStatusWellConfigured(
                $tracker,
                $source_tracker_collection
            ) === false
        ) {
            $this->buildSemanticError(
                $configuration_errors,
                $tracker_ids,
                dgettext('tuleap-program_management', 'Status'),
                \Tracker_Semantic_Status::NAME
            );
            $has_error = true;
            if (! $configuration_errors->shouldCollectAllIssues()) {
                return false;
            }
        }

        return ! $has_error;
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

    private function buildSemanticError(
        ConfigurationErrorsCollector $configuration_errors,
        array $tracker_ids,
        string $semantic_name,
        string $semantic_shortname
    ): void {
        $tracker_urls = [];
        foreach ($tracker_ids as $id) {
            $url            = '/plugins/tracker/?' . http_build_query(
                ['tracker' => $id, 'func' => 'admin-semantic', 'semantic' => $semantic_shortname]
            );
            $tracker_urls[] = sprintf("<a href='%s'>#%d</a>", $url, $id);
        }
        $error = sprintf(
            dgettext(
                'tuleap-program_management',
                "Semantic '%s' is not well configured. Please check semantic of trackers %s."
            ),
            $semantic_name,
            implode(", ", $tracker_urls)
        );

        $configuration_errors->addError($error);
    }
}
