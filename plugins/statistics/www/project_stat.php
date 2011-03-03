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

// Grant access only to project admins
$user = UserManager::instance()->getCurrentUser();
if (!$project->userIsAdmin($user)) {
    header('Location: '.get_server_url());
}

$vPeriod = new Valid_WhiteList('period', array('year', 'months'));
$vPeriod->required();
if ($request->valid($vPeriod)) {
    $period = $request->get('period');
} else {
    $period = 'months';
}

$duMgr  = new Statistics_DiskUsageManager();

// selected service
$vServices = new Valid_WhiteList('services', array_keys($duMgr->getProjectServices()));
$vServices->required();
if ($request->validArray($vServices)) {
    $selectedServices = $request->get('services');
} else {
    $selectedServices = array_keys($duMgr->getProjectServices());
}

if ($project && !$project->isError()) {
    // Prepare params
    $serviceParam    = '';
    $first    = true;
    foreach ($selectedServices as $serv) {
        if ($first != true) {
            $serviceParam .= '&';
        }
        $serviceParam .= 'services[]='.$serv;
        $first     = false;
    }

    //Get dates for start and end period to watch statistics
    $info = $p->getPluginInfo();
    $statPeriod = $info->getPropertyValueForName('statistics_period');
    if (!$statPeriod) {
        $statPeriod = 3;
    }

    if ($period == 'year') {
        $statDuration = 12;
        $link = '?'.$serviceParam.'&group_id='.$groupId.'&period=months';
    } else {
        $statDuration = $statPeriod;
        $link = '?'.$serviceParam.'&group_id='.$groupId.'&period=year';
    }

    $endDate = date('Y-m-d');
    $startDate = date('Y-m-d', mktime(0, 0, 0, date('m')-$statDuration, date('d'), date('y')));

    $params['group'] = $groupId;
    $params['title'] = $GLOBALS['Language']->getText('admin_groupedit', 'proj_admin').': '.$project->getPublicName();
    project_admin_header($params);

    $title = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'disk_usage_period_'.$period, array($statDuration));
    //Display tooltip for start and end date.
    echo '<h2><span class="plugin_statistics_period" title="'.$GLOBALS['Language']->getText('plugin_statistics_admin_page','disk_usage_period', array($startDate, $endDate)).'">'.$title.'</span></h2>';

    $allowedQuota = $duMgr->getProperty('allowed_quota');
    if ($allowedQuota) {
        echo '<div id="help_init" class="stat_help">'.$GLOBALS['Language']->getText('plugin_statistics_admin_page', 'disk_usage_quota', array($allowedQuota.'GB')).'</div>';
        echo '<p><img src="/plugins/statistics/project_cumulativeDiskUsage_graph.php?func=usage&size='.$duMgr->returnTotalProjectSize($groupId).'&group_id='.$groupId.'" title="Disk usage percentage" /></p>';
    }
    echo '<h3>'.$GLOBALS['Language']->getText('plugin_statistics_show_service', 'service_growth').'</h3>';

    echo '<p><a href="'.$link.'">'.$GLOBALS['Language']->getText('plugin_statistics_admin_page', $period, $statPeriod).'</a></p>';
    echo '<form name="progress_by_service" method="get" action="?">';
    echo '<input type="hidden" name="group_id" value="'.$groupId.'" />';
    echo '<input type="hidden" name="period" value="'.$period.'" />';
    echo '<table>';
    echo '<tr>';
    echo '<th>Services</th>';
    echo '</tr>';

    echo '<tr>';
    $services = array();
    foreach ($duMgr->getProjectServices() as $service => $label) {
        $services[] = array('value' => $service, 'text' => $label);
    }
    echo '<td valign="top">';
    echo html_build_multiple_select_box_from_array($services, 'services[]', $selectedServices, '6', false, '', false, '', false, '', false).' ';
    echo '</td>';
    echo '</tr>';
    echo '</table>';

    echo '<input type="submit" value="'.$GLOBALS['Language']->getText('global', 'btn_submit').'"/>';
    echo '</form>';

    echo '<table><tr><td valign="top">';
    $duHtml = new Statistics_DiskUsageHtml($duMgr);
    $duHtml->getServiceEvolutionForPeriod($startDate, $endDate, $groupId, true);
    echo '</td><td valign="top"><img src="project_stat_graph.php?'.$serviceParam.'&group_id='.$groupId.'&start_date='.$startDate.'&end_date='.$endDate.'" title="Project disk usage graph" />';
    echo '</td></tr></table>';

    site_project_footer($params);
} else {
    header('Location: '.get_server_url());
}

?>