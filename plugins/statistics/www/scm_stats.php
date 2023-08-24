<?php
/**
 * Copyright (c) STMicroelectronics 2012. All rights reserved
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once __DIR__ . '/../../../src/www/include/pre.php';
require_once __DIR__ . '/../include/Statistics_Formatter.class.php';
require_once __DIR__ . '/../include/Statistics_Formatter_Svn.class.php';
require_once __DIR__ . '/../../../src/www/project/export/project_export_utils.php';

$pluginManager = PluginManager::instance();
$p             = $pluginManager->getPluginByName('statistics');
if (! $p || ! $pluginManager->isPluginEnabled($p)) {
    $GLOBALS['Response']->redirect('/');
}

// Grant access only to site admin
if (! UserManager::instance()->getCurrentUser()->isSuperUser()) {
    $GLOBALS['Response']->redirect('/');
}

$request = HTTPRequest::instance();

$vStartDate = new Valid('scm_statistics_start_date');
$vStartDate->addRule(new Rule_Date());
$vStartDate->required();
$startDate = $request->get('scm_statistics_start_date');
if ($request->valid($vStartDate)) {
    $startDate = $request->get('scm_statistics_start_date');
} else {
    $startDate = date('Y-m-d', strtotime('-1 year'));
}

$vEndDate = new Valid('scm_statistics_end_date');
$vEndDate->addRule(new Rule_Date());
$vEndDate->required();
$endDate = $request->get('scm_statistics_end_date');
if ($request->valid($vEndDate)) {
    $endDate = $request->get('scm_statistics_end_date');
} else {
    $endDate = date('Y-m-d');
}

if ($startDate > $endDate) {
    $GLOBALS['Response']->addFeedback(
        Feedback::ERROR,
        dgettext('tuleap-statistics', 'You made a mistake in selecting period. Please try again!')
    );
    $GLOBALS['Response']->redirect('/plugins/statistics/data_export.php');
}

$project_id = null;
if ($request->exist('scm_statistics_project_select')) {
    $project_name    = $request->get('scm_statistics_project_select');
    $project_manager = ProjectManager::instance();
    $project         = $project_manager->getProjectFromAutocompleter($project_name);
    $project_id      = $project->getId();
}

if ($request->exist('export')) {
    header('Content-Type: text/csv');
    header('Content-Disposition: filename=scm_stats_' . $startDate . '_' . $endDate . '.csv');
    $statsSvn = new Statistics_Formatter_Svn($startDate, $endDate, $project_id);
    echo $statsSvn->getStats();
    $em                  = EventManager::instance();
    $params['formatter'] = new Statistics_Formatter($startDate, $endDate, get_csv_separator(), $project_id);
    $em->processEvent('statistics_collector', $params);
    exit;
}
