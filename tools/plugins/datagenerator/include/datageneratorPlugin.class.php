<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * $Id$
 *
 * DataGeneratorPlugin
 */
require_once('common/plugin/Plugin.class.php');

class DataGeneratorPlugin extends Plugin {
	
	function DataGeneratorPlugin($id) {
		$this->Plugin($id);
        $this->_addHook('site_admin_option_hook', 'siteAdminHooks', false);
        $this->_addHook('cssfile', 'cssFile', false);
	}
	
    function &getPluginInfo() {
        if (!is_a($this->pluginInfo, 'DataGeneratorPluginInfo')) {
            require_once('DataGeneratorPluginInfo.class.php');
            $this->pluginInfo =& new DataGeneratorPluginInfo($this);
        }
        return $this->pluginInfo;
    }
    
    function siteAdminHooks($params) {
        echo '<li><a href="'.$this->getPluginPath().'/">DataGenerator</a></li>';
    }
    
    function cssFile($params) {
        // Only show the stylesheet if we're actually in the DataGenerator pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
        }
    }
    
    function process() {
        require_once('DataGenerator.class.php');
        $controler =& new DataGenerator();
        $controler->process();
    }
}

?>