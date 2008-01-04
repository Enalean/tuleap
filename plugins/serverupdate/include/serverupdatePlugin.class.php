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
    
    function myPageBox($params) {
        if (user_is_super_user()) {
            require_once('ServerUpdate.class.php');
            $um =& UserManager::instance();
            $user =& $um->getCurrentUser();
            
            $serverupdate = new ServerUpdate(null);
            $svnupdate = $serverupdate->getSVNUpdate();
            if ($svnupdate->getRepository() != "") {
                $commits = $svnupdate->getCommits();
                
                $nb_commits = count($commits);
                if ($nb_commits > 0) {
                    $nb_critical_updates = 0;
                    $critical_updates = false;
                    foreach($commits as $commit) {
                        if ($commit->getLevel() == 'critical') {
                            $critical_updates = true;
                            $nb_critical_updates++;
                        }
                    }
                    
                    if ($critical_updates) {
                        $value   = $GLOBALS['Language']->getText('plugin_serverupdate_widgets','my_serverupdates_nb_critical_updates', $nb_critical_updates);
                        $bgcolor = 'red';
                    } else {
                        $value   = $GLOBALS['Language']->getText('plugin_serverupdate_widgets','my_serverupdates_nb_updates', $nb_commits);
                        $bgcolor = 'orange';
                    }
                } else {
                    $value   = $GLOBALS['Language']->getText('plugin_serverupdate_widgets', 'my_serverupdates_up_to_date');
                    $bgcolor = 'green';
                }
            } else {
                $value   = $GLOBALS['Language']->getText('plugin_serverupdate_widgets', 'my_serverupdates_norepository');
                $bgcolor = 'black';
            }
            $params['result'][] = array(
                'text'    => '<a href="/plugins/serverupdate/">'. $GLOBALS['Language']->getText('plugin_serverupdate', 'descriptor_name') .'</a>',
                'value'   => $value,
                'bgcolor' => $bgcolor
            );
        }
    }
    
    function process() {
        require_once('ServerUpdate.class.php');
        $controler =& new ServerUpdate($this->_getThemePath());
        $controler->process();
    }
}
?>