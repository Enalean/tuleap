<?php

require_once('common/plugin/Plugin.class.php');

class mysql_maintenancePlugin extends Plugin {
	
	function mysql_maintenancePlugin($id) {
		$this->Plugin($id);
        $this->_addHook('site_admin_option_hook', 'siteAdminHooks', false);
	}
	
    function &getPluginInfo() {
        if (!is_a($this->pluginInfo, 'MySQLMaintenancePluginInfo')) {
            require_once('MySQLMaintenancePluginInfo.class.php');
            $this->pluginInfo =& new MySQLMaintenancePluginInfo($this);
        }
        return $this->pluginInfo;
    }

	function CallHook($hook, $params) {
		if ($hook == 'hook_name') {
			//do Something
		}
	}
    
    function siteAdminHooks($params) {
        echo '<li><a href="'.$this->getPluginPath().'/">MySQL Maintenance</a></li>';
    }
}

?>
