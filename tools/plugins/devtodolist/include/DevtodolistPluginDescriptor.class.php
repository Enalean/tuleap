<?php

require_once('common/plugin/PluginDescriptor.class.php');


class DevtodolistPluginDescriptor extends PluginDescriptor {
    
    function __construct() {
        parent::__construct($GLOBALS['Language']->getText('plugin_devtodolist', 'descriptor_name'), 'v1.0', $GLOBALS['Language']->getText('plugin_devtodolist', 'descriptor_description'));
    }
}
?>