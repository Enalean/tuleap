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
class PluginsAdministration_Presenter_InstalledPluginsPresenter
{

    /** @var array */
    public $plugins;

    public function __construct($plugins)
    {
        $this->plugins = $plugins;
    }

    public function title()
    {
        return dgettext('tuleap-pluginsadministration', 'Plugins');
    }

    public function installed_tab_label()
    {
        return dgettext('tuleap-pluginsadministration', 'Installed plugins');
    }

    public function not_installed_tab_label()
    {
        return dgettext('tuleap-pluginsadministration', 'Available plugins');
    }

    public function installed_pane_label()
    {
        return dgettext('tuleap-pluginsadministration', 'Plugins');
    }

    public function plugin_table_head()
    {
        return dgettext('tuleap-pluginsadministration', 'Name');
    }

    public function version_table_head()
    {
        return dgettext('tuleap-pluginsadministration', 'Version');
    }

    public function description_table_head()
    {
        return dgettext('tuleap-pluginsadministration', 'Description');
    }

    public function available_table_head()
    {
        return dgettext('tuleap-pluginsadministration', 'Enabled?');
    }

    public function scope_table_head()
    {
        return dgettext('tuleap-pluginsadministration', 'Scope');
    }

    public function icon_label()
    {
        return dgettext('tuleap-pluginsadministration', 'Details');
    }

    public function uninstall_icon_label()
    {
        return dgettext('tuleap-pluginsadministration', 'Uninstall');
    }

    public function restrict_icon_label()
    {
        return dgettext('tuleap-pluginsadministration', 'Restrict');
    }

    public function cant_restrict()
    {
        return dgettext('tuleap-pluginsadministration', 'Can\'t restrict this plugin because of its system scope');
    }

    public function uninstall_modal_title()
    {
        return dgettext('tuleap-pluginsadministration', 'Uninstall plugin');
    }

    public function uninstall_modal_content()
    {
        return dgettext('tuleap-pluginsadministration', 'Your about to uninstall a plugin. Please note the following points and confirm your action.');
    }

    public function uninstall_modal_cancel()
    {
        return dgettext('tuleap-pluginsadministration', 'Cancel');
    }

    public function uninstall_modal_submit()
    {
        return dgettext('tuleap-pluginsadministration', 'Uninstall');
    }

    public function error_uninstall_dependency()
    {
        return dgettext('tuleap-pluginsadministration', 'You can\'t uninstall this plugin since at least another plugin depends on it:');
    }

    public function uninstall_modal_list_sql()
    {
        return dgettext('tuleap-pluginsadministration', 'script <code>db/uninstall.sql</code> of this plugin will be launched');
    }

    public function uninstall_modal_list_directory()
    {
        return dgettext('tuleap-pluginsadministration', 'directory of this plugin will not be deleted');
    }

    public function uninstall_modal_list_web_space()
    {
        return dgettext('tuleap-pluginsadministration', 'web space and cgi scripts of this plugin will remain accessible unless you move or remove corresponding directory');
    }

    public function filter_label()
    {
        return dgettext('tuleap-pluginsadministration', 'Filter on name or description');
    }

    public function filter_empty_state()
    {
        return dgettext('tuleap-pluginsadministration', 'There isn\'t any matching plugins.');
    }
}
