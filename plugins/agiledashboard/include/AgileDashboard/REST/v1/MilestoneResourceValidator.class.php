<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

/**
 * This class has to validate data from the PUT requests concerning
 * milestone routes
 */
namespace Tuleap\AgileDashboard\REST\v1;

use AgileDashboard_Milestone_Backlog_Backlog;
use AgileDashboard_Milestone_Backlog_BacklogFactory;
use AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory;
use AgileDashboard_Milestone_Backlog_IBacklogItemCollection;
use PFUser;
use Planning_Milestone;
use Planning_MilestoneFactory;
use PlanningFactory;
use Project;
use Tracker_ArtifactFactory;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\Helpers\IdsFromBodyAreNotUniqueException;
use Tuleap\Tracker\REST\Helpers\OrderIdOutOfBoundException;
use Tuleap\Tracker\REST\Helpers\OrderRepresentation;
use Tuleap\Tracker\REST\Helpers\OrderValidator;

class MilestoneResourceValidator
{
    /** @var \Planning_MilestoneFactory */
    private $milestone_factory;

    /** @var AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory */
    private $backlog_item_collection_factory;

    /** @var AgileDashboard_Milestone_Backlog_BacklogFactory */
    private $backlog_factory;

    /** @var PlanningFactory */
    private $planning_factory;

    /** @var Tracker_ArtifactFactory */
    private $tracker_artifact_factory;

    public function __construct(
        PlanningFactory $planning_factory,
        Tracker_ArtifactFactory $tracker_artifact_factory,
        AgileDashboard_Milestone_Backlog_BacklogFactory $backlog_factory,
        Planning_MilestoneFactory $milestone_factory,
        AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory $backlog_row_collection_factory,
    ) {
        $this->planning_factory                = $planning_factory;
        $this->tracker_artifact_factory        = $tracker_artifact_factory;
        $this->backlog_factory                 = $backlog_factory;
        $this->milestone_factory               = $milestone_factory;
        $this->backlog_item_collection_factory = $backlog_row_collection_factory;
    }

    /**
     * @throws IdsFromBodyAreNotUniqueException
     * @throws ArtifactDoesNotExistException
     * @throws ArtifactIsNotInBacklogTrackerException
     * @throws ArtifactIsClosedOrAlreadyPlannedInAnotherMilestone
     */
    public function validateArtifactsFromBodyContent(array $ids, Planning_Milestone $milestone, PFUser $user)
    {
        return $this->validateArtifactsForMilestoneContent($ids, $milestone, $user, false);
    }

    /**
     * @throws IdsFromBodyAreNotUniqueException
     * @throws ArtifactDoesNotExistException
     * @throws ArtifactIsNotInBacklogTrackerException
     * @throws ArtifactIsClosedOrAlreadyPlannedInAnotherMilestone
     */
    public function validateArtifactsFromBodyContentWithClosedItems(array $ids, Planning_Milestone $milestone, PFUser $user)
    {
        return $this->validateArtifactsForMilestoneContent($ids, $milestone, $user, true);
    }

    private function validateArtifactsForMilestoneContent(array $ids, Planning_Milestone $milestone, PFUser $user, $accept_closed)
    {
        $open_unplanned = null;
        $todo           = null;
        $done           = null;

        $this->validateIdsAreUnique($ids);

        if (! $accept_closed) {
            //We only want to use this strategy when editing the list of items in a milestone. (a descendant
            //strategy would fetch all the children of the backlog items).
            $backlog = $this->backlog_factory->getSelfBacklog($milestone);

            if ($milestone->getParent()) {
                $open_unplanned = $this->backlog_item_collection_factory->getUnplannedOpenCollection($user, $milestone->getParent(), $this->backlog_factory->getBacklog($milestone->getParent()), false);
            } else {
                $top_milestone      = $this->milestone_factory->getVirtualTopMilestone($user, $milestone->getProject());
                $backlog_unassigned = $this->backlog_factory->getSelfBacklog($top_milestone);

                $open_unplanned = $this->backlog_item_collection_factory->getUnassignedOpenCollection($user, $top_milestone, $backlog_unassigned, false);
            }

            $done = $this->getMilestoneDoneBacklogItems($user, $milestone, $backlog);
            $todo = $this->getMilestoneTodoBacklogItems($user, $milestone, $backlog);
        }

        $backlog_tracker_ids = $this->planning_factory->getBacklogTrackersIds($milestone->getPlanning()->getId());

        $artifacts = $this->getArtifactsFromBodyContent($ids, $backlog_tracker_ids, $todo, $done, $open_unplanned);

        return count($artifacts) === count($ids);
    }

