<?php
/**
 * Copyright (c) STMicroelectronics 2012. All rights reserved
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

require 'pre.php';

$pluginManager = PluginManager::instance();
$p = $pluginManager->getPluginByName('statistics');
if (!$p || !$pluginManager->isPluginAvailable($p)) {
    header('Location: '.get_server_url());
}

// Grant access only to site admin
if (!UserManager::instance()->getCurrentUser()->isSuperUser()) {
    header('Location: '.get_server_url());
}

// TODO: i18n
$title = 'SCM stats';
$GLOBALS['HTML']->includeCalendarScripts();
$GLOBALS['HTML']->header(array('title' => $title));
echo '<h1>'.$title.'</h1>';

$GLOBALS['HTML']->footer(array());

?>