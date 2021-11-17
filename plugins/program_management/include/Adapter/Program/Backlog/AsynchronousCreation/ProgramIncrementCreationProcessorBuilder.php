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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation;

use Tracker_Artifact_Changeset_InitialChangesetCreator;
use Tracker_Artifact_Changeset_InitialChangesetFieldsValidator;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\ProgramManagement\Adapter\ArtifactVisibleVerifier;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\SubmissionDateRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkValueFormatter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ChangesetValuesFormatter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\DateValueFormatter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\DescriptionValueFormatter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\FieldValuesGathererRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldsGatherer;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Content\ContentDao;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Links\ArtifactsLinkedToParentDao;
use Tuleap\ProgramManagement\Adapter\Program\Feature\UserStoriesInMirroredProgramIncrementsPlanner;
use Tuleap\ProgramManagement\Adapter\Program\Plan\ProgramAdapter;
use Tuleap\ProgramManagement\Adapter\Program\PlanningAdapter;
use Tuleap\ProgramManagement\Adapter\Program\ProgramDao;
use Tuleap\ProgramManagement\Adapter\ProjectReferenceRetriever;
use Tuleap\ProgramManagement\Adapter\Team\MirroredTimeboxes\MirroredTimeboxesDao;
use Tuleap\ProgramManagement\Adapter\Workspace\MessageLog;
use Tuleap\ProgramManagement\Adapter\Workspace\ProjectManagerAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Artifact\ArtifactFactoryAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Fields\FormElementFactoryAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\TrackerFactoryAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\UserManagerAdapter;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\BuildProgramIncrementCreationProcessor;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ProcessProgramIncrementCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ProgramIncrementCreationProcessor;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\Tracker\Artifact\Creation\TrackerArtifactCreator;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkFieldValueDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\LinksRetriever;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao;

final class ProgramIncrementCreationProcessorBuilder implements BuildProgramIncrementCreationProcessor
{
    public function getProcessor(): ProcessProgramIncrementCreation
    {
        $user_manager                   = \UserManager::instance();
        $program_dao                    = new ProgramDao();
        $form_element_factory           = \Tracker_FormElementFactory::instance();
        $logger                         = \BackendLogger::getDefaultLogger('program_management_syslog');
        $artifact_factory               = \Tracker_ArtifactFactory::instance();
        $tracker_factory                = \TrackerFactory::instance();
        $artifacts_linked_to_parent_dao = new ArtifactsLinkedToParentDao();
        $user_retriever                 = new UserManagerAdapter($user_manager);
        $project_manager                = \ProjectManager::instance();
        $visibility_verifier            = new ArtifactVisibleVerifier($artifact_factory, $user_retriever);
        $tracker_retriever              = new TrackerFactoryAdapter($tracker_factory);
        $artifact_retriever             = new ArtifactFactoryAdapter($artifact_factory);
        $field_retriever                = new FormElementFactoryAdapter($tracker_retriever, $form_element_factory);
        $project_manager_adapter        = new ProjectManagerAdapter($project_manager, $user_retriever);

        $transaction_executor = new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection());

        $user_stories_planner = new UserStoriesInMirroredProgramIncrementsPlanner(
            new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
            $artifacts_linked_to_parent_dao,
            $artifact_retriever,
            new MirroredTimeboxesDao(),
            $visibility_verifier,
            new ContentDao(),
            $logger,
            $user_retriever,
            $artifacts_linked_to_parent_dao
        );

        $artifact_creator = new ArtifactCreatorAdapter(
            TrackerArtifactCreator::build(
                Tracker_Artifact_Changeset_InitialChangesetCreator::build($logger),
                Tracker_Artifact_Changeset_InitialChangesetFieldsValidator::build(),
                $logger
            ),
            $tracker_retriever,
            $user_retriever,
            new ChangesetValuesFormatter(
                new ArtifactLinkValueFormatter(),
                new DescriptionValueFormatter(),
                new DateValueFormatter()
            )
        );

        $synchronized_fields_gatherer = new SynchronizedFieldsGatherer(
            $tracker_retriever,
            new \Tracker_Semantic_TitleFactory(),
            new \Tracker_Semantic_DescriptionFactory(),
            new \Tracker_Semantic_StatusFactory(),
            new SemanticTimeframeBuilder(
                new SemanticTimeframeDao(),
                $form_element_factory,
                $tracker_factory,
                new LinksRetriever(
                    new ArtifactLinkFieldValueDao(),
                    $artifact_factory
                )
            ),
            $field_retriever
        );

        $mirror_creator = new ProgramIncrementsCreator(
            $transaction_executor,
            new StatusValueMapper($form_element_factory),
            $artifact_creator,
            $synchronized_fields_gatherer
        );

        return new ProgramIncrementCreationProcessor(
            new PlanningAdapter(\PlanningFactory::build(), $user_retriever),
            $mirror_creator,
            MessageLog::buildFromLogger($logger),
            $user_stories_planner,
            $program_dao,
            new ProjectReferenceRetriever($project_manager_adapter),
            $synchronized_fields_gatherer,
            new FieldValuesGathererRetriever($artifact_retriever, $form_element_factory),
            new SubmissionDateRetriever($artifact_retriever),
            $program_dao,
            new ProgramAdapter(
                $project_manager_adapter,
                new ProjectAccessChecker(
                    new RestrictedUserCanAccessProjectVerifier(),
                    \EventManager::instance()
                ),
                $program_dao,
                $user_retriever
            )
        );
    }
}
