<?php

require_once 'common/plugin/PluginDescriptor.class.php';

/**
 * TimelinePluginDescriptor
 */
class TimelinePluginDescriptor extends PluginDescriptor {
    
    function __construct() {
        parent::__construct($GLOBALS['Language']->getText('plugin_timeline', 'descriptor_name'), false, $GLOBALS['Language']->getText('plugin_timeline', 'descriptor_description'));
        $this->setVersionFromFile(dirname(__FILE__).'/../VERSION');
    }
}
?>