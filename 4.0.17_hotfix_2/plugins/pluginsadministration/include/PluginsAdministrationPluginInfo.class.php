<?php

require_once('common/plugin/PluginInfo.class.php');
require_once('PluginsAdministrationPluginDescriptor.class.php');
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * 
 *
 * PluginsAdministrationPluginInfo
 */
class PluginsAdministrationPluginInfo extends PluginInfo {
    
    function PluginsAdministrationPluginInfo(&$plugin) {
        $this->PluginInfo($plugin);
        $this->setPluginDescriptor(new PluginsAdministrationPluginDescriptor());
    }
    
}
?>