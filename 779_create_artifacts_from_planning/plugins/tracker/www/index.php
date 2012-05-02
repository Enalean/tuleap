<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('pre.php');
require_once(dirname(__FILE__).'/../include/Tracker/TrackerManager.class.php');
require_once('www/project/admin/permissions.php');
require_once('common/plugin/PluginManager.class.php');
 
$plugin_manager = PluginManager::instance();
$p = $plugin_manager->getPluginByName('tracker');
if ($p && $plugin_manager->isPluginAvailable($p)) {
    $request = HTTPRequest::instance();
    $current_user = UserManager::instance()->getCurrentUser();
    
    $tracker_manager = new TrackerManager();
    $tracker_manager->process(HTTPRequest::instance(), UserManager::instance()->getCurrentUser());
} else {
    header('Location: '.get_server_url());
}


?>
