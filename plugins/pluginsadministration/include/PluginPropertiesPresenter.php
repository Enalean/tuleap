<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
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
    public $enable_switch;
    public $are_there_dependencies;
    public $dependencies;
    public $is_there_readme;
    public $readme;
    public $are_there_hooks;
    public $hooks;
    public $are_there_properties;
    public $properties;
    public $are_there_additional_options;
    public $additional_options;
    public $properties_edit_web_ui_security;
    /**
     * @var \CSRFSynchronizerToken
     */
    public $csrf_token;

    public function __construct(
        $id,
        $name,
        $version,
        $description,
        $scope,
        $is_there_enable_switch,
        $enable_switch,
        $are_there_dependencies,
        $dependencies,
        $is_there_readme,
        $readme,
        $are_there_hooks,
        $hooks,
        $are_there_properties,
        $properties,
        $are_there_additional_options,
        $additional_options,
        \CSRFSynchronizerToken $csrf_token
    ) {
        $this->id                           = $id;
        $this->name                         = $name;
        $this->version                      = $version;
        $this->description                  = $description;
        $this->scope                        = $GLOBALS['Language']->getText('plugin_pluginsadministration', 'scope_'.$scope);
        $this->is_there_enable_switch       = $is_there_enable_switch;
        $this->enable_switch                = $enable_switch;
        $this->are_there_dependencies       = $are_there_dependencies;
        $this->dependencies                 = $dependencies;
        $this->is_there_readme              = $is_there_readme;
        $this->readme                       = $readme;
        $this->are_there_hooks              = $are_there_hooks;
        $this->hooks                        = $hooks;
        $this->are_there_properties         = $are_there_properties;
        $this->properties                   = $properties;
        $this->are_there_additional_options = $are_there_additional_options;
        $this->additional_options           = $additional_options;
        $this->csrf_token                   = $csrf_token;

        $this->properties_pane_title              = $GLOBALS['Language']->getText('plugin_pluginsadministration', 'properties_pane_title');
        $this->properties_pane_name               = $GLOBALS['Language']->getText('plugin_pluginsadministration', 'properties_pane_name');
        $this->properties_pane_version            = $GLOBALS['Language']->getText('plugin_pluginsadministration', 'properties_pane_version');
        $this->properties_pane_description        = $GLOBALS['Language']->getText('plugin_pluginsadministration', 'properties_pane_description');
        $this->properties_pane_scope              = $GLOBALS['Language']->getText('plugin_pluginsadministration', 'properties_pane_scope');
        $this->properties_pane_hooks              = $GLOBALS['Language']->getText('plugin_pluginsadministration', 'properties_pane_hooks');
        $this->properties_pane_dependencies       = $GLOBALS['Language']->getText('plugin_pluginsadministration', 'properties_pane_dependencies');
        $this->properties_pane_enabled            = $GLOBALS['Language']->getText('plugin_pluginsadministration', 'properties_pane_enabled');
        $this->properties_pane_update_label       = $GLOBALS['Language']->getText('plugin_pluginsadministration', 'properties_pane_update_label');
        $this->properties_pane_empty_hooks        = $GLOBALS['Language']->getText('plugin_pluginsadministration', 'properties_pane_empty_hooks');
        $this->properties_pane_empty_dependencies = $GLOBALS['Language']->getText('plugin_pluginsadministration', 'properties_pane_empty_dependencies');
        $this->properties_pane_readme_title       = $GLOBALS['Language']->getText('plugin_pluginsadministration', 'properties_pane_readme_title');
        $this->properties_edit_web_ui_security    = $GLOBALS['Language']->getText('plugin_pluginsadministration_properties', 'edit_web_ui_security');
        $this->can_submit                         = ! empty($properties) || ! empty($additional_options) || $this->scope == Plugin::SCOPE_PROJECT;
    }
}
