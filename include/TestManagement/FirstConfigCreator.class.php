<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

        foreach($template_tracker_ids as $tracker_itemname => $tracker_id) {
            if (! isset($tracker_mapping[$tracker_id])) {
                $tracker = $this->getTracker($project, $tracker_itemname);

                if (! $tracker) {
                    return;
                }

                $project_tracker_ids[$tracker_itemname] = $tracker->getId();
            } else {
                $project_tracker_ids[$tracker_itemname] = $tracker_mapping[$tracker_id];
            }
        }

        $this->config->setProjectConfiguration(
            $project,
            $project_tracker_ids[CAMPAIGN_TRACKER_SHORTNAME],
            $project_tracker_ids[DEFINITION_TRACKER_SHORTNAME],
            $project_tracker_ids[EXECUTION_TRACKER_SHORTNAME],
            $project_tracker_ids[ISSUE_TRACKER_SHORTNAME]
        );
    }

    public function createConfigForProjectFromXML(Project $project)
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

        foreach($tracker_itemnames as $tracker_itemname) {
            $tracker = $this->getTracker($project, $tracker_itemname);

            if (! $tracker) {
                $GLOBALS['Response']->redirect(TESTMANAGEMENT_BASE_URL . '/?group_id=' . urlencode($project->getID()));
            }

            $tracker_ids[$tracker_itemname] = $tracker->getId();
        }

        $this->config->setProjectConfiguration(
            $project,
            $tracker_ids[CAMPAIGN_TRACKER_SHORTNAME],
            $tracker_ids[DEFINITION_TRACKER_SHORTNAME],
            $tracker_ids[EXECUTION_TRACKER_SHORTNAME],
            $tracker_ids[ISSUE_TRACKER_SHORTNAME]
        );

        $this->success();
    }

    /**
     * @return null|\Tracker
     */
    private function getTracker(Project $project, $tracker_itemname)
    {
        $tracker = null;
        if ($this->isTrackerAlreadyCreated($project, $tracker_itemname)) {
            $tracker = $this->tracker_factory->getTrackerByShortnameAndProjectId(
                $tracker_itemname,
                $project->getId()
            );

            if (! $tracker) {
                # Tracker using this shortname is from TrackerEngine v3
                $GLOBALS['Response']->addFeedback(
                    Feedback::WARN,
                    $GLOBALS['Language']->getText(
                        'plugin_testmanagement_first_config',
                        'tracker_engine_version_error',
                        $tracker_itemname
                    )
                );
            }
        } else {
            $tracker = $this->createTrackerFromXML($project, $tracker_itemname);
        }

        return $tracker;
    }

    private function success()
    {
        $GLOBALS['Response']->addFeedback(
            Feedback::INFO,
            $GLOBALS['Language']->getText(
                'plugin_testmanagement_first_config',
                'created'
            )
        );
    }

    /** @return \Tracker|null */
    private function createTrackerFromXML(Project $project, $tracker_itemname)
    {
        $template_path = TESTMANAGEMENT_RESOURCE_DIR .'/Tracker_'.$tracker_itemname.'.xml';

        $tracker = $this->importTrackerStructure($project, $template_path);
        if (! $tracker) {
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                $GLOBALS['Language']->getText(
                    'plugin_testmanagement_first_config',
                    'internal_error'
                )
            );
        }

        return $tracker;
    }

    /** @return \Tracker */
    private function importTrackerStructure(Project $project, $template_path)
    {
        try {
            return $this->xml_import->createFromXMLFile($project, $template_path);
        } catch (\Exception $exception) {
            $this->logger->error('Unable to create testmanagement config for '. $project->getId() .': '. $exception->getMessage());
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

