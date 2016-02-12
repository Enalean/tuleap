<?php
/**
 * Copyright Enalean (c) 2013. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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
 * I build collections of IBacklogItem
 */
class AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory {

    /** @var AgileDashboard_BacklogItemDao */
    private $dao;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var Tracker_FormElementFactory */
    private $form_element_factory;

    /** @var AgileDashboard_Milestone_Backlog_IBacklogItemCollection[] */
    private $all_collection;

    /** @var AgileDashboard_Milestone_Backlog_IBacklogItemCollection[] */
    private $todo_collection;

    /** @var AgileDashboard_Milestone_Backlog_IBacklogItemCollection[] */
    private $done_collection;

    /** @var AgileDashboard_Milestone_Backlog_IBacklogItemCollection[] */
    private $inconsistent_collection;

    /** @var Planning_MilestoneFactory */
    private $milestone_factory;

    /** @var PlanningFactory */
    private $planning_factory;

    /** @var Boolean[] */
    private $cache_read_title;

    /** @var Boolean[] */
    private $cache_read_status;

    /** @var Boolean[] */
    private $cache_initial_effort;

    /** @var AgileDashboard_Milestone_Backlog_IBuildBacklogItemAndBacklogItemCollection */
    private $backlog_item_builder;

    public function __construct(
        AgileDashboard_BacklogItemDao $dao,
        Tracker_ArtifactFactory $artifact_factory,
        Tracker_FormElementFactory $form_element_factory,
        Planning_MilestoneFactory $milestone_factory,
        PlanningFactory $planning_factory,
        AgileDashboard_Milestone_Backlog_IBuildBacklogItemAndBacklogItemCollection $backlog_item_builder
    ) {
        $this->dao                  = $dao;
        $this->artifact_factory     = $artifact_factory;
        $this->form_element_factory = $form_element_factory;
        $this->milestone_factory    = $milestone_factory;
        $this->planning_factory     = $planning_factory;
        $this->backlog_item_builder = $backlog_item_builder;

        $this->all_collection            = array();
        $this->todo_collection           = array();
        $this->done_collection           = array();
    }

    public function getTodoCollection(
        PFUser $user,
        Planning_Milestone $milestone,
        AgileDashboard_Milestone_Backlog_BacklogStrategy $backlog_strategy,
        $redirect_to_self
    ) {
        $this->initCollections($user, $milestone, $backlog_strategy, $redirect_to_self);

        return $this->todo_collection[$milestone->getArtifactId()];
    }

    public function getDoneCollection(
        PFUser $user,
        Planning_Milestone $milestone,
        AgileDashboard_Milestone_Backlog_BacklogStrategy $backlog_strategy,
        $redirect_to_self
    ) {
        $this->initCollections($user, $milestone, $backlog_strategy, $redirect_to_self);

        return $this->done_collection[$milestone->getArtifactId()];
    }

    public function getUnassignedOpenCollection(
        PFUser $user,
        Planning_Milestone $milestone,
        AgileDashboard_Milestone_Backlog_BacklogStrategy $backlog_strategy,
        $redirect_to_self
    ) {
        $collection = $this->getUnplannedOpenCollection($user, $milestone, $backlog_strategy, $redirect_to_self);

        return $this->filterOutAssignedBacklogItems($collection, $user);
    }

    public function getUnassignedCollection(
        PFUser $user,
        Planning_Milestone $milestone,
        AgileDashboard_Milestone_Backlog_BacklogStrategy $backlog_strategy,
        $redirect_to_self
    ) {
        $collection = $this->getUnplannedCollection($user, $milestone, $backlog_strategy, $redirect_to_self);

        return $this->filterOutAssignedBacklogItems($collection, $user);
    }

