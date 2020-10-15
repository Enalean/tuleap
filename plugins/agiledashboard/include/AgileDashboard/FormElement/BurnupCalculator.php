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

namespace Tuleap\AgileDashboard\FormElement;

use AgileDashboard_Semantic_InitialEffortFactory;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetFactory;
use Tracker_ArtifactFactory;
use Tuleap\AgileDashboard\Semantic\SemanticDone;
use Tuleap\AgileDashboard\Semantic\SemanticDoneFactory;
use Tuleap\Tracker\Artifact\Artifact;

class BurnupCalculator
{
    /**
     * @var Tracker_Artifact_ChangesetFactory
     */
    private $changeset_factory;
    /**
     * @var BurnupDao
     */
    private $burnup_dao;
    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var AgileDashboard_Semantic_InitialEffortFactory
     */
    private $initial_effort_factory;
    /**
     * @var SemanticDoneFactory
     */
    private $semantic_done_factory;


    public function __construct(
        Tracker_Artifact_ChangesetFactory $changeset_factory,
        Tracker_ArtifactFactory $artifact_factory,
        BurnupDao $burnup_dao,
        AgileDashboard_Semantic_InitialEffortFactory $initial_effort_factory,
        SemanticDoneFactory $semantic_done_factory
    ) {
        $this->changeset_factory      = $changeset_factory;
        $this->burnup_dao             = $burnup_dao;
        $this->artifact_factory       = $artifact_factory;
        $this->initial_effort_factory = $initial_effort_factory;
        $this->semantic_done_factory  = $semantic_done_factory;
    }

    /**
     * @return BurnupEffort
     */
    public function getValue($artifact_id, $timestamp)
    {
        $backlog_items = $this->getPlanningLinkedArtifactAtTimestamp(
            $artifact_id,
            $timestamp
        );

        $total_effort = 0;
        $team_effort  = 0;
        foreach ($backlog_items as $item) {
            $artifact     = $this->artifact_factory->getArtifactById($item['id']);

            $effort        = $this->getEffort($artifact, $timestamp);
            $total_effort += $effort->getTotalEffort();
            $team_effort  += $effort->getTeamEffort();
        }

        return new BurnupEffort($team_effort, $total_effort);
    }

    private function getPlanningLinkedArtifactAtTimestamp(
        $artifact_id,
        $timestamp
    ) {
        return $this->burnup_dao->searchLinkedArtifactsAtGivenTimestamp($artifact_id, $timestamp);
    }

    /**
     * @return BurnupEffort
     */
    private function getEffort(
        Artifact $artifact,
        $timestamp
    ) {
        $semantic_initial_effort = $this->initial_effort_factory->getByTracker($artifact->getTracker());
        $semantic_done           = $this->semantic_done_factory->getInstanceByTracker($artifact->getTracker());

        /**
         * @var $initial_effort_field \Tracker_FormElement_Field
         */
        $initial_effort_field = $semantic_initial_effort->getField();
        $changeset            = $this->changeset_factory->getChangesetAtTimestamp($artifact, $timestamp);

        $total_effort = 0;
        $team_effort  = 0;
        if ($initial_effort_field && $this->artifactMustBeAddedInBurnupCalculation($changeset, $semantic_done)) {
            if ($artifact->getValue($initial_effort_field, $changeset)) {
                $total_effort = $artifact->getValue($initial_effort_field, $changeset)->getValue();
            }
            if ($changeset !== null && $semantic_done !== null && $semantic_done->isDone($changeset)) {
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
        SemanticDone $semantic_done
    ) {
        return $changeset->getArtifact()->isOpenAtGivenChangeset($changeset) || $semantic_done->isDone($changeset);
    }
}
