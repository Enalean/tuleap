<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Tracker\XML\Importer\TrackerExtraConfiguration;

class AgileDashboard_FirstScrumCreator
{

    /** @var Project */
    private $project;

    /** @var PlanningFactory */
    private $planning_factory;

    /** @var TrackerFactory */
    private $tracker_factory;

    /** @var ProjectXMLImporter */
    private $xml_importer;

    /** @var string */
    private $template_path;

    /** @var string[] */
    private $reserved_names = array(
        'epic', 'rel', 'sprint', 'task', 'story'
    );

    public function __construct(
        Project $project,
        PlanningFactory $planning_factory,
        TrackerFactory $tracker_factory,
        ProjectXMLImporter $xml_importer
    ) {
        $this->project          = $project;
        $this->xml_importer     = $xml_importer;
        $this->planning_factory = $planning_factory;
        $this->tracker_factory  = $tracker_factory;
        $this->template_path    = __DIR__ . '/../../resources/templates/scrum_dashboard_template.xml';
    }

    public function createFirstScrum()
    {
        if ($this->areThereConfiguredPlannings()) {
            return;
        }

        $already_existing_tracker = $this->getAlreadyExistingTracker();
        if ($already_existing_tracker) {
            $GLOBALS['Response']->addFeedback(Feedback::WARN, $GLOBALS['Language']->getText(
                'plugin_agiledashboard_first_scrum',
                'error_existing_tracker',
                $already_existing_tracker
            ));
            return;
        }

        $config              = new ImportConfig();
        $extra_configuration = new TrackerExtraConfiguration(['bugs']);
        $config->addExtraConfiguration($extra_configuration);

        try {
            $this->xml_importer->import($config, $this->project->getId(), $this->template_path);
            $GLOBALS['Response']->addFeedback(Feedback::INFO, $GLOBALS['Language']->getText('plugin_agiledashboard_first_scrum', 'created'));
        } catch (Exception $e) {
            $GLOBALS['Response']->addFeedback(Feedback::WARN, $GLOBALS['Language']->getText('plugin_agiledashboard_first_scrum', 'internal_error'));
        }
    }

    private function areThereConfiguredPlannings()
    {
        return count($this->planning_factory->getPlanningTrackerIdsByGroupId($this->project->getId())) > 0;
    }

    private function getAlreadyExistingTracker()
    {
        foreach ($this->reserved_names as $itemname) {
            if ($this->tracker_factory->isShortNameExists($itemname, $this->project->getId())) {
                return $itemname;
            }
        }
    }
}
