<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation;

use Feedback;
use Project;
use Tracker;
use Tracker_Exception;
use TrackerFactory;
use TrackerFromXmlException;
use TrackerXmlImport;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Tracker\Creation\JiraImporter\Import\JiraXmlExporter;
use Tuleap\Tracker\Creation\JiraImporter\JiraCredentials;
use Tuleap\Tracker\TrackerIsInvalidException;
use Tuleap\XML\MappingsRegistry;
use UserManager;
use XML_ParseException;
use XML_SimpleXMLCDATAFactory;
use XMLImportHelper;

class TrackerCreator
{
    /**
     * @var TrackerXmlImport
     */
    private $tracker_xml_import;
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    /**
     * @var TrackerCreatorXmlErrorDisplayer
     */
    private $xml_error_displayer;
    /**
     * @var TrackerCreationDataChecker
     */
    private $creation_data_checker;
    /**
     * @var XML_SimpleXMLCDATAFactory
     */
    private $cdata_section_factory;

    public function __construct(
        TrackerXmlImport $tracker_xml_import,
        TrackerFactory $tracker_factory,
        TrackerCreatorXmlErrorDisplayer $xml_error_displayer,
        TrackerCreationDataChecker $creation_data_checker,
        XML_SimpleXMLCDATAFactory $cdata_section_factory
    ) {
        $this->tracker_xml_import    = $tracker_xml_import;
        $this->tracker_factory       = $tracker_factory;
        $this->xml_error_displayer   = $xml_error_displayer;
        $this->creation_data_checker = $creation_data_checker;
        $this->cdata_section_factory = $cdata_section_factory;
    }

    public static function build(): self
    {
        $user_finder        = new XMLImportHelper(UserManager::instance());
        $tracker_xml_import = TrackerXmlImport::build($user_finder);

        return new TrackerCreator(
            $tracker_xml_import,
            TrackerFactory::instance(),
            TrackerCreatorXmlErrorDisplayer::build(),
            new TrackerCreationDataChecker(
                \ReferenceManager::instance(),
                new \TrackerDao(),
                TrackerFactory::instance()
            ),
            new XML_SimpleXMLCDATAFactory()
        );
    }

    /**
     * @throws Tracker_Exception
     * @throws TrackerIsInvalidException
     * @throws TrackerCreationHasFailedException
     */
    public function createTrackerFromXml(
        Project $project,
        string $file_path,
        string $name,
        string $description,
        string $itemname,
        ?string $color
    ): Tracker {
        try {
            return $this->tracker_xml_import->createFromXMLFileWithInfo(
                $project,
                $file_path,
                $name,
                $description,
                $itemname,
                $color
            );
        } catch (XML_ParseException $exception) {
            $this->xml_error_displayer->displayErrors($project, $exception->getErrors(), $exception->getFileLines());
            throw new TrackerCreationHasFailedException();
        } catch (TrackerFromXmlException $exception) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $exception->getMessage());
            throw new TrackerCreationHasFailedException();
        }
    }

    /**
     * @throws TrackerCreationHasFailedException
     * @throws \Tuleap\Tracker\TrackerIsInvalidException
     */
    public function duplicateTracker(
        Project $project,
        string $name,
        string $description,
        string $itemname,
        ?string $color,
        string $atid_template,
        \PFUser $user
    ): Tracker {
        $this->creation_data_checker->checkAtTrackerDuplication($itemname, $atid_template, $user);
        $duplicate = $this->tracker_factory->create(
            $project->getId(),
            -1,
            $atid_template,
            $name,
            $description,
            $itemname,
            $color
        );

        if (! $duplicate || ! $duplicate['tracker']) {
            throw new TrackerCreationHasFailedException();
        }

        return $duplicate['tracker'];
    }

    /**
     * @throws TrackerCreationHasFailedException
     * @throws TrackerIsInvalidException
     * @throws Tracker_Exception
     */
    public function createFromJira(
        Project $project,
        string $name,
        string $itemname,
        string $color,
        string $jira_token,
        string $jira_username,
        string $jira_url,
        string $jira_project_id
    ): Tracker {
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
        $this->cdata_section_factory->insert($tracker_xml, 'color', $color);

        $tracker_xml->addChild('cannedResponses');

        try {
            $jira_exporter->exportJiraToXml($tracker_xml, $jira_project_id);
            $trackers = $this->tracker_xml_import->import(
                new ImportConfig(),
                $project,
                $xml,
                new MappingsRegistry(),
                \ForgeConfig::get('tmp_dir')
            );

            if ($trackers && count($trackers) === 1) {
                $tracker_id = (int) array_values($trackers)[0];

                $tracker = $this->tracker_factory->getTrackerById($tracker_id);
                if ($tracker) {
                    return $tracker;
                }
            }

            throw new TrackerCreationHasFailedException();
        } catch (XML_ParseException $exception) {
            $this->xml_error_displayer->displayErrors($project, $exception->getErrors(), $exception->getFileLines());
            throw new TrackerCreationHasFailedException();
        } catch (TrackerFromXmlException $exception) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $exception->getMessage());
            throw new TrackerCreationHasFailedException();
        } catch (JiraImporter\JiraConnectionException $e) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $e->getI18nMessage());
            throw new TrackerCreationHasFailedException();
        }
    }

    /**
     * protected for testing purpose
     */
    protected function getJiraExporter(string $jira_token, string $jira_username, string $jira_url): JiraXmlExporter
    {
        $jira_credentials = new JiraCredentials($jira_url, $jira_username, $jira_token);
        return JiraXmlExporter::build($jira_credentials);
    }
}
