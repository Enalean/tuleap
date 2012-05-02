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

// Grant access only to project admins
$user = UserManager::instance()->getCurrentUser();
if (!$project->userIsAdmin($user)) {
    header('Location: '.get_server_url());
}

$duMgr  = new Statistics_DiskUsageManager();
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
    $error = true;
} elseif ($duration < 31536000) {
    $groupBy = 'Week';
} else {
    $groupBy = 'Month';
}

//
// Display graph
//
if (!$error) {
    $graph = new Statistics_DiskUsageGraph($duMgr);
    $graph->displayProjectGraph($groupId, $services, $groupBy, $startDate, $endDate, true, true, false);
}

?>