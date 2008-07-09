<?php

require_once('common/plugin/Plugin.class.php');

class ThemediffPlugin extends Plugin {
	
	function ThemediffPlugin($id) {
		$this->Plugin($id);
        $this->_addHook('site_admin_option_hook', 'siteAdminHooks', false);
	}
	
    function &getPluginInfo() {
        if (!is_a($this->pluginInfo, 'ThemediffPluginInfo')) {
            require_once('ThemediffPluginInfo.class.php');
            $this->pluginInfo =& new ThemediffPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    function siteAdminHooks($params) {
        echo '<li><a href="'.$this->getPluginPath().'/">Themediff</a></li>';
    }
}

?>
