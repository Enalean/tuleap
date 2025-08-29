<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\ArtifactsDeletion;

use EventManager;
use ForgeConfig;
use PermissionsDao;
use PermissionsManager;
use Psr\Log\LoggerInterface;
use Tracker_Artifact_PriorityHistoryDao;
use Tracker_Artifact_XMLExport;
use Tracker_ArtifactDao;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tracker_Workflow_Trigger_RulesBuilderFactory;
use Tracker_Workflow_Trigger_RulesDao;
use Tracker_Workflow_Trigger_RulesManager;
use Tracker_Workflow_Trigger_RulesProcessor;
use TrackerFactory;
use TrackerXmlExport;
use TransitionFactory;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Project\XML\Import\ExternalFieldsExtractor;
use Tuleap\Reference\CrossReferenceManager;
use Tuleap\Reference\CrossReferencesDao;
use Tuleap\Search\ItemToIndexQueueEventBased;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Artifact\ArtifactWithTrackerStructureExporter;
use Tuleap\Tracker\Artifact\Changeset\AfterNewChangesetHandler;
use Tuleap\Tracker\Artifact\Changeset\ArtifactChangesetSaver;
use Tuleap\Tracker\Artifact\Changeset\Comment\ChangesetCommentIndexer;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentCreator;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupPermissionDao;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupPermissionInserter;
use Tuleap\Tracker\Artifact\Changeset\FieldsToBeSavedInSpecificOrderRetriever;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetCreator;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetFieldValueSaver;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetPostProcessor;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetValidator;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\ActionsQueuer;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ArtifactForwardLinksRetriever;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ArtifactLinksByChangesetCache;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ChangesetValueArtifactLinkDao;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ReverseLinksDao;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ReverseLinksRetriever;
use Tuleap\Tracker\Artifact\ChangesetValue\ChangesetValueSaver;
use Tuleap\Tracker\Artifact\Dao\PriorityDao;
use Tuleap\Tracker\Artifact\Link\ArtifactLinker;
use Tuleap\Tracker\Artifact\RecentlyVisited\RecentlyVisitedDao;
use Tuleap\Tracker\Artifact\PriorityManager;
use Tuleap\Tracker\FormElement\ArtifactLinkValidator;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ParentLinkAction;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\SystemTypePresenterBuilder;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\FormElement\Field\Computed\ComputedFieldDao;
use Tuleap\Tracker\FormElement\Field\Computed\ComputedFieldDaoCache;
use Tuleap\Tracker\FormElement\Field\Text\TextValueValidator;
use Tuleap\Tracker\FormElement\FieldContentIndexer;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldDetector;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsRetriever;
use Tuleap\Tracker\Workflow\SimpleMode\SimpleWorkflowDao;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionExtractor;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionRetriever;
use Tuleap\Tracker\Workflow\Trigger\Siblings\SiblingsDao;
use Tuleap\Tracker\Workflow\Trigger\Siblings\SiblingsRetriever;
use Tuleap\Tracker\Workflow\WorkflowBackendLogger;
use Tuleap\Tracker\Workflow\WorkflowRulesManagerLoopSafeGuard;
use Tuleap\Tracker\Workflow\WorkflowUpdateChecker;
use UserManager;
use UserXMLExportedCollection;
use UserXMLExporter;
use XML_RNGValidator;
use XML_SimpleXMLCDATAFactory;

