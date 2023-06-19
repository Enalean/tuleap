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

use Tracker_Artifact_Changeset_ChangesetDataInitializator;
use Tracker_Artifact_Changeset_InitialChangesetFieldsValidator;
use Tracker_Artifact_ChangesetFactoryBuilder;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\ProgramManagement\Adapter\ArtifactVisibleVerifier;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\Iteration\IterationsDAO;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\Iteration\IterationsLinkedToProgramIncrementDAO;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Content\ContentDao;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\SubmissionDateRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkValueFormatter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ChangesetValuesFormatter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\DateValueFormatter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\DescriptionValueFormatter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\FieldValuesGathererRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldsGatherer;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Links\ArtifactsLinkedToParentDao;
use Tuleap\ProgramManagement\Adapter\Program\Feature\UserStoriesInMirroredProgramIncrementsPlanner;
use Tuleap\ProgramManagement\Adapter\Program\Feature\UserStoryInOneMirrorPlanner;
use Tuleap\ProgramManagement\Adapter\Program\Plan\ProgramAdapter;
use Tuleap\ProgramManagement\Adapter\Program\PlanningAdapter;
use Tuleap\ProgramManagement\Adapter\Program\ProgramDaoProject;
use Tuleap\ProgramManagement\Adapter\ProjectReferenceRetriever;
use Tuleap\ProgramManagement\Adapter\Team\MirroredTimeboxes\MirroredTimeboxesDao;
use Tuleap\ProgramManagement\Adapter\Team\TeamDao;
use Tuleap\ProgramManagement\Adapter\Team\VisibleTeamSearcher;
use Tuleap\ProgramManagement\Adapter\Workspace\MessageLog;
use Tuleap\ProgramManagement\Adapter\Workspace\ProjectManagerAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Artifact\ArtifactFactoryAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Fields\FormElementFactoryAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\TrackerFactoryAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\UserManagerAdapter;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\BuildProgramIncrementCreationProcessor;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ProcessProgramIncrementCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ProgramIncrementCreationProcessor;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\ProgramIncrementsPlanner;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\Search\ItemToIndexQueueEventBased;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Artifact\Changeset\AfterNewChangesetHandler;
use Tuleap\Tracker\Artifact\Changeset\ArtifactChangesetSaver;
use Tuleap\Tracker\Artifact\Changeset\Comment\ChangesetCommentIndexer;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentCreator;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupPermissionDao;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupPermissionInserter;
use Tuleap\Tracker\Artifact\Changeset\FieldsToBeSavedInSpecificOrderRetriever;
use Tuleap\Tracker\Artifact\Changeset\InitialChangesetCreator;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetCreator;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\ActionsRunner;
use Tuleap\Tracker\Artifact\ChangesetValue\ChangesetValueSaver;
use Tuleap\Tracker\Artifact\ChangesetValue\InitialChangesetValueSaver;
use Tuleap\Tracker\Artifact\Creation\TrackerArtifactCreator;
use Tuleap\Tracker\FormElement\ArtifactLinkValidator;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkFieldValueDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\LinksRetriever;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ParentLinkAction;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\FormElement\Field\Text\TextValueValidator;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldDetector;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsRetriever;
use Tuleap\Tracker\Workflow\SimpleMode\SimpleWorkflowDao;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionExtractor;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionRetriever;
use Tuleap\Tracker\Workflow\WorkflowUpdateChecker;

final class ProgramIncrementCreationProcessorBuilder implements BuildProgramIncrementCreationProcessor
{
    public function getProcessor(): ProcessProgramIncrementCreation
    {
        $user_manager                   = \UserManager::instance();
        $program_dao                    = new ProgramDaoProject();
        $form_element_factory           = \Tracker_FormElementFactory::instance();
        $logger                         = \Tuleap\ProgramManagement\ProgramManagementLogger::getLogger();
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
        $mirrored_timeboxes_dao         = new MirroredTimeboxesDao();
        $transaction_executor           = new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection());
        $artifact_links_usage_dao       = new ArtifactLinksUsageDao();
        $fields_retriever               = new FieldsToBeSavedInSpecificOrderRetriever($form_element_factory);
        $field_initializator            = new \Tracker_Artifact_Changeset_ChangesetDataInitializator($form_element_factory);
        $artifact_changeset_saver       = ArtifactChangesetSaver::build();
        $after_new_changeset_handler    = new AfterNewChangesetHandler($artifact_factory, $fields_retriever);
        $retrieve_workflow              = \WorkflowFactory::instance();
        $event_dispatcher               = \EventManager::instance();

