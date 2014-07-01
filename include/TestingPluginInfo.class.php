<?php

require_once 'common/plugin/PluginInfo.class.php';
require_once 'TestingPluginDescriptor.class.php';


/**
 * TestingPluginInfo
 */
class TestingPluginInfo extends PluginInfo {

    function __construct($plugin) {
        parent::__construct($plugin);
        $this->setPluginDescriptor(new TestingPluginDescriptor());
    }
}
?>