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
 * I build collections of BacklogRow
 */
class AgileDashboard_Milestone_Backlog_BacklogRowCollectionFactory {

    /** @var AgileDashboard_BacklogItemDao */
    private $dao;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var Tracker_FormElementFactory */
    private $form_element_factory;

    /** @var AgileDashboard_Milestone_Backlog_BacklogRowPresenterCollection[] */
    private $all_collection;

    /** @var AgileDashboard_Milestone_Backlog_BacklogRowPresenterCollection[] */
    private $todo_collection;

    /** @var AgileDashboard_Milestone_Backlog_BacklogRowPresenterCollection[] */
    private $done_collection;

    /** @var AgileDashboard_Milestone_Backlog_BacklogRowPresenterCollection[] */
    private $unplanned_open_collection;

    /** @var Planning_MilestoneFactory */
    private $milestone_factory;

    public function __construct(
        AgileDashboard_BacklogItemDao $dao,
        Tracker_ArtifactFactory $artifact_factory,
        Tracker_FormElementFactory $form_element_factory,
        Planning_MilestoneFactory $milestone_factory
    ) {
        $this->dao                  = $dao;
        $this->artifact_factory     = $artifact_factory;
        $this->form_element_factory = $form_element_factory;
        $this->milestone_factory    = $milestone_factory;

        $this->all_collection            = array();
        $this->todo_collection           = array();
        $this->done_collection           = array();
        $this->unplanned_open_collection = array();
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

    public function getUnplannedOpenCollection(
        PFUser $user,
        Planning_Milestone $milestone,
        AgileDashboard_Milestone_Backlog_BacklogStrategy $backlog_strategy,
        $redirect_to_self
    ) {
        $this->initCollections($user, $milestone, $backlog_strategy, $redirect_to_self);

        return $this->unplanned_open_collection[$milestone->getArtifactId()];
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

        $this->all_collection[$id]            = new AgileDashboard_Milestone_Backlog_BacklogRowPresenterCollection();
        $this->todo_collection[$id]           = new AgileDashboard_Milestone_Backlog_BacklogRowPresenterCollection();
        $this->done_collection[$id]           = new AgileDashboard_Milestone_Backlog_BacklogRowPresenterCollection();
        $this->unplanned_open_collection[$id] = new AgileDashboard_Milestone_Backlog_BacklogRowPresenterCollection();
        $artifacts            = array();
        $backlog_item_ids     = array();
        
        foreach ($backlog_strategy->getArtifacts($user) as $artifact) {
            $artifacts[$artifact->getId()] = $artifact;
            $backlog_item_ids[] = $artifact->getId();
        }

        $parents   = $this->getParentArtifacts($milestone, $user, $backlog_item_ids);
        $semantics = $this->getArtifactsSemantics($user, $milestone, $backlog_item_ids);
        $planned   = $this->getPlannedArtifactIds($user, $milestone);

        foreach ($artifacts as $artifact) {
            $this->pushItem(
                $milestone,
                $user,
                $artifact,
                $parents,
                $semantics,
                $planned,
                $redirect_to_self
            );
        }
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
        $this->todo_collection[$milestone->getArtifactId()]->setParentItemName($name);
        $this->done_collection[$milestone->getArtifactId()]->setParentItemName($name);
        $this->unplanned_open_collection[$milestone->getArtifactId()]->setParentItemName($name);
    }

    private function getParentTracker(array $artifacts) {
        if (count($artifacts) > 0) {
            $artifact = current($artifacts);
            reset($artifacts);
            return $artifact->getTracker();
        }

        return null;
    }


    private function getArtifactsSemantics(PFUser $user, Planning_Milestone $milestone, array $backlog_item_ids) {
        if (! $backlog_item_ids) {
            return array();
        }

        $semantics = array();
        foreach ($this->dao->getArtifactsSemantics($backlog_item_ids, $this->getSemanticsTheUserCanSee($user, $milestone)) as $row) {
            $semantics[$row['id']] = array(
                Tracker_Semantic_Title::NAME  => $row[Tracker_Semantic_Title::NAME],
                Tracker_Semantic_Status::NAME => $row[Tracker_Semantic_Status::NAME],
            );
        }

        return $semantics;
    }

    private function getSemanticsTheUserCanSee(PFUser $user, Planning_Milestone $milestone) {
        $backlog_tracker = $milestone->getPlanning()->getBacklogTracker();
        $semantics = array();
        if ($this->userCanReadBacklogTitleField($user ,$backlog_tracker)) {
            $semantics[] = Tracker_Semantic_Title::NAME;
        }
        if ($this->userCanReadBacklogStatusField($user, $backlog_tracker)) {
            $semantics[] = Tracker_Semantic_Status::NAME;
        }

        return $semantics;
    }

    protected function userCanReadBacklogTitleField(PFUser $user, Tracker $tracker) {
        $field = Tracker_Semantic_Title::load($tracker)->getField();
        if (! $field) {
            return false;
        }

        return $field->userCanRead($user);
    }

    protected function userCanReadBacklogStatusField(PFUser $user, Tracker $tracker) {
        $field = Tracker_Semantic_Status::load($tracker)->getField();
        if (! $field) {
            return false;
        }

        return $field->userCanRead($user);
    }

    protected function setRemainingEffort(PFUser $user, AgileDashboard_BacklogItem $backlog_item, Tracker_Artifact $artifact) {
        $field = $this->form_element_factory->getUsedFieldByNameForUser(
            $artifact->getTrackerId(),
            Tracker::REMAINING_EFFORT_FIELD_NAME,
            $user
        );
        if ($field) {
            $backlog_item->setRemainingEffort($field->fetchCardValue($artifact));
        }
    }

    private function pushItem(
        Planning_Milestone $milestone,
        PFUser $user,
        Tracker_Artifact $artifact,
        array $parents,
        array $semantics,
        array $planned,
        $redirect_to_self
    ) {
        $artifact_id = $artifact->getId();
        $artifact->setTitle($semantics[$artifact_id][Tracker_Semantic_Title::NAME]);

        $backlog_item = new AgileDashboard_BacklogItem($artifact, $redirect_to_self);
        if (isset($parents[$artifact_id])) {
            $backlog_item->setParent($parents[$artifact_id]);
        }

        $this->pushItemInOpenCollections($milestone, $user, $artifact, $semantics, $planned, $backlog_item);
        $this->pushItemInDoneCollection($milestone, $semantics, $artifact_id, $backlog_item);
        $this->all_collection[$milestone->getArtifactId()]->push($backlog_item);
    }

    private function pushItemInOpenCollections(Planning_Milestone $milestone, PFUser $user, Tracker_Artifact $artifact, array $semantics, array $planned, AgileDashboard_BacklogItem $backlog_item) {
        $artifact_id = $artifact->getId();
    
        if ($semantics[$artifact_id][Tracker_Semantic_Status::NAME] == AgileDashboard_BacklogItemDao::STATUS_OPEN) {
            $backlog_item->setStatus(Tracker_Semantic_Status::OPEN);
            $this->setRemainingEffort($user, $backlog_item, $artifact);
            $this->todo_collection[$milestone->getArtifactId()]->push($backlog_item);

            if (! in_array($artifact_id, $planned)) {
                $this->unplanned_open_collection[$milestone->getArtifactId()]->push($backlog_item);
            }
        }
    }

    private function pushItemInDoneCollection(Planning_Milestone $milestone, array $semantics, $artifact_id, AgileDashboard_BacklogItem $backlog_item) {
        if ($semantics[$artifact_id][Tracker_Semantic_Status::NAME] != AgileDashboard_BacklogItemDao::STATUS_OPEN) {
            $backlog_item->setStatus(Tracker_Semantic_Status::CLOSED);
            $this->done_collection[$milestone->getArtifactId()]->push($backlog_item);
        }
    }

    private function getPlannedArtifactIds(PFUser $user, Planning_Milestone $milestone) {
        if ($milestone instanceof Planning_TopMilestone) {
            $milestones = $this->milestone_factory->getTopMilestones($user, $milestone);
        } else {
            $milestones     = $this->milestone_factory->getSubMilestones($user, $milestone);
        }

        $milestones_ids = array_map(array($this, 'extractArtifactId'), $milestones);
        if (! $milestones_ids) {
            return array();
        }

        return $this->dao->getPlannedItemIds($milestones_ids);
    }

    private function extractArtifactId(Planning_Milestone $milestone) {
        return $milestone->getArtifactId();
    }
}
?>
