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
 *
 */

declare(strict_types=1);

namespace Tuleap\JiraImport\Project;

use Project;
use ProjectCreationData;
use ProjectCreator;
use ProjectXMLImporter;
use Psr\Log\LoggerInterface;
use Tuleap\JiraImport\JiraAgile\JiraBoardsRetrieverFromAPI;
use Tuleap\JiraImport\JiraAgile\JiraAgileImporter;
use Tuleap\JiraImport\Project\ArtifactLinkType\ArtifactLinkTypeImporter;
use Tuleap\Project\Registration\Template\EmptyTemplate;
use Tuleap\Project\Registration\Template\TemplateFactory;
use Tuleap\Project\SystemEventRunnerForProjectCreationFromXMLTemplate;
use Tuleap\Project\XML\Import\ArchiveInterface;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Project\XML\XMLFileContentRetriever;
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\JiraTuleapUsersMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\JiraUserOnTuleapCache;
use Tuleap\Tracker\Creation\JiraImporter\Import\JiraXmlExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldAndValueIDGenerator;
use Tuleap\Tracker\Creation\JiraImporter\JiraCredentials;
use Tuleap\Tracker\Creation\JiraImporter\JiraTrackerBuilder;
use Tuleap\Tracker\XML\Importer\TrackerImporterUser;
use Tuleap\Widget\ProjectHeartbeat;
use Tuleap\Tracker\XML\XMLTracker;
use User\XML\Import\IFindUserFromXMLReference;
use UserManager;
use XML_SimpleXMLCDATAFactory;

final class CreateProjectFromJira
{
    /**
     * @var IFindUserFromXMLReference
     */
    private $user_finder;
    /**
     * @var TemplateFactory
     */
    private $template_factory;
    /**
     * @var XMLFileContentRetriever
     */
    private $xml_file_content_retriever;
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var JiraTrackerBuilder
     */
    private $jira_tracker_builder;
    /**
     * @var XML_SimpleXMLCDATAFactory
     */
    private $cdata_section_factory;
    /**
     * @var ArtifactLinkTypeImporter
     */
    private $artifact_link_type_importer;

    public function __construct(
        UserManager $user_manager,
        TemplateFactory $template_factory,
        XMLFileContentRetriever $xml_file_content_retriever,
        IFindUserFromXMLReference $user_finder,
        JiraTrackerBuilder $jira_tracker_builder,
        XML_SimpleXMLCDATAFactory $cdata_section_factory,
        ArtifactLinkTypeImporter $artifact_link_type_importer
    ) {
        $this->user_manager                = $user_manager;
        $this->user_finder                 = $user_finder;
        $this->template_factory            = $template_factory;
        $this->xml_file_content_retriever  = $xml_file_content_retriever;
        $this->jira_tracker_builder        = $jira_tracker_builder;
        $this->cdata_section_factory       = $cdata_section_factory;
        $this->artifact_link_type_importer = $artifact_link_type_importer;
    }

    public function create(LoggerInterface $logger, ClientWrapper $jira_client, JiraCredentials $jira_credentials, string $jira_project, string $shortname, string $fullname): \Project
    {
        try {
            $xml_element = $this->generateFromJira($logger, $jira_client, $jira_credentials, $jira_project, $shortname, $fullname);
            $archive     = new JiraProjectArchive($xml_element);
            return $this->createProject($logger, $xml_element, $archive);
        } catch (\XML_ParseException $exception) {
            $this->logParseErrors($logger, $exception);
            throw $exception;
        }
    }

    public function generateArchive(LoggerInterface $logger, ClientWrapper $jira_client, JiraCredentials $jira_credentials, string $jira_project, string $shortname, string $fullname, string $archive_path): void
    {
        try {
            $xml_element = $this->generateFromJira($logger, $jira_client, $jira_credentials, $jira_project, $shortname, $fullname);
            $xml_element->saveXML($archive_path);
        } catch (\XML_ParseException $exception) {
            $this->logParseErrors($logger, $exception);
            throw $exception;
        }
    }

