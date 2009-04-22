<?php

/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * 
 *
 * CreateDAOPluginDescriptor
 */
require_once('common/plugin/PluginDescriptor.class.php');

class CreateDAOPluginDescriptor extends PluginDescriptor {
    
    function CreateDAOPluginDescriptor() {
        $this->PluginDescriptor($GLOBALS['Language']->getText('plugin_createdao', 'descriptor_name'), 'v1.0', $GLOBALS['Language']->getText('plugin_createdao', 'descriptor_description'));
    }
}
?>