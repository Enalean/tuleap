<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation;

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
use Tuleap\ProgramManagement\Adapter\Team\MirroredTimeboxes\MirroredTimeboxesDao;
use Tuleap\ProgramManagement\Adapter\Workspace\MessageLog;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Artifact\ArtifactFactoryAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Fields\FormElementFactoryAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\TrackerFactoryAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\TrackerOfArtifactRetriever;
use Tuleap\ProgramManagement\Adapter\Workspace\UserManagerAdapter;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\BuildIterationUpdateProcessor;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\IterationUpdateProcessor;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ProcessIterationUpdate;
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
use Tuleap\Tracker\Artifact\Changeset\NewChangesetCreator;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\ActionsQueuer;
use Tuleap\Tracker\Artifact\ChangesetValue\ChangesetValueSaver;
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

final class IterationUpdateProcessorBuilder implements BuildIterationUpdateProcessor
{
    public function getProcessor(): ProcessIterationUpdate
    {
        $logger                   = \Tuleap\ProgramManagement\ProgramManagementLogger::getLogger();
        $artifact_factory         = \Tracker_ArtifactFactory::instance();
        $tracker_factory          = \TrackerFactory::instance();
        $form_element_factory     = \Tracker_FormElementFactory::instance();
        $user_retriever           = new UserManagerAdapter(\UserManager::instance());
        $artifact_links_usage_dao = new ArtifactLinksUsageDao();
        $artifact_changeset_dao   = new \Tracker_Artifact_ChangesetDao();
        $visibility_verifier      = new ArtifactVisibleVerifier($artifact_factory, $user_retriever);
        $tracker_retriever        = new TrackerFactoryAdapter($tracker_factory);
        $artifact_retriever       = new ArtifactFactoryAdapter($artifact_factory);
        $field_retriever          = new FormElementFactoryAdapter($tracker_retriever, $form_element_factory);
        $fields_retriever         = new FieldsToBeSavedInSpecificOrderRetriever($form_element_factory);
        $event_dispatcher         = \EventManager::instance();

        $transaction_executor = new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection());

        $changeset_creator = new NewChangesetCreator(
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
            new \Tracker_Artifact_Changeset_ChangesetDataInitializator($form_element_factory),
            $transaction_executor,
            new ArtifactChangesetSaver(
                $artifact_changeset_dao,
                $transaction_executor,
                new \Tracker_ArtifactDao(),
                new ChangesetFromXmlDao()
            ),
            new ParentLinkAction($artifact_factory),
            new AfterNewChangesetHandler($artifact_factory, $fields_retriever),
            ActionsQueuer::build(\BackendLogger::getDefaultLogger()),
            new ChangesetValueSaver(),
            \WorkflowFactory::instance(),
            new CommentCreator(
                new \Tracker_Artifact_Changeset_CommentDao(),
                \ReferenceManager::instance(),
                new TrackerPrivateCommentUGroupPermissionInserter(new TrackerPrivateCommentUGroupPermissionDao()),
                new TextValueValidator(),
            ),
            new ChangesetCommentIndexer(
                new ItemToIndexQueueEventBased($event_dispatcher),
                $event_dispatcher,
                new \Tracker_Artifact_Changeset_CommentDao(),
            ),
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
        return new IterationUpdateProcessor(
            MessageLog::buildFromLogger($logger),
            $synchronized_fields_gatherer,
            new FieldValuesGathererRetriever($artifact_retriever, $form_element_factory),
            new SubmissionDateRetriever($artifact_retriever),
            new MirroredTimeboxesDao(),
            $visibility_verifier,
            new TrackerOfArtifactRetriever($artifact_retriever),
            new StatusValueMapper($form_element_factory),
            new ChangesetAdder(
                $artifact_retriever,
                $user_retriever,
                new ChangesetValuesFormatter(
                    new ArtifactLinkValueFormatter(),
                    new DescriptionValueFormatter(),
                    new DateValueFormatter()
                ),
                $changeset_creator
            )
        );
    }
}
