<?php

require_once 'common/plugin/PluginFileInfo.class.php';
require_once 'TemplatePluginDescriptor.class.php';

class TemplatePluginInfo extends PluginFileInfo {

    function __construct($plugin) {
        parent::__construct($plugin, 'template');
        $this->setPluginDescriptor(new TemplatePluginDescriptor());
    }

}
?>