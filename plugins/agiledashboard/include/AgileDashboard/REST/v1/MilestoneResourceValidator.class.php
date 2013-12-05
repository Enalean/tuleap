<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * This class has to validate data from the PUT requests concerning
 * milestone routes
 */
namespace Tuleap\AgileDashboard\REST\v1;

use \PlanningFactory;
use \Tracker_ArtifactFactory;
use \Tracker_Artifact;
use \Tracker_FormElementFactory;
use \AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory;
use \AgileDashboard_Milestone_Backlog_BacklogStrategyFactory;
use \AgileDashboard_Milestone_Backlog_BacklogStrategy;
use \AgileDashboard_Milestone_Backlog_IBacklogItemCollection;
use \Planning_MilestoneFactory;
use \Planning_Milestone;
use \PFUser;
use \Project;

class MilestoneResourceValidator {

    /** @var \Planning_MilestoneFactory */
    private $milestone_factory;

    /** @var AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory */
    private $backlog_item_collection_factory;

    /** @var AgileDashboard_Milestone_Backlog_BacklogStrategyFactory */
    private $backlog_strategy_factory;

    /** @var PlanningFactory */
    private $planning_factory;

    /** @var Tracker_ArtifactFactory */
    private $tracker_artifact_factory;

    /** @var Tracker_FormElementFactory */
    private $tracker_form_element_factory;

    public function __construct(
        PlanningFactory $planning_factory,
        Tracker_ArtifactFactory $tracker_artifact_factory,
        Tracker_FormElementFactory $tracker_form_element_factory,
        AgileDashboard_Milestone_Backlog_BacklogStrategyFactory $backlog_strategy_factory,
        Planning_MilestoneFactory $milestone_factory,
        AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory $backlog_row_collection_factory
    ) {
        $this->planning_factory                = $planning_factory;
        $this->tracker_artifact_factory        = $tracker_artifact_factory;
        $this->tracker_form_element_factory    = $tracker_form_element_factory;
        $this->backlog_strategy_factory        = $backlog_strategy_factory;
        $this->milestone_factory               = $milestone_factory;
        $this->backlog_item_collection_factory = $backlog_row_collection_factory;
    }

    public function validateArtifactsFromBodyContent(array $ids, Planning_Milestone $milestone, PFUser $user) {
        $strategy       = null;
        $open_unplanned = null;

        if (! $this->idsAreUnique($ids)) {
            throw new IdsFromBodyAreNotUniqueException();
        }

        if ($milestone->getParent()) {
            $strategy = $this->backlog_strategy_factory->getBacklogStrategy($milestone);

            $open_unplanned = $this->backlog_item_collection_factory->getUnplannedOpenCollection($user, $milestone->getParent(), $this->backlog_strategy_factory->getBacklogStrategy($milestone->getParent()), false);

        } else {
            $top_milestone       = $this->milestone_factory->getVirtualTopMilestone($user, $milestone->getProject());
            $strategy            = $this->backlog_strategy_factory->getSelfBacklogStrategy($milestone);
            $strategy_unassigned = $this->backlog_strategy_factory->getSelfBacklogStrategy($top_milestone);

            $open_unplanned = $this->backlog_item_collection_factory->getUnassignedOpenCollection($user, $top_milestone, $strategy_unassigned, false);
        }

        $done = $this->getMilestoneDoneBacklogItems($user, $milestone, $strategy);
        $todo = $this->getMilestoneTodoBacklogItems($user, $milestone, $strategy);

        $backlog_tracker_ids = $this->planning_factory->getBacklogTrackersIds($milestone->getPlanning()->getId());

        $artifacts = $this->getArtifactsFromBodyContent($ids, $backlog_tracker_ids, $todo, $done, $open_unplanned);

        return count($artifacts) === count($ids);
    }

    private function idsAreUnique(array $ids) {
        $ids_unique = array_unique($ids);
        return count($ids) == count($ids_unique);
    }

