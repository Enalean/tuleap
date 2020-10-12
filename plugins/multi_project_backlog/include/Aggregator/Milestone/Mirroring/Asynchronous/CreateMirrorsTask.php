<?php
/*
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

namespace Tuleap\MultiProjectBacklog\Aggregator\Milestone\Mirroring\Asynchronous;

use BackendLogger;
use PFUser;
use ProjectManager;
use Psr\Log\LoggerInterface;
use Tracker_Artifact;
use Tracker_Artifact_Changeset_InitialChangesetCreator;
use Tracker_Artifact_Changeset_InitialChangesetFieldsValidator;
use Tracker_ArtifactCreator;
use Tracker_ArtifactFactory;
use Tracker_Semantic_DescriptionFactory;
use Tracker_Semantic_StatusFactory;
use Tracker_Semantic_TitleFactory;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\MultiProjectBacklog\Aggregator\AggregatorDao;
use Tuleap\MultiProjectBacklog\Aggregator\ContributorProjectsCollectionBuilder;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\MilestoneTrackerCollectionFactory;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\MilestoneTrackerRetrievalException;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\Mirroring\CopiedValuesGatherer;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\Mirroring\MilestoneMirroringException;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\Mirroring\MirrorMilestonesCreator;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\SynchronizedFieldRetrievalException;
use Tuleap\Tracker\Artifact\RecentlyVisited\RecentlyVisitedDao;
use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder;
use UserManager;
use XMLImportHelper;

class CreateMirrorsTask
{
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
    /**
     * @var PendingArtifactCreationDao
     */
    private $pending_artifact_creation_dao;

    public function __construct(
        CopiedValuesGatherer $copied_values_gatherer,
        ContributorProjectsCollectionBuilder $projects_collection_builder,
        MilestoneTrackerCollectionFactory $milestone_trackers_factory,
        MirrorMilestonesCreator $mirror_creator,
        LoggerInterface $logger,
        PendingArtifactCreationDao $pending_artifact_creation_dao
    ) {
        $this->copied_values_gatherer        = $copied_values_gatherer;
        $this->projects_collection_builder   = $projects_collection_builder;
        $this->milestone_trackers_factory    = $milestone_trackers_factory;
        $this->mirror_creator                = $mirror_creator;
        $this->logger                        = $logger;
        $this->pending_artifact_creation_dao = $pending_artifact_creation_dao;
    }

    public function createMirrors(Tracker_Artifact $artifact, PFUser $user, \Tracker_Artifact_Changeset $changeset): void
    {
        try {
            $this->create($artifact, $user, $changeset);
        } catch (MilestoneTrackerRetrievalException | MilestoneMirroringException | SynchronizedFieldRetrievalException $exception) {
            $this->logger->error('Error during creation of mirror milestones', ['exception' => $exception]);
        }
    }

    /**
     * @throws MilestoneMirroringException
     * @throws MilestoneTrackerRetrievalException
     * @throws SynchronizedFieldRetrievalException
     */
    private function create(
        \Tracker_Artifact $aggregator_top_milestone_artifact,
        \PFUser $current_user,
        \Tracker_Artifact_Changeset $last_changeset
    ): void {
        $tracker                = $aggregator_top_milestone_artifact->getTracker();

        $copied_values          = $this->copied_values_gatherer->gather(
            $last_changeset,
            $tracker
        );
        $contributor_projects   = $this->projects_collection_builder->getContributorProjectForAGivenAggregatorProject(
            $tracker->getProject()
        );
        $contributor_milestones = $this->milestone_trackers_factory->buildFromContributorProjects(
            $contributor_projects,
            $current_user
        );

        $this->mirror_creator->createMirrors($copied_values, $contributor_milestones, $current_user);

        $this->pending_artifact_creation_dao->deleteArtifactFromPendingCreation(
            (int) $aggregator_top_milestone_artifact->getId(),
            (int) $current_user->getId()
        );
    }

    public static function build(): self
    {
        $user_manager     = UserManager::instance();
        $aggregator_dao   = new AggregatorDao();
        $planning_factory = \PlanningFactory::build();

        $form_element_factory = \Tracker_FormElementFactory::instance();
        $artifact_factory     = Tracker_ArtifactFactory::instance();

        $transaction_executor = new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection());
        $visit_recorder       = new VisitRecorder(
            new RecentlyVisitedDao(),
            $transaction_executor
        );
        $logger               = BackendLogger::getDefaultLogger("multi_project_backlog_syslog");

        $artifact_creator = new Tracker_ArtifactCreator(
            $artifact_factory,
            Tracker_Artifact_Changeset_InitialChangesetFieldsValidator::build(),
            Tracker_Artifact_Changeset_InitialChangesetCreator::build($logger),
            $visit_recorder,
            $logger,
            $transaction_executor
        );

        $synchronized_fields_gatherer = new \Tuleap\MultiProjectBacklog\Aggregator\Milestone\SynchronizedFieldsGatherer(
            $form_element_factory,
            new Tracker_Semantic_TitleFactory(),
            new Tracker_Semantic_DescriptionFactory(),
            new Tracker_Semantic_StatusFactory(),
            new \Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder(
                new \Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao(),
                $form_element_factory
            )
        );

        $mirror_creator = new \Tuleap\MultiProjectBacklog\Aggregator\Milestone\Mirroring\MirrorMilestonesCreator(
            $transaction_executor,
            $synchronized_fields_gatherer,
            new \Tuleap\MultiProjectBacklog\Aggregator\Milestone\Mirroring\Status\StatusValueMapper(
                new \Tuleap\Tracker\FormElement\Field\ListFields\FieldValueMatcher(new XMLImportHelper($user_manager))
            ),
            $artifact_creator
        );

        return new self(
            new \Tuleap\MultiProjectBacklog\Aggregator\Milestone\Mirroring\CopiedValuesGatherer(
                $synchronized_fields_gatherer
            ),
            new ContributorProjectsCollectionBuilder(
                $aggregator_dao,
                ProjectManager::instance()
            ),
            new \Tuleap\MultiProjectBacklog\Aggregator\Milestone\MilestoneTrackerCollectionFactory($planning_factory),
            $mirror_creator,
            $logger,
            new PendingArtifactCreationDao()
        );
    }
}
