<?php

require_once('common/plugin/PluginInfo.class.php');
require_once('ServerUpdatePluginDescriptor.class.php');
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * 
 *
 * ServerUpdatePluginInfo
 */
class ServerUpdatePluginInfo extends PluginInfo {
    
    function ServerUpdatePluginInfo(&$plugin) {
        $this->PluginInfo($plugin);
        $this->setPluginDescriptor(new ServerUpdatePluginDescriptor());
    }
    
}
?>