<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

use BackendLogger;
use CrossReferenceManager;
use PermissionsManager;
use ProjectHistoryDao;
use Tracker_Artifact_PriorityDao;
use Tracker_Artifact_PriorityHistoryDao;
use Tracker_Artifact_PriorityManager;
use Tracker_Artifact_XMLExport;
use Tracker_ArtifactDao;
use Tracker_ArtifactFactory;
use Tracker_FormElement_Field_ComputedDao;
use Tracker_FormElement_Field_ComputedDaoCache;
use Tracker_FormElementFactory;
use Tracker_Workflow_Trigger_RulesBuilderFactory;
use Tracker_Workflow_Trigger_RulesDao;
use Tracker_Workflow_Trigger_RulesManager;
use Tracker_Workflow_Trigger_RulesProcessor;
use Tracker_Workflow_WorkflowUser;
use TrackerFactory;
use TrackerXmlExport;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Artifact\ArtifactWithTrackerStructureExporter;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenterFactory;
use Tuleap\Tracker\RecentlyVisited\RecentlyVisitedDao;
use Tuleap\XMLConvertor;
use UserXMLExportedCollection;
use UserXMLExporter;
use WorkflowBackendLogger;
use XML_RNGValidator;
use XML_SimpleXMLCDATAFactory;

class ArtifactDeletorBuilder
{
    /**
     * @return ArtifactDeletor
     */
    public static function build()
    {
        $user_manager              = \UserManager::instance();
        $artifact_factory          = Tracker_ArtifactFactory::instance();
        $artifact_priority_manager = new Tracker_Artifact_PriorityManager(
            new Tracker_Artifact_PriorityDao(),
            new Tracker_Artifact_PriorityHistoryDao(),
            $user_manager,
            $artifact_factory
        );

        $rng_validator                = new XML_RNGValidator();
        $user_xml_exporter            = new UserXMLExporter(
            $user_manager,
            new UserXMLExportedCollection($rng_validator, new XML_SimpleXMLCDATAFactory())
        );
        $artifact_links_usage_dao     = new ArtifactLinksUsageDao();
        $logger                       = new WorkflowBackendLogger(new BackendLogger());
        $tracker_form_element_factory = Tracker_FormElementFactory::instance();
        $event_manager                = \EventManager::instance();
        $exporter                     = new TrackerXmlExport(
            TrackerFactory::instance(),
            new Tracker_Workflow_Trigger_RulesManager(
                new Tracker_Workflow_Trigger_RulesDao(),
                $tracker_form_element_factory,
                new Tracker_Workflow_Trigger_RulesProcessor(new Tracker_Workflow_WorkflowUser(), $logger),
                $logger,
                new Tracker_Workflow_Trigger_RulesBuilderFactory($tracker_form_element_factory)
            ),
            $rng_validator,
            new Tracker_Artifact_XMLExport($rng_validator, $artifact_factory, false, $user_xml_exporter),
            $user_xml_exporter,
            $event_manager,
            new NaturePresenterFactory(new NatureDao(), $artifact_links_usage_dao),
            $artifact_links_usage_dao
        );

        return new ArtifactDeletor(
            new Tracker_ArtifactDao(),
            PermissionsManager::instance(),
            new CrossReferenceManager(),
            $artifact_priority_manager,
            new ProjectHistoryDao(),
            $event_manager,
            new ArtifactWithTrackerStructureExporter($exporter, new XMLConvertor()),
            new Tracker_FormElement_Field_ComputedDaoCache(new Tracker_FormElement_Field_ComputedDao()),
            new RecentlyVisitedDao()
        );
    }
}
