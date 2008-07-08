<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id$
 *
 * salomePluginDescriptor
 */
require_once('common/plugin/PluginDescriptor.class.php');


class IMPluginDescriptor extends PluginDescriptor {
    
    function IMPluginDescriptor() {
        $GLOBALS['Language']->loadLanguageMsg('IM', 'IM');
        $this->PluginDescriptor($GLOBALS['Language']->getText('plugin_im', 'name'), $GLOBALS['Language']->getText('plugin_im', 'version'), $GLOBALS['Language']->getText('plugin_im', 'description'));
    }
}
?>