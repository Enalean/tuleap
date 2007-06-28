<?php

require_once('common/plugin/PluginDescriptor.class.php');

$GLOBALS['Language']->loadLanguageMsg('serverUpdate', 'serverupdate');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * ServerUpdatePluginDescriptor
 */
class ServerUpdatePluginDescriptor extends PluginDescriptor {
    
    function ServerUpdatePluginDescriptor() {
        $version     = 'v1.0.0';
        $name        = $GLOBALS['Language']->getText('plugin_serverupdate', 'descriptor_name');
        $description = $GLOBALS['Language']->getText('plugin_serverupdate', 'descriptor_description');
        $this->PluginDescriptor($name, $version, $description);
    }
    
}
?>