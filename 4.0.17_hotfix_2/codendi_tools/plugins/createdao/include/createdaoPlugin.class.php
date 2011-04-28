<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * 
 *
 * CreateDAOPlugin
 */
require_once('common/plugin/Plugin.class.php');

class CreateDAOPlugin extends Plugin {
	
	function CreateDAOPlugin($id) {
		$this->Plugin($id);
        $this->_addHook('site_admin_option_hook', 'siteAdminHooks', false);
	}
	
    function &getPluginInfo() {
        if (!is_a($this->pluginInfo, 'CreateDAOPluginInfo')) {
            require_once('CreateDAOPluginInfo.class.php');
            $this->pluginInfo =& new CreateDAOPluginInfo($this);
        }
        return $this->pluginInfo;
    }
    
    function siteAdminHooks($params) {
        echo '<li><a href="'.$this->getPluginPath().'/">CreateDAO</a></li>';
    }
    
    function process() {
        require_once('CreateDAO.class.php');
        $controler =& new CreateDAO($this);
        $controler->process();
    }
}

?>