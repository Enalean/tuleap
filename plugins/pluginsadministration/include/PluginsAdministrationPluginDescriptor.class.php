<?php

require_once('common/plugin/PluginDescriptor.class.php');


/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * 
 *
 * PluginsAdministrationPluginDescriptor
 */
class PluginsAdministrationPluginDescriptor extends PluginDescriptor {
    
    function PluginsAdministrationPluginDescriptor() {
        $version     = 'v1.0.1';
        $name        = $GLOBALS['Language']->getText('plugin_pluginsadministration', 'descriptor_name');
        $description = $GLOBALS['Language']->getText('plugin_pluginsadministration', 'descriptor_description');
        $this->PluginDescriptor($name, $version, $description);
    }
    
}
?>