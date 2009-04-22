<?php


/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * $Id$
 *
 * DataGeneratorPluginInfo
 */
require_once('common/plugin/PluginInfo.class.php');
require_once('DataGeneratorPluginDescriptor.class.php');

class DataGeneratorPluginInfo extends PluginInfo {
    
    function DataGeneratorPluginInfo(&$plugin) {
        $this->PluginInfo($plugin);
        $this->setPluginDescriptor(new DataGeneratorPluginDescriptor());
    }
    
}
?>