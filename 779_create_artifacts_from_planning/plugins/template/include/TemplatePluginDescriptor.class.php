<?php

require_once 'common/plugin/PluginDescriptor.class.php';


class TemplatePluginDescriptor extends PluginDescriptor {
    
    function __construct() {
        parent::__construct($GLOBALS['Language']->getText('plugin_template', 'descriptor_name'), false, $GLOBALS['Language']->getText('plugin_template', 'descriptor_description'));
        $this->setVersionFromFile(dirname(__FILE__).'/../VERSION');
    }
}
?>