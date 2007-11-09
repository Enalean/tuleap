<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * CodeXJRIPlugin
 */
require_once('common/plugin/Plugin.class.php');

class CodeXJRIPlugin extends Plugin {
	
	function CodeXJRIPlugin($id) {
		$this->Plugin($id);
        $this->_addHook('site_admin_option_hook', 'siteAdminHooks', false);
        $this->_addHook('cssfile', 'cssFile', false);
	}
	
    function &getPluginInfo() {
        if (!is_a($this->pluginInfo, 'CodeXJRIPluginInfo')) {
            require_once('CodeXJRIPluginInfo.class.php');
            $this->pluginInfo =& new CodeXJRIPluginInfo($this);
        }
        return $this->pluginInfo;
    }
    
    function siteAdminHooks($params) {
        echo '<li><a href="'.$this->_getPluginPath().'/">CodeXJRI</a></li>';
    }
    
    function cssFile($params) {
        // Only show the stylesheet if we're actually in the CodeXJRI pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->_getPluginPath()) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->_getThemePath().'/css/style.css" />';
        }
    }
    
    function process() {
        require_once('CodeXJRI.class.php');
        $controler =& new CodeXJRI();
        $controler->process();
    }
}

?>