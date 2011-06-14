<?php

require_once 'common/plugin/PluginInfo.class.php';
require_once 'TimelinePluginDescriptor.class.php';

/**
 * TimelinePluginInfo
 */
class TimelinePluginInfo extends PluginInfo {
    
    function __construct($plugin) {
        parent::__construct($plugin);
        $this->setPluginDescriptor(new TimelinePluginDescriptor());
    }
    
}
?>