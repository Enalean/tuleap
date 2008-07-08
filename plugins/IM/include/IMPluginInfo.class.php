<?php
require_once('common/plugin/PluginInfo.class.php');
require_once('IMPluginDescriptor.class.php');

class IMPluginInfo extends PluginInfo {

    function IMPluginInfo(&$plugin) {
        $this->PluginInfo($plugin);
        $this->setPluginDescriptor(new IMPluginDescriptor());
    }
}
?>