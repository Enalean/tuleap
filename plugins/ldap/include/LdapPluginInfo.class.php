<?php

require_once('common/plugin/PluginInfo.class.php');
require_once('LdapPluginDescriptor.class.php');

class LdapPluginInfo extends PluginInfo {
    
    function LdapPluginInfo(&$plugin) {
        $this->PluginInfo($plugin);
        $this->setPluginDescriptor(new LdapPluginDescriptor());
    }
    
}
?>