<?php


/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id$
 *
 * testsPluginInfo
 */
require_once('common/plugin/PluginInfo.class.php');
require_once('testsPluginDescriptor.class.php');

class testsPluginInfo extends PluginInfo {
    
    function testsPluginInfo(&$plugin) {
        $this->PluginInfo($plugin);
        $this->setPluginDescriptor(new testsPluginDescriptor());
    }
    
}
?>