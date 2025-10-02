<?php
/**
 * Copyright Enalean (c) 2013 - Present. All rights reserved.
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

namespace Tuleap\AgileDashboard\Milestone\Backlog;

use AgileDashboard_Milestone_Backlog_DescendantItemsCollection;
use AgileDashboard_Milestone_Backlog_DescendantItemsFinder;
use PFUser;
use Planning_Milestone;
use Planning_VirtualTopMilestone;
use Tracker_ArtifactFactory;
use Tuleap\AgileDashboard\BacklogItemDao;
use Tuleap\Tracker\Tracker;

/**
 * I retrieve the content of the backlog
 */
class MilestoneBacklog
{
    private int $content_size = 0;

    private int $backlog_size = 0;

    /** @var Tracker[] */
    private array $backlogitem_trackers;
    private AgileDashboard_Milestone_Backlog_DescendantItemsFinder $items_finder;

    /**
     * @psalm-param Tracker[] $descendant_trackers
     */
    public function __construct(
        readonly Tracker_ArtifactFactory $artifact_factory,
        private readonly Planning_Milestone $milestone,
        readonly array $item_names,
        private readonly array $descendant_trackers,
        readonly BacklogItemDao $item_dao,
        readonly \Tuleap\Tracker\Artifact\Dao\ArtifactDao $artifact_dao,
        private readonly ?int $limit = null,
        private readonly ?int $offset = null,
    ) {
        $this->backlogitem_trackers = $item_names;

        $this->items_finder = new AgileDashboard_Milestone_Backlog_DescendantItemsFinder(
            $item_dao,
            $artifact_dao,
            $artifact_factory,
            $milestone,
            $this->getDescendantTrackerIds()
        );
    }

    /**
     * @return Tracker[]
     */
    public function getDescendantTrackers(): array
    {
        return $this->descendant_trackers;
    }

    public function getDescendantTrackerIds(): array
    {
        $ids = [];
        foreach ($this->descendant_trackers as $tracker) {
            $ids[] = $tracker->getId();
        }

        return $ids;
    }

    public function getTotalContentSize(): int
    {
        return $this->content_size;
    }

    public function getTotalBacklogSize(): int
    {
        return $this->backlog_size;
    }

    /** @return Tracker[] */
    public function getItemTrackers(): array
    {
        return $this->backlogitem_trackers;
    }

    public function getMilestoneBacklogArtifactsTracker(): array
    {
        return $this->getDescendantTrackers();
    }

    public function getArtifacts(PFUser $user): AgileDashboard_Milestone_Backlog_DescendantItemsCollection
    {
        if ($this->milestone instanceof Planning_VirtualTopMilestone) {
            if ($this->limit !== null && $this->offset !== null) {
                $artifacts_collection = $this->items_finder->getAllTopMilestoneContentItemsWithLimitAndOffset($user, $this->limit, $this->offset);
            } else {
                $artifacts_collection = $this->items_finder->getAllTopMilestoneContentItems($user);
            }
        } else {
            if ($this->limit !== null || $this->offset !== null) {
                $artifacts_collection = $this->items_finder->getAllMilestoneContentItemsWithLimitAndOffset($user, $this->limit, $this->offset);
            } else {
                $artifacts_collection = $this->items_finder->getAllUIMilestoneBacklogItems($user);
            }
        }

        return $artifacts_collection;
    }

    public function getOpenUnplannedArtifacts(PFUser $user, array $sub_milestone_ids): AgileDashboard_Milestone_Backlog_DescendantItemsCollection
    {
        if ($this->milestone instanceof Planning_VirtualTopMilestone) {
            if ($this->limit !== null && $this->offset !== null) {
                $artifacts_collection = $this->items_finder->getTopMilestoneOpenUnplannedBacklogItemsWithLimitAndOffset(
                    $user,
                    $this->limit,
                    $this->offset
                );
            } else {
                $artifacts_collection = $this->items_finder->getAllTopMilestoneOpenUnplannedBacklogItems($user);
            }
        } else {
            if ($this->limit !== null || $this->offset !== null) {
                $artifacts_collection = $this->items_finder->getMilestoneOpenUnplannedBacklogItemsWithLimitAndOffset($user, $sub_milestone_ids, $this->limit, $this->offset);
            } else {
                $artifacts_collection = $this->items_finder->getAllMilestoneOpenUnplannedBacklogItems($user, $sub_milestone_ids);
            }
        }

        return $artifacts_collection;
    }

    public function getOpenClosedUnplannedArtifacts(PFUser $user, array $sub_milestone_ids): AgileDashboard_Milestone_Backlog_DescendantItemsCollection
    {
        if ($this->milestone instanceof Planning_VirtualTopMilestone) {
            if ($this->limit === null && $this->offset === null) {
                return $this->items_finder->getAllTopMilestoneUnplannedBacklogItems($user);
            }

            return $this->items_finder->getTopMilestoneOpenClosedUnplannedBacklogItemsWithLimitAndOffset(
                $user,
                $this->limit,
                $this->offset
            );
        }

        if ($this->limit !== null || $this->offset !== null) {
            return $this->items_finder->getMilestoneOpenClosedUnplannedBacklogItemsWithLimitAndOffset($user, $sub_milestone_ids, $this->limit, $this->offset);
        }

        return $this->items_finder->getAllMilestoneUnplannedBacklogItems($user, $sub_milestone_ids);
    }

    public function getUnplannedArtifacts(PFUser $user, array $sub_milestone_ids): AgileDashboard_Milestone_Backlog_DescendantItemsCollection
    {
        if ($this->milestone instanceof Planning_VirtualTopMilestone) {
            if ($this->limit !== null && $this->offset !== null) {
                $artifacts_collection = $this->items_finder->getTopMilestoneUnplannedBacklogItemsWithLimitAndOffset($user, $this->limit, $this->offset);
            } else {
                $artifacts_collection = $this->items_finder->getAllTopMilestoneUnplannedBacklogItems($user);
            }
        } else {
            if ($this->limit !== null || $this->offset !== null) {
                $artifacts_collection = $this->items_finder->getMilestoneUnplannedBacklogItemsWithLimitAndOffset($user, $sub_milestone_ids, $this->limit, $this->offset);
            } else {
                $artifacts_collection = $this->items_finder->getAllMilestoneUnplannedBacklogItems($user, $sub_milestone_ids);
            }
        }

        return $artifacts_collection;
    }
}
