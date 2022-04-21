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
use SimpleXMLElement;
use Tuleap\JiraImport\JiraAgile\Board\Backlog\JiraBoardBacklogRetrieverFromAPI;
use Tuleap\JiraImport\JiraAgile\Board\JiraBoardConfigurationRetrieverFromAPI;
use Tuleap\JiraImport\JiraAgile\IssuesLinkedToEpicsRetriever;
use Tuleap\JiraImport\JiraAgile\JiraBoard;
use Tuleap\JiraImport\JiraAgile\JiraBoardsRetrieverFromAPI;
use Tuleap\JiraImport\JiraAgile\JiraAgileImporter;
use Tuleap\JiraImport\JiraAgile\JiraEpicIssuesRetrieverFromAPI;
use Tuleap\JiraImport\JiraAgile\JiraEpicRetrieverFromAPI;
use Tuleap\JiraImport\JiraAgile\JiraSprintIssuesRetrieverFromAPI;
use Tuleap\JiraImport\JiraAgile\JiraSprintRetrieverFromAPI;
use Tuleap\JiraImport\Project\ArtifactLinkType\ArtifactLinkTypeImporter;
use Tuleap\JiraImport\Project\Dashboard\RoadmapDashboardCreator;
use Tuleap\Project\Registration\Template\EmptyTemplate;
use Tuleap\Project\Registration\Template\TemplateFactory;
use Tuleap\Project\SystemEventRunnerForProjectCreationFromXMLTemplate;
use Tuleap\Project\XML\Import\ArchiveInterface;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Project\XML\XMLFileContentRetriever;
use Tuleap\ProjectMilestones\Widget\DashboardProjectMilestones;
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;
use Tuleap\Tracker\Creation\JiraImporter\Configuration\PlatformConfigurationRetriever;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\LinkedIssuesCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\JiraXmlExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldAndValueIDGenerator;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraTuleapUsersMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUserOnTuleapCache;
use Tuleap\Tracker\Creation\JiraImporter\IssueType;
use Tuleap\Tracker\Creation\JiraImporter\JiraCredentials;
use Tuleap\Tracker\Creation\JiraImporter\JiraTrackerBuilder;
use Tuleap\Tracker\Creation\JiraImporter\UserRole\UserRolesChecker;
use Tuleap\Tracker\Creation\TrackerCreationDataChecker;
use Tuleap\Tracker\XML\Importer\TrackerImporterUser;
use Tuleap\Widget\ProjectHeartbeat;
use Tuleap\Tracker\XML\XMLTracker;
use Tuleap\Widget\ProjectMembers\ProjectMembers;
use User\XML\Import\IFindUserFromXMLReference;
use UserManager;

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
     * @var ArtifactLinkTypeImporter
     */
    private $artifact_link_type_importer;

    /**
     * @var PlatformConfigurationRetriever
     */
    private $platform_configuration_collection_builder;
    /**
     * @var \ProjectManager
     */
    private $project_manager;

    private UserRolesChecker $user_roles_checker;
    private RoadmapDashboardCreator $roadmap_dashboard_creator;

    public function __construct(
        UserManager $user_manager,
        TemplateFactory $template_factory,
        XMLFileContentRetriever $xml_file_content_retriever,
        IFindUserFromXMLReference $user_finder,
        JiraTrackerBuilder $jira_tracker_builder,
        ArtifactLinkTypeImporter $artifact_link_type_importer,
        PlatformConfigurationRetriever $platform_configuration_collection_builder,
        \ProjectManager $project_manager,
        UserRolesChecker $user_roles_checker,
        RoadmapDashboardCreator $roadmap_dashboard_creator,
    ) {
        $this->user_manager                              = $user_manager;
        $this->user_finder                               = $user_finder;
        $this->template_factory                          = $template_factory;
        $this->xml_file_content_retriever                = $xml_file_content_retriever;
        $this->jira_tracker_builder                      = $jira_tracker_builder;
        $this->artifact_link_type_importer               = $artifact_link_type_importer;
        $this->platform_configuration_collection_builder = $platform_configuration_collection_builder;
        $this->project_manager                           = $project_manager;
        $this->user_roles_checker                        = $user_roles_checker;
        $this->roadmap_dashboard_creator                 = $roadmap_dashboard_creator;
    }

    public function create(
        LoggerInterface $logger,
        ClientWrapper $jira_client,
        JiraCredentials $jira_credentials,
        string $jira_project,
        string $shortname,
        string $fullname,
        string $jira_epic_issue_type,
    ): \Project {
        try {
            if ($this->project_manager->getProjectByCaseInsensitiveUnixName($shortname) !== null) {
                throw new \RuntimeException('Project shortname already exists');
            }
            $xml_element = $this->generateFromJira(
                $logger,
                $jira_client,
                $jira_credentials,
                $jira_project,
                $shortname,
                $fullname,
                $jira_epic_issue_type
            );

            $archive = new JiraProjectArchive($xml_element);
            return $this->createProject($logger, $xml_element, $archive);
        } catch (\XML_ParseException $exception) {
            $this->logParseErrors($logger, $exception);
            throw $exception;
        }
    }

    public function generateArchive(
        LoggerInterface $logger,
        ClientWrapper $jira_client,
        JiraCredentials $jira_credentials,
        string $jira_project,
        string $shortname,
        string $fullname,
        string $jira_epic_issue_type,
        string $archive_path,
    ): void {
        try {
            $xml_element = $this->generateFromJira(
                $logger,
                $jira_client,
                $jira_credentials,
                $jira_project,
                $shortname,
                $fullname,
                $jira_epic_issue_type
            );

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

    private function generateFromJira(
        LoggerInterface $logger,
        ClientWrapper $jira_client,
        JiraCredentials $jira_credentials,
        string $jira_project,
        string $shortname,
        string $fullname,
        string $jira_epic_issue_type,
    ): SimpleXMLElement {
        $this->user_roles_checker->checkUserIsAdminOfJiraProject(
            $jira_client,
            $logger,
            $jira_project
        );

        $jira_issue_types = $this->jira_tracker_builder->buildFromProjectKey($jira_client, $jira_project);
        if (count($jira_issue_types) === 0) {
            throw new \RuntimeException("There are no Jira issue types to import");
        }

        $this->artifact_link_type_importer->import($jira_client, $logger);

        $platform_configuration_collection = $this->platform_configuration_collection_builder->getJiraPlatformConfiguration(
            $jira_client,
            $logger
        );

        $linked_issues_collection = new LinkedIssuesCollection();

        $board_retriever = new JiraBoardsRetrieverFromAPI(
            $jira_client,
            $logger,
        );

        $board               = $board_retriever->getFirstScrumBoardForProject($jira_project);
        $board_configuration = null;
        if ($board) {
            $board_configuration_retriever = new JiraBoardConfigurationRetrieverFromAPI(
                $jira_client,
                $logger,
            );
            $board_configuration           = $board_configuration_retriever->getScrumBoardConfiguration($board);
            if ($board_configuration === null) {
                throw new \RuntimeException('Cannot fetch configuration for board ' . $board->id);
            }
            if ($board_configuration->estimation_field) {
                $logger->debug('Agile: estimation field: ' . $board_configuration->estimation_field);
                $platform_configuration_collection->setStoryPointsField($board_configuration->estimation_field);
            }

            $issues_linked_to_epics_retriever = new IssuesLinkedToEpicsRetriever(
                new JiraEpicRetrieverFromAPI(
                    $jira_client,
                    $logger,
                ),
                new JiraEpicIssuesRetrieverFromAPI(
                    $jira_client,
                    $logger,
                ),
            );
            $linked_issues_collection         = $issues_linked_to_epics_retriever->getLinkedIssues($board);
        }

        $jira_agile_importer = new JiraAgileImporter(
            new JiraSprintRetrieverFromAPI(
                $jira_client,
                $logger,
            ),
            new JiraSprintIssuesRetrieverFromAPI(
                $jira_client,
                $logger,
            ),
            new JiraBoardBacklogRetrieverFromAPI(
                $jira_client,
                $logger,
            ),
            \EventManager::instance()
        );

        $import_user = $this->user_manager->getUserById(TrackerImporterUser::ID);
        assert($import_user !== null);
        $jira_exporter = JiraXmlExporter::build(
            $jira_client,
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
            if (
                (string) $service['shortname'] === \trackerPlugin::SERVICE_SHORTNAME ||
                (string) $service['shortname'] === \AgileDashboardPlugin::PLUGIN_SHORTNAME
            ) {
                $service['enabled'] = '1';
            }
        }

        $field_id_generator = new FieldAndValueIDGenerator();

        $trackers_xml = $xml_element->addChild('trackers');

        foreach ($jira_issue_types as $jira_issue_type) {
            $logger->info(sprintf("Import tracker %s", $jira_issue_type->getName()));

            $tracker_fullname = $jira_issue_type->getName();
            $tracker_itemname = TrackerCreationDataChecker::getShortNameWithValidFormat($jira_issue_type->getName());

            $tracker = (new XMLTracker($jira_issue_type->getId(), $tracker_itemname))->withName($tracker_fullname);

            $tracker_xml = $jira_exporter->exportJiraToXml(
                $platform_configuration_collection,
                $tracker,
                $jira_credentials->getJiraUrl(),
                $jira_project,
                $jira_issue_type,
                $field_id_generator,
                $linked_issues_collection
            );

            $jira_exporter->appendTrackerXML($trackers_xml, $tracker_xml);
        }

        if ($board && $board_configuration) {
            $jira_agile_importer->exportScrum(
                $logger,
                $xml_element,
                $board,
                $board_configuration,
                $field_id_generator,
                $import_user,
                $jira_issue_types,
                $jira_epic_issue_type
            );
        }

        return $this->addWidgetOnDashboard(
            $xml_element,
            $board,
            $jira_issue_types,
            $jira_epic_issue_type,
            $logger
        );
    }

    /**
     * @param IssueType[] $jira_issue_types
     */
    private function addWidgetOnDashboard(
        \SimpleXMLElement $xml_element,
        ?JiraBoard $board,
        array $jira_issue_types,
        string $jira_epic_issue_type,
        LoggerInterface $logger,
    ): \SimpleXMLElement {
        $xml_dashboards = $xml_element->addChild('dashboards');
        $xml_dashboard  = $xml_dashboards->addChild("dashboard");
        $xml_dashboard->addAttribute('name', 'Dashboard');

        $xml_dashboard_line     = $xml_dashboard->addChild("line");
        $xml_dashboard_column01 = $xml_dashboard_line->addChild("column");
        if ($board !== null) {
            $xml_dashboard_column01->addChild("widget")->addAttribute("name", DashboardProjectMilestones::NAME);
        }
        $xml_dashboard_column01->addChild("widget")->addAttribute("name", ProjectMembers::NAME);
        $xml_dashboard_column02 = $xml_dashboard_line->addChild("column");
        $xml_dashboard_column02->addChild("widget")->addAttribute("name", ProjectHeartbeat::NAME);

        $this->roadmap_dashboard_creator->createRoadmapDashboard(
            $xml_element,
            $xml_dashboards,
            $jira_issue_types,
            $jira_epic_issue_type,
            $logger
        );

        return $xml_element;
    }

    /**
     * @throws \Tuleap\Project\Registration\Template\InvalidTemplateException
     * @throws \Tuleap\Project\XML\Import\ImportNotValidException
     */
    private function createProject(LoggerInterface $logger, SimpleXMLElement $xml_element, ArchiveInterface $archive): Project
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
