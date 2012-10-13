<?php


/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * 
 *
 * LanguagesPluginInfo
 */
require_once('common/plugin/PluginInfo.class.php');
require_once('LanguagesPluginDescriptor.class.php');

class LanguagesPluginInfo extends PluginInfo {
    
    function LanguagesPluginInfo(&$plugin) {
        $this->PluginInfo($plugin);
        $this->setPluginDescriptor(new LanguagesPluginDescriptor());
    }
    
}
?>