<?php
/**
 * Copyright Enalean (c) 2013 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
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

namespace Tuleap\AgileDashboard\Milestone\Backlog;

use AgileDashboard_Milestone_Backlog_DescendantItemsCollection;
use AgileDashboard_Milestone_Backlog_IBacklogItemCollection;
use AgileDashBoard_Semantic_InitialEffort;
use PFUser;
use Planning;
use Planning_Milestone;
use Planning_MilestoneFactory;
use PlanningFactory;
use Tracker_ArtifactFactory;
use Tracker_FormElement_IComputeValues;
use Tuleap\AgileDashboard\BacklogItemDao;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\RemainingEffortValueRetriever;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Dao\PriorityDao;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\Permission\RetrieveUserPermissionOnArtifacts;
use Tuleap\Tracker\Semantic\Status\RetrieveSemanticStatusField;
use Tuleap\Tracker\Semantic\Status\TrackerSemanticStatus;
use Tuleap\Tracker\Semantic\Title\RetrieveSemanticTitleField;
use Tuleap\Tracker\Semantic\Title\TrackerSemanticTitle;
use Tuleap\Tracker\Tracker;

/**
 * I build collections of IBacklogItem
 */
class BacklogItemCollectionFactory
{
    /** @var bool[] */
    private array $cache_read_title;

    /** @var bool[] */
    private array $cache_read_status;

    private array $cache_read_initial_effort = [];
    private array $open_and_closed_collection;
    private array $open_closed_and_inconsistent_collection;
    private array $todo_collection;
    private array $done_collection;
    private array $inconsistent_collection;

    public function __construct(
        private readonly BacklogItemDao $dao,
        private readonly Tracker_ArtifactFactory $artifact_factory,
        private readonly Planning_MilestoneFactory $milestone_factory,
        private readonly PlanningFactory $planning_factory,
        private readonly IBuildBacklogItemAndBacklogItemCollection $backlog_item_builder,
        private readonly RemainingEffortValueRetriever $remaining_effort_value_retriever,
        private readonly ArtifactsInExplicitBacklogDao $artifacts_in_explicit_backlog_dao,
        private readonly PriorityDao $artifact_priority_dao,
        private readonly RetrieveUserPermissionOnArtifacts $user_permission_on_artifacts_retriever,
        private readonly RetrieveSemanticTitleField $retrieve_semantic_title_field,
        private readonly RetrieveSemanticStatusField $retrieve_semantic_status_field,
    ) {
        $this->open_and_closed_collection              = [];
        $this->open_closed_and_inconsistent_collection = [];
        $this->todo_collection                         = [];
        $this->done_collection                         = [];
    }

    public function getTodoCollection(
        PFUser $user,
        Planning_Milestone $milestone,
        MilestoneBacklog $backlog,
        ?string $redirect_to_self,
    ): AgileDashboard_Milestone_Backlog_IBacklogItemCollection {
        $this->initCollections($user, $milestone, $backlog, $redirect_to_self);

        return $this->todo_collection[$milestone->getArtifactId() ?? 0];
    }

    public function getDoneCollection(
        PFUser $user,
        Planning_Milestone $milestone,
        MilestoneBacklog $backlog,
        ?string $redirect_to_self,
    ): AgileDashboard_Milestone_Backlog_IBacklogItemCollection {
        $this->initCollections($user, $milestone, $backlog, $redirect_to_self);

        return $this->done_collection[$milestone->getArtifactId() ?? 0];
    }

    public function getUnassignedOpenCollection(
        PFUser $user,
        Planning_Milestone $milestone,
        MilestoneBacklog $backlog,
        ?string $redirect_to_self,
    ): AgileDashboard_Milestone_Backlog_IBacklogItemCollection {
        $collection = $this->getUnplannedOpenCollection($user, $milestone, $backlog, $redirect_to_self);

        return $this->filterOutAssignedBacklogItems($collection, $user);
    }

