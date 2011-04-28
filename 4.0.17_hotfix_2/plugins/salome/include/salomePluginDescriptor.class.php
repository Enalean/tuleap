<?php

/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * $Id$
 *
 * salomePluginDescriptor
 */
require_once('common/plugin/PluginDescriptor.class.php');


class salomePluginDescriptor extends PluginDescriptor {
    
    function __construct() {
        parent::__construct($GLOBALS['Language']->getText('plugin_salome', 'descriptor_name'), false, $GLOBALS['Language']->getText('plugin_salome', 'descriptor_description'));
        $this->setVersionFromFile(dirname(__FILE__).'/../VERSION');
    }
}
?>