<?php

require_once('common/plugin/PluginInfo.class.php');
require_once('TemplatePluginDescriptor.class.php');

class TemplatePluginInfo extends PluginInfo {
    
    function TemplatePluginInfo(&$plugin) {
        $this->PluginInfo($plugin);
        $this->setPluginDescriptor(new TemplatePluginDescriptor());
    }
    
}
?>