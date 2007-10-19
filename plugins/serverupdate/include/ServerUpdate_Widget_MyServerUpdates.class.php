<?php

require_once('common/widget/Widget.class.php');
require_once('common/include/UserManager.class.php');
require_once('ServerUpdate.class.php');


/**
* ServerUpdate_Widget_MyServerUpdates
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  marc.nazarian@xrce.xerox.com
*/
class ServerUpdate_Widget_MyServerUpdates extends Widget {
    var $pluginPath;
    function ServerUpdate_Widget_MyServerUpdates($pluginPath) {
        $this->Widget('myserverupdates');
        $this->pluginPath = $pluginPath;
    }
    function getTitle() {
        return $GLOBALS['Language']->getText('plugin_serverupdate_widgets', 'my_serverupdates');
    }
    
    function getContent() {
        require_once('www/my/my_utils.php');

        $html = '';

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
                    $html .= '<p><a style="background:red; color:white; padding: 2px 8px; font-weight:bold;" href="/plugins/serverupdate/">'.$GLOBALS['Language']->getText('plugin_serverupdate_widgets','my_serverupdates_nb_critical_updates', $nb_critical_updates).'</a> '.$GLOBALS['Language']->getText('plugin_serverupdate_widgets', 'my_serverupdates_ontheserver').'</p>';
                } else {
                    $html .= '<p><a style="background:orange; color:white; padding: 2px 8px; font-weight:bold;" href="/plugins/serverupdate/">'.$GLOBALS['Language']->getText('plugin_serverupdate_widgets','my_serverupdates_nb_updates', $nb_commits).'</a> '.$GLOBALS['Language']->getText('plugin_serverupdate_widgets', 'my_serverupdates_ontheserver').'</p>';
                }
                $html .= '<a href="/plugins/serverupdate/">'.$GLOBALS['Language']->getText('plugin_serverupdate_widgets', 'my_serverupdates_see_updates').'</a>';
            } else {
                $html .= '<p><a style="background:green; color:white; padding: 2px 8px; font-weight:bold;" href="/plugins/serverupdate/">'.$GLOBALS['Language']->getText('plugin_serverupdate_widgets', 'my_serverupdates_up_to_date').'</a></p>';
                $html .= '<a href="/plugins/serverupdate/">'.$GLOBALS['Language']->getText('plugin_serverupdate_widgets', 'my_serverupdates_goto_serverupdate').'</a>';
            }
        } else {
            $html .= '<a href="/plugins/serverupdate/">'.$GLOBALS['Language']->getText('plugin_serverupdate_widgets', 'my_serverupdates_norepository').'</a>';
            $html .= '<a href="/plugins/serverupdate/">'.$GLOBALS['Language']->getText('plugin_serverupdate_widgets', 'my_serverupdates_goto_serverupdate').'</a>';
        }
        return $html;
    }
}

?>