    public function getUnplannedCollection(
        PFUser $user,
        Planning_Milestone $milestone,
        AgileDashboard_Milestone_Backlog_DescendantBacklogStrategy $backlog_strategy,
        $redirect_to_self
    ) {
        $artifacts         = array();
        $backlog_item_ids  = array();
        $sub_milestone_ids = $this->getSubmilestoneIds($user, $milestone);

        $item_collection = $backlog_strategy->getUnplannedArtifacts($user, $sub_milestone_ids);
        foreach ($item_collection as $artifact) {
            $artifacts[$artifact->getId()] = $artifact;
            $backlog_item_ids[]            = $artifact->getId();
        }

        $parents    = $this->getParentArtifacts($milestone, $user, $backlog_item_ids);
        $semantics  = $this->getArtifactsSemantics($user, $milestone, $backlog_item_ids, $artifacts);

        $collection = $this->backlog_item_builder->getCollection();
        foreach ($artifacts as $artifact) {
            $artifact_id = $artifact->getId();

            if (! isset($semantics[$artifact_id]) || ! isset($semantics[$artifact_id][Tracker_Semantic_Status::NAME])) {
                continue;
            }

            $artifact->setTitle($semantics[$artifact_id][Tracker_Semantic_Title::NAME]);

            $backlog_item = $this->backlog_item_builder->getItem($artifact, $redirect_to_self);
            $backlog_item->setStatus($semantics[$artifact_id][Tracker_Semantic_Status::NAME]);
            if (isset($parents[$artifact_id])) {
                $backlog_item->setParent($parents[$artifact_id]);
            }

            $this->setInitialEffort($backlog_item, $semantics[$artifact_id]);

            $collection->push($backlog_item);
        }
        $collection->setTotalAvaialableSize($item_collection->getTotalAvaialableSize());

        return $collection;
    }

    public function getUnplannedOpenCollection(
        PFUser $user,
        Planning_Milestone $milestone,
        AgileDashboard_Milestone_Backlog_DescendantBacklogStrategy $backlog_strategy,
        $redirect_to_self
    ) {
        $artifacts         = array();
        $backlog_item_ids  = array();
        $sub_milestone_ids = $this->getSubmilestoneIds($user, $milestone);

        $item_collection = $backlog_strategy->getOpenUnplannedArtifacts($user, $sub_milestone_ids);
        foreach ($item_collection as $artifact) {
            $artifacts[$artifact->getId()] = $artifact;
            $backlog_item_ids[]            = $artifact->getId();
        }

        $parents    = $this->getParentArtifacts($milestone, $user, $backlog_item_ids);
        $semantics  = $this->getArtifactsSemantics($user, $milestone, $backlog_item_ids, $artifacts);

        if (empty($backlog_item_ids)) {
            $children = 0;
        } else {
            $children = $this->artifact_factory->getChildrenCount($backlog_item_ids);
        }

        $collection = $this->backlog_item_builder->getCollection();
        foreach ($artifacts as $artifact) {
            $artifact_id = $artifact->getId();

            if (! isset($semantics[$artifact_id])
                || ! isset($semantics[$artifact_id][Tracker_Semantic_Status::NAME])
                || $semantics[$artifact_id][Tracker_Semantic_Status::NAME] != AgileDashboard_BacklogItemDao::STATUS_OPEN
            ) {
                continue;
            }

            $artifact->setTitle($semantics[$artifact_id][Tracker_Semantic_Title::NAME]);

            $backlog_item = $this->backlog_item_builder->getItem($artifact, $redirect_to_self);
            $backlog_item->setStatus(Tracker_Semantic_Status::OPEN);
            if (isset($parents[$artifact_id])) {
                $backlog_item->setParent($parents[$artifact_id]);
            }
            if (isset($children[$artifact_id])) {
                $backlog_item->setHasChildren($children[$artifact_id] > 0);
            }

            $this->setInitialEffort($backlog_item, $semantics[$artifact_id]);

            $collection->push($backlog_item);
        }
        $collection->setTotalAvaialableSize($item_collection->getTotalAvaialableSize());

        return $collection;
    }

    public function getInconsistentCollection(
        PFUser $user,
        Planning_Milestone $milestone,
        AgileDashboard_Milestone_Backlog_BacklogStrategy $backlog_strategy,
        $redirect_to_self
    ) {
        $this->initCollections($user, $milestone, $backlog_strategy, $redirect_to_self);

        return $this->inconsistent_collection[$milestone->getArtifactId()];
    }

    public function getAllCollection(
        PFUser $user,
        Planning_Milestone $milestone,
        AgileDashboard_Milestone_Backlog_BacklogStrategy $backlog_strategy,
        $redirect_to_self
    ) {
        $this->initCollections($user, $milestone, $backlog_strategy, $redirect_to_self);

        return $this->all_collection[$milestone->getArtifactId()];
    }

