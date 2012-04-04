<?php
/**
 * Copyright (c) STMicroelectronics, 2012. All Rights Reserved.
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

require_once 'pre.php';
require_once dirname(__FILE__).'/../include/GitDao.class.php';
require_once dirname(__FILE__).'/../include/Git_LastPushesGraph.class.php';

$vGroupId = new Valid_GroupId();
$vGroupId->required();
if ($request->valid($vGroupId)) {
    $groupId = $request->get('group_id');
    $project = ProjectManager::instance()->getProject($groupId);
} else {
    header('Location: '.get_server_url());
}

$vDuration = new Valid_UInt();
if ($request->valid($vDuration)) {
    $nb_weeks = $request->get('duration');
} else {
    header('Location: '.get_server_url());
}
$imageRenderer = new Git_LastPushesGraph();

$dao             = new GitDao();
$repoList        = $dao->getProjectRepositoryList($groupId);
$day             = 24 * 3600;
$week            = 7 * $day;
$today           = $_SERVER['REQUEST_TIME'];
$start_of_period = strtotime("-$nb_weeks weeks");

$dates   = array();
$year    = array();
$weekNum = array();
for ($i = $start_of_period ; $i <= $today ; $i += $week) {
    $dates[]   = date('M d', $i);
    $weekNum[] = intval(date('W', $i));
    $year[]    = intval(date('Y', $i));
}
$nb_repo = count($repoList);
$graph = $imageRenderer->prepareGraph($nb_repo, $dates);
$colors    = array_reverse(array_slice($GLOBALS['HTML']->getChartColors(), 0, $nb_repo));
$nb_colors = count($colors);
$i         = 0;
$bplot     = array();
$displayChart = false;
foreach ($repoList as $repository) {
    $pushes = array();
    $gitLogDao = new Git_LogDao();
    foreach ($weekNum as $key => $w) {
        $res = $gitLogDao->getRepositoryPushesByWeek($repository['repository_id'], $w, $year[$key]);
        if ($res && !$res->isError()) {
            if ($res->valid()) {
                $row          = $res->current();
                $pushes[$key] = intval($row['pushes']);
                $res->next();
                if ($pushes[$key] > 0) {
                    $displayChart = true;
                }
            }
        }
        $pushes = array_pad($pushes, $nb_weeks, 0);
    }
    if ($displayChart) {
        $b2plot = new BarPlot($pushes);
        $color  = $colors[$i++ % $nb_colors];   
        $b2plot->SetFillgradient($color, $color.':0.6', GRAD_VER);
        $b2plot->SetLegend($repository['repository_name']);
        $bplot[] = $b2plot;
    }
}

if ($displayChart) {
    $imageRenderer->displayAccumulatedGraph($bplot, $graph);
} else {
    //$pngErrorMessage = new Git_LastPushesGraph();
    $msg = "There is no logged pushes in the last $nb_weeks weeks";
    $imageRenderer->displayError($msg);

}

?>
