<?php

require_once 'common/plugin/PluginDescriptor.class.php';

/**
 * TestingPluginDescriptor
 */
class TestingPluginDescriptor extends PluginDescriptor {

    function __construct() {
        parent::__construct($GLOBALS['Language']->getText('plugin_testing', 'descriptor_name'), false, $GLOBALS['Language']->getText('plugin_testing', 'descriptor_description'));
        $this->setVersionFromFile(dirname(__FILE__).'/../VERSION');
    }
}
?>