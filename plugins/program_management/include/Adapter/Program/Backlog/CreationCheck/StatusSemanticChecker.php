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

use Tracker_Semantic_StatusDao;
use Tracker_Semantic_StatusFactory;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\CheckStatus;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Source\SourceTrackerCollection;
use Tuleap\ProgramManagement\Domain\ProgramTracker;

final class StatusSemanticChecker implements CheckStatus
{
    private Tracker_Semantic_StatusDao $semantic_status_dao;
    private Tracker_Semantic_StatusFactory $semantic_status_factory;

    public function __construct(
        Tracker_Semantic_StatusDao $semantic_status_dao,
        Tracker_Semantic_StatusFactory $semantic_status_factory
    ) {
        $this->semantic_status_dao     = $semantic_status_dao;
        $this->semantic_status_factory = $semantic_status_factory;
    }

    public function isStatusWellConfigured(
        ProgramTracker $tracker,
        SourceTrackerCollection $source_tracker_collection,
        ConfigurationErrorsCollector $configuration_errors
    ): bool {
        $program_tracker_status_semantic = $this->semantic_status_factory->getByTracker(
            $tracker->getFullTracker()
        );

        if ($program_tracker_status_semantic->getField() === null) {
            $url   = '/plugins/tracker/?' . http_build_query(
                ['tracker' => $tracker->getTrackerId(), 'func' => 'admin-semantic', 'semantic' => "status"]
            );
            $error = sprintf(
                dgettext(
                    'tuleap-program_management',
                    "Semantic 'status' is not linked to a field in program tracker <a href='%s'>#%d</a>"
                ),
                $url,
                $tracker->getTrackerId()
            );

            $configuration_errors->addError($error);
            return false;
        }

        $nb_of_trackers_without_status = $this->semantic_status_dao->getNbOfTrackerWithoutSemanticStatusDefined(
            $source_tracker_collection->getSourceTrackerIds()
        );
        if ($nb_of_trackers_without_status > 0) {
            $error = sprintf(
                dgettext(
                    'tuleap-program_management',
                    "Some tracker does not have status semantic defined, please check trackers %s"
                ),
                implode(', ', $this->getTrackersLinks($source_tracker_collection))
            );
            $configuration_errors->addError($error);
            return false;
        }

        $program_open_values_labels = $program_tracker_status_semantic->getOpenLabels();

        foreach ($source_tracker_collection->getSourceTrackers() as $source_tracker) {
            $status_semantic = $this->semantic_status_factory->getByTracker($source_tracker->getFullTracker());
            $array_diff      = array_diff($program_open_values_labels, $status_semantic->getOpenLabels());
            if (count($array_diff) > 0) {
                $error = sprintf(
                    dgettext(
                        'tuleap-program_management',
                        'Values "%s" are not found in every tracker, please check tracker %s'
                    ),
                    implode(', ', $array_diff),
                    implode(', ', $this->getTrackersLinks($source_tracker_collection))
                );
                $configuration_errors->addError($error);
                return false;
            }
        }

        return true;
    }

    private function getTrackersLinks(SourceTrackerCollection $source_tracker_collection): array
    {
        $tracker_urls = [];
        foreach ($source_tracker_collection->getSourceTrackerIds() as $id) {
            $url            = '/plugins/tracker/?' . http_build_query(
                ['tracker' => $id, 'func' => 'admin-semantic', 'semantic' => "status"]
            );
            $tracker_urls[] = sprintf("<a href='%s'>#%d</a>", $url, $id);
        }

        return $tracker_urls;
    }
}
