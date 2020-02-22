<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

use Tuleap\AgileDashboard\Event\IScrumAdminSectionControllers;

class AdminScrumPresenter
{

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

    /**
     * @var bool
     */
    public $can_burnup_be_configured;

    /**
     * @var bool
     */
    public $does_configuration_allows_planning_creation;

    private $root_planning_name;
    private $planning_hierarchy = array();
    private $can_create_planning;
    private $additional_content;

    /**
     * @var bool
     */
    public $explicit_top_backlog_enabled;

    /**
     * @var bool
     */
    public $must_display_explicit_top_backlog_switch;

    /**
     * @var bool
     */
    public $has_workflow_action_add_to_top_backlog_defined;

    /**
     * @var IScrumAdminSectionControllers[]
     */
    public $additional_scrum_sections_controllers;

    public function __construct(
        array $plannings,
        $group_id,
        $can_create_planning,
        $root_planning_name,
        array $hierarchy,
        $scrum_activated,
        $scrum_title,
        $can_scrum_mono_milestone_be_enabled,
        bool $can_burnup_be_configured,
        $use_mono_milestone,
        $does_configuration_allows_planning_creation,
        $additional_content,
        bool $explicit_top_backlog_enabled,
        bool $has_workflow_action_add_to_top_backlog_defined,
        bool $user_lab_feature,
        array $additional_scrum_sections_controllers
    ) {
        $this->plannings                                   = $plannings;
        $this->group_id                                    = $group_id;
        $this->can_create_planning                         = $can_create_planning;
        $this->root_planning_name                          = $root_planning_name;
        $this->scrum_activated                             = $scrum_activated;
        $this->scrum_title                                 = $scrum_title;
        $this->can_scrum_mono_milestone_be_enabled         = $can_scrum_mono_milestone_be_enabled;
        $this->can_burnup_be_configured                    = $can_burnup_be_configured;
        $this->use_mono_milestone                          = $use_mono_milestone;
        $this->does_configuration_allows_planning_creation = $does_configuration_allows_planning_creation;
        $this->additional_content                          = $additional_content;

        $this->cannot_create_planning_in_scrum_v2          = $GLOBALS['Language']->getText(
            'plugin_agiledashboard',
            'cannot_create_planning_in_scrum_v2'
        );

        foreach ($hierarchy as $tracker) {
            $this->planning_hierarchy[] = $tracker->getName();
        }

        $this->explicit_top_backlog_enabled              = $explicit_top_backlog_enabled;
        $this->must_display_explicit_top_backlog_switch  = (bool) $explicit_top_backlog_enabled || $user_lab_feature;
        $this->has_workflow_action_add_to_top_backlog_defined = $has_workflow_action_add_to_top_backlog_defined;
        $this->additional_scrum_sections_controllers          = $additional_scrum_sections_controllers;
    }

    public function has_plannings()
    {
        return count($this->plannings) > 0;
    }

    public function create_planning()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_create');
    }

    public function import_template()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'import_template');
    }

    public function export_template()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'export_template');
    }

    public function import_export_section()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'import_export_section');
    }

    public function planning_section()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_section');
    }

    public function general_settings_section()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'general_settings_section');
    }

    public function can_create_planning()
    {
        return $this->can_create_planning;
    }

    public function cannot_create_planning()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'cannot_create_planning');
    }

    public function cannot_create_planning_no_trackers()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'cannot_create_planning_no_trackers');
    }

    public function cannot_create_planning_hierarchy()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'cannot_create_planning_hierarchy', array(
            $this->getPlanningNamesHierarchy()
        ));
    }

    private function getPlanningNamesHierarchy()
    {
        if (count($this->planning_hierarchy) > 0) {
            return implode(', ', $this->planning_hierarchy);
        } else {
            return '';
        }
    }

    public function cannot_create_planning_config()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'cannot_create_planning_config', array(
            $this->root_planning_name
        ));
    }

    public function cannot_create_planning_popover_title()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'cannot_create_planning_popover_title');
    }

    public function edit_action_label()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'edit_action_label');
    }

    public function config_title()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'config_title');
    }

    public function activate_scrum_label()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'activate_scrum_label');
    }

    public function title_label()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'title');
    }

    public function title_label_help()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'title_scrum_help');
    }

    public function first_scrum_will_be_created()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'first_scrum_will_be_created');
    }

    public function token()
    {
        $token = new CSRFSynchronizerToken('/plugins/agiledashboard/?action=admin');
        return $token->fetchHTMLInput();
    }

    public function activate_scrum_mono_milestone_label()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'activate_scrum_mono_milestone_label');
    }

    public function warning_feature_under_construction()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'warning_feature_under_construction');
    }

    public function scrum_monomilestone_title()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'scrum_monomilestone_title');
    }

    public function additional_content()
    {
        return $this->additional_content;
    }
}
