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

use AgileDashboard_Semantic_InitialEffortFactory;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetFactory;
use Tracker_ArtifactFactory;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDone;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneFactory;

class BurnupCalculator
{
    public function __construct(
        private Tracker_Artifact_ChangesetFactory $changeset_factory,
        private Tracker_ArtifactFactory $artifact_factory,
        private BurnupDataDAO $burnup_dao,
        private AgileDashboard_Semantic_InitialEffortFactory $initial_effort_factory,
        private SemanticDoneFactory $semantic_done_factory,
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

    private function getPlanningLinkedArtifactAtTimestamp(
        int $artifact_id,
        int $timestamp,
        array $backlog_trackers_ids,
    ) {
        return $this->burnup_dao->searchLinkedArtifactsAtGivenTimestamp($artifact_id, $timestamp, $backlog_trackers_ids);
    }

    /**
     * @return BurnupEffort
     */
    private function getEffort(
        Artifact $artifact,
        $timestamp,
    ) {
        $semantic_initial_effort = $this->initial_effort_factory->getByTracker($artifact->getTracker());
        $semantic_done           = $this->semantic_done_factory->getInstanceByTracker($artifact->getTracker());

        $initial_effort_field = $semantic_initial_effort->getField();
        \assert($initial_effort_field instanceof \Tracker_FormElement_Field);
        $changeset = $this->changeset_factory->getChangesetAtTimestamp($artifact, $timestamp);

        $total_effort = 0;
        $team_effort  = 0;
        if ($changeset !== null && $initial_effort_field && $this->artifactMustBeAddedInBurnupCalculation($changeset, $semantic_done)) {
            if ($artifact->getValue($initial_effort_field, $changeset)) {
                $total_effort = $artifact->getValue($initial_effort_field, $changeset)->getValue();
            }
            if ($semantic_done !== null && $semantic_done->isDone($changeset)) {
                $team_effort = $total_effort;
            }
        }

        return new BurnupEffort((float) $team_effort, (float) $total_effort);
    }

    /**
     * @return bool
     */
    private function artifactMustBeAddedInBurnupCalculation(
        Tracker_Artifact_Changeset $changeset,
        SemanticDone $semantic_done,
    ) {
        return $changeset->getArtifact()->isOpenAtGivenChangeset($changeset) || $semantic_done->isDone($changeset);
    }
}