    public function validateSubmilestonesFromBodyContent(array $ids, Planning_Milestone $milestone, PFUser $user)
    {
        if (! $milestone->getArtifact()->userCanUpdate($user)) {
            throw new UserCannotUpdateMilestoneException($milestone->getArtifactId());
        }

        $this->validateIdsAreUnique($ids);

        foreach ($ids as $id) {
            $this->checkSubMilestoneById($milestone, $user, $id);
        }
    }

    /**
     * @throws ArtifactDoesNotExistException
     * @throws ArtifactIsNotInBacklogTrackerException
     * @throws ArtifactIsClosedOrAlreadyPlannedInAnotherMilestone
     */
    private function getArtifactsFromBodyContent(
        array $ids,
        array $backlog_tracker_ids,
        ?AgileDashboard_Milestone_Backlog_IBacklogItemCollection $todo = null,
        ?AgileDashboard_Milestone_Backlog_IBacklogItemCollection $done = null,
        ?AgileDashboard_Milestone_Backlog_IBacklogItemCollection $open_unplanned = null,
    ) {
        $artifacts = [];

        foreach ($ids as $potential_backlog_item_id) {
            $artifact = $this->tracker_artifact_factory->getArtifactById($potential_backlog_item_id);
            if (! $artifact) {
                throw new ArtifactDoesNotExistException($potential_backlog_item_id);
            }

            if (! in_array($artifact->getTrackerId(), $backlog_tracker_ids)) {
                throw new ArtifactIsNotInBacklogTrackerException($potential_backlog_item_id);
            }

            if (
                $todo !== null
                && ! $this->isArtifactInUnplannedParentMilestoneBacklogItems($artifact, $open_unplanned)
                && ! $this->isArtifactInPlannedMilestoneBacklogItems($artifact, $done, $todo)
            ) {
                throw new ArtifactIsClosedOrAlreadyPlannedInAnotherMilestone($potential_backlog_item_id);
            }

            $artifacts[$artifact->getId()] = $artifact;
        }

        return $artifacts;
    }

    private function getMilestoneDoneBacklogItems(PFUser $user, Planning_Milestone $milestone, AgileDashboard_Milestone_Backlog_Backlog $backlog)
    {
        return $this->backlog_item_collection_factory->getDoneCollection($user, $milestone, $backlog, false);
    }

    private function getMilestoneTodoBacklogItems(PFUser $user, Planning_Milestone $milestone, AgileDashboard_Milestone_Backlog_Backlog $backlog)
    {
        return $this->backlog_item_collection_factory->getTodoCollection($user, $milestone, $backlog, false);
    }

    private function isArtifactInUnplannedParentMilestoneBacklogItems(Artifact $artifact, AgileDashboard_Milestone_Backlog_IBacklogItemCollection $unplanned_backlog_items)
    {
        return $unplanned_backlog_items->containsId($artifact->getId());
    }

    private function isArtifactInPlannedMilestoneBacklogItems(Artifact $artifact, AgileDashboard_Milestone_Backlog_IBacklogItemCollection $done, AgileDashboard_Milestone_Backlog_IBacklogItemCollection $todo)
    {
        return ($done->containsId($artifact->getId()) || $todo->containsId($artifact->getId()));
    }

    /**
     * @throws IdsFromBodyAreNotUniqueException
     * @throws ArtifactIsNotInUnplannedBacklogItemsException
     */
    public function validateArtifactIdsAreInUnplannedMilestone(array $ids, Planning_Milestone $milestone, PFUser $user)
    {
        $this->validateIdsAreUnique($ids);

        $unplanned = $this->backlog_item_collection_factory->getUnplannedCollection($user, $milestone, $this->backlog_factory->getBacklog($milestone), false);

        foreach ($ids as $id) {
            if (! $unplanned->containsId($id)) {
                throw new ArtifactIsNotInUnplannedBacklogItemsException($id);
            }
        }

        return true;
    }

    /**
     * @throws IdsFromBodyAreNotUniqueException
     */
    public function validateIdsAreUnique(array $ids)
    {
        $ids_unique = array_unique($ids);

        if (count($ids) != count($ids_unique)) {
            throw new IdsFromBodyAreNotUniqueException();
        }
    }

