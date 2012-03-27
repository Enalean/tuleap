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

require 'pre.php';
require_once 'common/chart/Chart.class.php';
require_once dirname(__FILE__).'/../include/GitDao.class.php';

$vGroupId = new Valid_GroupId();
$vGroupId->required();
if ($request->valid($vGroupId)) {
    $groupId = $request->get('group_id');
    $project = ProjectManager::instance()->getProject($groupId);
} else {
    header('Location: '.get_server_url());
}

$dao      = new GitDao();
$repoList = $dao->getProjectRepositoryList($group_id);

$nb_weeks        = 4 * 3;
$duration        = 7 * $nb_weeks;
$day             = 24 * 3600;
$week            = 7 * $day;
$today           = $_SERVER['REQUEST_TIME'];
$start_of_period = strtotime("-$nb_weeks weeks");

$dates = array();
for($i = $start_of_period ; $i <= $today ; $i += $week) {
    $dates[] = date('M d', $i);
}
$fixture = array(12,2,12,7,14,4,12,9,15,0,14,4);

$graph = new Graph(580,850);
$graph->SetAngle(90);
$graph->SetScale("textlin");

$graph->img->SetMargin(-10,-10,200,350);
$graph->SetMarginColor('white');
$graph->title->Set('Project last git pushes');
$graph->title->SetFont(FF_FONT2,FS_BOLD);

$graph->xaxis->SetLabelMargin(15);
$graph->xaxis->SetLabelAlign('right','center');
$graph->xaxis->SetTickLabels($dates);

$graph->yaxis->SetPos('max');
$graph->yaxis->SetTitle("Pushes",'center');
$graph->yaxis->SetTitleSide(SIDE_RIGHT);
$graph->yaxis->title->SetFont(FF_FONT2,FS_BOLD);
$graph->yaxis->title->SetAngle(0);
$graph->yaxis->title->Align('left','top');
$graph->yaxis->SetTitleMargin(30);

$graph->yaxis->SetLabelSide(SIDE_RIGHT);
$graph->yaxis->SetLabelAlign('center','top');

$graph->legend->Pos(0.1,0.95,'left','bottom');

$nb_repo = count($repoList);
$colors = array_reverse(array_slice($GLOBALS['HTML']->getChartColors(), 0, $nb_repo));
$nb_colors = count($colors);
$i = 0;
$bplot = array();
foreach ($repoList as $repository) {
    $b2plot = new BarPlot($fixture);
    $color = $colors[$i++ % $nb_colors];
    $b2plot->SetColor($color.':0.7');
    $b2plot->setFillColor($color);
    $b2plot->SetLegend($repository['repository_name']);
    $bplot[] = $b2plot;
}

$abplot = new AccBarPlot($bplot);
$abplot->SetShadow();

$abplot->value->Show();
$abplot->value->SetFont(FF_FONT1,FS_NORMAL);
$abplot->value->SetAlign('left','center');
$abplot->value->SetColor("black","darkred");
$abplot->value->SetFormat('%.1d commits');

$graph->Add($abplot);
$graph->Stroke();

?>