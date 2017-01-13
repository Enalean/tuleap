<?php
/**
 * Copyright (c) STMicroelectronics 2014. All rights reserved
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

require_once('pre.php');

$GLOBALS['HTML']->includeFooterJavascriptFile("/scripts/ckeditor-4.3.2/ckeditor.js");
$GLOBALS['HTML']->includeFooterJavascriptFile('/scripts/tuleap/tuleap-ckeditor-toolbar.js');

$pluginManager    = PluginManager::instance();
$statisticsPlugin = $pluginManager->getPluginByName('statistics');
if (! $statisticsPlugin || ! $pluginManager->isPluginAvailable($statisticsPlugin)) {
    $GLOBALS['Response']->redirect('/');
}

if (! UserManager::instance()->getCurrentUser()->isSuperUser()) {
    $GLOBALS['Response']->redirect('/');
}

$project_quota_html = new ProjectQuotaHtml();
$project_quota_html->displayProjectsOverQuota();
