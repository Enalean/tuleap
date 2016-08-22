<?php

require_once('common/plugin/Plugin.class.php');

/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * 
 *
 * PluginsAdministrationPlugin
 */
class PluginsAdministrationPlugin extends Plugin {
    
    function PluginsAdministrationPlugin($id) {
        $this->Plugin($id);
        $this->_addHook('site_admin_option_hook', 'siteAdminHooks', true);
        $this->_addHook('cssfile', 'cssFile', false);
    }
    
    function &getPluginInfo() {
        if (!is_a($this->pluginInfo, 'PluginsAdministrationPluginInfo')) {
            require_once('PluginsAdministrationPluginInfo.class.php');
            $this->pluginInfo = new PluginsAdministrationPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    function siteAdminHooks($hook, $params)
    {
        $site_url  = $this->getPluginPath() . '/';
        $site_name = $GLOBALS['Language']->getText('plugin_pluginsadministration', 'descriptor_name');
        echo '<li><a href="', $site_url, '">', $site_name, '</a></li>';
    }
    
    function cssFile($params) {
        // Only show the stylesheet if we're actually in the PluginsAdministration pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
        }
    }
    
    function process() {
        require_once('PluginsAdministration.class.php');
        $controler = new PluginsAdministration();
        $controler->process();
    }
}
?>