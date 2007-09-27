<?php

require_once('common/plugin/PluginDescriptor.class.php');

$GLOBALS['Language']->loadLanguageMsg('MySQLMaintenance', 'mysql_maintenance');

class MySQLMaintenancePluginDescriptor extends PluginDescriptor {
    
    function MySQLMaintenancePluginDescriptor() {
        $this->PluginDescriptor('MySQLMaintenance', 'v1.0', $GLOBALS['Language']->getText('plugin_MySQLMaintenance', 'descriptor_description'));
    }
}
?>