<?php

/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * $Id$
 *
 * testsPluginDescriptor
 */
require_once('common/plugin/PluginDescriptor.class.php');


class testsPluginDescriptor extends PluginDescriptor {
    
    function __construct() {
        parent::__construct($GLOBALS['Language']->getText('plugin_tests', 'descriptor_name'), '1.0', $GLOBALS['Language']->getText('plugin_tests', 'descriptor_description'));
    }
}
?>