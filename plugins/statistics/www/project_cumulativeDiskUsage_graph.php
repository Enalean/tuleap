<?php
/**
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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
require_once __DIR__ . '/../include/Statistics_DiskUsageGraph.class.php';
require_once __DIR__ . '/../include/ProjectQuotaManager.class.php';

use Tuleap\Statistics\DiskUsage\Subversion\Collector as SVNCollector;
use Tuleap\Statistics\DiskUsage\Subversion\Retriever as SVNRetriever;

// First, check plugin availability
$pluginManager = PluginManager::instance();
$p             = $pluginManager->getPluginByName('statistics');
if (! $p || ! $pluginManager->isPluginEnabled($p)) {
    $GLOBALS['Response']->redirect('/');
}

$vGroupId = new Valid_GroupId();
$vGroupId->required();
if ($request->valid($vGroupId)) {
    $groupId = $request->get('group_id');
    $project = ProjectManager::instance()->getProject($groupId);
} else {
    $GLOBALS['Response']->redirect('/');
}

$func = $request->getValidated('func', new Valid_WhiteList('usage', 'progress'), '');

//Get dates for start and end period to watch statistics
$disk_usage_dao = new Statistics_DiskUsageDao();
$svn_log_dao    = new SVN_LogDao();
$svn_retriever  = new SVNRetriever($disk_usage_dao);
$svn_collector  = new SVNCollector($svn_log_dao, $svn_retriever);
$duMgr          = new Statistics_DiskUsageManager(
    $disk_usage_dao,
    $svn_collector,
    EventManager::instance()
);

$statPeriod = $duMgr->getProperty('statistics_period');
if (! $statPeriod) {
    $statPeriod = 3;
}
$endDate   = date('Y-m-d');
$startDate = date('Y-m-d', mktime(0, 0, 0, date('m') - $statPeriod, date('d'), date('y')));


$services = $duMgr->getProjectServices();

// Display graph
$graph = new Statistics_DiskUsageGraph($duMgr);

$graph->displayProjectTotalSizeGraph($groupId, 'Week', $startDate, $endDate);
