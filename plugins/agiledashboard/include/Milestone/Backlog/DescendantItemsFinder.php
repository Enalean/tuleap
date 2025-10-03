<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

use PFUser;
use Planning_Milestone;
use Tracker_ArtifactFactory;
use Tracker_HierarchyFactory;
use Tuleap\AgileDashboard\BacklogItemDao;

class DescendantItemsFinder
{
    private int $milestone_id;
    private array $backlog_tracker_ids;

    public function __construct(
        private readonly BacklogItemDao $item_dao,
        private readonly \Tuleap\Tracker\Artifact\Dao\ArtifactDao $artifact_dao,
        private readonly Tracker_ArtifactFactory $artifact_factory,
        Planning_Milestone $milestone,
        private readonly array $descendant_tracker_ids,
    ) {
        $this->milestone_id        = $milestone->getArtifactId() ?? 0;
        $this->backlog_tracker_ids = $milestone->getPlanning()->getBacklogTrackersIds();
    }

    public function getAllTopMilestoneContentItems(PFUser $user): DescendantItemsCollection
    {
        $result = $this->item_dao->getTopBacklogArtifacts($this->backlog_tracker_ids);

        return $this->getItemsForUser($user, $result, $this->item_dao->foundRows());
    }

    public function getAllTopMilestoneContentItemsWithLimitAndOffset(PFUser $user, int $limit, int $offset): DescendantItemsCollection
    {
        $result = $this->item_dao->getTopBacklogArtifactsWithLimitAndOffset(
            $this->backlog_tracker_ids,
            $limit,
            $offset
        );

        return $this->getItemsForUser($user, $result, $this->item_dao->foundRows());
    }

    public function getAllUIMilestoneBacklogItems(PFUser $user): DescendantItemsCollection
    {
        $result = $this->artifact_dao->getLinkedArtifactsOfTrackersConcatenatedToCustomList(
            $this->milestone_id,
            $this->descendant_tracker_ids,
            $this->getDescendantPlannifiableItems()
        );

        return $this->getItemsForUser($user, $result, $this->artifact_dao->foundRows());
    }

    public function getAllMilestoneContentItemsWithLimitAndOffset(PFUser $user, ?int $limit, ?int $offset): DescendantItemsCollection
    {
        $result = $this->artifact_dao->getLinkedArtifactsOfTrackersWithLimitAndOffset(
            $this->milestone_id,
            $this->backlog_tracker_ids,
            $limit,
            $offset
        );

        return $this->getItemsForUser($user, $result, $this->artifact_dao->foundRows());
    }

    public function getAllTopMilestoneOpenUnplannedBacklogItems(PFUser $user): DescendantItemsCollection
    {
        $result = $this->item_dao->getOpenUnplannedTopBacklogArtifacts(
            $this->backlog_tracker_ids
        );

        return $this->getItemsForUser($user, $result, $this->item_dao->foundRows());
    }

    public function getAllTopMilestoneUnplannedBacklogItems(PFUser $user): DescendantItemsCollection
    {
        $result = $this->item_dao->getUnplannedTopBacklogArtifacts($this->backlog_tracker_ids);

        return $this->getItemsForUser($user, $result, $this->item_dao->foundRows());
    }

    public function getTopMilestoneOpenUnplannedBacklogItemsWithLimitAndOffset(
        PFUser $user,
        int $limit,
        int $offset,
    ): DescendantItemsCollection {
        $result = $this->item_dao->getOpenUnplannedTopBacklogArtifactsWithLimitAndOffset(
            $this->descendant_tracker_ids,
            $limit,
            $offset
        );

        return $this->getItemsForUser($user, $result, $this->item_dao->foundRows());
    }

    public function getTopMilestoneOpenClosedUnplannedBacklogItemsWithLimitAndOffset(
        PFUser $user,
        ?int $limit,
        ?int $offset,
    ): DescendantItemsCollection {
        $result = $this->item_dao->getOpenClosedUnplannedTopBacklogArtifactsWithLimitAndOffset(
            $this->descendant_tracker_ids,
            $limit,
            $offset
        );

        return $this->getItemsForUser($user, $result, $this->item_dao->foundRows());
    }

    public function getTopMilestoneUnplannedBacklogItemsWithLimitAndOffset(PFUser $user, int $limit, int $offset): DescendantItemsCollection
    {
        $result = $this->item_dao->getUnplannedTopBacklogArtifactsWithLimitAndOffset(
            $this->descendant_tracker_ids,
            $limit,
            $offset
        );

        return $this->getItemsForUser($user, $result, $this->item_dao->foundRows());
    }

    public function getAllMilestoneOpenUnplannedBacklogItems(PFUser $user, array $sub_milestone_ids): DescendantItemsCollection
    {
        $result = $this->artifact_dao->getLinkedOpenArtifactsOfTrackersNotLinkedToOthers(
            $this->milestone_id,
            $this->descendant_tracker_ids,
            $sub_milestone_ids,
            $this->getDescendantPlannifiableItems()
        );

        return $this->getItemsForUser($user, $result, $this->artifact_dao->foundRows());
    }

    public function getAllMilestoneUnplannedBacklogItems(PFUser $user, array $sub_milestone_ids): DescendantItemsCollection
    {
        $result = $this->artifact_dao->getLinkedArtifactsOfTrackersNotLinkedToOthers(
            $this->milestone_id,
            $this->descendant_tracker_ids,
            $sub_milestone_ids,
            $this->getDescendantPlannifiableItems()
        );

        return $this->getItemsForUser($user, $result, $this->artifact_dao->foundRows());
    }

