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

// Grant access only to project admins
$user = UserManager::instance()->getCurrentUser();
if (! $user->isAdmin($groupId)) {
    $GLOBALS['Response']->redirect('/');
}

$disk_usage_dao = new Statistics_DiskUsageDao();
$svn_log_dao    = new SVN_LogDao();
$svn_retriever  = new SVNRetriever($disk_usage_dao);
$svn_collector  = new SVNCollector($svn_log_dao, $svn_retriever);
$duMgr          = new Statistics_DiskUsageManager(
    $disk_usage_dao,
    $svn_collector,
    EventManager::instance()
);

$vServices = new Valid_WhiteList('services', array_keys($duMgr->getProjectServices(false)));
$vServices->required();
if ($request->validArray($vServices)) {
    $services = $request->get('services');
} else {
    $services = array_keys($duMgr->getProjectServices(false));
}

$vStartDate = new Valid('start_date');
$vStartDate->addRule(new Rule_Date());
$vStartDate->required();
if ($request->valid($vStartDate)) {
    $startDate = $request->get('start_date');
} else {
    $startDate = '';
}

$vEndDate = new Valid('end_date');
$vEndDate->addRule(new Rule_Date());
$vEndDate->required();
if ($request->valid($vEndDate)) {
    $endDate = $request->get('end_date');
} else {
    $endDate = date('Y-m-d');
}

$duration = strtotime($endDate) - strtotime($startDate);

$error = false;
if ($duration <= 0) {
    $feedback[] = 'You made a mistake in selecting period. Please try again!';
    $error      = true;
} elseif ($duration < 31536000) {
    $groupBy = 'week';
} else {
    $groupBy = 'month';
}

// Display graph
if (! $error) {
    $graph = new Statistics_DiskUsageGraph($duMgr);
    $graph->displayProjectGraph($groupId, $services, $groupBy, $startDate, $endDate, true, true, false);
}
