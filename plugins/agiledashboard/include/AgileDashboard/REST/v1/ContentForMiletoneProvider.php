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

use Planning_MilestoneFactory;
use PlanningFactory;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tuleap\AgileDashboard\BacklogItemDao;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\Milestone\Backlog\BacklogItemBuilder;
use Tuleap\AgileDashboard\Milestone\Backlog\BacklogItemCollectionFactory;
use Tuleap\AgileDashboard\Milestone\Backlog\IBacklogItemCollection;
use Tuleap\AgileDashboard\Milestone\Backlog\MilestoneBacklogFactory;
use Tuleap\AgileDashboard\RemainingEffortValueRetriever;
use Tuleap\Tracker\Artifact\Dao\PriorityDao;
use Tuleap\Tracker\Semantic\Status\CachedSemanticStatusFieldRetriever;
use Tuleap\Tracker\Semantic\Title\CachedSemanticTitleFieldRetriever;

class ContentForMiletoneProvider
{
    public function __construct(
        private readonly MilestoneBacklogFactory $backlog_factory,
        private readonly BacklogItemCollectionFactory $backlog_item_collection_factory,
    ) {
    }

    public static function build(Planning_MilestoneFactory $milestone_factory): self
    {
        $planning_factory         = PlanningFactory::build();
        $tracker_artifact_factory = Tracker_ArtifactFactory::instance();

        return new self(
            new MilestoneBacklogFactory(
                new BacklogItemDao(),
                $tracker_artifact_factory,
                $planning_factory,
                new \Tuleap\Tracker\Artifact\Dao\ArtifactDao(),
            ),
            new BacklogItemCollectionFactory(
                new BacklogItemDao(),
                $tracker_artifact_factory,
                $milestone_factory,
                $planning_factory,
                new BacklogItemBuilder(),
                new RemainingEffortValueRetriever(
                    Tracker_FormElementFactory::instance()
                ),
                new ArtifactsInExplicitBacklogDao(),
                new PriorityDao(),
                \Tuleap\Tracker\Permission\TrackersPermissionsRetriever::build(),
                CachedSemanticTitleFieldRetriever::instance(),
                CachedSemanticStatusFieldRetriever::instance(),
            )
        );
    }

    public function getContent(
        \Planning_Milestone $milestone,
        \PFUser $user,
        int $limit,
        int $offset,
    ): IBacklogItemCollection {
        $backlog = $this->backlog_factory->getSelfBacklog($milestone, $limit, $offset);

        return $this->backlog_item_collection_factory->getOpenAndClosedCollection(
            $user,
            $milestone,
            $backlog,
            ''
        );
    }
}
