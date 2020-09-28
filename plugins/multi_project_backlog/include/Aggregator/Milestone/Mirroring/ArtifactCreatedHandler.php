<?php
/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\MultiProjectBacklog\Aggregator\Milestone\Mirroring;

use Psr\Log\LoggerInterface;
use Tuleap\MultiProjectBacklog\Aggregator\AggregatorDao;
use Tuleap\MultiProjectBacklog\Aggregator\ContributorProjectsCollectionBuilder;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\MilestoneTrackerCollectionFactory;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\MilestoneTrackerRetrievalException;
use Tuleap\Tracker\Artifact\Event\ArtifactCreated;

class ArtifactCreatedHandler
{
    /**
     * @var AggregatorDao
     */
    private $aggregator_dao;
    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var \PlanningFactory
     */
    private $planning_factory;
    /**
     * @var CopiedValuesGatherer
     */
    private $copied_values_gatherer;
    /**
     * @var ContributorProjectsCollectionBuilder
     */
    private $projects_collection_builder;
    /**
     * @var MilestoneTrackerCollectionFactory
     */
    private $milestone_trackers_factory;
    /**
     * @var MirrorMilestonesCreator
     */
    private $mirror_creator;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        AggregatorDao $aggregator_dao,
        \UserManager $user_manager,
        \PlanningFactory $planning_factory,
        CopiedValuesGatherer $copied_values_gatherer,
        ContributorProjectsCollectionBuilder $projects_collection_builder,
        MilestoneTrackerCollectionFactory $milestone_trackers_factory,
        MirrorMilestonesCreator $mirror_creator,
        LoggerInterface $logger
    ) {
        $this->aggregator_dao              = $aggregator_dao;
        $this->user_manager                = $user_manager;
        $this->planning_factory            = $planning_factory;
        $this->copied_values_gatherer      = $copied_values_gatherer;
        $this->projects_collection_builder = $projects_collection_builder;
        $this->milestone_trackers_factory  = $milestone_trackers_factory;
        $this->mirror_creator              = $mirror_creator;
        $this->logger                      = $logger;
    }

    public function handle(ArtifactCreated $event): void
    {
        $artifact = $event->getArtifact();
        $tracker  = $artifact->getTracker();
        $project  = $tracker->getProject();

        if (! $this->aggregator_dao->isProjectAnAggregatorProject((int) $project->getID())) {
            return;
        }

        $current_user = $this->user_manager->getCurrentUser();
        try {
            $root_planning = $this->planning_factory->getVirtualTopPlanning($current_user, (int) $project->getID());
        } catch (\Planning_NoPlanningsException $e) {
            // Do nothing if there is no planning
            return;
        }

        $aggregator_top_milestones_tracker_id = $root_planning->getPlanningTrackerId();
        if ($tracker->getId() !== $aggregator_top_milestones_tracker_id) {
            return;
        }

        try {
            $this->createMirrors($event->getChangeset(), $tracker, $project, $current_user);
        } catch (MilestoneTrackerRetrievalException | MilestoneMirroringException $exception) {
            // Swallow the exception and let Aggregator Milestone be created
            $this->logger->error('Error during creation of mirror milestones', ['exception' => $exception]);
        }
    }

    /**
     * @throws MilestoneMirroringException
     * @throws MilestoneTrackerRetrievalException
     */
    private function createMirrors(
        \Tracker_Artifact_Changeset $aggregator_top_milestone_last_changeset,
        \Tracker $aggregator_top_milestone_tracker,
        \Project $aggregator_project,
        \PFUser $current_user
    ): void {
        $copied_values          = $this->copied_values_gatherer->gather(
            $aggregator_top_milestone_last_changeset,
            $aggregator_top_milestone_tracker
        );
        $contributor_projects   = $this->projects_collection_builder->getContributorProjectForAGivenAggregatorProject(
            $aggregator_project
        );
        $contributor_milestones = $this->milestone_trackers_factory->buildFromContributorProjects(
            $contributor_projects,
            $current_user
        );

        $this->mirror_creator->createMirrors($copied_values, $contributor_milestones, $current_user);
    }
}
