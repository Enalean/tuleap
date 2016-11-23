<?php
/**
 * Copyright (c) STMicroelectronics 2012. All rights reserved
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Statistics\AdminHeaderPresenter;
use Tuleap\Statistics\SCMStatisticsPresenter;

require_once 'pre.php';
require_once dirname(__FILE__) . '/../include/Statistics_Formatter.class.php';
require_once dirname(__FILE__) . '/../include/Statistics_Formatter_Cvs.class.php';
require_once dirname(__FILE__) . '/../include/Statistics_Formatter_Svn.class.php';
require_once 'www/project/export/project_export_utils.php';

$pluginManager = PluginManager::instance();
$p             = $pluginManager->getPluginByName('statistics');
if (! $p || ! $pluginManager->isPluginAvailable($p)) {
    header('Location: ' . get_server_url());
}

// Grant access only to site admin
if (! UserManager::instance()->getCurrentUser()->isSuperUser()) {
    header('Location: ' . get_server_url());
}

$request = HTTPRequest::instance();

$error = false;

$vStartDate = new Valid('start');
$vStartDate->addRule(new Rule_Date());
$vStartDate->required();
$startDate = $request->get('start');
if ($request->valid($vStartDate)) {
    $startDate = $request->get('start');
} else {
    $startDate = date('Y-m-d', strtotime('-1 year'));
}

$vEndDate = new Valid('end');
$vEndDate->addRule(new Rule_Date());
$vEndDate->required();
$endDate = $request->get('end');
if ($request->valid($vEndDate)) {
    $endDate = $request->get('end');
} else {
    $endDate = date('Y-m-d');
}

if ($startDate >= $endDate) {
    $error = true;
    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_statistics', 'period_error'));
}

$groupId  = null;
if ($request->exist('group_name')) {
    $group_name = $request->get('group_name');

    $project_manager = ProjectManager::instance();
    $project         = $project_manager->getProjectFromAutocompleter($group_name);
    $groupId         = $project->getId();
}

if (! $error && $request->exist('export')) {
    header('Content-Type: text/csv');
    header('Content-Disposition: filename=scm_stats_' . $startDate . '_' . $endDate . '.csv');
    $statsSvn = new Statistics_Formatter_Svn($startDate, $endDate, $groupId);
    echo $statsSvn->getStats();
    $statsCvs = new Statistics_Formatter_Cvs($startDate, $endDate, $groupId);
    echo $statsCvs->getStats();
    $em                  = EventManager::instance();
    $params['formatter'] = new Statistics_Formatter($startDate, $endDate, get_csv_separator(), $groupId);
    $em->processEvent('statistics_collector', $params);
    exit;
} else {
    $title = $GLOBALS['Language']->getText('plugin_statistics', 'index_page_title');

    $header_presenter = new AdminHeaderPresenter(
        $title,
        'scm_statistics'
    );

    $scm_statistics_presenter = new SCMStatisticsPresenter(
        $header_presenter,
        $startDate,
        $endDate,
        $groupId
    );

    $admin_page_renderer = new AdminPageRenderer();
    $admin_page_renderer->renderANoFramedPresenter(
        $title,
        ForgeConfig::get('codendi_dir') . '/plugins/statistics/templates',
        SCMStatisticsPresenter::TEMPLATE,
        $scm_statistics_presenter
    );
}
