<?php

/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 *
 * SvnToDimensionsPluginDescriptor
 */
require_once('common/plugin/PluginDescriptor.class.php');

class SvnToDimensionsPluginDescriptor extends PluginDescriptor {
    
    function SvnToDimensionsPluginDescriptor() {
        $this->PluginDescriptor($GLOBALS['Language']->getText('plugin_svntodimensions', 'descriptor_name'), '1.0', $GLOBALS['Language']->getText('plugin_svntodimensions', 'descriptor_description'));
    }
}
?>
