<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\FormElement;

use Tracker_ArtifactFactory;
use Tuleap\AgileDashboard\FormElement\Burnup\Calculator\RetrieveBurnupEffortForArtifact;
use Tuleap\Tracker\Artifact\Artifact;

class BurnupCalculator
{
    public function __construct(
        private Tracker_ArtifactFactory $artifact_factory,
        private BurnupDataDAO $burnup_dao,
        private RetrieveBurnupEffortForArtifact $calculator,
    ) {
    }

    public function getValue(int $artifact_id, int $timestamp, array $backlog_trackers_ids): BurnupEffort
    {
        $backlog_items = $this->getPlanningLinkedArtifactAtTimestamp(
            $artifact_id,
            $timestamp,
            $backlog_trackers_ids
        );

        $total_effort = 0;
        $team_effort  = 0;
        foreach ($backlog_items as $item) {
            $artifact = $this->artifact_factory->getArtifactById($item['id']);
            if (! $artifact) {
                continue;
            }

            $effort        = $this->getEffort($artifact, $timestamp);
            $total_effort += $effort->getTotalEffort();
            $team_effort  += $effort->getTeamEffort();
        }

        return new BurnupEffort($team_effort, $total_effort);
    }

    /**
     * @return list<array{id: int}>
     */
    private function getPlanningLinkedArtifactAtTimestamp(
        int $artifact_id,
        int $timestamp,
        array $backlog_trackers_ids,
    ): array {
        return $this->burnup_dao->searchLinkedArtifactsAtGivenTimestamp($artifact_id, $timestamp, $backlog_trackers_ids);
    }

    private function getEffort(
        Artifact $artifact,
        int $timestamp,
    ): BurnupEffort {
        return $this->calculator->getEffort($artifact, $timestamp);
    }
}
