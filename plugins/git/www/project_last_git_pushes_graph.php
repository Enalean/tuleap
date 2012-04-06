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

require_once dirname(__FILE__).'/../include/Git_LastPushesGraph.class.php';

$vGroupId = new Valid_GroupId();
$vGroupId->required();
if ($request->valid($vGroupId)) {
    $groupId = $request->get('group_id');
} else {
    header('Location: '.get_server_url());
}

$plugin = PluginManager::instance()->getPluginByName('git');
$duration = $plugin->getPluginInfo()->getPropVal('duration');
if (empty($duration) || $duration > Git_LastPushesGraph::MAX_WEEKSNUMBER) {
    $duration = 12;
}
$imageRenderer = new Git_LastPushesGraph($groupId, $duration);
$imageRenderer->display();
?>
