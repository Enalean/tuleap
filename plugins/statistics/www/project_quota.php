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

require_once 'pre.php';
require_once dirname(__FILE__).'/../include/ProjectQuotaHtml.class.php';

$pluginManager = PluginManager::instance();
$p = $pluginManager->getPluginByName('statistics');
if (!$p || !$pluginManager->isPluginAvailable($p)) {
    header('Location: '.get_server_url());
}

// Grant access only to site admin
if (!UserManager::instance()->getCurrentUser()->isSuperUser()) {
    header('Location: '.get_server_url());
}

$request = HTTPRequest::instance();
$pqHtml  = new ProjectQuotaHtml();
$pqHtml->HandleRequest($request);

$html = $pqHtml->displayProjectQuota($request);

$title = $GLOBALS['Language']->getText('plugin_statistics', 'quota_title');
$GLOBALS['HTML']->header(array('title' => $title, 'main_classes' => array('tlp-framed')));

echo '<div id="plugin_statistics">';
echo '<h1>'.$title.'</h1>';
echo $html;
echo '</div>';

$GLOBALS['HTML']->footer(array());

?>
