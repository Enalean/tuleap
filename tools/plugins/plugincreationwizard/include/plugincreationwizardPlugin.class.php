<?php

require_once('common/plugin/Plugin.class.php');

class PluginCreationWizardPlugin extends Plugin {
	
	function PluginCreationWizardPlugin($id) {
		$this->Plugin($id);
        $this->addHook('site_admin_option_hook', 'siteAdminHooks', false);
        $this->addHook('cssfile', 'cssFile', false);
	}
	
    function &getPluginInfo() {
        if (!is_a($this->pluginInfo, 'PluginCreationWizardPluginInfo')) {
            require_once('PluginCreationWizardPluginInfo.class.php');
            $this->pluginInfo =& new PluginCreationWizardPluginInfo($this);
        }
        return $this->pluginInfo;
    }
    
    function siteAdminHooks($params) {
        echo '<li><a href="'.$this->getPluginPath().'/">PluginCreationWizard</a></li>';
    }
    
    function cssFile($params) {
        // Only show the stylesheet if we're actually in the PluginsAdministration pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], '/plugins/plugincreationwizard/') === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
        }
    }
    
    function process() {
        require_once('PluginCreationWizard.class.php');
        $controler =& new PluginCreationWizard();
        $controler->process();
    }
}

?>
