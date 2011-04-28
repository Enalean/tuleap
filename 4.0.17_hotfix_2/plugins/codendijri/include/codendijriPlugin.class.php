<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

require_once('common/plugin/Plugin.class.php');

class CodendiJRIPlugin extends Plugin {
	
	function CodendiJRIPlugin($id) {
		$this->Plugin($id);
        $this->_addHook('site_admin_option_hook', 'siteAdminHooks', false);
        $this->_addHook('cssfile', 'cssFile', false);
	}
	
    function &getPluginInfo() {
        if (!is_a($this->pluginInfo, 'CodendiJRIPluginInfo')) {
            require_once('CodendiJRIPluginInfo.class.php');
            $this->pluginInfo =& new CodendiJRIPluginInfo($this);
        }
        return $this->pluginInfo;
    }
    
    function siteAdminHooks($params) {
        echo '<li><a href="'.$this->getPluginPath().'/">CodendiJRI</a></li>';
    }
    
    function cssFile($params) {
        // Only show the stylesheet if we're actually in the CodendiJRI pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
        }
    }
    
    function process() {
        require_once('CodendiJRI.class.php');
        $controler =& new CodendiJRI();
        $controler->process();
    }
}

?>