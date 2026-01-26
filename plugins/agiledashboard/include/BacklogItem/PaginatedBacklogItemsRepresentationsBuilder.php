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

use PFUser;
use Planning_Milestone;
use Planning_VirtualTopMilestone;
use Tuleap\AgileDashboard\AgileDashboard\REST\v1\Milestone\MilestoneBacklogInclude;
use Tuleap\AgileDashboard\AgileDashboard\REST\v1\Milestone\MilestoneBacklogRequest;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\AgileDashboard\Milestone\Backlog\BacklogItemCollectionFactory;
use Tuleap\AgileDashboard\Milestone\Backlog\IBacklogItemCollection;
use Tuleap\AgileDashboard\Milestone\Backlog\MilestoneBacklog;
use Tuleap\AgileDashboard\Milestone\Backlog\MilestoneBacklogFactory;
use Tuleap\AgileDashboard\Milestone\Criterion\Status\StatusOpen;
use Tuleap\AgileDashboard\REST\v1\BacklogItemRepresentationFactory;

final readonly class PaginatedBacklogItemsRepresentationsBuilder
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
        MilestoneBacklogRequest $request,
    ): PaginatedBacklogItemsRepresentations {
        $backlog = $this->backlog_factory->getBacklog($user, $request->milestone, $request->limit, $request->offset);

        return $this->getBacklogItemsRepresentations($user, $backlog, $request);
    }

    public function getPaginatedBacklogItemsRepresentationsForTopMilestone(
        PFUser $user,
        Planning_Milestone $top_milestone,
        int $limit,
        int $offset,
    ): PaginatedBacklogItemsRepresentations {
        $backlog = $this->backlog_factory->getSelfBacklog($top_milestone, $limit, $offset);

        return $this->getBacklogItemsRepresentations(
            $user,
            $backlog,
            new MilestoneBacklogRequest(
                $top_milestone,
                new StatusOpen(),
                MilestoneBacklogInclude::NOT_PLANNED,
                $limit,
                $offset,
            ),
        );
    }

    private function getBacklogItemsRepresentations(
        PFUser $user,
        MilestoneBacklog $backlog,
        MilestoneBacklogRequest $request,
    ): PaginatedBacklogItemsRepresentations {
        $backlog_items                 = $this->getMilestoneBacklogItems($user, $backlog, $request);
        $backlog_items_representations = [];

        foreach ($backlog_items as $backlog_item) {
            $backlog_items_representations[] = $this->backlog_item_representation_factory->createBacklogItemRepresentation($backlog_item);
        }

        return new PaginatedBacklogItemsRepresentations($backlog_items_representations, $backlog_items->getTotalAvaialableSize());
    }

    private function getMilestoneBacklogItems(
        PFUser $user,
        MilestoneBacklog $backlog,
        MilestoneBacklogRequest $request,
    ): IBacklogItemCollection {
        if (
            $request->milestone instanceof Planning_VirtualTopMilestone &&
            $this->explicit_backlog_dao->isProjectUsingExplicitBacklog($request->milestone->getGroupId())
        ) {
            return $this->backlog_item_collection_factory->getExplicitTopBacklogItems(
                $user,
                $request->milestone,
                null,
                $request->limit,
                $request->offset
            );
        }

        if ($request->include === MilestoneBacklogInclude::PLANNED_AND_NOT_PLANNED) {
            return $this->backlog_item_collection_factory->getPlannableCollection($user, $request->milestone, $backlog, null);
        }

        if ($request->criterion instanceof StatusOpen) {
            return $this->backlog_item_collection_factory->getUnplannedOpenCollection($user, $request->milestone, $backlog, null);
        }

        return $this->backlog_item_collection_factory->getOpenClosedUnplannedCollection($user, $request->milestone, $backlog, null);
    }
}
