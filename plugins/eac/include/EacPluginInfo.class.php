<?php

require_once('common/plugin/PluginInfo.class.php');
require_once('EacPluginDescriptor.class.php');

class EacPluginInfo extends PluginInfo {
    
    function EacPluginInfo(&$plugin) {
        $this->PluginInfo($plugin);
        $this->setPluginDescriptor(new EacPluginDescriptor());
    }
    
}
?>