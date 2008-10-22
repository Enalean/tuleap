<?php

require_once('common/plugin/PluginDescriptor.class.php');


class LdapPluginDescriptor extends PluginDescriptor {
    
    function LdapPluginDescriptor() {
        $this->PluginDescriptor('Ldap', 'v1.0', $GLOBALS['Language']->getText('plugin_ldap', 'descriptor_description'));
    }
}
?>