<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Velocity\Semantic;

use AgileDashBoard_Semantic_InitialEffort;
use Tracker;
use Tuleap\AgileDashboard\Semantic\SemanticDone;

class SemanticFormatter
{
    /**
     * @return array
     */
    public function formatBacklogTrackers(
        array $backlog_without_done_semantic,
        array $backlog_without_initial_effort_semantic,
        array $backlog_trackers
    ) {
        $formatted_tracker = [];
        foreach ($backlog_trackers as $tracker) {
            $misconfigured = [];
            if (isset($backlog_without_done_semantic[$tracker->getId()])) {
                $misconfigured[] = $this->formatDoneMisconfiguredTracker($tracker);
            }

            if (isset($backlog_without_initial_effort_semantic[$tracker->getId()])) {
                $misconfigured[] = $this->formatInitialEffortMisconfigredTracker($tracker);
            }

            $formatted_tracker[] = [
                "name"                        => $tracker->getName(),
                "misconfigured_semantics"     => $misconfigured,
                "nb_semantic_misconfigured"   => count($misconfigured),
                "has_misconfigured_semantics" => count($misconfigured) > 0,
                "tracker_url"                 => TRACKER_BASE_URL . "?" . http_build_query(
                    [
                        "tracker" => $tracker->getId(),
                        "func"    => "admin"
                    ]
                )
            ];
        }

        return $formatted_tracker;
    }

    public function getSemanticMisconfiguredForAllTrackers(
        array $backlog_trackers,
        array $backlog_trackers_without_done_semantic,
        array $backlog_trackers_without_initial_effort_semantic
    ) {
        $misconfigured_semantic = [];
        if (count($backlog_trackers) === count($backlog_trackers_without_done_semantic)) {
            $misconfigured_semantic[] = SemanticDone::getLabel();
        }

        if (count($backlog_trackers) === count($backlog_trackers_without_initial_effort_semantic)) {
            $misconfigured_semantic[] = AgileDashBoard_Semantic_InitialEffort::getLabel();
        }

        return $misconfigured_semantic;
    }

    private function formatDoneMisconfiguredTracker(Tracker $tracker)
    {
        return [
            "semantic_url"  => TRACKER_BASE_URL . "?" . http_build_query(
                [
                    "tracker"  => $tracker->getId(),
                    "func"     => "admin-semantic",
                    "semantic" => "done"
                ]
            ),
            "semantic_name" => SemanticDone::getLabel(),
            "name"          => $tracker->getName()
        ];
    }

    private function formatInitialEffortMisconfigredTracker(Tracker $tracker)
    {
        return [
            "semantic_url"  => TRACKER_BASE_URL . "?" . http_build_query(
                [
                    "tracker"  => $tracker->getId(),
                    "func"     => "admin-semantic",
                    "semantic" => "initial_effort"
                ]
            ),
            "semantic_name" => AgileDashBoard_Semantic_InitialEffort::getLabel(),
            "name"          => $tracker->getName()
        ];
    }
}
