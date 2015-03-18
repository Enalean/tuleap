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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
class PluginsAdministration_Presenter_InstalledPluginsPresenter {

    /** @var string */
    public $help;

    /** @var array */
    public $plugins;

    public function __construct($help, $plugins) {
        $this->help    = $help;
        $this->plugins = $plugins;
    }

    public function plugins_legend() {
        return $GLOBALS['Language']->getText('plugin_pluginsadministration','plugins');
    }

    public function plugin_table_head() {
        return $GLOBALS['Language']->getText('plugin_pluginsadministration','Plugin');
    }

    public function available_table_head() {
        return $GLOBALS['Language']->getText('plugin_pluginsadministration','Available?');
    }

    public function scope_table_head() {
        return $GLOBALS['Language']->getText('plugin_pluginsadministration','Scope');
    }

    public function action_table_head() {
        return $GLOBALS['Language']->getText('plugin_pluginsadministration','Actions');
    }

    public function property_title() {
        return $GLOBALS['Language']->getText('plugin_pluginsadministration','properties');
    }

    public function icon_label() {
        return $GLOBALS['Language']->getText('plugin_pluginsadministration', 'properties_icon_label');
    }

    public function uninstall_title() {
        return $GLOBALS['Language']->getText('plugin_pluginsadministration','uninstall_plugin');
    }

    public function uninstall_icon_label() {
        return $GLOBALS['Language']->getText('plugin_pluginsadministration', 'uninstall_plugin_icon_label');
    }

    public function restrict_title() {
        return $GLOBALS['Language']->getText('plugin_pluginsadministration','manage_restriction_by_project');
    }

    public function restrict_icon_label() {
        return $GLOBALS['Language']->getText('plugin_pluginsadministration', 'manage_restriction_by_project_icon_label');
    }
}