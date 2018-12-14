<?php
/**
 * Copyright (c) Enalean, 2013-2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2006. STMicroelectronics.
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
require_once('pre.php');
require_once('common/plugin/PluginManager.class.php');
require_once(dirname(__FILE__).'/../include/common/GraphicEngineUserPrefs.class.php');
require_once(dirname(__FILE__).'/../include/data-access/GraphOnTrackers_Report.class.php');
require_once('common/tracker/ArtifactType.class.php');
require_once('common/tracker/ArtifactFieldFactory.class.php');

$plugin_manager = PluginManager::instance();
$p = $plugin_manager->getPluginByName('graphontrackers');
if ($p && $plugin_manager->isPluginAvailable($p)) {
    
    $request = HTTPRequest::instance();
    if ($request->valid(new Valid_GroupId()) 
        && ($request->valid(new Valid_UInt('report_graphic_id')))
        && ($request->valid(new Valid_UInt('atid')))
        && ($request->valid(new Valid_WhiteList('type', array('gantt','pie','bar','line'))))) {
        
        $report_graphic_id = $request->get('report_graphic_id');
        $group_id          = $request->get('group_id');
        $atid              = $request->get('atid');
        $type              = $request->get('type');
    
        $pm = ProjectManager::instance();
        $group = $pm->getProject($group_id);
        if ($group === false) {
            exit();
        }

        if ($request->valid(new Valid_UInt('id'))) {
            $id = $request->get('id');
            $gr = new GraphOnTrackers_Report($report_graphic_id);
            if ($c = $gr->getChart($id)) {
                $c->stroke($group_id,$atid);
                //Layout::showDebugInfo();
            }
        }
    }
} else {
    header('Location: '.get_server_url());
}
?>
