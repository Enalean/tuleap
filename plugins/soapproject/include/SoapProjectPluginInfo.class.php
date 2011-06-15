<?php

require_once 'common/plugin/PluginInfo.class.php';
require_once 'SoapProjectPluginDescriptor.class.php';


/**
 * SoapProjectPluginInfo
 */
class SoapProjectPluginInfo extends PluginInfo {

    function __construct($plugin) {
        parent::__construct($plugin);
        $this->setPluginDescriptor(new SoapProjectPluginDescriptor());
    }
}
?>