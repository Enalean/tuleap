<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Notification\Mention\MentionedUserInTextRetriever;
use Tuleap\ProgramManagement\Adapter\ArtifactVisibleVerifier;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\SubmissionDateRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkValueFormatter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ChangesetValuesFormatter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\DateValueFormatter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\DescriptionValueFormatter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\FieldValuesGathererRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldsGatherer;
use Tuleap\ProgramManagement\Adapter\Program\Plan\CachedProgramBuilder;
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
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\TrackerOfArtifactRetriever;
use Tuleap\ProgramManagement\Adapter\Workspace\UserManagerAdapter;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\BuildIterationCreationProcessor;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\IterationCreationProcessor;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ProcessIterationCreation;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\Search\ItemToIndexQueueEventBased;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Artifact\Changeset\AfterNewChangesetHandler;
use Tuleap\Tracker\Artifact\Changeset\ArtifactChangesetSaver;
use Tuleap\Tracker\Artifact\Changeset\ChangesetFromXmlDao;
use Tuleap\Tracker\Artifact\Changeset\Comment\ChangesetCommentIndexer;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentCreator;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupPermissionDao;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupPermissionInserter;
use Tuleap\Tracker\Artifact\Changeset\FieldsToBeSavedInSpecificOrderRetriever;
use Tuleap\Tracker\Artifact\Changeset\InitialChangesetCreator;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetCreator;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetFieldValueSaver;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetPostProcessor;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetValidator;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\ActionsQueuer;
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
use Tuleap\Tracker\Semantic\Description\CachedSemanticDescriptionFieldRetriever;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldDetector;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsRetriever;
use Tuleap\Tracker\Workflow\SimpleMode\SimpleWorkflowDao;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionExtractor;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionRetriever;
use Tuleap\Tracker\Workflow\WorkflowUpdateChecker;

final class IterationCreationProcessorBuilder implements BuildIterationCreationProcessor
{
    public function getProcessor(): ProcessIterationCreation
    {
        $logger                   = \Tuleap\ProgramManagement\ProgramManagementLogger::getLogger();
        $artifact_factory         = \Tracker_ArtifactFactory::instance();
        $tracker_factory          = \TrackerFactory::instance();
        $form_element_factory     = \Tracker_FormElementFactory::instance();
        $program_DAO              = new ProgramDaoProject();
        $project_manager          = \ProjectManager::instance();
        $event_manager            = \EventManager::instance();
        $user_retriever           = new UserManagerAdapter(\UserManager::instance());
        $transaction_executor     = new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection());
        $message_logger           = MessageLog::buildFromLogger($logger);
        $visibility_verifier      = new ArtifactVisibleVerifier($artifact_factory, $user_retriever);
        $artifact_links_usage_dao = new ArtifactLinksUsageDao();
        $artifact_changeset_dao   = new \Tracker_Artifact_ChangesetDao();
        $tracker_retriever        = new TrackerFactoryAdapter($tracker_factory);
        $artifact_retriever       = new ArtifactFactoryAdapter($artifact_factory);
        $field_retriever          = new FormElementFactoryAdapter($tracker_retriever, $form_element_factory);
        $project_manager_adapter  = new ProjectManagerAdapter($project_manager, $user_retriever);

