<?php
/*
 * Copyright (c) Xerox, 2008. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2008. Xerox Codendi Team.
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
require_once('common/plugin/Plugin.class.php');

/**
 * Base class for the plugin graphontrackers_scrum
 */
class GraphOnTrackersV5_ScrumPlugin extends Plugin {
    
    /**
     * Constructor
     */
    function __construct($id) {
        parent::__construct($id);
        $this->_addHook('graphontrackersv5_load_chart_factories', 'graphontrackersv5_load_chart_factories', false);
    }
    
    /**
     * Return the info of the plugin
     * @return PluginInfo
     */
    function getPluginInfo() {
        if (!is_a($this->pluginInfo, 'GraphOnTrackersV5_ScrumPluginInfo')) {
            require_once('graphontrackersv5_scrumPluginInfo.class.php');
            $this->pluginInfo = new GraphOnTrackersV5_ScrumPluginInfo($this);
        }
        return $this->pluginInfo;
    }
    
    /**
     * return the different charts provided by this plugin
     */
    function graphontrackersv5_load_chart_factories($params) {
        require_once('GraphOnTrackersV5_Scrum_Chart_Burndown.class.php');
        $params['factories']['graphontrackers_scrum_burndown'] = array(
            //The type of the chart
            'chart_type'      => 'graphontrackers_scrum_burndown',
            //The classname of the chart. The class must be already declared.
            'chart_classname' => 'GraphOnTrackers_Scrum_Chart_Burndown',
            //The icon used for the button 'Add a chart'
            'icon'            => $this->getThemePath().'/images/burndown.png',
            //The title for the button 'Add a chart'
            'title'           => $GLOBALS['Language']->getText('plugin_graphontrackers_scrum', 'add_title_burndown'),
        );
        
        require_once('GraphOnTrackersV5_Scrum_Chart_Burnup.class.php');
        $params['factories']['graphontrackers_scrum_burnup'] = array(
            //The type of the chart
            'chart_type'      => 'graphontrackers_scrum_burnup',
            //The classname of the chart. The class must be already declared.
            'chart_classname' => 'GraphOnTrackers_Scrum_Chart_Burnup',
            //The icon used for the button 'Add a chart'
            'icon'            => $this->getThemePath().'/images/burnup.png',
            //The title for the button 'Add a chart'
            'title'           => $GLOBALS['Language']->getText('plugin_graphontrackers_scrum', 'add_title_burnup'),
        );
    }
}
?>