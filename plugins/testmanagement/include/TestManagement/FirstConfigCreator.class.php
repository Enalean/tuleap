<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\TestManagement;

use Project;
use Psr\Log\LoggerInterface;
use TrackerFactory;
use TrackerXmlImport;
use Tuleap\TestManagement\Administration\TrackerChecker;
use Tuleap\TestManagement\Administration\TrackerHasAtLeastOneFrozenFieldsPostActionException;
use Tuleap\TestManagement\Administration\TrackerNotInProjectException;

class FirstConfigCreator
{
    /** @var Config */
    private $config;

    /** @var TrackerFactory */
    private $tracker_factory;

    /** @var TrackerXmlImport */
    private $xml_import;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @var TrackerChecker
     */
    private $tracker_checker;

    public function __construct(
        Config $config,
        TrackerFactory $tracker_factory,
        TrackerXmlImport $xml_import,
        TrackerChecker $tracker_checker,
        LoggerInterface $logger
    ) {
        $this->config           = $config;
        $this->tracker_factory  = $tracker_factory;
        $this->xml_import       = $xml_import;
        $this->logger           = $logger;
        $this->tracker_checker  = $tracker_checker;
    }

    /**
     * @throws TrackerComesFromLegacyEngineException
     * @throws TrackerNotCreatedException
     * @throws TrackerHasAtLeastOneFrozenFieldsPostActionException
     * @throws TrackerNotInProjectException
     *
     */
    public function createConfigForProjectFromTemplate(
        Project $project,
        Project $template,
        array $tracker_mapping
    ): void {
        if (! $this->config->isConfigNeeded($project)) {
            return;
        }

        $template_tracker_ids = array(
            CAMPAIGN_TRACKER_SHORTNAME   => $this->config->getCampaignTrackerId($template),
            DEFINITION_TRACKER_SHORTNAME => $this->config->getTestDefinitionTrackerId($template),
            EXECUTION_TRACKER_SHORTNAME  => $this->config->getTestExecutionTrackerId($template),
            ISSUE_TRACKER_SHORTNAME      => $this->config->getIssueTrackerId($template)
        );
        $project_tracker_ids = array();

        foreach ($template_tracker_ids as $tracker_itemname => $tracker_id) {
            if (! isset($tracker_mapping[$tracker_id])) {
                $tracker = $this->getTracker($project, $tracker_itemname);
                if ($tracker) {
                    $project_tracker_ids[$tracker_itemname] = $tracker->getId();
                }
            } elseif ($tracker_id) {
                $project_tracker_ids[$tracker_itemname] = $tracker_mapping[$tracker_id];
            }
        }

        $this->saveConfiguration($project, $project_tracker_ids);
    }

    /**
     * @throws TrackerComesFromLegacyEngineException
     * @throws TrackerNotCreatedException
     * @throws TrackerHasAtLeastOneFrozenFieldsPostActionException
     * @throws TrackerNotInProjectException
     *
     */
    public function createConfigForProjectFromXML(Project $project): void
    {
        $tracker_ids       = array();
        $tracker_itemnames = array(
            CAMPAIGN_TRACKER_SHORTNAME,
            DEFINITION_TRACKER_SHORTNAME,
            EXECUTION_TRACKER_SHORTNAME,
            ISSUE_TRACKER_SHORTNAME
        );

        if (! $this->config->isConfigNeeded($project)) {
            return;
        }

        foreach ($tracker_itemnames as $tracker_itemname) {
            $tracker = $this->getTracker($project, $tracker_itemname);
            if (! $tracker) {
                continue;
            }
            $tracker_ids[$tracker_itemname] = $tracker->getId();
        }

        $this->saveConfiguration($project, $tracker_ids);
    }

    /**
     * @throws TrackerHasAtLeastOneFrozenFieldsPostActionException
     * @throws TrackerNotInProjectException
     *
     */
    private function saveConfiguration(Project $project, array $tracker_ids): void
    {
        $campaign_tracker_id   = $tracker_ids[CAMPAIGN_TRACKER_SHORTNAME];
        $definition_tracker_id = $tracker_ids[DEFINITION_TRACKER_SHORTNAME];
        $execution_tracker_id  = $tracker_ids[EXECUTION_TRACKER_SHORTNAME];
        $issue_tracker_id      = $tracker_ids[ISSUE_TRACKER_SHORTNAME];

        $this->tracker_checker->checkTrackerIsInProject($project, $campaign_tracker_id);
        $this->tracker_checker->checkTrackerIsInProject($project, $issue_tracker_id);
        $this->tracker_checker->checkSubmittedTrackerCanBeUsed($project, $definition_tracker_id);
        $this->tracker_checker->checkSubmittedTrackerCanBeUsed($project, $execution_tracker_id);

        $this->config->setProjectConfiguration(
            $project,
            $campaign_tracker_id,
            $definition_tracker_id,
            $execution_tracker_id,
            $issue_tracker_id
        );
    }

    /**
     * @return \Tracker|null
     *
     * @throws TrackerComesFromLegacyEngineException
     * @throws TrackerNotCreatedException
     */
    private function getTracker(Project $project, string $tracker_itemname)
    {
        $tracker = null;
        if ($this->isTrackerAlreadyCreated($project, $tracker_itemname)) {
            $tracker = $this->tracker_factory->getTrackerByShortnameAndProjectId(
                $tracker_itemname,
                $project->getId()
            );

            if (! $tracker) {
                // Tracker using this shortname is from TrackerEngine v3
                throw new TrackerComesFromLegacyEngineException($tracker_itemname);
            }
        } else {
            $tracker = $this->createTrackerFromXML($project, $tracker_itemname);
        }

        return $tracker;
    }

    /**
     * @return \Tracker|null
     * @throws TrackerNotCreatedException
     */
    private function createTrackerFromXML(Project $project, string $tracker_itemname)
    {
        $template_path = TESTMANAGEMENT_RESOURCE_DIR . '/Tracker_' . $tracker_itemname . '.xml';
        if ($tracker_itemname === ISSUE_TRACKER_SHORTNAME) {
            $template_path = (string) realpath(__DIR__ . '/../../../tracker/resources/templates/Tracker_Bugs.xml');
        }

        $tracker = $this->importTrackerStructure($project, $template_path);
        if (! $tracker) {
            throw new TrackerNotCreatedException();
        }

        return $tracker;
    }

    /**
     * @return \Tracker|null
     */
    private function importTrackerStructure(Project $project, string $template_path)
    {
        $created_tracker = null;
        try {
            $created_tracker = $this->xml_import->createFromXMLFile($project, $template_path);
        } catch (\Exception $exception) {
            $this->logger->error('Unable to create testmanagement config for ' . $project->getId() . ': ' . $exception->getMessage());
        } finally {
            return $created_tracker;
        }
    }

    /**
     * @return bool
     */
    private function isTrackerAlreadyCreated(Project $project, string $tracker_itemname)
    {
        $is_tracker_already_created = $this->tracker_factory->isShortNameExists(
            $tracker_itemname,
            $project->getId()
        );

        return $is_tracker_already_created;
    }
}
