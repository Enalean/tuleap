<?php

require_once('common/plugin/Plugin.class.php');

/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
        $this->_addHook('widget_myadmin', 'myPageBox', false);
    }
    
    function &getPluginInfo() {
        if (!is_a($this->pluginInfo, 'ServerUpdatePluginInfo')) {
            require_once('ServerUpdatePluginInfo.class.php');
            $this->pluginInfo =& new ServerUpdatePluginInfo($this);
        }
        return $this->pluginInfo;
    }
    
    function siteAdminHooks($hook, $params) {
        $site_url  = $this->getPluginPath().'/';
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
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
        }
    }
    
    function myPageBox($params) {
        if (user_is_super_user()) {
            $params['result'][] = array(
                'text'    => '<a href="/plugins/serverupdate/">'. $GLOBALS['Language']->getText('plugin_serverupdate', 'descriptor_name') .'</a>',
                'value'   => '<span id="widget_serverupdate_status">'. $GLOBALS['HTML']->getImage('ic/spinner.gif', array('alt' => 'Please wait...')) .'</span><script type="text/javascript">'."
                document.observe('dom:loaded', function() {
                    new Ajax.Request('/plugins/serverupdate/?view=widgetstatus', {
                        onSuccess: function(transport) {
                            var json = eval(transport.responseText);
                            $('widget_serverupdate_status').parentNode.setStyle({backgroundColor:json.bgcolor});
                            $('widget_serverupdate_status').update(json.value);
                        }
                    });
                });
                </script>".'
                <noscript style="color:red">(javascript mandatory)</noscript>',
                'bgcolor' => 'white'
            );
        }
    }
    
    function process() {
        require_once('ServerUpdate.class.php');
        $controler =& new ServerUpdate($this->getThemePath());
        $controler->process();
    }
}
?>