        $synchronized_fields_gatherer = new SynchronizedFieldsGatherer(
            $tracker_retriever,
            new \Tuleap\Tracker\Semantic\Title\TrackerSemanticTitleFactory(),
            CachedSemanticDescriptionFieldRetriever::instance(),
            new \Tuleap\Tracker\Semantic\Status\TrackerSemanticStatusFactory(),
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

        $changeset_values_formatter = new ChangesetValuesFormatter(
            new ArtifactLinkValueFormatter(),
            new DescriptionValueFormatter(),
            new DateValueFormatter()
        );

        $artifact_changeset_saver = new ArtifactChangesetSaver(
            $artifact_changeset_dao,
            $transaction_executor,
            new \Tracker_ArtifactDao(),
            new ChangesetFromXmlDao()
        );

        $retrieve_workflow           = \WorkflowFactory::instance();
        $fields_validator            = \Tracker_Artifact_Changeset_InitialChangesetFieldsValidator::build();
        $field_initializator         = new \Tracker_Artifact_Changeset_ChangesetDataInitializator($form_element_factory);
        $fields_retriever            = new FieldsToBeSavedInSpecificOrderRetriever($form_element_factory);
        $after_new_changeset_handler = new AfterNewChangesetHandler($artifact_factory, $fields_retriever);
        $artifact_creator            = new ArtifactCreatorAdapter(
            TrackerArtifactCreator::build(
                new InitialChangesetCreator(
                    $fields_validator,
                    $fields_retriever,
                    $field_initializator,
                    $logger,
                    $artifact_changeset_saver,
                    $after_new_changeset_handler,
                    \WorkflowFactory::instance(),
                    new InitialChangesetValueSaver()
                ),
                $fields_validator,
                $logger
            ),
            $tracker_retriever,
            $user_retriever,
            $changeset_values_formatter
        );

        $fields_retriever  = new FieldsToBeSavedInSpecificOrderRetriever($form_element_factory);
        $user_manager      = \UserManager::instance();
        $changeset_creator = new NewChangesetCreator(
            $transaction_executor,
            $artifact_changeset_saver,
            $after_new_changeset_handler,
            $retrieve_workflow,
            new CommentCreator(
                new \Tracker_Artifact_Changeset_CommentDao(),
                \ReferenceManager::instance(),
                new TrackerPrivateCommentUGroupPermissionInserter(new TrackerPrivateCommentUGroupPermissionDao()),
                new TextValueValidator(),
            ),
            new NewChangesetFieldValueSaver(
                $fields_retriever,
                new ChangesetValueSaver(),
            ),
            new NewChangesetValidator(
                new \Tracker_Artifact_Changeset_NewChangesetFieldsValidator(
                    $form_element_factory,
                    new ArtifactLinkValidator(
                        $artifact_factory,
                        new TypePresenterFactory(
                            new TypeDao(),
                            $artifact_links_usage_dao
                        ),
                        $artifact_links_usage_dao,
                        $event_manager,
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
                $field_initializator,
                new ParentLinkAction($artifact_factory),
            ),
            new NewChangesetPostProcessor(
                \EventManager::instance(),
                ActionsQueuer::build(\BackendLogger::getDefaultLogger()),
                new ChangesetCommentIndexer(
                    new ItemToIndexQueueEventBased($event_manager),
                    $event_manager,
                    new \Tracker_Artifact_Changeset_CommentDao(),
                ),
                new MentionedUserInTextRetriever($user_manager),
            ),
        );

        $changeset_adder = new ChangesetAdder(
            $artifact_retriever,
            $user_retriever,
            $changeset_values_formatter,
            $changeset_creator
        );

        $mirrors_creator = new IterationsCreator(
            $logger,
            $transaction_executor,
            new PlanningAdapter(\PlanningFactory::build(), $user_retriever),
            new StatusValueMapper($form_element_factory),
            $synchronized_fields_gatherer,
            $artifact_creator,
            new MirroredTimeboxesDao(),
            $visibility_verifier,
            new TrackerOfArtifactRetriever($artifact_retriever),
            $changeset_adder,
            new ProjectReferenceRetriever($project_manager_adapter)
        );

        $project_access_checker = new ProjectAccessChecker(
            new RestrictedUserCanAccessProjectVerifier(),
            $event_manager
        );

        return new IterationCreationProcessor(
            $message_logger,
            $synchronized_fields_gatherer,
            new FieldValuesGathererRetriever($artifact_retriever, $form_element_factory),
            new SubmissionDateRetriever($artifact_retriever),
            $program_DAO,
            CachedProgramBuilder::instance(),
            new VisibleTeamSearcher(
                $program_DAO,
                $user_retriever,
                $project_manager_adapter,
                $project_access_checker,
                new TeamDao()
            ),
            $mirrors_creator
        );
    }
}
