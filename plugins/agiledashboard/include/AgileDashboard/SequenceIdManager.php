<?php
/**
 * Copyright (c) Enalean, 2014 - present. All Rights Reserved.
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

class AgileDashboard_SequenceIdManager
{
    /**
     * @var AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory
     */
    private $backlog_item_collection_factory;

    /** @var AgileDashboard_Milestone_Backlog_BacklogFactory */
    private $backlog_factory;

    /** @var array */
    private $backlog_item_ids;

    public function __construct(
        AgileDashboard_Milestone_Backlog_BacklogFactory $backlog_factory,
        AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory $backlog_item_collection_factory
    ) {
        $this->backlog_item_collection_factory = $backlog_item_collection_factory;
        $this->backlog_factory                 = $backlog_factory;
        $this->backlog_item_ids                = array();
    }

    public function getSequenceId(PFUser $user, Planning_Milestone $milestone, $artifact_id)
    {
        $this->loadBacklogForMilestoneIfNeeded($user, $milestone);

        return $this->getArtifactPosition((int) $milestone->getArtifactId(), $artifact_id);
    }

    private function getArtifactPosition($milestone_id, $artifact_id)
    {
        if (! isset($this->backlog_item_ids[$milestone_id][$artifact_id])) {
            return;
        }

        return $this->backlog_item_ids[$milestone_id][$artifact_id];
    }

    private function loadBacklogForMilestoneIfNeeded(PFUser $user, Planning_Milestone $milestone)
    {
        if (! $milestone->getArtifactId()) {
                $this->loadTopBacklog($user, $milestone);
                return;
        }

        if (! isset($this->backlog_item_ids[$milestone->getArtifactId()])) {
            $this->backlog_item_ids[$milestone->getArtifactId() ?? 0] = array();
            $backlog           = $this->backlog_factory->getBacklog($milestone);
            $backlog_artifacts = $backlog->getArtifacts($user);

            $this->storeBacklogArtifacts($milestone->getArtifactId(), $backlog_artifacts);
        }
    }

    private function loadTopBacklog(PFUser $user, Planning_Milestone $milestone)
    {
        if (! isset($this->backlog_item_ids[(int) $milestone->getArtifactId()])) {
            $this->backlog_item_ids[(int) $milestone->getArtifactId()] = array();

            $backlog_unassigned = $this->backlog_factory->getSelfBacklog($milestone);
            $backlog_artifacts  = $this->backlog_item_collection_factory->getUnassignedOpenCollection($user, $milestone, $backlog_unassigned, false);

            $this->storeTopBacklogArtifacts((int) $milestone->getArtifactId(), $backlog_artifacts);
        }
    }

    private function storeTopBacklogArtifacts($milestone_id, AgileDashboard_Milestone_Backlog_BacklogItemCollection $backlog_items)
    {
        $artifact_position = 1;
        foreach ($backlog_items as $backlog_item) {
            $this->backlog_item_ids[$milestone_id][$backlog_item->getArtifact()->getId()] = $artifact_position;
            $artifact_position = $artifact_position + 1;
        }
    }

    private function storeBacklogArtifacts($milestone_id, AgileDashboard_Milestone_Backlog_DescendantItemsCollection $backlog_artifacts)
    {
        $artifact_position = 1;
        foreach ($backlog_artifacts as $backlog_artifact) {
            $this->backlog_item_ids[$milestone_id][$backlog_artifact->getId()] = $artifact_position;
            $artifact_position = $artifact_position + 1;
        }
    }
}
