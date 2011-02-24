<?php
/**
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
 *
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
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require 'pre.php';
require_once dirname(__FILE__).'/../include/Statistics_DiskUsageHtml.class.php';

// First, check plugin availability
$pluginManager = PluginManager::instance();
$p = $pluginManager->getPluginByName('statistics');
if (!$p || !$pluginManager->isPluginAvailable($p)) {
    header('Location: '.get_server_url());
}

$vGroupId = new Valid_UInt('group_id');
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
$duHtml = new Statistics_DiskUsageHtml($duMgr);


//Growth for a perid of 3 months
//May be turned on config param
$endDate = date('Y-m-d');
$startDate = date('Y-m-d',mktime(0,0,0,date('m')-3,date('d'),date('y')));

$title = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'disk_usage_period', array($startDate, $endDate));

$GLOBALS['HTML']->header(array('title' => $title));
echo '<h2>'.$title.'</h2>';


if ($project && !$project->isError()) {
    $projectName = $project->getPublicName().' ('.$project->getUnixName().')';
} else {
    $projectName = '';
}
echo '<h3>'.$GLOBALS['Language']->getText('plugin_statistics_show_service', 'service_growth').' <a href="/project/admin/?group_id='.$groupId.'">'.$projectName.'</a></h3>';
if ($groupId) {
    $duHtml->getServiceEvolutionForPeriod($startDate, $endDate, $groupId);
    echo '<p><h3>'.$GLOBALS['Language']->getText('plugin_statistics_show_service', 'service_growth_graph').'</h3>';
    echo '<img src="project_stat_graph.php?group_id='.$groupId.'&start_date='.$startDate.'&end_date='.$endDate.'" title="Project disk usage graph" /></p>';
}

$GLOBALS['HTML']->footer(array());

?>