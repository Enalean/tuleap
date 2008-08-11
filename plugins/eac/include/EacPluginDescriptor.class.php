<?php

require_once('common/plugin/PluginDescriptor.class.php');

class EacPluginDescriptor extends PluginDescriptor {
    
    function EacPluginDescriptor() {
        $GLOBALS['Language']->loadLanguageMsg('eac', 'eac');
        $this->PluginDescriptor($GLOBALS['Language']->getText('plugin_eac', 'descriptor_name'), 'v1.0', $GLOBALS['Language']->getText('plugin_eac', 'descriptor_description'));
    }
}
?>