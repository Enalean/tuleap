<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Trafficlights;

use Project;
use TrackerFactory;
use TrackerXmlImport;
use Logger;
use Feedback;

class FirstConfigCreator
{
    /** @var Config */
    private $config;

    /** @var Tracker_TrackerFactory */
    private $tracker_factory;

    /** @var Tracker_TrackerXmlImport */
    private $xml_import;

    /** @var Logger */
    private $logger;

    public function __construct(
        Config $config,
        TrackerFactory $tracker_factory,
        TrackerXmlImport $xml_import,
        Logger $logger
    ) {
        $this->config           = $config;
        $this->tracker_factory  = $tracker_factory;
        $this->xml_import       = $xml_import;
        $this->logger           = $logger;
    }

    public function createConfigForProjectFromTemplate(
        Project $project,
        Project $template,
        array $tracker_mapping
    ) {
        if (! $this->isConfigNeeded($project)) {
            return;
        }

        $tracker_ids = array(
            'campaign'  => $this->config->getCampaignTrackerId($template),
            'test_def'  => $this->config->getTestDefinitionTrackerId($template),
            'test_exec' => $this->config->getTestExecutionTrackerId($template)
        );

        if (! isset($tracker_mapping[$tracker_ids['campaign']]) ||
            ! isset($tracker_mapping[$tracker_ids['test_def']]) ||
            ! isset($tracker_mapping[$tracker_ids['test_exec']])
        ) {
            return;
        }

        $this->config->setProjectConfiguration(
            $project,
            $tracker_mapping[$tracker_ids['campaign']],
            $tracker_mapping[$tracker_ids['test_def']],
            $tracker_mapping[$tracker_ids['test_exec']]
        );
    }

    private function isConfigNeeded(Project $project)
    {
        return (! $this->config->getCampaignTrackerId($project)) ||
               (! $this->config->getTestDefinitionTrackerId($project)) ||
               (! $this->config->getTestExecutionTrackerId($project));
    }

    public function createConfigForProjectFromXml(Project $project)
    {
        $tracker_ids            = array();
        $template_paths         = array(
            TRAFFICLIGHTS_RESOURCE_DIR .'/Tracker_campaign.xml',
            TRAFFICLIGHTS_RESOURCE_DIR .'/Tracker_test_def.xml',
            TRAFFICLIGHTS_RESOURCE_DIR .'/Tracker_test_exec.xml'
        );

        if (! $this->isConfigNeeded($project)) {
            return;
        }

        foreach($template_paths as $template_path) {
            $tracker_itemname = $this->xml_import->getTrackerItemNameFromXMLFile($template_path);

            if ($this->isTrackerAlreadyCreated($project, $tracker_itemname)) {
                $this->warn(
                    $GLOBALS['Language']->getText(
                        'plugin_trafficlights_first_config',
                        'error_existing_tracker',
                        $tracker_itemname
                    )
                );
                return;
            }

            $tracker = $this->importTrackerStructure($project, $template_path);
            if (! $tracker) {
                $this->warn(
                    $GLOBALS['Language']->getText(
                        'plugin_trafficlights_first_config',
                        'internal_error'
                    )
                );
               return;
            }

            $tracker_ids[$tracker_itemname] = $tracker->getId();
        }

        $this->config->setProjectConfiguration(
            $project,
            $tracker_ids['campaign'],
            $tracker_ids['test_def'],
            $tracker_ids['test_exec']
        );

        $GLOBALS['Response']->addFeedback(
            Feedback::INFO,
            $GLOBALS['Language']->getText(
                'plugin_trafficlights_first_config',
                'created'
            ),
            CODENDI_PURIFIER_DISABLED
        );
    }

    private function warn($message)
    {
        $GLOBALS['Response']->addFeedback(Feedback::WARN, $message);
    }

    /** @return Tracker */
    private function importTrackerStructure(Project $project, $template_path)
    {
        try {
            return $this->xml_import->createFromXMLFile($project, $template_path);
        } catch (Exception $exception) {
            $this->logger->error('Unable to create trafficligts config for '. $project->getId() .': '. $exception->getMessage());
            return;
        }
    }

    /** @return Boolean */
    private function isTrackerAlreadyCreated($project, $tracker_itemname)
    {
        $is_tracker_already_created = $this->tracker_factory->isShortNameExists(
            $tracker_itemname,
            $project->getId()
        );

        return $is_tracker_already_created;
    }
}

