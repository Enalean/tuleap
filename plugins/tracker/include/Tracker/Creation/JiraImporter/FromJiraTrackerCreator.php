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
use ForgeConfig;
use Project;
use Psr\Log\LoggerInterface;
use Tracker;
use Tracker_Exception;
use TrackerFactory;
use TrackerFromXmlException;
use TrackerFromXmlImportCannotBeCreatedException;
use TrackerXmlImport;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment\AttachmentDownloader;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\JiraUserOnTuleapCache;
use Tuleap\Tracker\Creation\JiraImporter\Import\JiraXmlExporter;
use Tuleap\Tracker\Creation\TrackerCreationDataChecker;
use Tuleap\Tracker\Creation\TrackerCreationHasFailedException;
use Tuleap\Tracker\TrackerIsInvalidException;
use Tuleap\XML\MappingsRegistry;
use UserManager;
use XML_ParseException;
use XML_SimpleXMLCDATAFactory;
use XMLImportHelper;

class FromJiraTrackerCreator
{
    private const LOG_IDENTIFIER = "jira_import_syslog";

    /**
     * @var TrackerXmlImport
     */
    private $tracker_xml_import;
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var TrackerCreationDataChecker
     */
    private $creation_data_checker;
    /**
     * @var XML_SimpleXMLCDATAFactory
     */
    private $cdata_section_factory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var JiraUserOnTuleapCache
     */
    private $jira_user_on_tuleap_cache;

    public function __construct(
        TrackerXmlImport $tracker_xml_import,
        TrackerFactory $tracker_factory,
        TrackerCreationDataChecker $creation_data_checker,
        XML_SimpleXMLCDATAFactory $cdata_section_factory,
        LoggerInterface $logger,
        JiraUserOnTuleapCache $jira_user_on_tuleap_cache
    ) {
        $this->tracker_xml_import        = $tracker_xml_import;
        $this->tracker_factory           = $tracker_factory;
        $this->creation_data_checker     = $creation_data_checker;
        $this->cdata_section_factory     = $cdata_section_factory;
        $this->logger                    = $logger;
        $this->jira_user_on_tuleap_cache = $jira_user_on_tuleap_cache;
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
            new XML_SimpleXMLCDATAFactory(),
            $logger,
            $jira_user_on_tuleap_cache
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
        ConcealedString $jira_token,
        string $jira_username,
        string $jira_url,
        string $jira_project_id,
        string $jira_issue_type_name,
        \PFUser $user
    ): Tracker {
        $this->logger->info("Begin import from jira.");
        $this->logger->info("Selected jira project: $jira_project_id");
        $this->logger->info("Selected jira issue type: $jira_issue_type_name");

        $this->creation_data_checker->checkAtProjectCreation((int) $project->getID(), $name, $itemname);
        $jira_exporter = $this->getJiraExporter($jira_token, $jira_username, $jira_url);

        $xml          = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project />');
        $trackers_xml = $xml->addChild('trackers');
        $tracker_xml  = $trackers_xml->addChild('tracker');
        $tracker_xml->addAttribute('instantiate_for_new_projects', '0');
        $tracker_xml->addAttribute('id', "T200");
        $tracker_xml->addAttribute('parent_id', "0");

        $this->cdata_section_factory->insert($tracker_xml, 'name', $name);
        $this->cdata_section_factory->insert($tracker_xml, 'item_name', $itemname);
        $this->cdata_section_factory->insert($tracker_xml, 'description', $description);
        $this->cdata_section_factory->insert($tracker_xml, 'color', $color);

        $tracker_xml->addChild('cannedResponses');

        $jira_exporter->exportJiraToXml($tracker_xml, $jira_url, $jira_project_id, $jira_issue_type_name);

        try {
            $trackers = $this->tracker_xml_import->import(
                new ImportConfig(),
                $project,
                $xml,
                new MappingsRegistry(),
                ForgeConfig::get('tmp_dir') . '/' . AttachmentDownloader::JIRA_TEMP_FOLDER . '/',
                $user
            );
        } catch (
            TrackerFromXmlException |
            TrackerFromXmlImportCannotBeCreatedException |
            Tracker_Exception |
            XML_ParseException $exception
        ) {
            $this->logger->info("Ending import from jira with errors.");
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
     */
    protected function getJiraExporter(
        ConcealedString $jira_token,
        string $jira_username,
        string $jira_url
    ): JiraXmlExporter {
        $jira_credentials = new JiraCredentials($jira_url, $jira_username, $jira_token);

        return JiraXmlExporter::build(
            $jira_credentials,
            $this->logger,
            $this->jira_user_on_tuleap_cache
        );
    }
}
