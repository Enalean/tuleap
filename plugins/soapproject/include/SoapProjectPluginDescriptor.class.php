<?php

require_once 'common/plugin/PluginDescriptor.class.php';

/**
 * SoapProjectPluginDescriptor
 */
class SoapProjectPluginDescriptor extends PluginDescriptor {

    function __construct() {
        parent::__construct($GLOBALS['Language']->getText('plugin_soapproject', 'descriptor_name'), false, $GLOBALS['Language']->getText('plugin_soapproject', 'descriptor_description'));
        $this->setVersionFromFile(dirname(__FILE__).'/../VERSION');
    }
}
?>