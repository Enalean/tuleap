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

declare(strict_types=1);

namespace Tuleap\AgileDashboard\BacklogItem;

use AgileDashboard_Milestone_Backlog_IBacklogItemCollection;
use PFUser;
use Planning_Milestone;
use Planning_VirtualTopMilestone;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\AgileDashboard\Milestone\Backlog\BacklogItemCollectionFactory;
use Tuleap\AgileDashboard\Milestone\Backlog\MilestoneBacklog;
use Tuleap\AgileDashboard\Milestone\Backlog\MilestoneBacklogFactory;
use Tuleap\AgileDashboard\Milestone\Criterion\Status\ISearchOnStatus;
use Tuleap\AgileDashboard\Milestone\Criterion\Status\StatusOpen;
use Tuleap\AgileDashboard\REST\v1\BacklogItemRepresentationFactory;

final readonly class AgileDashboard_BacklogItem_PaginatedBacklogItemsRepresentationsBuilder //phpcs:ignore Squiz.Classes.ValidClassName.NotPascalCase
{
    public function __construct(
        private BacklogItemRepresentationFactory $backlog_item_representation_factory,
        private BacklogItemCollectionFactory $backlog_item_collection_factory,
        private MilestoneBacklogFactory $backlog_factory,
        private ExplicitBacklogDao $explicit_backlog_dao,
    ) {
    }

    public function getPaginatedBacklogItemsRepresentationsForMilestone(
        PFUser $user,
        Planning_Milestone $milestone,
        ISearchOnStatus $criterion,
        int $limit,
        int $offset,
    ): AgileDashboard_BacklogItem_PaginatedBacklogItemsRepresentations {
        $backlog = $this->backlog_factory->getBacklog($user, $milestone, $limit, $offset);

        return $this->getBacklogItemsRepresentations($user, $milestone, $backlog, $criterion, $limit, $offset);
    }

    public function getPaginatedBacklogItemsRepresentationsForTopMilestone(
        PFUser $user,
        Planning_Milestone $top_milestone,
        int $limit,
        int $offset,
    ): AgileDashboard_BacklogItem_PaginatedBacklogItemsRepresentations {
        $backlog = $this->backlog_factory->getSelfBacklog($top_milestone, $limit, $offset);

        return $this->getBacklogItemsRepresentations($user, $top_milestone, $backlog, new StatusOpen(), $limit, $offset);
    }

    private function getBacklogItemsRepresentations(
        PFUser $user,
        Planning_Milestone $milestone,
        MilestoneBacklog $backlog,
        ISearchOnStatus $criterion,
        int $limit,
        int $offset,
    ): AgileDashboard_BacklogItem_PaginatedBacklogItemsRepresentations {
        $backlog_items                 = $this->getMilestoneBacklogItems($user, $milestone, $backlog, $criterion, $limit, $offset);
        $backlog_items_representations = [];

        foreach ($backlog_items as $backlog_item) {
            $backlog_items_representations[] = $this->backlog_item_representation_factory->createBacklogItemRepresentation($backlog_item);
        }

        return new AgileDashboard_BacklogItem_PaginatedBacklogItemsRepresentations($backlog_items_representations, $backlog_items->getTotalAvaialableSize());
    }

    private function getMilestoneBacklogItems(
        PFUser $user,
        Planning_Milestone $milestone,
        MilestoneBacklog $backlog,
        ISearchOnStatus $criterion,
        int $limit,
        int $offset,
    ): AgileDashboard_Milestone_Backlog_IBacklogItemCollection {
        if (
            $milestone instanceof Planning_VirtualTopMilestone &&
            $this->explicit_backlog_dao->isProjectUsingExplicitBacklog($milestone->getGroupId())
        ) {
            return $this->backlog_item_collection_factory->getExplicitTopBacklogItems(
                $user,
                $milestone,
                null,
                $limit,
                $offset
            );
        }

        if ($criterion instanceof StatusOpen) {
            return $this->backlog_item_collection_factory->getUnplannedOpenCollection($user, $milestone, $backlog, null);
        }

        return $this->backlog_item_collection_factory->getOpenClosedUnplannedCollection($user, $milestone, $backlog, null);
    }
}