    private function initCollections(
        PFUser $user,
        Planning_Milestone $milestone,
        AgileDashboard_Milestone_Backlog_BacklogStrategy $backlog_strategy,
        $redirect_to_self
    ) {
        if (isset($this->all_collection[$milestone->getArtifactId()])) {
            return;
        }

        $id = $milestone->getArtifactId();

        $this->all_collection[$id]          = $this->backlog_item_builder->getCollection();
        $this->todo_collection[$id]         = $this->backlog_item_builder->getCollection();
        $this->done_collection[$id]         = $this->backlog_item_builder->getCollection();
        $this->inconsistent_collection[$id] = $this->backlog_item_builder->getCollection();
        $artifacts            = array();
        $backlog_item_ids     = array();

        $items_collection = $backlog_strategy->getArtifacts($user);
        foreach ($items_collection as $artifact) {
            $artifacts[$artifact->getId()] = $artifact;
            $backlog_item_ids[] = $artifact->getId();
        }

        $parents   = $this->getParentArtifacts($milestone, $user, $backlog_item_ids);
        $semantics = $this->getArtifactsSemantics($user, $milestone, $backlog_item_ids, $artifacts);
        $planned   = $this->getPlannedArtifactIds($user, $milestone);

        foreach ($artifacts as $artifact) {
            $this->pushItem(
                $milestone,
                $artifact,
                $parents,
                $semantics,
                $redirect_to_self
            );
        }

        $this->all_collection[$id]->setTotalAvaialableSize($items_collection->getTotalAvaialableSize());

        $this->initInconsistentItems($user, $milestone, $redirect_to_self, $planned);
    }

    private function getParentArtifacts(Planning_Milestone $milestone, PFUser $user, array $backlog_item_ids) {
        $parents         = $this->artifact_factory->getParents($backlog_item_ids);
        $parent_tracker  = $this->getParentTracker($parents);
        if ($parent_tracker) {
            $this->setParentItemName($milestone, $parent_tracker->getName());
            if ($this->userCanReadBacklogTitleField($user, $parent_tracker)) {
                $this->artifact_factory->setTitles($parents);
            } else {
                foreach ($parents as $artifact) {
                    $artifact->setTitle("");
                }
            }
        }

        return $parents;
    }

    private function setParentItemName(Planning_Milestone $milestone, $name) {
        if (! $milestone->getArtifactId() ) {
            return;
        }

        if (isset($this->todo_collection[$milestone->getArtifactId()])) {
            $this->todo_collection[$milestone->getArtifactId()]->setParentItemName($name);
        }

        if (isset($this->done_collection[$milestone->getArtifactId()])) {
            $this->done_collection[$milestone->getArtifactId()]->setParentItemName($name);
        }
    }

    private function getParentTracker(array $artifacts) {
        if (count($artifacts) > 0) {
            $artifact = current($artifacts);
            reset($artifacts);
            return $artifact->getTracker();
        }

        return null;
    }


    private function getArtifactsSemantics(PFUser $user, Planning_Milestone $milestone, array $backlog_item_ids, $artifacts) {
        if (! $backlog_item_ids) {
            return array();
        }

        $semantics              = array();
        $allowed_semantics      = array(
            Tracker_Semantic_Title::NAME,
            Tracker_Semantic_Status::NAME,
        );

        foreach ($this->dao->getArtifactsSemantics($backlog_item_ids, $allowed_semantics) as $row) {
            $artifact = $artifacts[$row['id']];
            $tracker  = $artifact->getTracker();

            $this->setTitleSemantic($user, $artifact, $tracker, $row, $semantics);
            $this->setStatusSemantic($user, $artifact, $tracker, $row, $semantics);
            $this->setInitialEffortSemantic($user, $artifact, $tracker, $row, $semantics);
        }

        return $semantics;
    }

    private function setTitleSemantic(PFUser $user, Tracker_Artifact $artifact, Tracker $tracker, array $row, array &$semantics) {
        $semantics[$artifact->getId()][Tracker_Semantic_Title::NAME] = '';
        if ($this->userCanReadBacklogTitleField($user, $tracker)) {
            $semantics[$artifact->getId()][Tracker_Semantic_Title::NAME] = $row[Tracker_Semantic_Title::NAME];
        }
    }

