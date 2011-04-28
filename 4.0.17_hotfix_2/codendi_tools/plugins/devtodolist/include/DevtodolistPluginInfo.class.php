<?php

require_once('common/plugin/PluginInfo.class.php');
require_once('DevtodolistPluginDescriptor.class.php');

class DevtodolistPluginInfo extends PluginInfo {
    
    function __construct($plugin) {
        parent::__construct($plugin);
        $this->setPluginDescriptor(new DevtodolistPluginDescriptor());
    }
    
}
?>