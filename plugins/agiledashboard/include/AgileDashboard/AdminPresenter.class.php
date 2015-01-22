<?php
/**
 * Copyright (c) Enalean, 2012-2014. All Rights Reserved.
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

class AdminPresenter {

    /** @var bool */
    public $uses_lab_features;

    /** @var int */
    public $group_id;

    /** @var array */
    public $plannings = array();

    /** @var bool */
    public $kanban_activated;

    /** @var bool */
    public $scrum_activated;

    /** @var bool */
    public $all_activated;

    /** @var string */
    public $scrum_title;

    /** @var string */
    public $kanban_title;

    private $root_planning_tracker_url;
    private $root_planning_name;
    private $planning_hierarchy = array();
    private $can_create_planning;

    public function __construct(
        array $plannings,
        $group_id,
        $can_create_planning,
        $root_planning_tracker_url,
        $root_planning_name,
        array $hierarchy,
        $uses_lab_features,
        $kanban_activated,
        $scrum_activated,
        $all_activated,
        $scrum_title,
        $kanban_title
    ) {
        $this->plannings                 = $plannings;
        $this->group_id                  = $group_id;
        $this->can_create_planning       = $can_create_planning;
        $this->root_planning_tracker_url = $root_planning_tracker_url;
        $this->root_planning_name        = $root_planning_name;
        $this->uses_lab_features         = $uses_lab_features;
        $this->kanban_activated          = $kanban_activated;
        $this->scrum_activated           = $scrum_activated;
        $this->all_activated             = $all_activated;
        $this->scrum_title               = $scrum_title;
        $this->kanban_title              = $kanban_title;

        foreach ($hierarchy as $tracker) {
            $this->planning_hierarchy[] = $tracker->getName();
        }
    }

    public function has_plannings() {
       return count($this->plannings) > 0;
    }

    public function create_planning() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_create');
    }

    public function import_template() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'import_template');
    }

    public function export_template() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'export_template');
    }

    public function import_export_section() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'import_export_section');
    }

    public function planning_section() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_section');
    }

    public function can_create_planning() {
        return $this->can_create_planning;
    }

    public function cannot_create_planning() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'cannot_create_planning');
    }

    public function cannot_create_planning_no_trackers() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'cannot_create_planning_no_trackers');
    }

    public function cannot_create_planning_hierarchy() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'cannot_create_planning_hierarchy', array(
            $this->getPlanningNamesHierarchy()
        ));
    }

    private function getPlanningNamesHierarchy() {
        if (count($this->planning_hierarchy) > 0) {
            return implode(', ', $this->planning_hierarchy);
        } else {
            return '';
        }
    }

    public function cannot_create_planning_config() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'cannot_create_planning_config', array(
            $this->root_planning_name,
            $this->root_planning_tracker_url.'&func=admin-hierarchy',
        ));
    }

    public function config_title() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'config_title');
    }

    public function config_submit_label() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'config_submit_label');
    }

    public function kanban_label() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'kanban_label');
    }

    public function scrum_label() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'scrum_label');
    }

    public function all_label() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'all_label');
    }

    public function activate_service_legend() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'activate_service_legend');
    }

    public function lab_mode_message() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'lab_mode_message');
    }

    public function title_label() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'title');
    }

    public function token() {
        $token = new CSRFSynchronizerToken('/plugins/agiledashboard/?action=admin');
        return $token->fetchHTMLInput();
    }
}