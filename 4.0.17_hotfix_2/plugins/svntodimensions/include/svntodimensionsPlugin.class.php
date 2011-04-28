<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 *
 * SvnToDimensionsPlugin
 */
require_once('common/plugin/Plugin.class.php');

class SvnToDimensionsPlugin extends Plugin {
	
	function SvnToDimensionsPlugin($id) {
		$this->Plugin($id);
        $this->_addHook('site_admin_option_hook', 'siteAdminHooks', false);
        $this->_addHook('plugin_load_language_file',         'loadPluginLanguageFile',            false);
        $this->_addHook('cssfile', 'cssFile', false);
	}
	
    function &getPluginInfo() {
        if (!is_a($this->pluginInfo, 'SvnToDimensionsPluginInfo')) {
            require_once('SvnToDimensionsPluginInfo.class.php');
            $this->pluginInfo =& new SvnToDimensionsPluginInfo($this);
        }
        return $this->pluginInfo;
    }
    
    function siteAdminHooks($params) {
        echo '<li><a href="'.$this->getPluginPath().'/adminSvnPasserelle">SvnToDimensions</a></li>';
    }
    
    function cssFile($params) {
        // Only show the stylesheet if we're actually in the SvnToDimensions pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
        }
    }
    
    function loadPluginLanguageFile($params) {
    }
    
    
    function process() {
        require_once('SvnToDimensions.class.php');
        $controler =& new SvnToDimensions($this);
        $controler->process();
    }
}

?>