class ArchiveAndDeleteArtifactTaskBuilder
{
    public function build(LoggerInterface $logger): ArchiveAndDeleteArtifactTask
    {
        $user_manager             = UserManager::instance();
        $tracker_artifact_factory = Tracker_ArtifactFactory::instance();
        $formelement_factory      = Tracker_FormElementFactory::instance();
        $event_manager            = EventManager::instance();
        $rng_validator            = new XML_RNGValidator();
        $external_field_extractor = new ExternalFieldsExtractor($event_manager);
        $user_xml_exporter        = new UserXMLExporter(
            $user_manager,
            new UserXMLExportedCollection($rng_validator, new XML_SimpleXMLCDATAFactory())
        );
        $fields_retriever         = new FieldsToBeSavedInSpecificOrderRetriever($formelement_factory);

        $workflow_logger = new WorkflowBackendLogger(\BackendLogger::getDefaultLogger(), ForgeConfig::get('sys_logger_level'));

        $artifact_links_usage_dao = new ArtifactLinksUsageDao();
        $cross_references_dao     = new CrossReferencesDao();
        $cross_reference_manager  = new CrossReferenceManager($cross_references_dao);
        $tracker_artifact_dao     = new Tracker_ArtifactDao();
        $db_connection            = DBFactory::getMainTuleapDBConnection();
        $type_presenter_factory   = new TypePresenterFactory(new TypeDao(), $artifact_links_usage_dao, new SystemTypePresenterBuilder($event_manager));
        $artifact_linker          = new ArtifactLinker(
            Tracker_FormElementFactory::instance(),
            new NewChangesetCreator(
                new DBTransactionExecutorWithConnection($db_connection),
                ArtifactChangesetSaver::build(),
                new AfterNewChangesetHandler($tracker_artifact_factory, $fields_retriever),
                \WorkflowFactory::instance(),
                new CommentCreator(
                    new \Tracker_Artifact_Changeset_CommentDao(),
                    \ReferenceManager::instance(),
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
                    new \Tracker_Artifact_Changeset_NewChangesetFieldsValidator(
                        $formelement_factory,
                        new ArtifactLinkValidator(
                            $tracker_artifact_factory,
                            $type_presenter_factory,
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
                    new \Tracker_Artifact_Changeset_ChangesetDataInitializator($formelement_factory),
                    new ParentLinkAction($tracker_artifact_factory),
                ),
                new NewChangesetPostProcessor(
                    $event_manager,
                    ActionsQueuer::build($logger),
                    new ChangesetCommentIndexer(
                        new ItemToIndexQueueEventBased($event_manager),
                        $event_manager,
                        new \Tracker_Artifact_Changeset_CommentDao(),
                    ),
                ),
            ),
            new ArtifactForwardLinksRetriever(new ArtifactLinksByChangesetCache(), new ChangesetValueArtifactLinkDao(), $tracker_artifact_factory),
        );

        return new ArchiveAndDeleteArtifactTask(
            new ArtifactWithTrackerStructureExporter(
                new TrackerXmlExport(
                    TrackerFactory::instance(),
                    new Tracker_Workflow_Trigger_RulesManager(
                        new Tracker_Workflow_Trigger_RulesDao(),
                        $formelement_factory,
                        new Tracker_Workflow_Trigger_RulesProcessor(
                            new \Tracker_Workflow_WorkflowUser(),
                            new SiblingsRetriever(
                                new SiblingsDao(),
                                $tracker_artifact_factory
                            ),
                            $workflow_logger
                        ),
                        $workflow_logger,
                        new Tracker_Workflow_Trigger_RulesBuilderFactory($formelement_factory),
                        new WorkflowRulesManagerLoopSafeGuard($workflow_logger)
                    ),
                    $rng_validator,
                    new Tracker_Artifact_XMLExport(
                        $rng_validator,
                        $tracker_artifact_factory,
                        false,
                        $user_xml_exporter,
                        $external_field_extractor
                    ),
                    $user_xml_exporter,
                    $event_manager,
                    $type_presenter_factory,
                    $artifact_links_usage_dao,
                    $external_field_extractor,
                    $logger
                ),
                new \Tuleap\XMLConvertor()
            ),
            new ArtifactDependenciesCleaner(
                new PermissionsManager(new PermissionsDao()),
                new PriorityManager(
                    new PriorityDao(),
                    new Tracker_Artifact_PriorityHistoryDao(),
                    $user_manager,
                    $tracker_artifact_factory,
                    $db_connection->getDB(),
                ),
                $tracker_artifact_dao,
                new ComputedFieldDaoCache(new ComputedFieldDao()),
                new RecentlyVisitedDao(),
                new PendingArtifactRemovalDao(),
                new PostArtifactMoveReferencesCleaner(
                    new ReverseLinksRetriever(new ReverseLinksDao(), $tracker_artifact_factory),
                    $artifact_linker,
                    $tracker_artifact_factory,
                    new PostArtifactMoveReferenceManager($cross_references_dao)
                ),
                new PostArtifactDeletionCleaner(
                    $cross_reference_manager,
                    $tracker_artifact_dao
                ),
            ),
            new FieldContentIndexer(new ItemToIndexQueueEventBased($event_manager), $event_manager),
            new ChangesetCommentIndexer(
                new ItemToIndexQueueEventBased($event_manager),
                $event_manager,
                new \Tracker_Artifact_Changeset_CommentDao(),
            ),
            $event_manager,
            $db_connection,
            $logger
        );
    }
}
