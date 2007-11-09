<?php

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * CodeXJRIPluginDescriptor
 */
require_once('common/plugin/PluginDescriptor.class.php');

$GLOBALS['Language']->loadLanguageMsg('codexjri', 'codexjri');

class CodeXJRIPluginDescriptor extends PluginDescriptor {
    
    function CodeXJRIPluginDescriptor() {
        $this->PluginDescriptor($GLOBALS['Language']->getText('plugin_codexjri', 'descriptor_name'), '1.0', $GLOBALS['Language']->getText('plugin_codexjri', 'descriptor_description'));
    }
}
?>