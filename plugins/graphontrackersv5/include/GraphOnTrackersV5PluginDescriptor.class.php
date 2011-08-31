<?php
/* 
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2006. STMicroelectronics.
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
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('common/plugin/PluginDescriptor.class.php');


class GraphOnTrackersV5PluginDescriptor extends PluginDescriptor {
    
    function GraphOnTrackersV5PluginDescriptor() {
        $this->PluginDescriptor($GLOBALS['Language']->getText('plugin_graphontrackersv5', 'descriptor_name'), 'v1.0', $GLOBALS['Language']->getText('plugin_graphontrackersv5', 'descriptor_description'));
    }
    
}
?>
