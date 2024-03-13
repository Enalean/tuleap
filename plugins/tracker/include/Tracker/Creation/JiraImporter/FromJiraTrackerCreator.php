<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\Tracker\Creation\JiraImporter;

use BackendLogger;
use EventManager;
use ForgeConfig;
use Project;
use Psr\Log\LoggerInterface;
use Tracker;
use Tracker_Exception;
use TrackerFactory;
use TrackerFromXmlException;
use TrackerFromXmlImportCannotBeCreatedException;
use TrackerXmlImport;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Tracker\Creation\JiraImporter\Configuration\PlatformConfigurationRetriever;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment\AttachmentDownloader;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\LinkedIssuesCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\JiraIssuesFromIssueTypeInDedicatedTrackerInXmlExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldAndValueIDGenerator;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUserOnTuleapCache;
use Tuleap\Tracker\Creation\JiraImporter\Import\XML\JiraXMLNodeBuilder;
use Tuleap\Tracker\Creation\JiraImporter\UserRole\UserIsNotProjectAdminException;
use Tuleap\Tracker\Creation\JiraImporter\UserRole\UserRolesChecker;
use Tuleap\Tracker\Creation\JiraImporter\UserRole\UserRolesCheckerInterface;
use Tuleap\Tracker\Creation\JiraImporter\UserRole\UserRolesResponseNotWellFormedException;
use Tuleap\Tracker\Creation\TrackerCreationDataChecker;
use Tuleap\Tracker\Creation\TrackerCreationHasFailedException;
use Tuleap\Tracker\TrackerColor;
use Tuleap\Tracker\TrackerIsInvalidException;
use Tuleap\Tracker\XML\XMLTracker;
use Tuleap\XML\MappingsRegistry;
use UserManager;
use XML_ParseException;
use XMLImportHelper;

class FromJiraTrackerCreator
{
    private const LOG_IDENTIFIER     = "jira_import_syslog";
    public const DEFAULT_IMPORT_MODE = "multi-trackers";

    public function __construct(
        private readonly TrackerXmlImport $tracker_xml_import,
        private readonly TrackerFactory $tracker_factory,
        private readonly TrackerCreationDataChecker $creation_data_checker,
        private readonly LoggerInterface $logger,
        private readonly JiraUserOnTuleapCache $jira_user_on_tuleap_cache,
        private readonly PlatformConfigurationRetriever $platform_configuration_retriever,
        private readonly UserRolesCheckerInterface $user_roles_checker,
    ) {
    }

    public static function build(JiraUserOnTuleapCache $jira_user_on_tuleap_cache): self
    {
        $user_finder        = new XMLImportHelper(UserManager::instance());
        $tracker_xml_import = TrackerXmlImport::build($user_finder, BackendLogger::getDefaultLogger());

        $logger = BackendLogger::getDefaultLogger(self::LOG_IDENTIFIER);

        return new self(
            $tracker_xml_import,
            TrackerFactory::instance(),
            new TrackerCreationDataChecker(
                \ReferenceManager::instance(),
                new \TrackerDao(),
                new PendingJiraImportDao(),
                TrackerFactory::instance()
            ),
            $logger,
            $jira_user_on_tuleap_cache,
            new PlatformConfigurationRetriever(
                EventManager::instance()
            ),
            new UserRolesChecker()
        );
    }

    /**
     * @throws TrackerCreationHasFailedException
     * @throws TrackerIsInvalidException
     * @throws Tracker_Exception
     * @throws XML_ParseException
     * @throws TrackerFromXmlException
     * @throws JiraConnectionException
     * @throws \RuntimeException
     */
    public function createFromJira(
        Project $project,
        string $name,
        string $itemname,
        string $description,
        string $color,
        JiraCredentials $jira_credentials,
        JiraClient $jira_client,
        string $jira_project_id,
        string $jira_issue_type_id,
        \PFUser $user,
    ): Tracker {
        $this->logger->info("Begin import from jira.");
        $this->logger->info("Selected jira project: $jira_project_id");
        $this->logger->info("Selected jira issue type: $jira_issue_type_id");

        $this->creation_data_checker->checkAtProjectCreation((int) $project->getID(), $name, $itemname);

        $platform_configuration_collection = $this->platform_configuration_retriever->getJiraPlatformConfiguration(
            $jira_client,
            $this->logger
        );

        $jira_tracker_builder = new JiraTrackerBuilder();
        $issue_type           = $jira_tracker_builder->buildFromIssueTypeId($jira_client, $jira_issue_type_id);
        if (! $issue_type) {
            $this->logger->error('Cannot get issue type ' . $jira_issue_type_id);
            throw new TrackerCreationHasFailedException('Cannot get issue type ' . $jira_issue_type_id);
        }

        try {
            $this->user_roles_checker->checkUserIsAdminOfJiraProject(
                $jira_client,
                $this->logger,
                $jira_project_id
            );
        } catch (UserRolesResponseNotWellFormedException | UserIsNotProjectAdminException $exception) {
            throw new TrackerCreationHasFailedException($exception->getMessage());
        }

        $tracker_for_export = (new XMLTracker('T200', $itemname))
            ->withName($name)
            ->withDescription($description)
            ->withColor(TrackerColor::fromName($color));

        $jira_exporter = $this->getJiraExporter($jira_client, $this->logger);
        $tracker_xml   = $jira_exporter->exportIssuesToXml(
            $platform_configuration_collection,
            $tracker_for_export,
            $jira_credentials->getJiraUrl(),
            $jira_project_id,
            $issue_type,
            new FieldAndValueIDGenerator(),
            new LinkedIssuesCollection(),
            self::DEFAULT_IMPORT_MODE,
        );

        $xml = JiraXMLNodeBuilder::buildProjectSimpleXmlElement($tracker_xml);

        try {
            $trackers = $this->tracker_xml_import->import(
                new ImportConfig(),
                $project,
                $xml,
                new MappingsRegistry(),
                ForgeConfig::get('tmp_dir') . '/' . AttachmentDownloader::JIRA_TEMP_FOLDER . '/',
                $user,
            );
        } catch (
            TrackerFromXmlException |
            TrackerFromXmlImportCannotBeCreatedException |
            Tracker_Exception |
            XML_ParseException $exception
        ) {
            $this->logger->info("Ending import from jira with errors.");
            $this->logger->info($exception->getMessage());
            $this->logger->info($exception->getTraceAsString());
            $xml_content = $tracker_xml->asXML();
            if ($xml_content !== false && is_string($xml_content)) {
                $this->logger->debug("Generated XML content: $xml_content");
            }

            throw $exception;
        }


        if ($trackers && count($trackers) === 1) {
            $tracker_id = (int) array_values($trackers)[0];

            $tracker = $this->tracker_factory->getTrackerById($tracker_id);
            if ($tracker) {
                $this->logger->info("Ending import from jira without error.");
                return $tracker;
            }
        }

        throw new TrackerCreationHasFailedException();
    }

    /**
     * protected for testing purpose
     * @throws \RuntimeException
     * @throws \JsonException
     */
    protected function getJiraExporter(JiraClient $client, LoggerInterface $logger): JiraIssuesFromIssueTypeInDedicatedTrackerInXmlExporter
    {
        return JiraIssuesFromIssueTypeInDedicatedTrackerInXmlExporter::build(
            $client,
            $this->logger,
            $this->jira_user_on_tuleap_cache,
        );
    }
}
