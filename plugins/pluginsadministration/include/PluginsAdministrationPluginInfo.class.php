<?php

require_once('common/plugin/PluginInfo.class.php');
require_once('PluginsAdministrationPluginDescriptor.class.php');
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id: Sanitizer.class.php,v 1.1 2005/05/10 09:48:11 nterray Exp $
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