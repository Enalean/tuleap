<?php

require_once('common/plugin/PluginDescriptor.class.php');


class TemplatePluginDescriptor extends PluginDescriptor {
    
    function TemplatePluginDescriptor() {
        $this->PluginDescriptor($GLOBALS['Language']->getText('plugin_template', 'descriptor_name'), 'v1.0', $GLOBALS['Language']->getText('plugin_template', 'descriptor_description'));
    }
}
?>