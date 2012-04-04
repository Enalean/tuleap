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
$imageRenderer = new Git_LastPushesGraph($groupId, $nb_weeks);
$imageRenderer->setUpGraphEnvironnment();
$graph = $imageRenderer->prepareGraph();
$bplot = $imageRenderer->displayRepositoryPushesByWeek();
if ($imageRenderer->displayChart) {
    $imageRenderer->displayAccumulatedGraph($bplot, $graph);
} else {
    $msg = "There is no logged pushes in the last $nb_weeks weeks";
    $imageRenderer->displayError($msg);
}

?>