    private function setStatusSemantic(PFUser $user, Tracker_Artifact $artifact, Tracker $tracker, array $row, array &$semantics) {
        $semantics[$artifact->getId()][Tracker_Semantic_Status::NAME] = '';
        if ($this->userCanReadBacklogStatusField($user, $tracker)) {
            $semantics[$artifact->getId()][Tracker_Semantic_Status::NAME] = $row[Tracker_Semantic_Status::NAME];
        }
    }

    private function setInitialEffortSemantic(PFUser $user, Tracker_Artifact $artifact, Tracker $tracker, array $row, array &$semantics) {
        $semantics[$artifact->getId()][AgileDashBoard_Semantic_InitialEffort::NAME] = '';
        if ($this->userCanReadInitialEffortField($user, $tracker)) {
            $semantics[$artifact->getId()][AgileDashBoard_Semantic_InitialEffort::NAME] = $this->getSemanticEffortValue($user, $artifact);
        }
    }


    /**
     * @param PFUser $user
     * @param Tracker_Artifact $artifact
     * @return string | number
     */
    private function getSemanticEffortValue(PFUser $user, Tracker_Artifact $artifact) {
        if (! $field = $this->getInitialEffortField($artifact->getTracker())) {
            return false;
        }

        return $field->getComputedValue($user, $artifact);
    }

    protected function userCanReadBacklogTitleField(PFUser $user, Tracker $tracker) {
        if (! isset($this->cache_read_title[$tracker->getId()])) {
            $field = Tracker_Semantic_Title::load($tracker)->getField();
            if (! $field) {
                $this->cache_read_title[$tracker->getId()] = false;
            } else {
                $this->cache_read_title[$tracker->getId()] = $field->userCanRead($user);
            }
        }
        return $this->cache_read_title[$tracker->getId()];
    }

    protected function userCanReadBacklogStatusField(PFUser $user, Tracker $tracker) {
        if (! isset($this->cache_read_status[$tracker->getId()])) {
            $this->cache_read_status[$tracker->getId()] = false;
            $field = Tracker_Semantic_Status::load($tracker)->getField();
            if ($field) {
                $this->cache_read_status[$tracker->getId()] = $field->userCanRead($user);
            }
        }
        return $this->cache_read_status[$tracker->getId()];
    }

    protected function userCanReadInitialEffortField(PFUser $user, Tracker $tracker) {
        if (! isset($this->cache_initial_effort[$tracker->getId()])) {
            $this->cache_read_initial_effort[$tracker->getId()] = false;
            $field = $this->getInitialEffortField($tracker);
            if ($field && $field->userCanRead($user)) {
                $this->cache_read_initial_effort[$tracker->getId()] = true;
            }
        }
        return $this->cache_read_initial_effort[$tracker->getId()];
    }

    /**
     * @param Tracker $tracker
     * @return Tracker_FormElement_Field | null
     */
    protected function getInitialEffortField(Tracker $tracker) {
        return AgileDashBoard_Semantic_InitialEffort::load($tracker)->getField();
    }

    protected function setInitialEffort(AgileDashboard_Milestone_Backlog_IBacklogItem $backlog_item, $semantics_per_artifact) {
        if ( isset($semantics_per_artifact[AgileDashBoard_Semantic_InitialEffort::NAME]) ) {
            $backlog_item->setInitialEffort($semantics_per_artifact[AgileDashBoard_Semantic_InitialEffort::NAME]);
        }
    }

    private function pushItem(
        Planning_Milestone $milestone,
        Tracker_Artifact $artifact,
        array $parents,
        array $semantics,
        $redirect_to_self
    ) {
        $artifact_id = $artifact->getId();
        if (!isset($semantics[$artifact_id])) {
            return;
        }

        $artifact->setTitle($semantics[$artifact_id][Tracker_Semantic_Title::NAME]);

        $backlog_item = $this->backlog_item_builder->getItem($artifact, $redirect_to_self);

        if (isset($parents[$artifact_id])) {
            $backlog_item->setParent($parents[$artifact_id]);
        }

        $this->pushItemInOpenCollections($milestone, $artifact, $semantics, $backlog_item);
        $this->pushItemInDoneCollection($milestone, $semantics, $artifact_id, $backlog_item);
        $this->all_collection[$milestone->getArtifactId()]->push($backlog_item);
    }

