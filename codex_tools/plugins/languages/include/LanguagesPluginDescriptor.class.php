<?php

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * LanguagesPluginDescriptor
 */
require_once('common/plugin/PluginDescriptor.class.php');

$GLOBALS['Language']->loadLanguageMsg('languages', 'languages');

class LanguagesPluginDescriptor extends PluginDescriptor {
    
    function LanguagesPluginDescriptor() {
        $this->PluginDescriptor($GLOBALS['Language']->getText('plugin_languages', 'descriptor_name'), 'v1.0', $GLOBALS['Language']->getText('plugin_languages', 'descriptor_description'));
    }
}
?>