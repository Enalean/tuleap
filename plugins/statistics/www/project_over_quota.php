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

use Tuleap\Admin\AdminPageRenderer;

require_once 'pre.php';

require_once dirname(__FILE__).'/../include/ProjectQuotaHtml.class.php';

$pluginManager    = PluginManager::instance();
$statisticsPlugin = $pluginManager->getPluginByName('statistics');
if (! $statisticsPlugin || ! $pluginManager->isPluginAvailable($statisticsPlugin)) {
    $GLOBALS['Response']->redirect('/');
}

if (! UserManager::instance()->getCurrentUser()->isSuperUser()) {
    $GLOBALS['Response']->redirect('/');
}

$title = $GLOBALS['Language']->getText('plugin_statistics', 'projects_over_quota_title');
$GLOBALS['HTML']->header(array('title' => $title, 'main_classes' => array('tlp-framed')));

$project_quota_html = new ProjectQuotaHtml();
$project_quota_html->displayProjectsOverQuota();

$GLOBALS['HTML']->footer(array());
