<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\PluginsAdministration;

use Plugin;

class PluginPropertiesPresenter
{
    public $id;
    public $name;
    public $version;
    public $description;
    public $scope;
    public $is_there_enable_switch;
    public $enable_url;
    public $are_there_dependencies;
    public $dependencies;
    public $is_there_readme;
    public $readme;
    public $are_there_properties;
    public $properties;
    public $are_there_additional_options;
    public $additional_options;
    public $properties_edit_web_ui_security;
    /**
     * @var \CSRFSynchronizerToken
     */
    public $csrf_token;
    /**
     * @var bool
     */
    public $is_enabled;

    public function __construct(
        $id,
        $name,
        $version,
        $description,
        $scope,
        $is_there_enable_switch,
        $enable_url,
        $are_there_dependencies,
        $dependencies,
        $is_there_readme,
        $readme,
        $are_there_properties,
        $properties,
        $are_there_additional_options,
        $additional_options,
        \CSRFSynchronizerToken $csrf_token,
        bool $is_enabled
    ) {
        $this->id                           = $id;
        $this->name                         = $name;
        $this->version                      = $version;
        $this->description                  = $description;
        $this->scope                        = $this->getScopeLabel((int) $scope);
        $this->is_there_enable_switch       = $is_there_enable_switch;
        $this->enable_url                   = $enable_url;
        $this->are_there_dependencies       = $are_there_dependencies;
        $this->dependencies                 = $dependencies;
        $this->is_there_readme              = $is_there_readme;
        $this->readme                       = $readme;
        $this->are_there_properties         = $are_there_properties;
        $this->properties                   = $properties;
        $this->are_there_additional_options = $are_there_additional_options;
        $this->additional_options           = $additional_options;
        $this->csrf_token                   = $csrf_token;
        $this->is_enabled                   = $is_enabled;

        $this->properties_pane_title              = dgettext('tuleap-pluginsadministration', 'Properties');
        $this->properties_pane_name               = dgettext('tuleap-pluginsadministration', 'Name');
        $this->properties_pane_version            = dgettext('tuleap-pluginsadministration', 'Version');
        $this->properties_pane_description        = dgettext('tuleap-pluginsadministration', 'Description');
        $this->properties_pane_scope              = dgettext('tuleap-pluginsadministration', 'Scope');
        $this->properties_pane_dependencies       = dgettext('tuleap-pluginsadministration', 'Dependencies');
        $this->properties_pane_enabled            = dgettext('tuleap-pluginsadministration', 'Enabled?');
        $this->properties_pane_update_label       = dgettext('tuleap-pluginsadministration', 'Update properties');
        $this->properties_pane_empty_dependencies = dgettext('tuleap-pluginsadministration', 'No dependencies');
        $this->properties_pane_readme_title       = dgettext('tuleap-pluginsadministration', 'Readme');
        $this->properties_edit_web_ui_security    = dgettext('tuleap-pluginsadministration', 'Editing plugin properties through the web UI present a security risk, it is strongly advised to disable it. Check the deployment guide for more information.');
        $this->can_submit                         = ! empty($properties) || ! empty($additional_options) || $this->scope == Plugin::SCOPE_PROJECT;
    }

    private function getScopeLabel(int $scope): string
    {
        if ($scope === Plugin::SCOPE_PROJECT) {
            return dgettext('tuleap-pluginsadministration', 'Projects');
        }

        return dgettext('tuleap-pluginsadministration', 'System');
    }
}
