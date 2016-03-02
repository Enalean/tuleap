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

class AgileDashboard_FirstKanbanCreator {

    /** @var Project */
    private $project;

    /** @var AgileDashboard_KanbanManager */
    private $kanban_manager;

    /** @var TrackerFactory */
    private $tracker_factory;

    /** @var TrackerXmlImport */
    private $xml_import;

    /** @var string */
    private $template_path;

    /** var string */
    private $tracker_itemname;

    public function __construct(
        Project $project,
        AgileDashboard_KanbanManager $kanban_manager,
        TrackerFactory $tracker_factory,
        TrackerXmlImport $xml_import
    ) {
        $this->project          = $project;
        $this->kanban_manager   = $kanban_manager;
        $this->tracker_factory  = $tracker_factory;
        $this->xml_import       = $xml_import;
        $this->template_path    = AGILEDASHBOARD_RESOURCE_DIR .'/Tracker_kanbantask.xml';
        $this->tracker_itemname = $this->xml_import->getTrackerItemNameFromXMLFile($this->template_path);
    }

    public function createFirstKanban() {
        if (! $this->isFirstKanbanNeeded()) {
            return;
        }

        if ($this->isTrackerAlreadyCreated()) {
            $this->warn(
                $GLOBALS['Language']->getText(
                    'plugin_agiledashboard_first_kanban',
                    'error_existing_tracker',
                    $this->tracker_itemname
                )
            );
            return;
        }

        $tracker = $this->importTrackerStructure();
        if (! $tracker) {
            $this->warn($GLOBALS['Language']->getText('plugin_agiledashboard_first_kanban', 'internal_error'));
            return;
        }

        $kanban_id = $this->kanban_manager->createKanban($tracker->getName(), $tracker->getId());
        if (! $kanban_id) {
            $this->warn($GLOBALS['Language']->getText('plugin_agiledashboard_first_kanban', 'internal_error'));
            return;
        }

        $GLOBALS['Response']->addFeedback(
            Feedback::INFO,
            $GLOBALS['Language']->getText(
                'plugin_agiledashboard_first_kanban',
                'created',
                '?'. http_build_query(
                    array(
                        'group_id' => $this->project->getId(),
                        'action'   => 'showKanban',
                        'id'       => $kanban_id
                    )
                )
            ),
            CODENDI_PURIFIER_DISABLED
        );
    }

    private function warn($message) {
        $GLOBALS['Response']->addFeedback(Feedback::WARN, $message);
    }

    /** @return Tracker */
    private function importTrackerStructure() {
        try {
            return $this->xml_import->createFromXMLFile($this->project, $this->template_path);
        } catch (Exception $exception) {
            $logger = new BackendLogger();
            $logger->error('Unable to create first kanban for '. $this->project->getId() .': '. $exception->getMessage());
            return;
        }
    }

    private function isFirstKanbanNeeded() {
        $used_trackers = $this->kanban_manager->getTrackersUsedAsKanban($this->project);

        return count($used_trackers) === 0;
    }

    private function isTrackerAlreadyCreated() {
        $is_tracker_already_created = $this->tracker_factory->isShortNameExists(
            $this->tracker_itemname,
            $this->project->getId()
        );

        return $is_tracker_already_created;
    }
}