    private function getArtifactsFromBodyContent(
        array $ids,
        array $backlog_tracker_ids,
        AgileDashboard_Milestone_Backlog_IBacklogItemCollection $todo,
        AgileDashboard_Milestone_Backlog_IBacklogItemCollection $done,
        AgileDashboard_Milestone_Backlog_IBacklogItemCollection $open_unplanned
    ) {
        $artifacts = array();

        foreach ($ids as $potential_backlog_item_id) {

            $artifact = $this->tracker_artifact_factory->getArtifactById($potential_backlog_item_id);
            if (! $artifact) {
                throw new ArtifactDoesNotExistException($potential_backlog_item_id);
            }

            if (! in_array($artifact->getTrackerId(), $backlog_tracker_ids)) {
                throw new ArtifactIsNotInBacklogTrackerException($potential_backlog_item_id);
            }

            if (! $this->isArtifactInUnplannedParentMilestoneBacklogItems($artifact, $open_unplanned)
                && ! $this->isArtifactInPlannedMilestoneBacklogItems($artifact, $done, $todo)
            ) {
                throw new ArtifactIsClosedOrAlreadyPlannedInAnotherMilestone($potential_backlog_item_id);
            }

            $artifacts[$artifact->getId()] = $artifact;
        }

        return $artifacts;
    }

    private function getMilestoneDoneBacklogItems(PFUser $user, Planning_Milestone $milestone, AgileDashboard_Milestone_Backlog_BacklogStrategy $strategy) {
        return $this->backlog_item_collection_factory->getDoneCollection($user, $milestone, $strategy, false);
    }

    private function getMilestoneTodoBacklogItems(PFUser $user, Planning_Milestone $milestone, AgileDashboard_Milestone_Backlog_BacklogStrategy $strategy) {
        return $this->backlog_item_collection_factory->getTodoCollection($user, $milestone, $strategy, false);
    }

    private function isArtifactInUnplannedParentMilestoneBacklogItems(Tracker_Artifact $artifact, AgileDashboard_Milestone_Backlog_IBacklogItemCollection $unplanned_backlog_items) {
        return $unplanned_backlog_items->containsId($artifact->getId());
    }

    private function isArtifactInPlannedMilestoneBacklogItems(Tracker_Artifact $artifact, AgileDashboard_Milestone_Backlog_IBacklogItemCollection $done, AgileDashboard_Milestone_Backlog_IBacklogItemCollection $todo) {
        return ($done->containsId($artifact->getId()) || $todo->containsId($artifact->getId()));
    }

    public function validateArtifactIdsAreInOpenAndUnplannedMilestone(array $ids, Planning_Milestone $milestone, PFUser $user) {
        if (! $this->idsAreUnique($ids)) {
            throw new IdsFromBodyAreNotUniqueException();
        }

        $open_unplanned = $this->backlog_item_collection_factory->getUnplannedOpenCollection($user, $milestone, $this->backlog_strategy_factory->getBacklogStrategy($milestone), false);

        foreach($ids as $id) {
            if (! $open_unplanned->containsId($id)) {
                throw new ArtifactIsNotInOpenAndUnplannedBacklogItemsException($id);
            }
        }

        return true;
    }

    public function validateArtifactIdsAreInOpenAndUnassignedTopBacklog(array $ids, PFUser $user, Project $project) {
        if (! $this->idsAreUnique($ids)) {
            throw new IdsFromBodyAreNotUniqueException();
        }

        $top_milestone       = $this->milestone_factory->getVirtualTopMilestone($user, $project);
        $strategy_unassigned = $this->backlog_strategy_factory->getSelfBacklogStrategy($top_milestone);
        $open_unassigned     = $this->backlog_item_collection_factory->getUnassignedOpenCollection($user, $top_milestone, $strategy_unassigned, false);

        foreach($ids as $id) {
            if (! $open_unassigned->containsId($id)) {
                throw new ArtifactIsNotInOpenAndUnassignedTopBacklogItemsException($id);
            }
        }

        return true;
    }
}