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

class AvailablePluginsPresenter
{
    public $plugins;
    public $title;
    public $installed_tab_label;
    public $not_installed_tab_label;
    public $not_installed_pane_label;
    public $plugin_table_head;
    public $description_table_head;
    public $install_label;
    public $filter_label;
    public $no_local_plugins;
    public $filter_empty_state;
    /**
     * @var \CSRFSynchronizerToken
     */
    public $csrf_token;

    public function __construct(array $plugins, \CSRFSynchronizerToken $csrf_token)
    {
        $this->plugins                  = $plugins;
        $this->title                    = dgettext('tuleap-pluginsadministration', 'Plugins');
        $this->installed_tab_label      = dgettext('tuleap-pluginsadministration', 'Installed plugins');
        $this->not_installed_tab_label  = dgettext('tuleap-pluginsadministration', 'Available plugins');
        $this->not_installed_pane_label = dgettext('tuleap-pluginsadministration', 'Plugins');
        $this->plugin_table_head        = dgettext('tuleap-pluginsadministration', 'Name');
        $this->description_table_head   = dgettext('tuleap-pluginsadministration', 'Description');
        $this->install_label            = dgettext('tuleap-pluginsadministration', 'Install');
        $this->install_modal_title      = dgettext('tuleap-pluginsadministration', 'Install plugin');
        $this->install_modal_content    = dgettext('tuleap-pluginsadministration', 'Your about to install a plugin. Please confirm your action.');
        $this->install_modal_submit     = dgettext('tuleap-pluginsadministration', 'Install');
        $this->install_modal_cancel     = dgettext('tuleap-pluginsadministration', 'Cancel');
        $this->filter_label             = dgettext('tuleap-pluginsadministration', 'Filter on name or description');
        $this->no_local_plugins         = dgettext('tuleap-pluginsadministration', 'All locally available plugins have been installed.');
        $this->filter_empty_state       = dgettext('tuleap-pluginsadministration', 'There isn\'t any matching plugins.');

        $this->sortPlugins();
        $this->csrf_token = $csrf_token;
    }

    private function sortPlugins()
    {
        usort($this->plugins, function ($a, $b) {
            return strnatcasecmp($a['full_name'], $b['full_name']);
        });
    }
}
