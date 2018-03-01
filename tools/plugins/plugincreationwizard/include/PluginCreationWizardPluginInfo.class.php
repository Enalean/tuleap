<?php

require_once('common/plugin/PluginInfo.class.php');
require_once('PluginCreationWizardPluginDescriptor.class.php');

class PluginCreationWizardPluginInfo extends PluginInfo {
    
    public function __construct($plugin) {
        parent::__construct($plugin);
        $this->setPluginDescriptor(new PluginCreationWizardPluginDescriptor());
    }
    
}