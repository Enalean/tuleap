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

if ($project && !$project->isError()) {

    // Grant access only to project admins
    $user = UserManager::instance()->getCurrentUser();
    if (!$project->userIsAdmin($user)) {
        header('Location: '.get_server_url());
    }
    
    //Get dates for start and end period to watch statistics
    $info = $p->getPluginInfo();
    $statPeriod = $info->getPropertyValueForName('statistics_period');
    if (!$statPeriod) {
        $statPeriod = 5;
    }
    $endDate = date('Y-m-d');
    $startDate = date('Y-m-d',mktime(0,0,0,date('m')-$statPeriod,date('d'),date('y')));

    $params['group'] = $groupId;
    $params['toptab'] = 'admin';
    $params['title'] = $GLOBALS['Language']->getText('admin_groupedit', 'proj_admin').': '.$project->getPublicName();
    site_project_header($params);

    $title = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'disk_usage_period', array($startDate, $endDate));
    echo '<h2>'.$title.'</h2>';

    echo '<div id="help_init" class="stat_help">'.$GLOBALS['Language']->getText('plugin_statistics_admin_page', 'disk_usage_quota').'</div>';
    echo '<h3>'.$GLOBALS['Language']->getText('plugin_statistics_show_service', 'service_growth').'</h3>';

    $duMgr  = new Statistics_DiskUsageManager();
    $duHtml = new Statistics_DiskUsageHtml($duMgr);
    $duHtml->getServiceEvolutionForPeriod($startDate, $endDate, $groupId);
    echo '<p><h3>'.$GLOBALS['Language']->getText('plugin_statistics_show_service', 'service_growth_graph').'</h3>';
    echo '<img src="project_stat_graph.php?group_id='.$groupId.'&start_date='.$startDate.'&end_date='.$endDate.'" title="Project disk usage graph" /></p>';

    site_project_footer($params);
} else {
    header('Location: '.get_server_url());
}

?>