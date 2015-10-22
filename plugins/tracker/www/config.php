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

require_once('pre.php');

$plugin_manager = PluginManager::instance();
$plugin = $plugin_manager->getPluginByName('tracker');
if ($plugin && $plugin_manager->isPluginAvailable($plugin)) {
    $request      = HTTPRequest::instance();
    $current_user = UserManager::instance()->getCurrentUser();
    $router = new TrackerPluginConfigRouter(
        new CSRFSynchronizerToken($_SERVER['SCRIPT_URL']),
        new TrackerPluginConfigController(
            new TrackerPluginConfig(
                new TrackerPluginConfigDao()
            ),
            new Config_LocalIncFinder(),
            EventManager::instance()
        )
    );
    $router->process($request, $GLOBALS['HTML'], $current_user);
} else {
    header('Location: '.get_server_url());
}
