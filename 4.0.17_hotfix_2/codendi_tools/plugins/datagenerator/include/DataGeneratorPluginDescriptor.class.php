<?php

/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * $Id$
 *
 * DataGeneratorPluginDescriptor
 */
require_once('common/plugin/PluginDescriptor.class.php');


class DataGeneratorPluginDescriptor extends PluginDescriptor {
    
    function DataGeneratorPluginDescriptor() {
        $this->PluginDescriptor($GLOBALS['Language']->getText('plugin_datagenerator', 'descriptor_name'), '0.1', $GLOBALS['Language']->getText('plugin_datagenerator', 'descriptor_description'));
    }
}
?>