    public function getUnassignedCollection(
        PFUser $user,
        Planning_Milestone $milestone,
        MilestoneBacklog $backlog,
        ?string $redirect_to_self,
    ): AgileDashboard_Milestone_Backlog_IBacklogItemCollection {
        $collection = $this->getUnplannedCollection($user, $milestone, $backlog, $redirect_to_self);

        return $this->filterOutAssignedBacklogItems($collection, $user);
    }

    public function getUnplannedCollection(
        PFUser $user,
        Planning_Milestone $milestone,
        MilestoneBacklog $backlog,
        ?string $redirect_to_self,
    ): AgileDashboard_Milestone_Backlog_IBacklogItemCollection {
        $sub_milestone_ids = $this->getSubmilestoneIds($user, $milestone);

        $item_collection = $backlog->getUnplannedArtifacts($user, $sub_milestone_ids);
        return $this->buildBacklogItemCollection($item_collection, $user, $milestone, $redirect_to_self);
    }

    public function getOpenClosedUnplannedCollection(
        PFUser $user,
        Planning_Milestone $milestone,
        MilestoneBacklog $backlog,
        ?string $redirection_url,
    ): AgileDashboard_Milestone_Backlog_IBacklogItemCollection {
        $sub_milestone_ids = $this->getSubmilestoneIds($user, $milestone);

        $item_collection = $backlog->getOpenClosedUnplannedArtifacts($user, $sub_milestone_ids);
        return $this->buildBacklogItemCollection($item_collection, $user, $milestone, $redirection_url);
    }

    private function buildBacklogItemCollection(
        AgileDashboard_Milestone_Backlog_DescendantItemsCollection $item_collection,
        PFUser $user,
        Planning_Milestone $milestone,
        ?string $redirection_url,
    ): AgileDashboard_Milestone_Backlog_IBacklogItemCollection {
        $permissions_on_items = $this->user_permission_on_artifacts_retriever->retrieveUserPermissionOnArtifacts(
            $user,
            iterator_to_array($item_collection),
            \Tuleap\Tracker\Permission\ArtifactPermissionType::PERMISSION_VIEW
        );

        $artifacts        = [];
        $backlog_item_ids = [];

        foreach ($permissions_on_items->allowed as $artifact) {
            $artifacts[$artifact->getId()] = $artifact;
            $backlog_item_ids[]            = $artifact->getId();
        }

        $parents   = $this->getParentArtifacts($milestone, $user, $backlog_item_ids);
        $semantics = $this->getArtifactsSemantics($user, $milestone, $backlog_item_ids, $artifacts);

        $collection = $this->backlog_item_builder->getCollection();
        foreach ($artifacts as $artifact) {
            $artifact_id = $artifact->getId();

            if (! isset($semantics[$artifact_id]) || ! isset($semantics[$artifact_id][TrackerSemanticStatus::NAME])) {
                continue;
            }

            $artifact->setTitle($this->artifact_factory->getTitleFromRowAsText($semantics[$artifact_id]));

            $backlog_item = $this->backlog_item_builder->getItem($artifact, $redirection_url, false);
            $backlog_item->setStatus(
                $artifact->getStatus(),
                $semantics[$artifact_id][TrackerSemanticStatus::NAME] === 1 ? TrackerSemanticStatus::OPEN : TrackerSemanticStatus::CLOSED
            );
            if (isset($parents[$artifact_id]) && $parents[$artifact_id]->userCanView($user)) {
                $backlog_item->setParent($parents[$artifact_id]);
            }

            $this->setInitialEffort($backlog_item, $semantics[$artifact_id]);
            $backlog_item->setRemainingEffort(
                $this->remaining_effort_value_retriever->getRemainingEffortValue($user, $backlog_item->getArtifact())
            );

            $collection->push($backlog_item);
        }
        $collection->setTotalAvaialableSize($item_collection->getTotalAvaialableSize());

        return $collection;
    }

