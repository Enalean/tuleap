<?php
/**
 * Copyright (c) Enalean, 2012-2016. All Rights Reserved.
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

class AdminScrumPresenter {

    /** @var int */
    public $group_id;

    /** @var array */
    public $plannings = array();

    /** @var bool */
    public $scrum_activated;

    /** @var string */
    public $scrum_title;

    /** @var  bool */
    public $use_mono_milestone;

    /**
     * @var bool
     */
    public $can_scrum_mono_milestone_be_enabled;

    private $root_planning_tracker_url;
    private $root_planning_name;
    private $planning_hierarchy = array();
    private $can_create_planning;
    private $additional_panes;

    public function __construct(
        array $plannings,
        $group_id,
        $can_create_planning,
        $root_planning_tracker_url,
        $root_planning_name,
        array $hierarchy,
        $scrum_activated,
        $scrum_title,
        array $additional_panes,
        $can_scrum_mono_milestone_be_enabled,
        $use_mono_milestone
    ) {
        $this->plannings                           = $plannings;
        $this->group_id                            = $group_id;
        $this->can_create_planning                 = $can_create_planning;
        $this->root_planning_tracker_url           = $root_planning_tracker_url;
        $this->root_planning_name                  = $root_planning_name;
        $this->scrum_activated                     = $scrum_activated;
        $this->scrum_title                         = $scrum_title;
        $this->additional_panes                    = $additional_panes;
        $this->can_scrum_mono_milestone_be_enabled = $can_scrum_mono_milestone_be_enabled;
        $this->use_mono_milestone                  = $use_mono_milestone;

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

    public function general_settings_section() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'general_settings_section');
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

    public function edit_action_label() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'edit_action_label');
    }

    public function delete_action_label() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'delete_action_label');
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

    public function activate_scrum_label() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'activate_scrum_label');
    }

    public function title_label() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'title');
    }

    public function title_label_help() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'title_scrum_help');
    }

    public function scrum_activated_label() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'scrum_activated_label');
    }

    public function scrum_not_activated_label() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'scrum_not_activated_label');
    }

    public function first_scrum_will_be_created() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'first_scrum_will_be_created');
    }

    public function token() {
        $token = new CSRFSynchronizerToken('/plugins/agiledashboard/?action=admin');
        return $token->fetchHTMLInput();
    }

    public function additional_panes()
    {
        return array_values($this->additional_panes);
    }

    public function activate_scrum_mono_milestone_label()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard','activate_scrum_mono_milestone_label');
    }

    public function warning_feature_under_construction()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard','warning_feature_under_construction');
    }

    public function scrum_monomilestone_title()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard','scrum_monomilestone_title');
    }
}
