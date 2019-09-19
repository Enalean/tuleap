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

class PluginsAdministration_ManageAllowedProjectsPresenter
{

    public const TEMPLATE = 'manage-allowed-projects';

    /**
     * @var Plugin
     */
    private $plugin;

    /**
     * @var Project[]
     */
    public $allowed_projects;

    /**
     * @var bool
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

    public function there_is_no_project()
    {
        return count($this->allowed_projects) === 0;
    }

    public function restricted_resource_action()
    {
        return '/plugins/pluginsadministration/?action=set-plugin-restriction&plugin_id=' . $this->plugin->getId();
    }

    public function restricted_resource_action_csrf()
    {
        $csrf = new CSRFSynchronizerToken('/plugins/pluginsadministration/?action=set-plugin-restriction&plugin_id=' . $this->plugin->getId());
        return $csrf->fetchHTMLInput();
    }

    public function update_allowed_projects_action()
    {
        return '/plugins/pluginsadministration/?action=update-allowed-project-list&plugin_id=' . $this->plugin->getId();
    }

    public function update_allowed_projects_action_csrf()
    {
        $csrf = new CSRFSynchronizerToken('/plugins/pluginsadministration/?action=update-allowed-project-list&plugin_id=' . $this->plugin->getId());
        return $csrf->fetchHTMLInput();
    }

    public function resource_allowed_project_title()
    {
        return sprintf(dgettext('tuleap-pluginsadministration', '%1$s projects restriction'), $this->plugin->getPluginInfo()->getPluginDescriptor()->getFullName());
    }

    public function resource_allowed_project_pane_title()
    {
        return sprintf(dgettext('tuleap-pluginsadministration', 'Projects allowed to use %1$s'), $this->plugin->getPluginInfo()->getPluginDescriptor()->getFullName());
    }

    public function resource_allowed_project_information()
    {
        return sprintf(dgettext('tuleap-pluginsadministration', 'This section allows to define which projects will be able to use %1$s.'), $this->plugin->getPluginInfo()->getPluginDescriptor()->getFullName());
    }

    public function resource_allowed_project_allow_all()
    {
        return dgettext('tuleap-pluginsadministration', 'Allow all the projects to use this plugin');
    }

    public function resource_allowed_project_list()
    {
        return dgettext('tuleap-pluginsadministration', 'List of allowed projects');
    }

    public function resource_allowed_project_list_allow_placeholder()
    {
        return dgettext('tuleap-pluginsadministration', 'Project name');
    }

    public function resource_allowed_project_list_filter_placeholder()
    {
        return dgettext('tuleap-pluginsadministration', 'Filter on project id or name');
    }

    public function resource_allowed_project_list_allow_project()
    {
        return dgettext('tuleap-pluginsadministration', 'Allow access');
    }

    public function resource_allowed_project_list_revoke_projects()
    {
        return dgettext('tuleap-pluginsadministration', 'Revoke access');
    }

    public function resource_allowed_project_list_id()
    {
        return dgettext('tuleap-pluginsadministration', 'Id');
    }

    public function resource_allowed_project_list_name()
    {
        return dgettext('tuleap-pluginsadministration', 'Name');
    }

    public function resource_allowed_project_list_empty()
    {
        return dgettext('tuleap-pluginsadministration', 'Currently, there are no projects allowed to use this plugin.');
    }

    public function resource_allowed_project_revoke_title()
    {
        return dgettext('tuleap-pluginsadministration', 'Warning');
    }

    public function resource_allowed_project_revoke_description()
    {
        return dgettext('tuleap-pluginsadministration', 'You are about to revoke the access to this plugin to one or several projects. Are you sure you want to do this?');
    }

    public function resource_allowed_project_revoke_yes()
    {
        return dgettext('tuleap-pluginsadministration', 'Yes, revoke access');
    }

    public function resource_allowed_project_revoke_no()
    {
        return dgettext('tuleap-pluginsadministration', 'No');
    }

    public function resource_allowed_project_filter_empty()
    {
        return $GLOBALS['Language']->getText('admin', 'allowed_projects_filter_empty');
    }
}
