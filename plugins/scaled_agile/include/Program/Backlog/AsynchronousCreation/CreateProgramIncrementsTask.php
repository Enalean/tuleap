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
use ProjectManager;
use Psr\Log\LoggerInterface;
use Tracker_Artifact_Changeset_InitialChangesetCreator;
use Tracker_Artifact_Changeset_InitialChangesetFieldsValidator;
use Tracker_FormElementFactory;
use Tracker_Semantic_DescriptionFactory;
use Tracker_Semantic_StatusFactory;
use Tracker_Semantic_TitleFactory;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\ScaledAgile\Program\Backlog\ProgramDao;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Project\TeamProjectsCollectionBuilder;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Changeset\Values\ArtifactLinkValueAdapter;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Changeset\Values\DescriptionValueAdapter;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Changeset\Values\EndPeriodValueAdapter;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Changeset\Values\SourceChangesetValuesCollectionAdapter;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Changeset\Values\StartDateValueAdapter;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Changeset\Values\StatusValueAdapter;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Changeset\Values\TitleValueAdapter;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Fields\FieldArtifactLinkAdapter;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Fields\FieldDescriptionAdapter;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Fields\FieldRetrievalException;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Fields\FieldStatusAdapter;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Fields\FieldsTimeFrameAdapter;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Fields\FieldTitleAdapter;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Fields\SynchronizedFieldsAdapter;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\ReplicationData;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Tracker\ProjectIncrementTrackerRetrievalException;
use Tuleap\ScaledAgile\Program\Backlog\TrackerCollectionFactory;
use Tuleap\ScaledAgile\Program\PlanningConfiguration\PlanningAdapter;
use Tuleap\ScaledAgile\ProjectDataAdapter;
use Tuleap\Tracker\Artifact\Creation\TrackerArtifactCreator;
use Tuleap\Tracker\FormElement\Field\ListFields\FieldValueMatcher;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao;
use UserManager;
use XMLImportHelper;

class CreateProgramIncrementsTask
{
    /**
     * @var SourceChangesetValuesCollectionAdapter
     */
    private $changeset_collection_adapter;
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
        SourceChangesetValuesCollectionAdapter $changeset_collection_adapter,
        TeamProjectsCollectionBuilder $projects_collection_builder,
        TrackerCollectionFactory $scale_tracker_factory,
        ProjectIncrementsCreator $program_increment_creator,
        LoggerInterface $logger,
        PendingArtifactCreationDao $pending_artifact_creation_dao
    ) {
        $this->changeset_collection_adapter  = $changeset_collection_adapter;
        $this->projects_collection_builder   = $projects_collection_builder;
        $this->scale_tracker_factory         = $scale_tracker_factory;
        $this->program_increment_creator     = $program_increment_creator;
        $this->logger                        = $logger;
        $this->pending_artifact_creation_dao = $pending_artifact_creation_dao;
    }

    public function createProjectIncrements(ReplicationData $replication_data): void
    {
        try {
            $this->create($replication_data);
        } catch (ProjectIncrementTrackerRetrievalException | ProjectIncrementCreationException | FieldRetrievalException $exception) {
            $this->logger->error('Error during creation of project increments ', ['exception' => $exception]);
        }
    }

    /**
     * @throws ProjectIncrementCreationException
     * @throws ProjectIncrementTrackerRetrievalException
     * @throws FieldRetrievalException
     */
    private function create(ReplicationData $replication_data): void
    {
        $copied_values = $this->changeset_collection_adapter->buildCollection($replication_data);

        $team_projects = $this->projects_collection_builder->getTeamProjectForAGivenProgramProject($replication_data->getProjectData());

        $team_project_increments_tracker = $this->scale_tracker_factory->buildFromTeamProjects(
            $team_projects,
            $replication_data->getUser()
        );

        $this->program_increment_creator->createProjectIncrements(
            $copied_values,
            $team_project_increments_tracker,
            $replication_data->getUser()
        );

        $this->pending_artifact_creation_dao->deleteArtifactFromPendingCreation(
            (int) $replication_data->getArtifactData()->getId(),
            (int) $replication_data->getUser()->getId()
        );
    }

    public static function build(): self
    {
        $user_manager     = UserManager::instance();
        $program_dao      = new ProgramDao();

        $form_element_factory = Tracker_FormElementFactory::instance();
        $transaction_executor = new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection());
        $logger               = BackendLogger::getDefaultLogger("scaled_agile_syslog");

        $artifact_creator = TrackerArtifactCreator::build(
            Tracker_Artifact_Changeset_InitialChangesetCreator::build($logger),
            Tracker_Artifact_Changeset_InitialChangesetFieldsValidator::build(),
            $logger
        );

        $synchronized_fields_gatherer = new SynchronizedFieldsAdapter(
            new FieldArtifactLinkAdapter($form_element_factory),
            new FieldTitleAdapter(new Tracker_Semantic_TitleFactory()),
            new FieldDescriptionAdapter(new Tracker_Semantic_DescriptionFactory()),
            new FieldStatusAdapter(new Tracker_Semantic_StatusFactory()),
            new FieldsTimeFrameAdapter(
                new SemanticTimeframeBuilder(
                    new SemanticTimeframeDao(),
                    $form_element_factory
                )
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
            new SourceChangesetValuesCollectionAdapter(
                $synchronized_fields_gatherer,
                new TitleValueAdapter(),
                new DescriptionValueAdapter(),
                new StatusValueAdapter(),
                new StartDateValueAdapter(),
                new EndPeriodValueAdapter(),
                new ArtifactLinkValueAdapter()
            ),
            new TeamProjectsCollectionBuilder(
                $program_dao,
                new ProjectDataAdapter(ProjectManager::instance())
            ),
            new TrackerCollectionFactory(new PlanningAdapter(\PlanningFactory::build())),
            $mirror_creator,
            $logger,
            new PendingArtifactCreationDao()
        );
    }
}
