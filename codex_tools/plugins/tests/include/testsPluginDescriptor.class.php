<?php

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id$
 *
 * testsPluginDescriptor
 */
require_once('common/plugin/PluginDescriptor.class.php');


class testsPluginDescriptor extends PluginDescriptor {
    
    function testsPluginDescriptor() {
        $this->PluginDescriptor($GLOBALS['Language']->getText('plugin_tests', 'descriptor_name'), '1.0', $GLOBALS['Language']->getText('plugin_tests', 'descriptor_description'));
    }
}
?>