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
use Tracker_Artifact_PriorityDao;
use Tracker_Artifact_PriorityHistoryDao;
use Tracker_Artifact_PriorityManager;
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
use Tuleap\DB\DBFactory;
use Tuleap\Project\XML\Import\ExternalFieldsExtractor;
use Tuleap\Reference\CrossReferenceManager;
use Tuleap\Reference\CrossReferencesDao;
use Tuleap\Search\ItemToIndexQueueEventBased;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Artifact\ArtifactWithTrackerStructureExporter;
use Tuleap\Tracker\Artifact\Changeset\Comment\ChangesetCommentIndexer;
use Tuleap\Tracker\Artifact\RecentlyVisited\RecentlyVisitedDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\FormElement\Field\Computed\ComputedFieldDao;
use Tuleap\Tracker\FormElement\Field\Computed\ComputedFieldDaoCache;
use Tuleap\Tracker\FormElement\FieldContentIndexer;
use Tuleap\Tracker\Workflow\Trigger\Siblings\SiblingsDao;
use Tuleap\Tracker\Workflow\Trigger\Siblings\SiblingsRetriever;
use Tuleap\Tracker\Workflow\WorkflowBackendLogger;
use Tuleap\Tracker\Workflow\WorkflowRulesManagerLoopSafeGuard;
use UserManager;
use UserXMLExportedCollection;
use UserXMLExporter;
use XML_RNGValidator;
use XML_SimpleXMLCDATAFactory;

class ArchiveAndDeleteArtifactTaskBuilder
{
    public function build(LoggerInterface $logger)
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

        $workflow_logger = new WorkflowBackendLogger(\BackendLogger::getDefaultLogger(), ForgeConfig::get('sys_logger_level'));

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
                    new TypePresenterFactory(new TypeDao(), new ArtifactLinksUsageDao()),
                    new ArtifactLinksUsageDao(),
                    $external_field_extractor
                ),
                new \Tuleap\XMLConvertor()
            ),
            new ArtifactDependenciesDeletor(
                new PermissionsManager(new PermissionsDao()),
                new CrossReferenceManager(new CrossReferencesDao()),
                new Tracker_Artifact_PriorityManager(
                    new Tracker_Artifact_PriorityDao(),
                    new Tracker_Artifact_PriorityHistoryDao(),
                    $user_manager,
                    $tracker_artifact_factory
                ),
                new Tracker_ArtifactDao(),
                new ComputedFieldDaoCache(new ComputedFieldDao()),
                new RecentlyVisitedDao(),
                new PendingArtifactRemovalDao(),
                new ArtifactChangesetValueDeletorDAO()
            ),
            new FieldContentIndexer(new ItemToIndexQueueEventBased($event_manager), $event_manager),
            new ChangesetCommentIndexer(
                new ItemToIndexQueueEventBased($event_manager),
                $event_manager,
                new \Tracker_Artifact_Changeset_CommentDao(),
            ),
            $event_manager,
            DBFactory::getMainTuleapDBConnection(),
            $logger
        );
    }
}
