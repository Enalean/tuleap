<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

class PluginsAdministration extends Controler
{
    public function __construct()
    {
        HTTPRequest::instance()->checkUserIsSuperUser();
    }

    public function request()
    {
        $request = HTTPRequest::instance();

        if ($request->exist('view')) {
            switch ($request->get('view')) {
                case 'available':
                case 'properties':
                case 'restrict':
                    $this->view = $request->get('view');
                    break;
                default:
                    $this->view = 'installed';
                    break;
            }
        } else {
            $this->view = 'installed';
        }

        if ($request->exist('action')) {
            switch ($request->get('action')) {
                case 'available':
                    $this->action = 'available';
                    break;
                case 'unavailable':
                    $this->action = 'unavailable';
                    break;
                case 'install':
                    if ($request->exist('confirm')) {
                        $this->action = 'install';
                    }
                    break;
                case 'uninstall':
                    if ($request->exist('confirm')) {
                        $this->action = 'uninstall';
                    }
                    break;

                case 'change_plugin_properties':
                    if ($request->exist('plugin_id')) {
                        $this->action = 'changePluginProperties';
                        $this->view   = 'properties';
                    }
                    break;
                case 'set-plugin-restriction':
                    $this->action = 'setPluginRestriction';
                    $this->view   = 'restrict';
                    break;
                case 'update-allowed-project-list':
                    $this->action = 'updateAllowedProjectList';
                    $this->view   = 'restrict';
                    break;
                default:
                    break;
            }
        }
    }
}
