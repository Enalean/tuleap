<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class AgileDashboard_SequenceIdManager {

    /** @var AgileDashboard_Milestone_Backlog_BacklogStrategyFactory */
    private $strategy_factory;

    /** @var Array */
    private $backlog_item_ids;

    public function __construct(AgileDashboard_Milestone_Backlog_BacklogStrategyFactory $strategy_factory) {
        $this->strategy_factory = $strategy_factory;
        $this->backlog_item_ids = array();
    }

    public function getSequenceId(PFUser $user, Planning_Milestone $milestone, $artifact_id) {
        $this->loadBacklogForMilestoneIfNeeded($user, $milestone);

        $position = $this->getArtifactPositionInMilestone($milestone, $artifact_id);

        if (! $position) {
            return;
        }

        return $position;
    }

    private function getArtifactPositionInMilestone(Planning_Milestone $milestone, $artifact_id) {
        if (! isset($this->backlog_item_ids[$milestone->getArtifactId()][$artifact_id])) {
            return;
        }

        return $this->backlog_item_ids[$milestone->getArtifactId()][$artifact_id];
    }

    private function loadBacklogForMilestoneIfNeeded(PFUser $user, Planning_Milestone $milestone) {
        if (! isset($this->backlog_item_ids[$milestone->getArtifactId()])) {
            $this->backlog_item_ids[$milestone->getArtifactId()] = array();
            $strategy          = $this->strategy_factory->getBacklogStrategy($milestone);
            $backlog_artifacts = $strategy->getArtifacts($user);

            $this->storeBacklogArtifacts($milestone, $backlog_artifacts);
        }
    }

    private function storeBacklogArtifacts(Planning_Milestone $milestone, array $backlog_artifacts) {
        $artifact_position = 1;
        foreach ($backlog_artifacts as $backlog_artifact) {
            $this->backlog_item_ids[$milestone->getArtifactId()][$backlog_artifact->getId()] = $artifact_position;
            $artifact_position = $artifact_position + 1;
        }
    }
}
