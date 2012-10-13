<?php

require_once('common/plugin/PluginDescriptor.class.php');


class ThemediffPluginDescriptor extends PluginDescriptor {
    
    function ThemediffPluginDescriptor() {
        $this->PluginDescriptor($GLOBALS['Language']->getText('plugin_themediff', 'descriptor_name'), 'v1.0', $GLOBALS['Language']->getText('plugin_themediff', 'descriptor_description'));
    }
}
?>