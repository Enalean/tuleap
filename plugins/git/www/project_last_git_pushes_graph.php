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
} else {
    header('Location: '.get_server_url());
}

$vWeeksNumber = new Valid_UInt('weeks_number');
if($request->valid($vWeeksNumber)) {
    $weeksNumber = $request->get('weeks_number');
}

$plugin = PluginManager::instance()->getPluginByName('git');
if (empty($weeksNumber)) {
    $weeksNumber = $plugin->getPluginInfo()->getPropVal('weeks_number');
}
if (empty($weeksNumber) || $weeksNumber > Git_LastPushesGraph::MAX_WEEKSNUMBER) {
    $weeksNumber = 12;
}
$imageRenderer = new Git_LastPushesGraph($groupId, $weeksNumber);
$imageRenderer->display();
?>