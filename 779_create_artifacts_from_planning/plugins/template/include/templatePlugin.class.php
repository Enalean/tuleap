<?php

require_once('common/plugin/Plugin.class.php');

class TemplatePlugin extends Plugin {
	
	function TemplatePlugin($id) {
		$this->Plugin($id);
        $this->_addHook('hook_name');
        $this->_addHook('site_admin_option_hook', 'siteAdminHooks', false);
	}
	
    function getPluginInfo() {
        if (!is_a($this->pluginInfo, 'TemplatePluginInfo')) {
            require_once('TemplatePluginInfo.class.php');
            $this->pluginInfo =& new TemplatePluginInfo($this);
        }
        return $this->pluginInfo;
    }

	function CallHook($hook, $params) {
		if ($hook == 'hook_name') {
			//do Something
		}
	}
    
    function siteAdminHooks($params) {
        echo '<li><a href="'.$this->getPluginPath().'/">Template</a></li>';
    }
    
    function process() {
        echo '<h1>Template</h1>';
        echo $this->getPluginInfo()->getpropVal('answer');
    }
}

?>