    public function getUnplannedOpenCollection(
        PFUser $user,
        Planning_Milestone $milestone,
        MilestoneBacklog $backlog,
        ?string $redirect_to_self,
    ): AgileDashboard_Milestone_Backlog_IBacklogItemCollection {
        $artifacts         = [];
        $sub_milestone_ids = $this->getSubmilestoneIds($user, $milestone);

        $item_collection = $backlog->getOpenUnplannedArtifacts($user, $sub_milestone_ids);
        foreach ($item_collection as $artifact) {
            $artifacts[$artifact->getId()] = $artifact;
        }

        $collection = $this->buildTopBacklogCollection(
            $user,
            $milestone,
            $redirect_to_self,
            $artifacts
        );
        $collection->setTotalAvaialableSize($item_collection->getTotalAvaialableSize());

        return $collection;
    }

    public function getInconsistentCollection(
        PFUser $user,
        Planning_Milestone $milestone,
        MilestoneBacklog $backlog,
        ?string $redirect_to_self,
    ): AgileDashboard_Milestone_Backlog_IBacklogItemCollection {
        $this->initCollections($user, $milestone, $backlog, $redirect_to_self);

        return $this->inconsistent_collection[$milestone->getArtifactId() ?? 0];
    }

    public function getOpenClosedAndInconsistentCollection(
        PFUser $user,
        Planning_Milestone $milestone,
        MilestoneBacklog $backlog,
        ?string $redirect_to_self,
    ): AgileDashboard_Milestone_Backlog_IBacklogItemCollection {
        $this->initCollections($user, $milestone, $backlog, $redirect_to_self);

        if ($this->inconsistent_collection[$milestone->getArtifactId() ?? 0]->getTotalAvaialableSize() === 0) {
            return $this->open_closed_and_inconsistent_collection[$milestone->getArtifactId() ?? 0];
        }

        return $this->reorderOpenClosedAndInconsistentCollection($milestone);
    }

    public function getOpenAndClosedCollection(
        PFUser $user,
        Planning_Milestone $milestone,
        MilestoneBacklog $backlog,
        ?string $redirect_to_self,
    ): AgileDashboard_Milestone_Backlog_IBacklogItemCollection {
        $this->initCollections($user, $milestone, $backlog, $redirect_to_self);

        return $this->open_and_closed_collection[$milestone->getArtifactId() ?? 0];
    }

    private function initCollections(
        PFUser $user,
        Planning_Milestone $milestone,
        MilestoneBacklog $backlog,
        ?string $redirect_to_self,
    ): void {
        if (isset($this->open_and_closed_collection[$milestone->getArtifactId() ?? 0])) {
            return;
        }

        $id = $milestone->getArtifactId() ?? 0;

        $this->open_and_closed_collection[$id]              = $this->backlog_item_builder->getCollection();
        $this->open_closed_and_inconsistent_collection[$id] = $this->backlog_item_builder->getCollection();
        $this->todo_collection[$id]                         = $this->backlog_item_builder->getCollection();
        $this->done_collection[$id]                         = $this->backlog_item_builder->getCollection();
        $this->inconsistent_collection[$id]                 = $this->backlog_item_builder->getCollection();
        $artifacts                                          = [];
        $backlog_item_ids                                   = [];

        $items_collection = $backlog->getArtifacts($user);
        foreach ($items_collection as $artifact) {
            $artifacts[$artifact->getId()] = $artifact;
            $backlog_item_ids[]            = $artifact->getId();
        }

        $parents   = $this->getParentArtifacts($milestone, $user, $backlog_item_ids);
        $semantics = $this->getArtifactsSemantics($user, $milestone, $backlog_item_ids, $artifacts);
        $planned   = $this->getPlannedArtifactIds($user, $milestone);

        foreach ($artifacts as $artifact) {
            $this->pushItem(
                $milestone,
                $artifact,
                $user,
                $parents,
                $semantics,
                $redirect_to_self
            );
        }

        $this->open_and_closed_collection[$id]->setTotalAvaialableSize($items_collection->getTotalAvaialableSize());
        $this->open_closed_and_inconsistent_collection[$id]->setTotalAvaialableSize(
            $items_collection->getTotalAvaialableSize()
        );

        $this->initInconsistentItems($user, $milestone, $redirect_to_self, $planned);
    }

