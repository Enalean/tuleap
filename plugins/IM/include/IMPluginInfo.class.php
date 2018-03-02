<?php
require_once('common/plugin/PluginInfo.class.php');
require_once('IMPluginDescriptor.class.php');

class IMPluginInfo extends PluginInfo {

    public function __construct($plugin) {
        parent::__construct($plugin);
        $this->setPluginDescriptor(new IMPluginDescriptor());
    }
}
