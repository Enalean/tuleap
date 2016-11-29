<?php
/**
 * Copyright (c) STMicroelectronics, 2009. All Rights Reserved.
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

use Tuleap\Statistics\DiskUsageRouter;
use Tuleap\Statistics\DiskUsageSearchFieldsPresenterBuilder;
use Tuleap\Statistics\DiskUsageServicesPresenterBuilder;
use Tuleap\Statistics\DiskUsageTopProjectsPresenterBuilder;

require 'pre.php';
require_once dirname(__FILE__).'/../include/Statistics_DiskUsageHtml.class.php';

// First, check plugin availability
$pluginManager = PluginManager::instance();
$p = $pluginManager->getPluginByName('statistics');
if (!$p || !$pluginManager->isPluginAvailable($p)) {
    header('Location: '.get_server_url());
}

// Grant access only to site admin
if (!UserManager::instance()->getCurrentUser()->isSuperUser()) {
    header('Location: '.get_server_url());
}

$duMgr  = new Statistics_DiskUsageManager();
$duHtml = new Statistics_DiskUsageHtml($duMgr);

$valid_menu = new Valid_WhiteList('menu', array('one_project_details', 'top_projects', 'services', 'top_users', 'one_user_details'));
$valid_menu->required();
if ($request->valid($valid_menu)) {
    $menu = $request->get('menu');
} else {
    $menu = 'services';
}

$vStartDate = new Valid('start_date');
$vStartDate->addRule(new Rule_Date());
$vStartDate->required();
if ($request->valid($vStartDate)) {
    $startDate = $request->get('start_date');
} else {
    $startDate = date('Y-m-d', strtotime('-1 week'));
}

if (strtotime($startDate) < strtotime('-3 months')) {
    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_statistics', 'querying_purged_data'));
}

$vEndDate = new Valid('end_date');
$vEndDate->addRule(new Rule_Date());
$vEndDate->required();
if ($request->valid($vStartDate)) {
    $endDate = $request->get('end_date');
} else {
    $endDate = date('Y-m-d');
}

if (strtotime($startDate) >= strtotime($endDate)) {
    $GLOBALS['Response']->addFeedback('error', 'You made a mistake in selecting period. Please try again!');
}

$vGroupId = new Valid_UInt('group_id');
$vGroupId->required();
if ($request->valid($vGroupId)) {
    $groupId = $request->get('group_id');
} else {
    $groupId = '';
}

$vUserId = new Valid_UInt('user_id');
$vUserId->required();
if ($request->valid($vUserId)) {
    $userId = $request->get('user_id');
} else {
    $userId = '';
}

$vServices = new Valid_WhiteList('services', array_keys($duMgr->getProjectServices()));
$vServices->required();
if ($request->validArray($vServices)) {
    $selectedServices = $request->get('services');
} else {
    switch ($menu) {
        case 'services':
        case 'one_project_details':
            $selectedServices = array(Statistics_DiskUsageManager::SVN);
            break;
        case 'top_projects':
            $selectedServices = array_keys($duMgr->getProjectServices());
            break;
        default:
    }

}

$selectedGroupByDate = $request->get('group_by');

$vRelative = new Valid_WhiteList('relative', array('true'));
$vRelative->required();
if ($request->valid($vRelative)) {
    $relative = true;
} else {
    $relative = false;
}

$vOrder = new Valid_WhiteList('order', array('start_size', 'end_size', 'evolution', 'evolution_rate'));
$vOrder->required();
if ($request->valid($vOrder)) {
    $order = $request->get('order');
} else {
    $order = 'end_size';
}

$vOffset = new Valid_UInt('offset');
$vOffset->required();
if ($request->valid($vOffset)) {
    $offset = $request->get('offset');
} else {
    $offset = 0;
}

$disk_usage_output = new Statistics_DiskUsageOutput(
    $duMgr
);
$disk_usage_graph = new Statistics_DiskUsageGraph(
    $duMgr
);
$disk_usage_search_fields_builder = new DiskUsageSearchFieldsPresenterBuilder();
$disk_usage_services_builder      = new DiskUsageServicesPresenterBuilder(
    ProjectManager::instance(),
    $duMgr,
    $disk_usage_output,
    $disk_usage_graph,
    $disk_usage_search_fields_builder
);
$disk_usage_top_projects_builder = new DiskUsageTopProjectsPresenterBuilder(
    $duMgr,
    $disk_usage_output,
    $disk_usage_search_fields_builder,
    $disk_usage_services_builder
);

$disk_usage_router = new DiskUsageRouter(
    $duMgr,
    $disk_usage_services_builder,
    $disk_usage_top_projects_builder
);

$disk_usage_router->route($request);

switch ($menu) {
    case 'one_project_details':
        $project = ProjectManager::instance()->getProject($groupId);
        if ($project && !$project->isError()) {
            $projectName = $project->getPublicName().' ('.$project->getUnixName().')';
        } else {
            $projectName = '';
        }

        // Prepare params
        $urlParam    = '';
        $first    = true;
        foreach ($selectedServices as $serv) {
            if ($first != true) {
                $urlParam .= '&';
            }
            $urlParam .= 'services[]='.$serv;
            $first     = false;
        }

        echo '<h2>'.$GLOBALS['Language']->getText('plugin_statistics_show_service', 'service_growth').$projectName.'</h2>';

        echo '<form name="progress_by_project" method="get" action="?">';
        echo '<input type="hidden" name="menu" value="one_project_details" />';
        echo '<label>Project: </label>';
        echo '<input type="text" name="group_id" id="plugin_statistics_project" value="'.$groupId.'" size="4" />';
        echo ' <a href="/admin/groupedit.php?group_id='.$groupId.'">'.$projectName.'</a><br/>';

        echo '<table>';
        echo '<tr>';
        echo '<th>Services</th>';
        echo '<th>Group by</th>';
        echo '<th>Start date</th>';
        echo '<th>End date</th>';
        echo '<th>Options</th>';
        echo '</tr>';

        echo '<tr>';

        $services = array();
        foreach ($duMgr->getProjectServices() as $service => $label) {
            $services[] = array('value' => $service, 'text' => $label);
        }
        echo '<td valign="top">';
        echo html_build_multiple_select_box_from_array($services, 'services[]', $selectedServices, '6', false, '', false, '', false, '', false).' ';
        echo '</td>';

        echo '<td valign="top">';
        echo html_build_select_box_from_array($groupByDate, 'group_by', $selectedGroupByDate, 1).'<br />';
        echo '</td>';

        echo '<td valign="top">';
        list($timestamp,) = util_date_to_unixtime($startDate);
        echo (html_field_date('start_date', $startDate, false, 10, 10, 'progress_by_project', false)).'<br /><em>'.html_time_ago($timestamp).'</em><br />';
        echo '</td>';

        echo '<td valign="top">';
        list($timestamp,) = util_date_to_unixtime($endDate);
        echo (html_field_date('end_date', $endDate, false, 10, 10, 'progress_by_project', false)).'<br /><em>'.html_time_ago($timestamp).'</em><br />';
        echo '</td>';

        $sel = '';
        if ($relative) {
            $sel = ' checked="checked"';
            $urlParam .= '&relative=true';
        }
        echo '<td valign="top">';
        echo '<input type="checkbox" name="relative" value="true" '.$sel.' id="statistics_graph_relative" />';
        echo '<label for="statistics_graph_relative">Relative Y-axis (depend of data set values)</label><br/>';
        echo '</td>';

        echo '</tr>';
        echo '</table>';

        echo '<input type="submit" value="'.$GLOBALS['Language']->getText('global', 'btn_submit').'"/>';

        echo '</form>';

        $urlParam .= '&start_date='.$startDate.'&end_date='.$endDate;
        $urlParam .= '&group_by='.$selectedGroupByDate;
        $urlParam .= '&group_id='.$groupId;
        $urlParam .= '&graph_type=graph_project';
        echo '<p><img src="disk_usage_graph.php?'.$urlParam.'"  title="Test result" /></p>';

        if (($groupId) && ($startDate) && ($endDate)) {
            $duHtml->getServiceEvolutionForPeriod($startDate, $endDate, $groupId, true);
        }
        break;

    case 'top_users':
        $urlParam = '';
        $urlParam .= '?menu=top_users&start_date='.$startDate.'&end_date='.$endDate;

        echo '<h2>'.$GLOBALS['Language']->getText('plugin_statistics_show_top_user', 'top_users').'</h2>';
        echo '<form name="top_users" method="get" action="?">';
        echo '<input type="hidden" name="menu" value="show_top_users" />';

        echo '<label>End: </label>';
        list($timestamp,) = util_date_to_unixtime($endDate);
        echo (html_field_date('end_date', $endDate, false, 10, 10, 'top_users', false)).'&nbsp;<em>'.html_time_ago($timestamp).'</em><br />';

        echo '<input type="submit" value="'.$GLOBALS['Language']->getText('global', 'btn_submit').'"/>';
        echo '</form>';

        $duHtml->getTopUsers($endDate, $order, $urlParam);
        break;

    case 'one_user_details':

        // Prepare params
        $urlParam    = '';

        echo '<h2>'.$GLOBALS['Language']->getText('plugin_statistics_show_one_user', 'user_growth').'</h2>';

        echo '<form name="progress_by_user" method="get" action="?">';
        echo '<input type="hidden" name="menu" value="one_user_details" />';

        echo '<label>User: </label>';
        echo '<input type="text" name="user_id" id="plugin_statistics_project" value="'.$userId.'" />';

        echo '<label>Group by:</label>';
        echo html_build_select_box_from_array($groupByDate, 'group_by', $selectedGroupByDate, 1).'<br />';

        echo '<label>Start: </label>';
        list($timestamp,) = util_date_to_unixtime($startDate);
        echo (html_field_date('start_date', $startDate, false, 10, 10, 'progress_by_user', false)).'&nbsp;<em>'.html_time_ago($timestamp).'</em><br />';

        echo '<label>End: </label>';
        list($timestamp,) = util_date_to_unixtime($endDate);
        echo (html_field_date('end_date', $endDate, false, 10, 10, 'progress_by_user', false)).'&nbsp;<em>'.html_time_ago($timestamp).'</em><br />';

        $sel = '';
        if ($relative) {
            $sel = ' checked="checked"';
            $urlParam .= '&relative=true';
        }
        echo '<input type="checkbox" name="relative" value="true" '.$sel.'/>';
        echo '<label>Relative Y-axis (depend of data set values):</label><br/>';

        echo '<input type="submit" value="'.$GLOBALS['Language']->getText('global', 'btn_submit').'"/>';
        echo '</form>';

        if (($userId) && ($startDate) && ($endDate)) {
            echo '<h3>'.$GLOBALS['Language']->getText('plugin_statistics_show_one_user', 'user_detail').'</h3>';
            $duHtml->getUserDetails($userId);

            $urlParam .= 'start_date='.$startDate.'&end_date='.$endDate;
            $urlParam .= '&group_by='.$selectedGroupByDate;
            $urlParam .= '&user_id='.$userId;
            $urlParam .= '&graph_type=graph_user';

            echo '<p><img src="disk_usage_graph.php?'.$urlParam.'"  title="Test result" /></p>';
            $duHtml->getUserEvolutionForPeriod($userId, $startDate, $endDate);
        }
        break;

    default:
}

$GLOBALS['HTML']->footer(array());

?>
