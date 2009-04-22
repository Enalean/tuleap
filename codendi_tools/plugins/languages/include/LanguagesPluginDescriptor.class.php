<?php

/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * 
 *
 * LanguagesPluginDescriptor
 */
require_once('common/plugin/PluginDescriptor.class.php');


class LanguagesPluginDescriptor extends PluginDescriptor {
    
    function LanguagesPluginDescriptor() {
        $this->PluginDescriptor($GLOBALS['Language']->getText('plugin_languages', 'descriptor_name'), 'v1.0', $GLOBALS['Language']->getText('plugin_languages', 'descriptor_description'));
    }
}
?>