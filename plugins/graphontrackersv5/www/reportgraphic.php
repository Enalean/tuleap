<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
require_once(TRACKER_BASE_DIR .'/Tracker/Report/dao/Tracker_Report_RendererDao.class.php');
require_once(dirname(__FILE__).'/../include/data-access/GraphOnTrackersV5_ChartDao.class.php');

$plugin_manager = PluginManager::instance();
$p = $plugin_manager->getPluginByName('graphontrackersv5');
if ($p && $plugin_manager->isPluginAvailable($p)) {
    
    $request = HTTPRequest::instance();
    if ($request->valid(new Valid_UInt('id'))) {
        
        $id = $request->get('id');
    
        $dao = new GraphOnTrackersV5_ChartDao();
        if ($row = $dao->searchById($id)->getRow()) {
            $renderer_dao = new Tracker_Report_RendererDao();
            if ($renderer = $renderer_dao->searchById($row['report_graphic_id'])->getRow()) {
                header('Location: '.TRACKER_BASE_URL.'/?'. http_build_query(array(
                    '_jpg_csimd'                              => 1,
                    'report'                                  => $renderer['report_id'],
                    'renderer'                                => $row['report_graphic_id'],
                    'func'                                    => 'renderer',
                    'renderer_plugin_graphontrackersv5[stroke]' => $id,
                )));
            }
        }
    }
} else {
    header('Location: '.get_server_url());
}
?>
