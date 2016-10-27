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

    /** @var array */
    public $plugins;

    public function __construct($plugins)
    {
        $this->plugins = $plugins;
    }

    public function title()
    {
        return $GLOBALS['Language']->getText('plugin_pluginsadministration', 'title');
    }

    public function installed_tab_label()
    {
        return $GLOBALS['Language']->getText('plugin_pluginsadministration', 'installed_tab_label');
    }

    public function not_installed_tab_label()
    {
        return $GLOBALS['Language']->getText('plugin_pluginsadministration', 'not_installed_tab_label');
    }

    public function installed_pane_label()
    {
        return $GLOBALS['Language']->getText('plugin_pluginsadministration', 'installed_pane_label');
    }

    public function plugin_table_head()
    {
        return $GLOBALS['Language']->getText('plugin_pluginsadministration', 'plugin_table_head');
    }

    public function version_table_head()
    {
        return $GLOBALS['Language']->getText('plugin_pluginsadministration', 'version_table_head');
    }

    public function description_table_head()
    {
        return $GLOBALS['Language']->getText('plugin_pluginsadministration', 'description_table_head');
    }

    public function available_table_head()
    {
        return $GLOBALS['Language']->getText('plugin_pluginsadministration', 'Available?');
    }

    public function scope_table_head()
    {
        return $GLOBALS['Language']->getText('plugin_pluginsadministration', 'Scope');
    }

    public function icon_label()
    {
        return $GLOBALS['Language']->getText('plugin_pluginsadministration', 'properties_icon_label');
    }

    public function uninstall_icon_label()
    {
        return $GLOBALS['Language']->getText('plugin_pluginsadministration', 'uninstall_plugin_icon_label');
    }

    public function restrict_icon_label()
    {
        return $GLOBALS['Language']->getText('plugin_pluginsadministration', 'manage_restriction_by_project_icon_label');
    }

    public function cant_restrict()
    {
        return $GLOBALS['Language']->getText('plugin_pluginsadministration', 'cant_restrict');
    }
}
