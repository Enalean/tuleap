<?php


/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id$
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