<?php
/**
 * Copyright (c) STMicroelectronics, 2009. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2009
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
require_once dirname(__FILE__).'/../include/Statistics_DiskUsageGraph.class.php';

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

$error = false;
$feedback = array();

$duMgr  = new Statistics_DiskUsageManager();

$graphType = $request->get('graph_type');

switch($graphType){

    case 'graph_service':

        $vServices = new Valid_WhiteList('services', array_keys($duMgr->getProjectServices()));
        $vServices->required();
        if ($request->validArray($vServices)) {
            $services = $request->get('services');
        } else {
            $services = array();
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
        if($request->valid($vGroupId)) {
            $groupId = $request->get('group_id');
        }

        $vServices = new Valid_WhiteList('services', array_keys($duMgr->getProjectServices()));
        $vServices->required();
        if ($request->validArray($vServices)) {
            $services = $request->get('services');
        } else {
            $services = array();
        }
    break;
    
    default:
}


$groupByDate = array('Day', 'Week', 'Month', 'Year');
$vGroupBy = new Valid_WhiteList('group_by', $groupByDate);
$vGroupBy->required();
if ($request->valid($vGroupBy)) {
    $selectedGroupByDate = $request->get('group_by');
} else {
    $selectedGroupByDate = 'Week';
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

$vRelative = new Valid_WhiteList('relative', array('true'));
$vRelative->required();
if ($request->valid($vRelative)) {
    $relative = true;
} else {
    $relative = false;
}

if (strtotime($startDate) >= strtotime($endDate)) {
    $feedback[] = $GLOBALS['Language']->getText('plugin_statistics', 'period_error');
    $error = true;
}

//
// Display graph
//

$graph = new Statistics_DiskUsageGraph($duMgr);
if (!$error) {

    switch($graphType){
    
        case 'graph_service':
            $graph->displayServiceGraph($services, $selectedGroupByDate, $startDate, $endDate, !$relative);
        break;
       
        case 'graph_user':
            $graph->displayUserGraph($userId, $selectedGroupByDate, $startDate, $endDate, !$relative) ;
        break;
        
        case 'graph_project':
            $graph->displayProjectGraph($groupId, $services, $selectedGroupByDate, $startDate, $endDate, !$relative) ;
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

?>