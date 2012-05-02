<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * 
 *
 * LanguagesPlugin
 */
require_once('common/plugin/Plugin.class.php');

class LanguagesPlugin extends Plugin {
	
	function LanguagesPlugin($id) {
		$this->Plugin($id);
        $this->_addHook('site_admin_option_hook', 'siteAdminHooks', false);
        $this->_addHook('cssfile', 'cssFile', false);
	}
	
    function &getPluginInfo() {
        if (!is_a($this->pluginInfo, 'LanguagesPluginInfo')) {
            require_once('LanguagesPluginInfo.class.php');
            $this->pluginInfo =& new LanguagesPluginInfo($this);
        }
        return $this->pluginInfo;
    }
    
    function siteAdminHooks($params) {
        echo '<li><a href="'.$this->getPluginPath().'/">Languages</a></li>';
    }
    
    function cssFile($params) {
        // Only show the stylesheet if we're actually in the Languages pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
        }
    }
    
}

?>