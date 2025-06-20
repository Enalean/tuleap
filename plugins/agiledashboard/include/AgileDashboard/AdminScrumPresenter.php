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

declare(strict_types=1);

namespace Tuleap\AgileDashboard;

use CSRFSynchronizerToken;
use Tuleap\AgileDashboard\Event\IScrumAdminSectionControllers;
use Tuleap\Tracker\Tracker;

final readonly class AdminScrumPresenter
{
    /** @var string[] */
    private array $planning_hierarchy;
    public bool $has_side_panes;
    public string $explicit_backlog_checkbox_title;
    public string $explicit_backlog_checkbox_info;

    /**
     * @param Tracker[] $hierarchy
     * @param IScrumAdminSectionControllers[] $additional_scrum_sections_controllers
     */
    public function __construct(
        public array $plannings,
        public int $group_id,
        public bool $can_create_planning,
        private string $root_planning_name,
        array $hierarchy,
        public bool $scrum_activated,
        public bool $does_configuration_allows_planning_creation,
        public string $additional_content,
        public bool $explicit_top_backlog_enabled,
        public bool $has_workflow_action_add_to_top_backlog_defined,
        public array $additional_scrum_sections_controllers,
        public bool $is_planning_administration_delegated,
        public bool $should_sidebar_display_last_milestones,
    ) {
        $this->planning_hierarchy = array_map(static fn(Tracker $tracker) => $tracker->getName(), $hierarchy);
        $this->has_side_panes     = ! $is_planning_administration_delegated || $additional_content !== '';

        $this->explicit_backlog_checkbox_title = dgettext('tuleap-agiledashboard', 'Explicit backlog');
        $this->explicit_backlog_checkbox_info  = dgettext('tuleap-agiledashboard', 'Explicit backlog allows to select, item per item, what goes in Scrum backlog.');
    }

    public function has_plannings(): bool
    {
        return count($this->plannings) > 0;
    }

    public function create_planning(): string
    {
        return dgettext('tuleap-agiledashboard', 'Create a new planning');
    }

    public function import_template(): string
    {
        return dgettext('tuleap-agiledashboard', 'Import a configuration from a template file');
    }

    public function export_template(): string
    {
        return dgettext('tuleap-agiledashboard', 'Export the configuration');
    }

    public function import_export_section(): string
    {
        return dgettext('tuleap-agiledashboard', 'Import/Export');
    }

    public function planning_section(): string
    {
        return dgettext('tuleap-agiledashboard', 'Planning management');
    }

    public function general_settings_section(): string
    {
        return dgettext('tuleap-agiledashboard', 'General settings');
    }

    public function cannot_create_planning(): string
    {
        return dgettext('tuleap-agiledashboard', 'You cannot create new planning because either:');
    }

    public function cannot_create_planning_no_trackers(): string
    {
        return dgettext('tuleap-agiledashboard', 'there is no trackers in tracker service');
    }

    public function cannot_create_planning_hierarchy(): string
    {
        return sprintf(dgettext('tuleap-agiledashboard', 'all potential planning trackers (%1$s) are already used by a planning configuration (see below).'), $this->getPlanningNamesHierarchy());
    }

    private function getPlanningNamesHierarchy(): string
    {
        if (count($this->planning_hierarchy) > 0) {
            return implode(', ', $this->planning_hierarchy);
        } else {
            return '';
        }
    }

    public function cannot_create_planning_config(): string
    {
        return sprintf(dgettext('tuleap-agiledashboard', 'The potential planning trackers are computed out of %1$s configuration and its hierarchy.'), $this->root_planning_name);
    }

    public function cannot_create_planning_popover_title(): string
    {
        return dgettext('tuleap-agiledashboard', 'Can\'t create new planning');
    }

    public function edit_action_label(): string
    {
        return dgettext('tuleap-agiledashboard', 'Edit');
    }

    public function activate_scrum_label(): string
    {
        return dgettext('tuleap-agiledashboard', 'Activate Scrum');
    }

    public function token(): string
    {
        $token = new CSRFSynchronizerToken('/plugins/agiledashboard/?action=admin');
        return $token->fetchHTMLInput();
    }
}
