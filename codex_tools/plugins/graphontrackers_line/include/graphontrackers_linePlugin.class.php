<?php
/*
 * Copyright (c) Xerox, 2008. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2008. Xerox Codex Team.
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
require_once('common/plugin/Plugin.class.php');

require_once('GraphOnTrackers_Line_Chart.class.php');

class graphontrackers_linePlugin extends Plugin {
    function graphontrackers_linePlugin($id) {
        parent::Plugin($id);
        $this->_addHook('graphontrackers_load_chart_factories', 'graphontrackers_load_chart_factories', false);
    }
    
    /**
     * return the info of the plugin
     */
    function getPluginInfo() {
        if (!is_a($this->pluginInfo, 'GraphOnTrackers_LinePluginInfo')) {
            require_once('GraphOnTrackers_LinePluginInfo.class.php');
            $this->pluginInfo =& new GraphOnTrackers_LinePluginInfo($this);
        }
        return $this->pluginInfo;
    }
    
    /**
     * return the different charts provided by this plugin
     */
    function graphontrackers_load_chart_factories($params) {
        $params['factories']['line'] = array(
            //The type of the chart
            'chart_type'      => 'line',
            //The classname of the chart. The class must be already declared.
            'chart_classname' => 'GraphOnTrackers_Line_Chart',
            //The icon used for the button 'Add a chart'
            'icon'            => $this->getThemePath().'/images/chart_line.png',
            //The title for the button 'Add a chart'
            'title'           => $GLOBALS['Language']->getText('plugin_graphontrackers_line', 'add_title'),
        );
    }
}
?>