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
    public $plannings = [];

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
    public $does_configuration_allows_planning_creation;

    private $root_planning_name;
    private $planning_hierarchy = [];
    private $can_create_planning;
    private $additional_content;

    /**
     * @var bool
     */
    public $explicit_top_backlog_enabled;

    /**
     * @var bool
     */
    public $has_workflow_action_add_to_top_backlog_defined;

    /**
     * @var IScrumAdminSectionControllers[]
     */
    public $additional_scrum_sections_controllers;
    /**
     * @var string
     * @psalm-readonly
     */
    public $cannot_create_planning_in_scrum_v2;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $is_planning_administration_delegated;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $has_side_panes;
    public readonly string $explicit_backlog_checkbox_title;
    public readonly string $explicit_backlog_checkbox_info;

    public function __construct(
        array $plannings,
        $group_id,
        $can_create_planning,
        $root_planning_name,
        array $hierarchy,
        $scrum_activated,
        $can_scrum_mono_milestone_be_enabled,
        $use_mono_milestone,
        $does_configuration_allows_planning_creation,
        $additional_content,
        bool $explicit_top_backlog_enabled,
        bool $has_workflow_action_add_to_top_backlog_defined,
        array $additional_scrum_sections_controllers,
        bool $is_planning_administration_delegated,
        public readonly bool $is_legacy_agiledashboard,
        public readonly bool $should_sidebar_display_last_milestones,
    ) {
        $this->plannings                                   = $plannings;
        $this->group_id                                    = $group_id;
        $this->can_create_planning                         = $can_create_planning;
        $this->root_planning_name                          = $root_planning_name;
        $this->scrum_activated                             = $scrum_activated;
        $this->can_scrum_mono_milestone_be_enabled         = $can_scrum_mono_milestone_be_enabled;
        $this->use_mono_milestone                          = $use_mono_milestone;
        $this->does_configuration_allows_planning_creation = $does_configuration_allows_planning_creation;
        $this->additional_content                          = $additional_content;

        $this->cannot_create_planning_in_scrum_v2 = dgettext('tuleap-agiledashboard', 'You cannot create more than one planning in scrum V2.');

        foreach ($hierarchy as $tracker) {
            $this->planning_hierarchy[] = $tracker->getName();
        }

        $this->explicit_top_backlog_enabled                   = $explicit_top_backlog_enabled;
        $this->has_workflow_action_add_to_top_backlog_defined = $has_workflow_action_add_to_top_backlog_defined;
        $this->additional_scrum_sections_controllers          = $additional_scrum_sections_controllers;
        $this->is_planning_administration_delegated           = $is_planning_administration_delegated;
        $this->has_side_panes                                 = ! $is_planning_administration_delegated || $additional_content !== '';

        $this->explicit_backlog_checkbox_title = $is_legacy_agiledashboard ? dgettext("tuleap-agiledashboard", "Explicit top backlog") : dgettext("tuleap-agiledashboard", "Explicit backlog");
        $this->explicit_backlog_checkbox_info  = $is_legacy_agiledashboard ? dgettext("tuleap-agiledashboard", "Explicit backlog allows to select, item per item, what goes in Scrum top backlog.") : dgettext("tuleap-agiledashboard", "Explicit backlog allows to select, item per item, what goes in Scrum backlog.");
    }

    public function has_plannings()
    {
        return count($this->plannings) > 0;
    }

    public function create_planning()
    {
        return dgettext('tuleap-agiledashboard', 'Create a new planning');
    }

    public function import_template()
    {
        return dgettext('tuleap-agiledashboard', 'Import a configuration from a template file');
    }

    public function export_template()
    {
        return dgettext('tuleap-agiledashboard', 'Export the configuration');
    }

    public function import_export_section()
    {
        return dgettext('tuleap-agiledashboard', 'Import/Export');
    }

    public function planning_section()
    {
        return dgettext('tuleap-agiledashboard', 'Planning management');
    }

    public function general_settings_section()
    {
        return dgettext('tuleap-agiledashboard', 'General settings');
    }

    public function can_create_planning()
    {
        return $this->can_create_planning;
    }

    public function cannot_create_planning()
    {
        return dgettext('tuleap-agiledashboard', 'You cannot create new planning because either:');
    }

    public function cannot_create_planning_no_trackers()
    {
        return dgettext('tuleap-agiledashboard', 'there is no trackers in tracker service');
    }

    public function cannot_create_planning_hierarchy()
    {
        return sprintf(dgettext('tuleap-agiledashboard', 'all potential planning trackers (%1$s) are already used by a planning configuration (see below).'), $this->getPlanningNamesHierarchy());
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
        return sprintf(dgettext('tuleap-agiledashboard', 'The potential planning trackers are computed out of %1$s configuration and its hierarchy.'), $this->root_planning_name);
    }

    public function cannot_create_planning_popover_title()
    {
        return dgettext('tuleap-agiledashboard', 'Can\'t create new planning');
    }

    public function edit_action_label()
    {
        return dgettext('tuleap-agiledashboard', 'Edit');
    }

    public function activate_scrum_label()
    {
        return dgettext('tuleap-agiledashboard', 'Activate Scrum');
    }

    public function first_scrum_will_be_created()
    {
        return dgettext('tuleap-agiledashboard', 'A first scrum configuration will be used during the activation. This operation can take a few seconds.');
    }

    public function token()
    {
        $token = new CSRFSynchronizerToken('/plugins/agiledashboard/?action=admin');
        return $token->fetchHTMLInput();
    }

    public function activate_scrum_mono_milestone_label()
    {
        return dgettext('tuleap-agiledashboard', 'Enable Scrum V2');
    }

    public function warning_feature_under_construction()
    {
        return dgettext('tuleap-agiledashboard', 'This feature is under development. Once checked it wont be possible to start scrum with default template.');
    }

    public function scrum_monomilestone_title()
    {
        return dgettext('tuleap-agiledashboard', 'Scrum mono milestone');
    }

    public function additional_content()
    {
        return $this->additional_content;
    }
}
