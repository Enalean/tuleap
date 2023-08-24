<?php
/**
 * Copyright (c) STMicroelectronics, 2009. All Rights Reserved.
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2009
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

// Grant access only to site admin
if (! UserManager::instance()->getCurrentUser()->isSuperUser()) {
    $GLOBALS['Response']->redirect('/');
}

$error    = false;
$feedback = [];

$disk_usage_dao = new Statistics_DiskUsageDao();
$svn_log_dao    = new SVN_LogDao();
$svn_retriever  = new SVNRetriever($disk_usage_dao);
$svn_collector  = new SVNCollector($svn_log_dao, $svn_retriever);
$duMgr          = new Statistics_DiskUsageManager(
    $disk_usage_dao,
    $svn_collector,
    EventManager::instance()
);

$graphType = $request->get('graph_type');

switch ($graphType) {
    case 'graph_service':
        $vServices = new Valid_WhiteList('services', array_keys($duMgr->getProjectServices()));
        $vServices->required();
        if ($request->validArray($vServices)) {
            $services = $request->get('services');
        } else {
            $services = [];
        }
        break;

    case 'graph_user':
        $vUserId = new Valid_UInt('user_id');
        $vUserId->required();
        if ($request->valid($vUserId)) {
            $userId = $request->get('user_id');
        }
        break;

    case 'graph_project':
        $vGroupId = new Valid_GroupId();
        $vGroupId->required();
        if ($request->valid($vGroupId)) {
            $groupId = $request->get('group_id');
        }

        $vServices = new Valid_WhiteList('services', array_keys($duMgr->getProjectServices()));
        $vServices->required();
        if ($request->validArray($vServices)) {
            $services = $request->get('services');
        } else {
            $services = [];
        }
        break;

    default:
}


$groupByDate = ['day', 'week', 'month', 'year'];
$vGroupBy    = new Valid_WhiteList('group_by', $groupByDate);
$vGroupBy->required();
if ($request->valid($vGroupBy)) {
    $selectedGroupByDate = $request->get('group_by');
} else {
    $selectedGroupByDate = 'week';
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
if ($request->valid($vStartDate)) {
    $endDate = $request->get('end_date');
} else {
    $endDate = date('Y-m-d');
}

$vRelative = new Valid_WhiteList('relative', ['true']);
$vRelative->required();
if ($request->valid($vRelative)) {
    $relative = true;
} else {
    $relative = false;
}

if (strtotime($startDate) > strtotime($endDate)) {
    $error = true;
}

// Display graph
$graph = new Statistics_DiskUsageGraph($duMgr);
if (! $error) {
    switch ($graphType) {
        case 'graph_service':
            $graph->displayServiceGraph($services, $selectedGroupByDate, $startDate, $endDate, ! $relative);
            break;

        case 'graph_user':
            $graph->displayUserGraph($userId, $selectedGroupByDate, $startDate, $endDate, ! $relative);
            break;

        case 'graph_project':
            $graph->displayProjectGraph($groupId, $services, $selectedGroupByDate, $startDate, $endDate, ! $relative);
            break;

        default:
    }
} else {
    $msg = '';
    foreach ($feedback as $m) {
        $msg .= $m;
    }
    $graph->displayError($msg);
}