        $new_changeset_creator = new NewChangesetCreator(
            new \Tracker_Artifact_Changeset_NewChangesetFieldsValidator(
                $form_element_factory,
                new ArtifactLinkValidator(
                    $artifact_factory,
                    new TypePresenterFactory(
                        new TypeDao(),
                        $artifact_links_usage_dao
                    ),
                    $artifact_links_usage_dao,
                    $event_dispatcher,
                ),
                new WorkflowUpdateChecker(
                    new FrozenFieldDetector(
                        new TransitionRetriever(
                            new StateFactory(
                                \TransitionFactory::instance(),
                                new SimpleWorkflowDao()
                            ),
                            new TransitionExtractor()
                        ),
                        FrozenFieldsRetriever::instance()
                    ),
                ),
            ),
            $fields_retriever,
            \EventManager::instance(),
            $field_initializator,
            $transaction_executor,
            $artifact_changeset_saver,
            new ParentLinkAction($artifact_factory),
            $after_new_changeset_handler,
            ActionsRunner::build(\BackendLogger::getDefaultLogger()),
            new ChangesetValueSaver(),
            $retrieve_workflow,
            new CommentCreator(
                new \Tracker_Artifact_Changeset_CommentDao(),
                \ReferenceManager::instance(),
                new TrackerPrivateCommentUGroupPermissionInserter(new TrackerPrivateCommentUGroupPermissionDao()),
                new ChangesetCommentIndexer(
                    new ItemToIndexQueueEventBased($event_dispatcher),
                    $event_dispatcher,
                    new \Tracker_Artifact_Changeset_CommentDao(),
                ),
                new TextValueValidator(),
            )
        );

        $user_stories_planner = new UserStoriesInMirroredProgramIncrementsPlanner(
            new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
            $artifacts_linked_to_parent_dao,
            $mirrored_timeboxes_dao,
            $visibility_verifier,
            new ContentDao(),
            $logger,
            $artifacts_linked_to_parent_dao,
            $mirrored_timeboxes_dao,
            new UserStoryInOneMirrorPlanner(
                $artifact_retriever,
                $logger,
                $new_changeset_creator,
                $user_retriever,
                $form_element_factory
            )
        );


        $event_manager    = \EventManager::instance();
        $artifact_creator = new ArtifactCreatorAdapter(
            TrackerArtifactCreator::build(
                new InitialChangesetCreator(
                    Tracker_Artifact_Changeset_InitialChangesetFieldsValidator::build(),
                    $fields_retriever,
                    new Tracker_Artifact_Changeset_ChangesetDataInitializator($form_element_factory),
                    $logger,
                    ArtifactChangesetSaver::build(),
                    new AfterNewChangesetHandler($artifact_factory, $fields_retriever),
                    \WorkflowFactory::instance(),
                    new InitialChangesetValueSaver()
                ),
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
            $synchronized_fields_gatherer,
        );

        $project_access_checker = new ProjectAccessChecker(
            new RestrictedUserCanAccessProjectVerifier(),
            $event_manager
        );

        $program_adapter = ProgramAdapter::instance();
        $iterations_dao  = new IterationsDAO();
        return new ProgramIncrementCreationProcessor(
            MessageLog::buildFromLogger($logger),
            $user_stories_planner,
            new VisibleTeamSearcher(
                $program_dao,
                $user_retriever,
                $project_manager_adapter,
                $project_access_checker,
                new TeamDao()
            ),
            $program_dao,
            $program_adapter,
            new ProgramIncrementsPlanner(
                MessageLog::buildFromLogger($logger),
                new PlanningAdapter(\PlanningFactory::build(), $user_retriever),
                $mirror_creator,
                new ProjectReferenceRetriever($project_manager_adapter),
                $synchronized_fields_gatherer,
                new FieldValuesGathererRetriever($artifact_retriever, $form_element_factory),
                new SubmissionDateRetriever($artifact_retriever),
                (new IterationCreationProcessorBuilder())->getProcessor(),
                $program_dao,
                $program_adapter,
                $iterations_dao,
                $iterations_dao,
                $visibility_verifier,
                new IterationsLinkedToProgramIncrementDAO(),
                new LastChangesetRetriever(
                    $artifact_retriever,
                    Tracker_Artifact_ChangesetFactoryBuilder::build()
                )
            )
        );
    }
}