    /**
     * @return Artifact[]
     */
    private function getParentArtifacts(Planning_Milestone $milestone, PFUser $user, array $backlog_item_ids): array
    {
        $parents        = $this->artifact_factory->getParents($backlog_item_ids);
        $parent_tracker = $this->getParentTracker($parents);
        if ($parent_tracker) {
            $this->setParentItemName($milestone, $parent_tracker->getName());
            if ($this->userCanReadBacklogTitleField($user, $parent_tracker)) {
                $this->artifact_factory->setTitles($parents);
            } else {
                foreach ($parents as $artifact) {
                    $artifact->setTitle('');
                }
            }
        }

        return $parents;
    }

    private function setParentItemName(Planning_Milestone $milestone, string $name): void
    {
        if ($milestone->getArtifactId() === null) {
            return;
        }

        if (isset($this->todo_collection[$milestone->getArtifactId() ?? 0])) {
            $this->todo_collection[$milestone->getArtifactId() ?? 0]->setParentItemName($name);
        }

        if (isset($this->done_collection[$milestone->getArtifactId() ?? 0])) {
            $this->done_collection[$milestone->getArtifactId() ?? 0]->setParentItemName($name);
        }
    }

    private function getParentTracker(array $artifacts): ?Tracker
    {
        if (count($artifacts) > 0) {
            $artifact = current($artifacts);
            reset($artifacts);
            return $artifact->getTracker();
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getArtifactsSemantics(PFUser $user, Planning_Milestone $milestone, array $backlog_item_ids, array $artifacts): array
    {
        if (! $backlog_item_ids) {
            return [];
        }

        $semantics         = [];
        $allowed_semantics = [
            TrackerSemanticTitle::NAME,
            TrackerSemanticStatus::NAME,
        ];

        foreach ($this->dao->getArtifactsSemantics($backlog_item_ids, $allowed_semantics) as $row) {
            $artifact = $artifacts[$row['id']];
            $tracker  = $artifact->getTracker();

            $this->setTitleSemantic($user, $artifact, $tracker, $row, $semantics);
            $this->setStatusSemantic($user, $artifact, $tracker, $row, $semantics);
            $this->setInitialEffortSemantic($user, $artifact, $tracker, $row, $semantics);
        }

        return $semantics;
    }

    /**
     * @param array<int, array<string, mixed>> $semantics
     */
    private function setTitleSemantic(PFUser $user, Artifact $artifact, Tracker $tracker, array $row, array &$semantics): void
    {
        $semantics[$artifact->getId()][TrackerSemanticTitle::NAME] = '';
        if ($this->userCanReadBacklogTitleField($user, $tracker)) {
            $semantics[$artifact->getId()][TrackerSemanticTitle::NAME] = $row[TrackerSemanticTitle::NAME];
            $semantics[$artifact->getId()]['title_format']             = $row['title_format'];
        }
    }

    /**
     * @param array<int, array<string, mixed>> $semantics
     */
    private function setStatusSemantic(PFUser $user, Artifact $artifact, Tracker $tracker, array $row, array &$semantics): void
    {
        $semantics[$artifact->getId()][TrackerSemanticStatus::NAME] = '';
        if ($this->userCanReadBacklogStatusField($user, $tracker)) {
            $semantics[$artifact->getId()][TrackerSemanticStatus::NAME] = $row[TrackerSemanticStatus::NAME];
        }
    }

    /**
     * @param array<int, array<string, mixed>> $semantics
     */
    private function setInitialEffortSemantic(PFUser $user, Artifact $artifact, Tracker $tracker, array $row, array &$semantics): void
    {
        $semantics[$artifact->getId()][AgileDashBoard_Semantic_InitialEffort::NAME] = '';
        if ($this->userCanReadInitialEffortField($user, $tracker)) {
            $semantics[$artifact->getId()][AgileDashBoard_Semantic_InitialEffort::NAME] = $this->getSemanticEffortValue($user, $artifact);
        }
    }

    private function getSemanticEffortValue(PFUser $user, Artifact $artifact): float|string|null
    {
        $field = $this->getInitialEffortField($artifact->getTracker());
        if ($field === null) {
            return '';
        }

        assert($field instanceof Tracker_FormElement_IComputeValues);
        return $field->getComputedValue($user, $artifact);
    }

    /**
     * protected for testing purpose
     */
    protected function userCanReadBacklogTitleField(PFUser $user, Tracker $tracker): bool
    {
        if (! isset($this->cache_read_title[$tracker->getId()])) {
            $field = $this->retrieve_semantic_title_field->fromTracker($tracker);
            if (! $field) {
                $this->cache_read_title[$tracker->getId()] = false;
            } else {
                $this->cache_read_title[$tracker->getId()] = $field->userCanRead($user);
            }
        }
        return $this->cache_read_title[$tracker->getId()];
    }

    /**
     * protected for testing purpose
     */
    protected function userCanReadBacklogStatusField(PFUser $user, Tracker $tracker): bool
    {
        if (! isset($this->cache_read_status[$tracker->getId()])) {
            $this->cache_read_status[$tracker->getId()] = false;
            $field                                      = $this->retrieve_semantic_status_field->fromTracker($tracker);
            if ($field) {
                $this->cache_read_status[$tracker->getId()] = $field->userCanRead($user);
            }
        }
        return $this->cache_read_status[$tracker->getId()];
    }

    /**
     * protected for testing purpose
     */
    protected function userCanReadInitialEffortField(PFUser $user, Tracker $tracker): bool
    {
        if (! isset($this->cache_read_initial_effort[$tracker->getId()])) {
            $this->cache_read_initial_effort[$tracker->getId()] = false;
            $field                                              = $this->getInitialEffortField($tracker);
            if ($field && $field->userCanRead($user)) {
                $this->cache_read_initial_effort[$tracker->getId()] = true;
            }
        }
        return $this->cache_read_initial_effort[$tracker->getId()];
    }

    protected function getInitialEffortField(Tracker $tracker): ?TrackerField
    {
        return AgileDashBoard_Semantic_InitialEffort::load($tracker)->getField();
    }

    /**
     * @param array<string, mixed> $semantics_per_artifact
     */
    protected function setInitialEffort(IBacklogItem $backlog_item, array $semantics_per_artifact): void
    {
        if (isset($semantics_per_artifact[AgileDashBoard_Semantic_InitialEffort::NAME])) {
            $backlog_item->setInitialEffort((float) $semantics_per_artifact[AgileDashBoard_Semantic_InitialEffort::NAME]);
        }
    }

    /**
     * @param Artifact[] $parents
     */
    private function pushItem(
        Planning_Milestone $milestone,
        Artifact $artifact,
        PFUser $user,
        array $parents,
        array $semantics,
        ?string $redirect_to_self,
    ): void {
        $artifact_id = $artifact->getId();
        if (! isset($semantics[$artifact_id])) {
            return;
        }

        $artifact->setTitle($this->artifact_factory->getTitleFromRowAsText($semantics[$artifact_id]));

        $backlog_item = $this->backlog_item_builder->getItem($artifact, $redirect_to_self, false);

        if (isset($parents[$artifact_id]) && $parents[$artifact_id]->userCanView($user)) {
            $backlog_item->setParent($parents[$artifact_id]);
        }

        $this->pushItemInOpenCollections($milestone, $artifact, $semantics, $backlog_item, $user);
        $this->pushItemInDoneCollection($milestone, $artifact, $semantics, $backlog_item, $user);
        $this->open_and_closed_collection[$milestone->getArtifactId() ?? 0]->push($backlog_item);
        $this->open_closed_and_inconsistent_collection[$milestone->getArtifactId() ?? 0]->push($backlog_item);
    }

    private function pushItemInOpenCollections(
        Planning_Milestone $milestone,
        Artifact $artifact,
        array $semantics,
        IBacklogItem $backlog_item,
        PFUser $user,
    ): void {
        $artifact_id = $artifact->getId();

        if ($semantics[$artifact_id][TrackerSemanticStatus::NAME] == BacklogItemDao::STATUS_OPEN) {
            $backlog_item->setStatus($artifact->getStatus(), TrackerSemanticStatus::OPEN);

            $this->setInitialEffort($backlog_item, $semantics[$artifact_id]);
            $backlog_item->setRemainingEffort(
                $this->remaining_effort_value_retriever->getRemainingEffortValue($user, $backlog_item->getArtifact())
            );
            $this->todo_collection[$milestone->getArtifactId() ?? 0]->push($backlog_item);
        }
    }

    private function pushItemInDoneCollection(
        Planning_Milestone $milestone,
        Artifact $artifact,
        array $semantics,
        IBacklogItem $backlog_item,
        PFUser $user,
    ): void {
        $this->setInitialEffort($backlog_item, $semantics[$artifact->getId() ?? 0]);
        $backlog_item->setRemainingEffort(
            $this->remaining_effort_value_retriever->getRemainingEffortValue($user, $backlog_item->getArtifact())
        );

        if ($semantics[$artifact->getId()][TrackerSemanticStatus::NAME] != BacklogItemDao::STATUS_OPEN) {
            $backlog_item->setStatus($artifact->getStatus(), TrackerSemanticStatus::CLOSED);
            $this->done_collection[$milestone->getArtifactId() ?? 0]->push($backlog_item);
        }
    }

    private function getPlannedArtifactIds(PFUser $user, Planning_Milestone $milestone): array
    {
        $sub_milestone_ids = $this->getSubmilestoneIds($user, $milestone);
        if (! $sub_milestone_ids) {
            return [];
        }
        return $this->dao->getPlannedItemIds($sub_milestone_ids);
    }

    private function getSubmilestoneIds(PFUser $user, Planning_Milestone $milestone): array
    {
        return $this->milestone_factory->getSubMilestoneIds($user, $milestone);
    }

    private function filterOutAssignedBacklogItems(AgileDashboard_Milestone_Backlog_IBacklogItemCollection $collection, PFUser $user): AgileDashboard_Milestone_Backlog_IBacklogItemCollection
    {
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

    private function getBacklogItemsArtifactIds(AgileDashboard_Milestone_Backlog_IBacklogItemCollection $collection): array
    {
        $artifact_ids = [];

        foreach ($collection as $backlog_item) {
            $artifact_ids[] = $backlog_item->getArtifact()->getId();
        }

        return $artifact_ids;
    }

    private function removeLinkedItemsFromCollection(
        AgileDashboard_Milestone_Backlog_IBacklogItemCollection $collection,
        array $linked_item_artifacts_ids,
    ): AgileDashboard_Milestone_Backlog_IBacklogItemCollection {
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
    private function getPlanningMilestoneTrackerIds(array $plannings): array
    {
        $ids = [];
        foreach ($plannings as $planning) {
            $ids[] = $planning->getPlanningTrackerId();
        }

        return $ids;
    }

    private function initInconsistentItems(
        PFUser $user,
        Planning_Milestone $milestone,
        ?string $redirection_url,
        array $planned,
    ): void {
        foreach ($planned as $planned_artifact_id) {
            if (! $this->open_and_closed_collection[$milestone->getArtifactId() ?? 0]->containsId($planned_artifact_id)) {
                $artifact = $this->artifact_factory->getArtifactByIdUserCanView($user, $planned_artifact_id);

                if ($artifact) {
                    $item = $this->backlog_item_builder->getItem($artifact, $redirection_url, true);
                    $item->setStatus($artifact->getStatus(), $artifact->getSemanticStatusValue());
                    $this->inconsistent_collection[$milestone->getArtifactId() ?? 0]->push($item);
                    $this->open_closed_and_inconsistent_collection[$milestone->getArtifactId() ?? 0]->push($item);
                }
            }
        }
    }

    private function reorderOpenClosedAndInconsistentCollection(Planning_Milestone $milestone): AgileDashboard_Milestone_Backlog_IBacklogItemCollection
    {
        $order_artifacts = [];
        $indexed_rank    = [];
        $sort_collection = $this->backlog_item_builder->getCollection();

        $item_ids = $this->open_closed_and_inconsistent_collection[$milestone->getArtifactId() ?? 0]->getItemIds();
        if (count($item_ids) === 0) {
            return $sort_collection;
        }

        $ranks = $this->artifact_priority_dao->getGlobalRanks($item_ids);
        foreach ($ranks as $rank) {
            $indexed_rank[$rank['artifact_id']] = $rank['rank'];
        }

        foreach ($this->open_closed_and_inconsistent_collection[$milestone->getArtifactId() ?? 0] as $artifact) {
            $order_artifacts[$indexed_rank[$artifact->id()]] = $artifact;
        }

        ksort($order_artifacts);

        foreach ($order_artifacts as $artifact) {
            $sort_collection->push($artifact);
        }

        return $sort_collection;
    }

    public function getExplicitTopBacklogItems(
        PFUser $user,
        Planning_Milestone $milestone,
        ?string $redirect_to_self,
        int $limit,
        int $offset,
    ): AgileDashboard_Milestone_Backlog_IBacklogItemCollection {
        $rows                  = $this->artifacts_in_explicit_backlog_dao->getOpenTopBacklogItemsForProjectSortedByRank(
            $milestone->getGroupId(),
            $limit,
            $offset
        );
        $collection_total_size = $this->artifacts_in_explicit_backlog_dao->foundRows();

        $artifacts = [];
        foreach ($rows as $row) {
            $artifact = $this->artifact_factory->getArtifactById($row['artifact_id']);
            if ($artifact === null) {
                continue;
            }
            $artifacts[$artifact->getId()] = $artifact;
        }

        $collection = $this->buildTopBacklogCollection(
            $user,
            $milestone,
            $redirect_to_self,
            $artifacts
        );
        $collection->setTotalAvaialableSize($collection_total_size);

        return $collection;
    }

    /**
     * @param Artifact[] $open_artifacts
     */
    private function buildTopBacklogCollection(
        PFUser $user,
        Planning_Milestone $milestone,
        ?string $redirection_url,
        array $open_artifacts,
    ): AgileDashboard_Milestone_Backlog_IBacklogItemCollection {
        $permissions_on_items = $this->user_permission_on_artifacts_retriever->retrieveUserPermissionOnArtifacts(
            $user,
            $open_artifacts,
            \Tuleap\Tracker\Permission\ArtifactPermissionType::PERMISSION_VIEW,
        );

        $backlog_item_ids = [];
        foreach ($permissions_on_items->allowed as $artifact) {
            $backlog_item_ids[] = $artifact->getId();
        }

        $parents   = $this->getParentArtifacts($milestone, $user, $backlog_item_ids);
        $semantics = $this->getArtifactsSemantics($user, $milestone, $backlog_item_ids, $open_artifacts);

        if (empty($backlog_item_ids)) {
            $children = 0;
        } else {
            $children = $this->artifact_factory->getChildrenCount($backlog_item_ids);
        }

        $collection = $this->backlog_item_builder->getCollection();
        foreach ($permissions_on_items->allowed as $artifact) {
            $artifact_id = $artifact->getId();

            if (! isset($semantics[$artifact_id])) {
                continue;
            }

            $artifact->setTitle($this->artifact_factory->getTitleFromRowAsText($semantics[$artifact_id]));

            $backlog_item = $this->backlog_item_builder->getItem($artifact, $redirection_url, false);
            $backlog_item->setStatus($artifact->getStatus(), TrackerSemanticStatus::OPEN);
            if (isset($parents[$artifact_id]) && $parents[$artifact_id]->userCanView($user)) {
                $backlog_item->setParent($parents[$artifact_id]);
            }
            if (isset($children[$artifact_id])) {
                $backlog_item->setHasChildren($children[$artifact_id] > 0);
            }

            $this->setInitialEffort($backlog_item, $semantics[$artifact_id]);
            $backlog_item->setRemainingEffort(
                $this->remaining_effort_value_retriever->getRemainingEffortValue($user, $backlog_item->getArtifact())
            );

            $collection->push($backlog_item);
        }

        return $collection;
    }
}