    private function pushItemInOpenCollections(Planning_Milestone $milestone, Tracker_Artifact $artifact, array $semantics, AgileDashboard_Milestone_Backlog_IBacklogItem $backlog_item) {
        $artifact_id = $artifact->getId();

        if ($semantics[$artifact_id][Tracker_Semantic_Status::NAME] == AgileDashboard_BacklogItemDao::STATUS_OPEN) {
            $backlog_item->setStatus(Tracker_Semantic_Status::OPEN);

            $this->setInitialEffort($backlog_item, $semantics[$artifact_id]);
            $this->todo_collection[$milestone->getArtifactId()]->push($backlog_item);
        }
    }

    private function pushItemInDoneCollection(Planning_Milestone $milestone, array $semantics, $artifact_id, AgileDashboard_Milestone_Backlog_IBacklogItem $backlog_item) {
        $this->setInitialEffort($backlog_item, $semantics[$artifact_id]);

        if ($semantics[$artifact_id][Tracker_Semantic_Status::NAME] != AgileDashboard_BacklogItemDao::STATUS_OPEN) {
            $backlog_item->setStatus(Tracker_Semantic_Status::CLOSED);
            $this->done_collection[$milestone->getArtifactId()]->push($backlog_item);
        }
    }

    private function getPlannedArtifactIds(PFUser $user, Planning_Milestone $milestone) {
        $sub_milestone_ids = $this->getSubmilestoneIds($user, $milestone);
        if (! $sub_milestone_ids) {
            return array();
        }
        return $this->dao->getPlannedItemIds($sub_milestone_ids);
    }

    private function getSubmilestoneIds(PFUser $user, Planning_Milestone $milestone) {
        return $this->milestone_factory->getSubMilestoneIds($user, $milestone);
    }

    private function filterOutAssignedBacklogItems(AgileDashboard_Milestone_Backlog_IBacklogItemCollection $collection, PFUser $user) {
        $artifact_ids = $this->getBacklogItemsArtifactIds($collection);

        if (! $artifact_ids) {
            return $collection;
        }

        $collection->rewind();
        $artifact                       = $collection->current()->getArtifact();
        $project_id                     = $artifact->getTracker()->getProject()->getID();
        $plannings                      = $this->planning_factory->getPlannings($user, $project_id);
        $planning_milestone_tracker_ids = $this->getPlanningMilestoneTrackerIds($plannings);

        if (! $planning_milestone_tracker_ids) {
            return $collection;
        }

        $linked_item_artifacts_ids = $this->artifact_factory->getArtifactIdsLinkedToTrackers($artifact_ids, $planning_milestone_tracker_ids);

        return $this->removeLinkedItemsFromCollection($collection, $linked_item_artifacts_ids);
    }

    private function getBacklogItemsArtifactIds(AgileDashboard_Milestone_Backlog_IBacklogItemCollection $collection) {
        $artifact_ids = array();

        foreach ($collection as $backlog_item) {
            $artifact_ids[] = $backlog_item->getArtifact()->getId();
        }

        return $artifact_ids;
    }

    private function removeLinkedItemsFromCollection(AgileDashboard_Milestone_Backlog_IBacklogItemCollection $collection, $linked_item_artifacts_ids) {
        $cleaned_collection = $this->backlog_item_builder->getCollection();

        foreach ($collection as $backlog_item) {
            $artifact_id = $backlog_item->getArtifact()->getId();
            if (! isset($linked_item_artifacts_ids[$artifact_id])) {
                $cleaned_collection->push($backlog_item);
            }
        }

        return $cleaned_collection;
    }

    /**
     * @param Planning[] $plannings
     */
    private function getPlanningMilestoneTrackerIds($plannings) {
        $ids = array();
        foreach ($plannings as $planning) {
            $ids[] = $planning->getPlanningTrackerId();
        }

        return $ids;
    }

    private function initInconsistentItems(PFUser $user, Planning_Milestone $milestone, $redirect_to_self, array $planned) {
        foreach ($planned as $planned_artifact_id) {
            if (! $this->all_collection[$milestone->getArtifactId()]->containsId($planned_artifact_id)) {
                $artifact = $this->artifact_factory->getArtifactByIdUserCanView($user, $planned_artifact_id);

                if ($artifact) {
                    $this->inconsistent_collection[$milestone->getArtifactId()]->push(
                        $this->backlog_item_builder->getItem($artifact, $redirect_to_self)
                    );
                }
            }
        }
    }
}
?>
