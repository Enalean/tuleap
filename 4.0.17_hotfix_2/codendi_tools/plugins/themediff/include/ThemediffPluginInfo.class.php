<?php

require_once('common/plugin/PluginInfo.class.php');
require_once('ThemediffPluginDescriptor.class.php');

class ThemediffPluginInfo extends PluginInfo {
    
    function ThemediffPluginInfo(&$plugin) {
        $this->PluginInfo($plugin);
        $this->setPluginDescriptor(new ThemediffPluginDescriptor());
    }
    
}
?>