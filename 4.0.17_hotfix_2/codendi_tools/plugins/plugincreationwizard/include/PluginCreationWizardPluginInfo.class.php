<?php

require_once('common/plugin/PluginInfo.class.php');
require_once('PluginCreationWizardPluginDescriptor.class.php');

class PluginCreationWizardPluginInfo extends PluginInfo {
    
    function PluginCreationWizardPluginInfo(&$plugin) {
        $this->PluginInfo($plugin);
        $this->setPluginDescriptor(new PluginCreationWizardPluginDescriptor());
    }
    
}
?>