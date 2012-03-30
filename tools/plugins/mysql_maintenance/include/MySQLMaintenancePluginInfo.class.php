<?php

require_once('common/plugin/PluginInfo.class.php');
require_once('MySQLMaintenancePluginDescriptor.class.php');

class MySQLMaintenancePluginInfo extends PluginInfo {
    
    function MySQLMaintenancePluginInfo(&$plugin) {
        $this->PluginInfo($plugin);
        $this->setPluginDescriptor(new MySQLMaintenancePluginDescriptor());
    }
    
}
?>