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

class PluginsAdministration_ManageAllowedProjectsPresenter {

    const TEMPLATE = 'manage-allowed-projects';

    /**
     * @var Plugin
     */
    private $plugin;

    /**
     * @var Project[]
     */
    public $allowed_projects;

    /**
     * @var Boolean
     */
    public $is_resource_restricted;

    public $allow_all_enabled = true;

    public function __construct(
        Plugin $plugin,
        array $allowed_projects,
        $is_plugin_restricted
    ) {
        $this->plugin                 = $plugin;
        $this->allowed_projects       = $allowed_projects;
        $this->is_resource_restricted = $is_plugin_restricted;
    }

    public function there_is_no_project() {
        return count($this->allowed_projects) === 0;
    }

    public function restricted_resource_action() {
        return '/plugins/pluginsadministration/?action=set-plugin-restriction&plugin_id=' . $this->plugin->getId();
    }

    public function restricted_resource_action_csrf() {
        $csrf = new CSRFSynchronizerToken('/plugins/pluginsadministration/?action=set-plugin-restriction&plugin_id=' . $this->plugin->getId());
        return $csrf->fetchHTMLInput();
    }

    public function update_allowed_projects_action() {
        return '/plugins/pluginsadministration/?action=update-allowed-project-list&plugin_id=' . $this->plugin->getId();
    }

    public function update_allowed_projects_action_csrf() {
        $csrf = new CSRFSynchronizerToken('/plugins/pluginsadministration/?action=update-allowed-project-list&plugin_id=' . $this->plugin->getId());
        return $csrf->fetchHTMLInput();
    }

    public function resource_allowed_project_title() {
        return $GLOBALS['Language']->getText('plugin_pluginsadministration', 'plugin_allowed_project_title', array($this->plugin->getPluginInfo()->getPluginDescriptor()->getFullName()));
    }

    public function resource_allowed_project_pane_title() {
        return $GLOBALS['Language']->getText('plugin_pluginsadministration', 'plugin_allowed_project_pane_title', array($this->plugin->getPluginInfo()->getPluginDescriptor()->getFullName()));
    }

    public function resource_allowed_project_information() {
        return $GLOBALS['Language']->getText('plugin_pluginsadministration', 'plugin_allowed_project_pane_information', array($this->plugin->getPluginInfo()->getPluginDescriptor()->getFullName()));
    }

    public function resource_allowed_project_allow_all() {
        return $GLOBALS['Language']->getText('plugin_pluginsadministration', 'plugin_allowed_project_allow_all');
    }

    public function resource_allowed_project_list() {
        return $GLOBALS['Language']->getText('plugin_pluginsadministration', 'plugin_allowed_project_list');
    }

    public function resource_allowed_project_list_allow_placeholder() {
        return $GLOBALS['Language']->getText('plugin_pluginsadministration', 'plugin_allowed_project_list_allow_placeholder');
    }

    public function resource_allowed_project_list_filter_placeholder() {
        return $GLOBALS['Language']->getText('plugin_pluginsadministration', 'plugin_allowed_project_list_filter_placeholder');
    }

    public function resource_allowed_project_list_allow_project() {
        return $GLOBALS['Language']->getText('plugin_pluginsadministration', 'plugin_allowed_project_list_allow_project');
    }

    public function resource_allowed_project_list_revoke_projects() {
        return $GLOBALS['Language']->getText('plugin_pluginsadministration', 'plugin_allowed_project_list_revoke_projects');
    }

    public function resource_allowed_project_list_id() {
        return $GLOBALS['Language']->getText('plugin_pluginsadministration', 'plugin_allowed_project_list_id');
    }

    public function resource_allowed_project_list_name() {
        return $GLOBALS['Language']->getText('plugin_pluginsadministration', 'plugin_allowed_project_list_name');
    }

    public function resource_allowed_project_list_empty() {
        return $GLOBALS['Language']->getText('plugin_pluginsadministration', 'plugin_allowed_project_list_empty');
    }

    public function resource_allowed_project_revoke_title() {
        return $GLOBALS['Language']->getText('plugin_pluginsadministration', 'plugin_allowed_project_revoke_title');
    }

    public function resource_allowed_project_revoke_description() {
        return $GLOBALS['Language']->getText('plugin_pluginsadministration', 'plugin_allowed_project_revoke_description');
    }

    public function resource_allowed_project_revoke_yes() {
        return $GLOBALS['Language']->getText('plugin_pluginsadministration', 'plugin_allowed_project_revoke_yes');
    }

    public function resource_allowed_project_revoke_no() {
        return $GLOBALS['Language']->getText('plugin_pluginsadministration', 'plugin_allowed_project_revoke_no');
    }
}
