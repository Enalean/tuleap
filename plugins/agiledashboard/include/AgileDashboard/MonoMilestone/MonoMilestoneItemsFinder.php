<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\MonoMilestone;

use AgileDashboard_Milestone_Backlog_DescendantItemsCollection;
use PFUser;
use Tracker_ArtifactFactory;
use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface;

class MonoMilestoneItemsFinder
{
    /**
     * @var MonoMilestoneBacklogItemDao
     */
    private $backlog_item_dao;
    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    public function __construct(
        MonoMilestoneBacklogItemDao $backlog_item_dao,
        Tracker_ArtifactFactory $artifact_factory
    ) {
        $this->backlog_item_dao = $backlog_item_dao;
        $this->artifact_factory = $artifact_factory;
    }

    public function getTopMilestoneOpenUnplannedBacklogItemsWithLimitAndOffset(
        PFUser $user,
        array $backlog_tracker_ids,
        ?int $limit,
        ?int $offset
    ): AgileDashboard_Milestone_Backlog_DescendantItemsCollection {
        $result = $this->backlog_item_dao->getTopBacklogArtifactsWithLimitAndOffset(
            $backlog_tracker_ids,
            $limit,
            $offset
        );

        return $this->getItemsForUser($user, $result, $this->backlog_item_dao->foundRows());
    }

    public function getTopMilestonesOpenClosedUnplannedBacklogItemsWithLimitAndOffset(
        PFUser $user,
        array $backlog_tracker_ids,
        ?int $limit,
        ?int $offset
    ): AgileDashboard_Milestone_Backlog_DescendantItemsCollection {
        $result = $this->backlog_item_dao->getTopBacklogOpenClosedArtifactsWithLimitAndOffset(
            $backlog_tracker_ids,
            $limit,
            $offset
        );

        return $this->getItemsForUser($user, $result, $this->backlog_item_dao->foundRows());
    }

    /**
     * @param false|LegacyDataAccessResultInterface $result
     * @return AgileDashboard_Milestone_Backlog_DescendantItemsCollection
     */
    private function getItemsForUser(PFUser $user, $result, $found_rows)
    {
        $items = new AgileDashboard_Milestone_Backlog_DescendantItemsCollection();

        if ($result === false) {
            return $items;
        }

        foreach ($result as $row) {
            $item = $this->artifact_factory->getInstanceFromRow($row);
            if ($item->userCanView($user)) {
                $items->push($item);
            }
        }

        $items->setTotalAvaialableSize($found_rows);

        return $items;
    }
}