    public function getMilestoneOpenUnplannedBacklogItemsWithLimitAndOffset(PFUser $user, array $sub_milestone_ids, ?int $limit, ?int $offset): DescendantItemsCollection
    {
        $result = $this->artifact_dao->getLinkedOpenArtifactsOfTrackersNotLinkedToOthersWithLimitAndOffset(
            $this->milestone_id,
            $this->descendant_tracker_ids,
            $sub_milestone_ids,
            $this->getDescendantPlannifiableItems(),
            $limit,
            $offset
        );

        return $this->getItemsForUser($user, $result, $this->artifact_dao->foundRows());
    }

    public function getMilestoneUnplannedBacklogItemsWithLimitAndOffset(
        PFUser $user,
        array $sub_milestone_ids,
        ?int $limit,
        ?int $offset,
    ): DescendantItemsCollection {
        $result = $this->artifact_dao->getLinkedArtifactsOfTrackersNotLinkedToOthersWithLimitAndOffset(
            $this->milestone_id,
            $this->descendant_tracker_ids,
            $sub_milestone_ids,
            $this->getDescendantPlannifiableItems(),
            $limit,
            $offset
        );

        return $this->getItemsForUser($user, $result, $this->artifact_dao->foundRows());
    }

    public function getMilestoneOpenClosedUnplannedBacklogItemsWithLimitAndOffset(
        PFUser $user,
        array $sub_milestone_ids,
        ?int $limit,
        ?int $offset,
    ): DescendantItemsCollection {
        $result = $this->artifact_dao->getLinkedOpenClosedArtifactsOfTrackersNotLinkedToOthersWithLimitAndOffset(
            $this->milestone_id,
            $this->descendant_tracker_ids,
            $sub_milestone_ids,
            $this->getDescendantPlannifiableItems(),
            $limit,
            $offset
        );

        return $this->getItemsForUser($user, $result, $this->artifact_dao->foundRows());
    }

    private function getItemsForUser(PFUser $user, array $result, int $found_rows): DescendantItemsCollection
    {
        $items = new DescendantItemsCollection();

        foreach ($result as $row) {
            $item = $this->artifact_factory->getInstanceFromRow($row);
            if ($item->userCanView($user)) {
                $items->push($item);
            }
        }

        $items->setTotalAvaialableSize($found_rows);

        return $items;
    }

    private function getDescendantPlannifiableItems(): array
    {
        $item_list = [];
        foreach ($this->getHierarchiesToSearchIn() as $hierarchy) {
            reset($hierarchy);

            $ids_of_parent_items_to_search_in = $this->getIdsOfLinkedParentItemsOfOneTrackerType(current($hierarchy));
            $tracker_id                       = next($hierarchy);
            $i                                = 0;

            while ($ids_of_parent_items_to_search_in && $i < count($hierarchy)) {
                $children = $this->getIdsOfChildItemsOfOneTrackerType(
                    $ids_of_parent_items_to_search_in,
                    $tracker_id
                );

                if ($this->canChildrenBePlanned($children, $tracker_id)) {
                    $item_list = array_merge($item_list, explode(',', $children));
                    break;
                }

                $tracker_id                       = next($hierarchy);
                $ids_of_parent_items_to_search_in = $children;
                $i++;
            }
        }

        return $item_list;
    }

    private function getHierarchiesToSearchIn(): array
    {
        $hierarchy_factory                          = Tracker_HierarchyFactory::instance();
        $types_in_submilestone_but_not_in_milestone = array_diff($this->descendant_tracker_ids, $this->backlog_tracker_ids);

        $hierarchies = [];
        foreach ($types_in_submilestone_but_not_in_milestone as $tracker_id) {
            $submilestone_backlog_tracker_hierarchy_tree = array_reverse($hierarchy_factory->getUpwardsHierarchyForTracker($tracker_id));

            $this->extractRelevantSubmilestoneBacklogTrackerHierarchyTreeSection($submilestone_backlog_tracker_hierarchy_tree);
            if (! $submilestone_backlog_tracker_hierarchy_tree) {
                continue;
            }

            $hierarchies[] = $submilestone_backlog_tracker_hierarchy_tree;
        }

        return $hierarchies;
    }

    /**
     * The tree is already in top to bottom order.
     * We only want the section of the tree that goes from a
     * tracker in a milestone to a tracker in a submilestone.
     *
     */
    private function extractRelevantSubmilestoneBacklogTrackerHierarchyTreeSection(array &$submilestone_tracker_hierarchy_tree): void
    {
        foreach ($submilestone_tracker_hierarchy_tree as $key => $value) {
            if (in_array($value, $this->backlog_tracker_ids)) {
                break;
            } else {
                unset($submilestone_tracker_hierarchy_tree[$key]);
            }
        }

        $submilestone_tracker_hierarchy_tree = array_values($submilestone_tracker_hierarchy_tree);
    }

    private function getIdsOfLinkedParentItemsOfOneTrackerType(int $tracker_id): string
    {
        $search = $this->artifact_dao->getLinkedArtifactsOfTrackerTypeAsString(
            $this->milestone_id,
            $tracker_id
        );

        return (string) $search['artifact_ids'];
    }

    /**
     * get all children of a given type of any of the parent items
     */
    private function getIdsOfChildItemsOfOneTrackerType(string $ids_of_parent_items_to_search_in, int $tracker_id): string
    {
        $search = $this->artifact_dao->getLinkedArtifactsOfArtifactsOfTrackerTypeAsString(
            $ids_of_parent_items_to_search_in,
            $tracker_id
        );

        return (string) $search['artifact_ids'];
    }

    private function canChildrenBePlanned(string $children, int $tracker_id): bool
    {
        return $children && in_array($tracker_id, $this->descendant_tracker_ids);
    }
}
