<?php
/**
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
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

require 'pre.php';
require_once dirname(__FILE__).'/../include/Statistics_DiskUsageGraph.class.php';
require_once dirname(__FILE__).'/../include/ProjectQuotaManager.class.php';

// First, check plugin availability
$pluginManager = PluginManager::instance();
$p = $pluginManager->getPluginByName('statistics');
if (!$p || !$pluginManager->isPluginAvailable($p)) {
    header('Location: '.get_server_url());
}

$vGroupId = new Valid_GroupId();
$vGroupId->required();
if ($request->valid($vGroupId)) {
    $groupId = $request->get('group_id');
    $project = ProjectManager::instance()->getProject($groupId);
} else {
    header('Location: '.get_server_url());
}

$func = $request->getValidated('func', new Valid_WhiteList('usage', 'progress'), '');

//Get dates for start and end period to watch statistics
$duMgr  = new Statistics_DiskUsageManager();
$statPeriod = $duMgr->getProperty('statistics_period');
if (!$statPeriod) {
    $statPeriod = 3;
}
$endDate = date('Y-m-d');
$startDate = date('Y-m-d', mktime(0, 0, 0, date('m')-$statPeriod, date('d'), date('y')));


$services = $duMgr->getProjectServices();

// Display graph
$graph = new Statistics_DiskUsageGraph($duMgr);

if ($func == 'usage') {
    //Retreive the config param & convert it to bytes
    $quota       = $duMgr->getProperty('allowed_quota');
    $pqm         = new ProjectQuotaManager();
    $customQuota = $pqm->getProjectCustomQuota($groupId);
    if ($customQuota) {
        $quota = $customQuota;
    }
    $allowed = $quota * (1024*1024*1024);
    $used    = $request->get('size');

    //In case of over usage
    if ($used > $allowed) {
        $used = $allowed;
        //May be should display warning
    }
    $graph->displayProjectProportionUsage($used, $allowed);
} else {
    $graph->displayProjectTotalSizeGraph($groupId, 'Week', $startDate, $endDate);
}

?>