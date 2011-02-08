<?php

/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 *
 * SvnToDimensionsPluginDescriptor
 */
require_once('common/plugin/PluginDescriptor.class.php');

class SvnToDimensionsPluginDescriptor extends PluginDescriptor {
    
    function __construct() {
        parent::__construct($GLOBALS['Language']->getText('plugin_svntodimensions', 'descriptor_name'), false, $GLOBALS['Language']->getText('plugin_svntodimensions', 'descriptor_description'));
        $this->setVersionFromFile(dirname(__FILE__).'/../VERSION');
    }
}
?>
