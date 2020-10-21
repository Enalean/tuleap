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

namespace Tuleap\ScaledAgile\Program\Backlog\AsynchronousCreation;

use BackendLogger;
use PFUser;
use PlanningFactory;
use ProjectManager;
use Psr\Log\LoggerInterface;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_Changeset_InitialChangesetCreator;
use Tracker_Artifact_Changeset_InitialChangesetFieldsValidator;
use Tracker_FormElementFactory;
use Tracker_Semantic_DescriptionFactory;
use Tracker_Semantic_StatusFactory;
use Tracker_Semantic_TitleFactory;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\ScaledAgile\Program\Backlog\ProgramDao;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Data\SynchronizedFields\CopiedValuesGatherer;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Data\SynchronizedFields\Status\StatusValueMapper;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Data\SynchronizedFields\SynchronizedFieldRetrievalException;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Data\SynchronizedFields\SynchronizedFieldsGatherer;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Project\TeamProjectsCollectionBuilder;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Tracker\ProjectIncrementTrackerRetrievalException;
use Tuleap\ScaledAgile\Program\Backlog\TrackerCollectionFactory;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Creation\TrackerArtifactCreator;
use Tuleap\Tracker\FormElement\Field\ListFields\FieldValueMatcher;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao;
use UserManager;
use XMLImportHelper;

class CreateProgramIncrementsTask
{
    /**
     * @var CopiedValuesGatherer
     */
    private $copied_values_gatherer;
    /**
     * @var TeamProjectsCollectionBuilder
     */
    private $projects_collection_builder;
    /**
     * @var TrackerCollectionFactory
     */
    private $scale_tracker_factory;
    /**
     * @var ProjectIncrementsCreator
     */
    private $program_increment_creator;
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
        TeamProjectsCollectionBuilder $projects_collection_builder,
        TrackerCollectionFactory $scale_tracker_factory,
        ProjectIncrementsCreator $program_increment_creator,
        LoggerInterface $logger,
        PendingArtifactCreationDao $pending_artifact_creation_dao
    ) {
        $this->copied_values_gatherer        = $copied_values_gatherer;
        $this->projects_collection_builder   = $projects_collection_builder;
        $this->scale_tracker_factory         = $scale_tracker_factory;
        $this->program_increment_creator     = $program_increment_creator;
        $this->logger                        = $logger;
        $this->pending_artifact_creation_dao = $pending_artifact_creation_dao;
    }

    public function createProjectIncrements(
        Artifact $source_artifact,
        PFUser $user,
        Tracker_Artifact_Changeset $source_changeset
    ): void {
        try {
            $this->create($source_artifact, $user, $source_changeset);
        } catch (ProjectIncrementTrackerRetrievalException | ProjectIncrementCreationException | SynchronizedFieldRetrievalException $exception) {
            $this->logger->error('Error during creation of project increments ', ['exception' => $exception]);
        }
    }

    /**
     * @throws ProjectIncrementCreationException
     * @throws ProjectIncrementTrackerRetrievalException
     * @throws SynchronizedFieldRetrievalException
     */
    private function create(
        Artifact $source_artifact,
        PFUser $current_user,
        Tracker_Artifact_Changeset $source_changeset
    ): void {
        $source_tracker = $source_artifact->getTracker();

        $copied_values = $this->copied_values_gatherer->gather(
            $source_changeset,
            $source_tracker
        );

        $team_projects = $this->projects_collection_builder->getTeamProjectForAGivenProgramProject(
            $source_tracker->getProject()
        );

        $team_project_increments_tracker = $this->scale_tracker_factory->buildFromTeamProjects(
            $team_projects,
            $current_user
        );

        $this->program_increment_creator->createProjectIncrements(
            $copied_values,
            $team_project_increments_tracker,
            $current_user
        );

        $this->pending_artifact_creation_dao->deleteArtifactFromPendingCreation(
            (int) $source_artifact->getId(),
            (int) $current_user->getId()
        );
    }

    public static function build(): self
    {
        $user_manager     = UserManager::instance();
        $program_dao      = new ProgramDao();
        $planning_factory = PlanningFactory::build();

        $form_element_factory = Tracker_FormElementFactory::instance();
        $transaction_executor = new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection());
        $logger               = BackendLogger::getDefaultLogger("scaled_agile_syslog");

        $artifact_creator = TrackerArtifactCreator::build(
            Tracker_Artifact_Changeset_InitialChangesetCreator::build($logger),
            Tracker_Artifact_Changeset_InitialChangesetFieldsValidator::build(),
            $logger
        );

        $synchronized_fields_gatherer = new SynchronizedFieldsGatherer(
            $form_element_factory,
            new Tracker_Semantic_TitleFactory(),
            new Tracker_Semantic_DescriptionFactory(),
            new Tracker_Semantic_StatusFactory(),
            new SemanticTimeframeBuilder(
                new SemanticTimeframeDao(),
                $form_element_factory
            )
        );

        $mirror_creator = new ProjectIncrementsCreator(
            $transaction_executor,
            $synchronized_fields_gatherer,
            new StatusValueMapper(
                new FieldValueMatcher(new XMLImportHelper($user_manager))
            ),
            $artifact_creator
        );

        return new self(
            new CopiedValuesGatherer(
                $synchronized_fields_gatherer
            ),
            new TeamProjectsCollectionBuilder(
                $program_dao,
                ProjectManager::instance()
            ),
            new TrackerCollectionFactory($planning_factory),
            $mirror_creator,
            $logger,
            new PendingArtifactCreationDao()
        );
    }
}