    private function logParseErrors(LoggerInterface $logger, \XML_ParseException $exception): void
    {
        $logger->debug($exception->getIndentedXml());
        foreach ($exception->getErrors() as $error) {
            $logger->error($error->getMessage() . ' (Type: ' . $error->getType() . ') Line: ' . $error->getLine() . ' Column: ' . $error->getColumn());
            $logger->error('Error @ line' . $exception->getSourceXMLForError($error));
        }
    }

    private function generateFromJira(LoggerInterface $logger, ClientWrapper $jira_client, JiraCredentials $jira_credentials, string $jira_project, string $shortname, string $fullname): \SimpleXMLElement
    {
        $jira_trackers = $this->jira_tracker_builder->build($jira_client, $jira_project);
        if (count($jira_trackers) === 0) {
            throw new \RuntimeException("There are no Jira issue types to import");
        }

        $this->artifact_link_type_importer->import($jira_client);

        $jira_agile_importer = new JiraAgileImporter(
            new JiraBoardsRetrieverFromAPI(
                $jira_client,
                $logger,
            ),
        );

        $import_user = $this->user_manager->getUserById(TrackerImporterUser::ID);
        assert($import_user !== null);
        $jira_exporter = JiraXmlExporter::build(
            $jira_credentials,
            $logger,
            new JiraUserOnTuleapCache(
                new JiraTuleapUsersMapping(),
                $import_user,
            ),
        );

        $template    = $this->template_factory->getTemplate(EmptyTemplate::NAME);
        $xml_element = $this->xml_file_content_retriever->getSimpleXMLElementFromFilePath($template->getXMLPath());

        $xml_element['unix-name'] = $shortname;
        $xml_element['full-name'] = $fullname;
        $xml_element['access']    = 'private';

        foreach ($xml_element->services->service as $service) {
            if ((string) $service['shortname'] === \trackerPlugin::SERVICE_SHORTNAME) {
                $service['enabled'] = '1';
            }
        }

        $field_id_generator = new FieldAndValueIDGenerator();

        $trackers_xml = $xml_element->addChild('trackers');

        foreach ($jira_trackers as $jira_tracker) {
            $logger->info(sprintf("Import tracker %s", $jira_tracker['name']));

            $tracker_fullname = $jira_tracker['name'];
            $tracker_itemname = str_replace('-', '_', $jira_tracker['name']);

            $tracker     = (new XMLTracker($jira_tracker['id'], $tracker_itemname))->withName($tracker_fullname);
            $tracker_xml = $tracker->export($trackers_xml);

            $jira_exporter->exportJiraToXml($tracker_xml, $jira_credentials->getJiraUrl(), $jira_project, $jira_tracker['id'], $field_id_generator);
        }

        $jira_agile_importer->exportScrum($logger, $xml_element, $jira_project, $field_id_generator);

        return $this->addWidgetOnDashboard($xml_element, [ProjectHeartbeat::NAME]);
    }

    /**
     * @param string[] $widget_names
     */
    private function addWidgetOnDashboard(\SimpleXMLElement $xml_element, array $widget_names): \SimpleXMLElement
    {
        $xml_dashboard = $xml_element->addChild('dashboards')->addChild("dashboard");
        $xml_dashboard->addAttribute('name', 'Dashboard');

        foreach ($widget_names as $widget_name) {
            $xml_dashboard
                ->addChild("line")
                ->addChild("column")
                ->addChild("widget")
                ->addAttribute("name", $widget_name);
        }

        return $xml_element;
    }

    /**
     * @throws \Tuleap\Project\Registration\Template\InvalidTemplateException
     * @throws \Tuleap\Project\XML\Import\ImportNotValidException
     */
    private function createProject(LoggerInterface $logger, \SimpleXMLElement $xml_element, ArchiveInterface $archive): Project
    {
        $data = ProjectCreationData::buildFromXML(
            $xml_element,
            null,
            null,
            $logger
        );

        $project_xml_importer = ProjectXMLImporter::build(
            $this->user_finder,
            ProjectCreator::buildSelfByPassValidation(),
            $logger,
        );

        return $project_xml_importer->importWithProjectData(
            new ImportConfig(),
            $archive,
            new SystemEventRunnerForProjectCreationFromXMLTemplate(),
            $data
        );
    }
}