    /**
     * @throws IdsFromBodyAreNotUniqueException
     * @throws ArtifactCannotBeInBacklogOfException
     */
    public function validateArtifactIdsCanBeAddedToBacklog(array $to_add, Planning_Milestone $milestone, PFUser $user)
    {
        $this->validateIdsAreUnique($to_add);

        $ids_to_add = $this->filterArtifactIdsAlreadyInBacklog($to_add, $milestone, $user);

        $indexed_children_backlog_trackers = $this->getIndexedChildrenBacklogTrackers($milestone);

        foreach ($ids_to_add as $id) {
            $artifact = $this->tracker_artifact_factory->getArtifactById($id);
            if (! isset($indexed_children_backlog_trackers[$artifact->getTrackerId()])) {
                throw new ArtifactCannotBeInBacklogOfException($milestone->getArtifactId(), $artifact->getId(), $artifact->getTracker()->getItemName(), array_keys($indexed_children_backlog_trackers));
            }
        }

        return $ids_to_add;
    }

    private function filterArtifactIdsAlreadyInBacklog(array $ids, Planning_Milestone $milestone, PFUser $user)
    {
        $indexed_backlog_items = $this->getIndexedBacklogItems($user, $milestone);
        $to_add                = [];
        foreach ($ids as $id) {
            if (! isset($indexed_backlog_items[$id])) {
                $to_add[] = $id;
            }
        }
        return $to_add;
    }

    private function getIndexedChildrenBacklogTrackers(Planning_Milestone $milestone)
    {
        $children_backlog_trackers = [];
        $children_planning         = $this->planning_factory->getChildrenPlanning($milestone->getPlanning());
        if ($children_planning) {
            foreach ($children_planning->getBacklogTrackersIds() as $id) {
                $children_backlog_trackers[$id] = true;
            }
        }
        return $children_backlog_trackers;
    }

    private function getIndexedBacklogItems(PFUser $user, Planning_Milestone $milestone)
    {
        $index         = [];
        $backlog_items = $this->getMilestoneBacklogItems($user, $milestone);
        foreach ($backlog_items as $item) {
            $index[$item->id()] = true;
        }
        return $index;
    }

    private function getMilestoneBacklogItems(PFUser $user, $milestone)
    {
        return $this->backlog_item_collection_factory->getUnplannedOpenCollection(
            $user,
            $milestone,
            $this->backlog_factory->getBacklog($milestone),
            false
        );
    }

    /**
     * @throws IdsFromBodyAreNotUniqueException
     * @throws ArtifactIsNotInUnassignedTopBacklogItemsException
     */
    public function validateArtifactIdsAreInUnassignedTopBacklog(array $ids, PFUser $user, Project $project)
    {
        $this->validateIdsAreUnique($ids);

        $top_milestone      = $this->milestone_factory->getVirtualTopMilestone($user, $project);
        $backlog_unassigned = $this->backlog_factory->getSelfBacklog($top_milestone);
        $unassigned         = $this->backlog_item_collection_factory->getUnassignedCollection($user, $top_milestone, $backlog_unassigned, false);

        foreach ($ids as $id) {
            if (! $unassigned->containsId($id)) {
                throw new ArtifactIsNotInUnassignedTopBacklogItemsException($id);
            }
        }

        return true;
    }

    private function checkSubMilestoneById(Planning_Milestone $milestone, PFUser $user, $sub_milesone_id)
    {
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

        if ($sub_milestone->getParent() && $sub_milestone->getParent()->getArtifactId() != $milestone->getArtifactId()) {
            throw new SubMilestoneAlreadyHasAParentException($sub_milesone_id);
        }
    }

    public function canBacklogItemBeAddedToMilestone(Artifact $artifact, array $allowed_trackers)
    {
        $artifact_tracker_id = $artifact->getTrackerId();

        foreach ($allowed_trackers as $tracker) {
            if ($tracker->getId() == $artifact_tracker_id) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws IdsFromBodyAreNotUniqueException
     * @throws OrderIdOutOfBoundException
     */
    public function canOrderContent(PFUser $user, Planning_Milestone $milestone, OrderRepresentation $order)
    {
        $order_validator = new OrderValidator($this->getIndexedLinkedArtifactIds($user, $milestone));
        $order_validator->validate($order);
    }

    private function getIndexedLinkedArtifactIds(PFUser $user, Planning_Milestone $milestone)
    {
        $linked_artifacts_index = [];
        foreach ($milestone->getArtifact()->getLinkedArtifacts($user) as $artifact) {
            $linked_artifacts_index[$artifact->getId()] = true;
        }
        return $linked_artifacts_index;
    }

    public function getValidatedArtifactsIdsToAddOrRemoveFromContent(PFUser $user, Planning_Milestone $milestone, $remove, $add)
    {
        $validator = new PatchAddRemoveValidator(
            $this->getIndexedLinkedArtifactIds($user, $milestone),
            new PatchAddContentValidator(
                $this,
                $milestone,
                $user
            )
        );
        return $validator->validate($milestone->getArtifactId(), $remove, $add);
    }
}
