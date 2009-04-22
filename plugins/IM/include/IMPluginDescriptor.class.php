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
    
    function IMPluginDescriptor() {
        $this->PluginDescriptor($GLOBALS['Language']->getText('plugin_im', 'name'), $GLOBALS['Language']->getText('plugin_im', 'version'), $GLOBALS['Language']->getText('plugin_im', 'description'));
    }
}
?>