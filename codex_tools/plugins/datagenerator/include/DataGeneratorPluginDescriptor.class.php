<?php

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id$
 *
 * DataGeneratorPluginDescriptor
 */
require_once('common/plugin/PluginDescriptor.class.php');

$GLOBALS['Language']->loadLanguageMsg('datagenerator', 'datagenerator');

class DataGeneratorPluginDescriptor extends PluginDescriptor {
    
    function DataGeneratorPluginDescriptor() {
        $this->PluginDescriptor($GLOBALS['Language']->getText('plugin_datagenerator', 'descriptor_name'), '0.1', $GLOBALS['Language']->getText('plugin_datagenerator', 'descriptor_description'));
    }
}
?>