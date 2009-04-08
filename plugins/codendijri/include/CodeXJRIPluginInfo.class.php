<?php


/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * CodeXJRIPluginInfo
 */
require_once('common/plugin/PluginInfo.class.php');
require_once('CodeXJRIPluginDescriptor.class.php');

class CodeXJRIPluginInfo extends PluginInfo {
    
    function CodeXJRIPluginInfo(&$plugin) {
        $this->PluginInfo($plugin);
        $this->setPluginDescriptor(new CodeXJRIPluginDescriptor());
    }
    
}
?>