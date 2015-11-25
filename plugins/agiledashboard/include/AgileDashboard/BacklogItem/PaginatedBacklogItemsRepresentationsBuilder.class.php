<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

use Tuleap\AgileDashboard\REST\v1\BacklogItemRepresentationFactory;

class AgileDashboard_BacklogItem_PaginatedBacklogItemsRepresentationsBuilder {

    /** @var BacklogItemRepresentationFactory */
    private $backlog_item_representation_factory;

    /** @var AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory */
    private $backlog_item_collection_factory;

    /** @var AgileDashboard_Milestone_Backlog_BacklogStrategyFactory */
    private $backlog_strategy_factory;


    public function __construct(
        BacklogItemRepresentationFactory $backlog_item_representation_factory,
        AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory $backlog_item_collection_factory,
        AgileDashboard_Milestone_Backlog_BacklogStrategyFactory $backlog_strategy_factory
    ) {
        $this->backlog_item_representation_factory = $backlog_item_representation_factory;
        $this->backlog_item_collection_factory     = $backlog_item_collection_factory;
        $this->backlog_strategy_factory            = $backlog_strategy_factory;
    }

    /**
     * @return AgileDashboard_BacklogItem_PaginatedBacklogItemsRepresentations;
     */
    public function getPaginatedBacklogItemsRepresentationsForMilestone(PFUser $user, Planning_Milestone $milestone, $limit, $offset) {
        $strategy = $this->backlog_strategy_factory->getBacklogStrategy($milestone, $limit, $offset);

        return $this->getBacklogItemsRepresentations($user, $milestone, $strategy);
    }

    /**
     * @return AgileDashboard_BacklogItem_PaginatedBacklogItemsRepresentations;
     */
    public function getPaginatedBacklogItemsRepresentationsForTopMilestone(PFUser $user, Planning_Milestone $top_milestone, $limit, $offset) {
        $strategy = $this->backlog_strategy_factory->getSelfBacklogStrategy($top_milestone, $limit, $offset);

        return $this->getBacklogItemsRepresentations($user, $top_milestone, $strategy);
    }

    private function getBacklogItemsRepresentations(PFUser $user, Planning_Milestone $milestone, $strategy) {
        $backlog_items                 = $this->getMilestoneBacklogItems($user, $milestone, $strategy);
        $backlog_items_representations = array();

        foreach ($backlog_items as $backlog_item) {
            $backlog_items_representations[] = $this->backlog_item_representation_factory->createBacklogItemRepresentation($backlog_item);
        }

        return new AgileDashboard_BacklogItem_PaginatedBacklogItemsRepresentations($backlog_items_representations, $backlog_items->getTotalAvaialableSize());
    }

    private function getMilestoneBacklogItems(PFUser $user, $milestone, $strategy) {
        return $this->backlog_item_collection_factory->getUnplannedOpenCollection($user, $milestone, $strategy, false);
    }

}
