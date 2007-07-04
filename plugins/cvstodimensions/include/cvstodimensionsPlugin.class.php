<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 *
 * CvsToDimensionsPlugin
 */
require_once('common/plugin/Plugin.class.php');

class CvsToDimensionsPlugin extends Plugin {
	
	function CvsToDimensionsPlugin($id) {
		$this->Plugin($id);
        $this->_addHook('site_admin_option_hook', 'siteAdminHooks', false);
        $this->_addHook('plugin_load_language_file',         'loadPluginLanguageFile',            false);
        $this->_addHook('cssfile', 'cssFile', false);
	}
	
    function &getPluginInfo() {
        if (!is_a($this->pluginInfo, 'CvsToDimensionsPluginInfo')) {
            require_once('CvsToDimensionsPluginInfo.class.php');
            $this->pluginInfo =& new CvsToDimensionsPluginInfo($this);
        }
        return $this->pluginInfo;
    }
    
    function siteAdminHooks($params) {
        echo '<li><a href="'.$this->_getPluginPath().'/">CvsToDimensions</a></li>';
    }
    
    function cssFile($params) {
        // Only show the stylesheet if we're actually in the CvsToDimensions pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->_getPluginPath()) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->_getThemePath().'/css/style.css" />';
        }
    }
    
    function loadPluginLanguageFile($params) {
        $GLOBALS['Language']->loadLanguageMsg('cvstodimensions', 'cvstodimensions');
    }
    
    
    function process() {
        require_once('CvsToDimensions.class.php');
        $controler =& new CvsToDimensions($this);
        $controler->process();
    }
}

?>