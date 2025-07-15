<?php
/**
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);
namespace Tuleap\Tracker\Artifact\Changeset;

use BackendLogger;
use EventManager;
use ReferenceManager;
use Tracker_Artifact_Changeset_ChangesetDataInitializator;
use Tracker_Artifact_Changeset_CommentDao;
use Tracker_Artifact_Changeset_NewChangesetFieldsValidator;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use TransitionFactory;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Search\ItemToIndexQueueEventBased;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Artifact\Changeset\Comment\ChangesetCommentIndexer;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentCreator;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupPermissionDao;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupPermissionInserter;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\ActionsQueuer;
use Tuleap\Tracker\Artifact\ChangesetValue\ChangesetValueSaver;
use Tuleap\Tracker\FormElement\ArtifactLinkValidator;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ParentLinkAction;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\FormElement\Field\Text\TextValueValidator;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldDetector;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsRetriever;
use Tuleap\Tracker\Workflow\SimpleMode\SimpleWorkflowDao;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionExtractor;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionRetriever;
use Tuleap\Tracker\Workflow\WorkflowUpdateChecker;
use WorkflowFactory;

final readonly class NewChangesetCreatorBuilder
{
    public static function build(): NewChangesetCreator
    {
        $form_element_factory     = Tracker_FormElementFactory::instance();
        $db_transaction_executor  = new DBTransactionExecutorWithConnection(
            DBFactory::getMainTuleapDBConnection()
        );
        $artifact_factory         = Tracker_ArtifactFactory::instance();
        $fields_retriever         = new FieldsToBeSavedInSpecificOrderRetriever($form_element_factory);
        $reference_manager        = ReferenceManager::instance();
        $artifact_links_usage_dao = new ArtifactLinksUsageDao();
        $event_manager            = EventManager::instance();

        $comment_changeset_dao =                 new Tracker_Artifact_Changeset_CommentDao();
        $user_manager          = \UserManager::instance();

        return new NewChangesetCreator(
            $db_transaction_executor,
            ArtifactChangesetSaver::build(),
            new AfterNewChangesetHandler($artifact_factory, $fields_retriever),
            WorkflowFactory::instance(),
            new CommentCreator(
                $comment_changeset_dao,
                $reference_manager,
                new TrackerPrivateCommentUGroupPermissionInserter(
                    new TrackerPrivateCommentUGroupPermissionDao()
                ),
                new TextValueValidator(),
            ),
            new NewChangesetFieldValueSaver(
                $fields_retriever,
                new ChangesetValueSaver(),
            ),
            new NewChangesetValidator(
                new Tracker_Artifact_Changeset_NewChangesetFieldsValidator(
                    $form_element_factory,
                    new ArtifactLinkValidator(
                        $artifact_factory,
                        new TypePresenterFactory(new TypeDao(), $artifact_links_usage_dao),
                        $artifact_links_usage_dao,
                        $event_manager,
                    ),
                    new WorkflowUpdateChecker(
                        new FrozenFieldDetector(
                            new TransitionRetriever(
                                new StateFactory(
                                    TransitionFactory::instance(),
                                    new SimpleWorkflowDao()
                                ),
                                new TransitionExtractor()
                            ),
                            FrozenFieldsRetriever::instance()
                        )
                    )
                ),
                new Tracker_Artifact_Changeset_ChangesetDataInitializator($form_element_factory),
                new ParentLinkAction($artifact_factory),
            ),
            new NewChangesetPostProcessor(
                $event_manager,
                ActionsQueuer::build(BackendLogger::getDefaultLogger()),
                new ChangesetCommentIndexer(
                    new ItemToIndexQueueEventBased($event_manager),
                    $event_manager,
                    $comment_changeset_dao,
                ),
            ),
        );
    }
}
