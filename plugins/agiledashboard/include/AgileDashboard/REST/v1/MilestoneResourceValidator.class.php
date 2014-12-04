<?php
/**
 * Copyright (c) Enalean, 2013, 2014. All Rights Reserved.
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
use \Tracker;
use \Tracker_FormElementFactory;
use \AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory;
use \AgileDashboard_Milestone_Backlog_BacklogStrategyFactory;
use \AgileDashboard_Milestone_Backlog_BacklogStrategy;
use \AgileDashboard_Milestone_Backlog_IBacklogItemCollection;
use \Planning_MilestoneFactory;
use \Planning_Milestone;
use \PFUser;
use \Project;
use \Tuleap\AgileDashboard\REST\v1\OrderRepresentation;

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
        $open_unplanned = null;

        if (! $this->idsAreUnique($ids)) {
            throw new IdsFromBodyAreNotUniqueException();
        }

        //We only want to use this strategy when editing the list of items in a milestone. (a descendant
        //strategy would fetch all the children of the backlog items).
        $strategy = $this->backlog_strategy_factory->getSelfBacklogStrategy($milestone);

        if ($milestone->getParent()) {
            $open_unplanned = $this->backlog_item_collection_factory->getUnplannedOpenCollection($user, $milestone->getParent(), $this->backlog_strategy_factory->getBacklogStrategy($milestone->getParent()), false);

        } else {
            $top_milestone       = $this->milestone_factory->getVirtualTopMilestone($user, $milestone->getProject());
            $strategy_unassigned = $this->backlog_strategy_factory->getSelfBacklogStrategy($top_milestone);

            $open_unplanned = $this->backlog_item_collection_factory->getUnassignedOpenCollection($user, $top_milestone, $strategy_unassigned, false);
        }

        $done = $this->getMilestoneDoneBacklogItems($user, $milestone, $strategy);
        $todo = $this->getMilestoneTodoBacklogItems($user, $milestone, $strategy);

        $backlog_tracker_ids = $this->planning_factory->getBacklogTrackersIds($milestone->getPlanning()->getId());

        $artifacts = $this->getArtifactsFromBodyContent($ids, $backlog_tracker_ids, $todo, $done, $open_unplanned);

        return count($artifacts) === count($ids);
    }

    public function validateSubmilestonesFromBodyContent(array $ids, Planning_Milestone $milestone, PFUser $user) {
        if (! $milestone->getArtifact()->userCanUpdate($user)){
            throw new UserCannotUpdateMilestoneException($milestone->getArtifactId());
        }

        if (! $this->idsAreUnique($ids)) {
            throw new IdsFromBodyAreNotUniqueException();
        }

        foreach($ids as $id) {
            $this->checkSubMilestoneById($milestone, $user, $id);
        }
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

    /**
     * @throws IdsFromBodyAreNotUniqueException
     * @throws ArtifactIsNotInOpenAndUnplannedBacklogItemsException
     */
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

    /**
     * @throws IdsFromBodyAreNotUniqueException
     * @throws ArtifactIsNotInOpenAndUnassignedTopBacklogItemsException
     */
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

    private function checkSubMilestoneById(Planning_Milestone $milestone, PFUser $user, $sub_milesone_id) {
        $sub_milestone = $this->milestone_factory->getBareMilestoneByArtifactId($user, $sub_milesone_id);

        if (! $sub_milestone) {
            throw new SubMilestoneDoesNotExistException($sub_milesone_id);
        }

        if (! $milestone->milestoneCanBeSubmilestone($sub_milestone)) {
            throw new ElementCannotBeSubmilestoneException($milestone->getArtifactId(), $sub_milestone->getArtifactId());
        }

        if (! $sub_milestone->getArtifact()->userCanView()) {
            throw new UserCannotReadSubMilestoneException($sub_milesone_id);
        }

        if($sub_milestone->getParent() && $sub_milestone->getParent()->getArtifactId() != $milestone->getArtifactId()) {
            throw new SubMilestoneAlreadyHasAParentException($sub_milesone_id);
        }
    }

    public function canBacklogItemBeAddedToMilestone(Tracker_Artifact $artifact, array $allowed_trackers) {
        $artifact_tracker_id = $artifact->getTrackerId();

        foreach ($allowed_trackers as $tracker) {
            if ($tracker->getId() == $artifact_tracker_id) {
                return true;
            }
        }

        return false;
    }

    public function canOrderContent(PFUser $user, Planning_Milestone $milestone, OrderRepresentation $order) {
        $order_validator = new OrderValidator($this->getIndexedLinkedArtifactIds($user, $milestone));
        $order_validator->validate($order);
    }

    private function getIndexedLinkedArtifactIds(PFUser $user, Planning_Milestone $milestone) {
        $linked_artifacts_index = array();
        foreach ($milestone->getArtifact()->getLinkedArtifacts($user) as $artifact) {
            $linked_artifacts_index[$artifact->getId()] = true;
        }
        return $linked_artifacts_index;
    }

    public function getValidatedArtifactsIdsToRemoveFromContent(PFUser $user, Planning_Milestone $milestone, $remove, $add) {
        $indexed_linked_artifacts = $this->getIndexedLinkedArtifactIds($user, $milestone);
        $remove                   = $remove != null ? $remove : array();
        $add                      = $add    != null ? $add    : array();

        $to_remove = $this->getIdsToRemoveThatAreNotInAddArray($remove, $add);
        $to_add    = $this->getIdsToAddThatAreNotInRemoveArray($remove, $add);
        foreach ($to_remove as $id) {
            if (! isset($indexed_linked_artifacts[$id])) {
                throw new ArtifactIsNotInMilestoneContentException($milestone->getArtifactId(), $id);
            }
            unset($indexed_linked_artifacts[$id]);
        }
        if (count($to_add)) {
            $this->validateArtifactsFromBodyContent($to_add, $milestone, $user);
        }

        return array_unique(array_merge(array_keys($indexed_linked_artifacts), $to_add));
    }

    private function getIdsToRemoveThatAreNotInAddArray($remove, $add) {
        return array_diff($remove, $add);
    }

    private function getIdsToAddThatAreNotInRemoveArray($remove, $add) {
        return array_diff($add, $remove);
    }
}
