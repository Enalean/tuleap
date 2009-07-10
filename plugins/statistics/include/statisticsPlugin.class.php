<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2008
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
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

require_once 'common/plugin/Plugin.class.php';

class StatisticsPlugin extends Plugin {
	
	function __construct($id) {
		parent::__construct($id);
        $this->_addHook('site_admin_option_hook', 'site_admin_option_hook', false);
        $this->_addHook('session_set_new', 'session_set_new', false);
	}
	
    function getPluginInfo() {
        if (!$this->pluginInfo instanceof StatisticsPluginInfo) {
            include_once('StatisticsPluginInfo.class.php');
            $this->pluginInfo = new StatisticsPluginInfo($this);
        }
        return $this->pluginInfo;
    }
    
    function site_admin_option_hook($params) {
        echo '<li><a href="'.$this->getPluginPath().'/">Statistics</a></li>';
    }
    
    function session_set_new($params) {
        $sql = 'INSERT INTO plugin_statistics_user_session (user_id, time)'.
               ' VALUES ('.db_ei($params['user_id']).','.db_ei($params['time']).')';
        db_query($sql);
    }
}

?>
