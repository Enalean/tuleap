<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\REST\v1;

use AgileDashboard_BacklogItemDao;
use AgileDashboard_Milestone_Backlog_BacklogFactory;
use AgileDashboard_Milestone_Backlog_BacklogItemBuilder;
use AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory;
use AgileDashboard_Milestone_Backlog_IBacklogItemCollection;
use Planning_MilestoneFactory;
use PlanningFactory;
use Tracker_Artifact_PriorityDao;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\RemainingEffortValueRetriever;

class ContentForMiletoneProvider
{
    /**
     * @var AgileDashboard_Milestone_Backlog_BacklogFactory
     */
    private $backlog_factory;
    /**
     * @var AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory
     */
    private $backlog_item_collection_factory;

    public function __construct(
        AgileDashboard_Milestone_Backlog_BacklogFactory $backlog_factory,
        AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory $backlog_item_collection_factory,
    ) {
        $this->backlog_factory                 = $backlog_factory;
        $this->backlog_item_collection_factory = $backlog_item_collection_factory;
    }

    public static function build(Planning_MilestoneFactory $milestone_factory): self
    {
        $planning_factory         = PlanningFactory::build();
        $tracker_artifact_factory = Tracker_ArtifactFactory::instance();

        return new self(
            new AgileDashboard_Milestone_Backlog_BacklogFactory(
                new AgileDashboard_BacklogItemDao(),
                $tracker_artifact_factory,
                $planning_factory,
            ),
            new AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory(
                new AgileDashboard_BacklogItemDao(),
                $tracker_artifact_factory,
                $milestone_factory,
                $planning_factory,
                new AgileDashboard_Milestone_Backlog_BacklogItemBuilder(),
                new RemainingEffortValueRetriever(
                    Tracker_FormElementFactory::instance()
                ),
                new ArtifactsInExplicitBacklogDao(),
                new Tracker_Artifact_PriorityDao()
            )
        );
    }

    public function getContent(
        \Planning_Milestone $milestone,
        \PFUser $user,
        int $limit,
        int $offset,
    ): AgileDashboard_Milestone_Backlog_IBacklogItemCollection {
        $backlog = $this->backlog_factory->getSelfBacklog($milestone, $limit, $offset);

        return $this->backlog_item_collection_factory->getOpenAndClosedCollection(
            $user,
            $milestone,
            $backlog,
            ''
        );
    }
}
