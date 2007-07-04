<?php

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 *
 * CvsToDimensionsPluginDescriptor
 */
require_once('common/plugin/PluginDescriptor.class.php');

$GLOBALS['Language']->loadLanguageMsg('cvstodimensions', 'cvstodimensions');

class CvsToDimensionsPluginDescriptor extends PluginDescriptor {
    
    function CvsToDimensionsPluginDescriptor() {
        $this->PluginDescriptor($GLOBALS['Language']->getText('plugin_cvstodimensions', 'descriptor_name'), '1.0', $GLOBALS['Language']->getText('plugin_cvstodimensions', 'descriptor_description'));
    }
}
?>