<?php

require_once('common/plugin/Plugin.class.php');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * ServerUpdatePlugin
 */
class ServerUpdatePlugin extends Plugin {
    
    function ServerUpdatePlugin($id) {
        $this->Plugin($id);
        $this->_addHook('site_admin_option_hook', 'siteAdminHooks', true);
        $this->_addHook('site_admin_menu_hook',   'siteAdminHooks', true);
        $this->_addHook('cssfile', 'cssFile', false);
    }
    
    function &getPluginInfo() {
        if (!is_a($this->pluginInfo, 'ServerUpdatePluginInfo')) {
            require_once('ServerUpdatePluginInfo.class.php');
            $this->pluginInfo =& new ServerUpdatePluginInfo($this);
        }
        return $this->pluginInfo;
    }
    
    function siteAdminHooks($hook, $params) {
        $GLOBALS['Language']->loadLanguageMsg('serverUpdate', 'serverupdate');
        $site_url  = $this->_getPluginPath().'/';
        $site_name = $GLOBALS['Language']->getText('plugin_serverupdate','descriptor_name');
        switch ($hook) {
            case 'site_admin_menu_hook':
                $HTML =& $params['HTML'];
                $HTML->menu_entry($site_url, $site_name);
                break;
            case 'site_admin_option_hook':
                echo '<li><a href="',$site_url,'">',$site_name,'</a></li>';
                break;
            default:
                break;
        }
    }
    
    function cssFile($params) {
        // Only show the stylesheet if we're actually in the PluginsAdministration pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->_getPluginPath()) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->_getThemePath().'/css/style.css" />';
        }
    }
    
    function process() {
        require_once('ServerUpdate.class.php');
        $controler =& new ServerUpdate($this->_getThemePath());
        $controler->process();
    }
}
?>