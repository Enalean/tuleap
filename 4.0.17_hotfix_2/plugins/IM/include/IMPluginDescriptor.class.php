<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * $Id$
 *
 * salomePluginDescriptor
 */
require_once('common/plugin/PluginDescriptor.class.php');


class IMPluginDescriptor extends PluginDescriptor {
    
    function __construct() {
        parent::__construct($GLOBALS['Language']->getText('plugin_im', 'name'), false, $GLOBALS['Language']->getText('plugin_im', 'description'));
        $this->setVersionFromFile(dirname(__FILE__).'/../VERSION');
    }
}
?>