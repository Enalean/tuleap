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
            
            if (count($commits) > 0) {
                $i = 0;
    
                $html .= '<table style="width:100%">';
                $html .= '<tr>';
                $html .= '<th>'.$GLOBALS['Language']->getText('plugin_serverupdate_widgets', 'my_serverupdates_revision').'</th>';
                $html .= '<th>'.$GLOBALS['Language']->getText('plugin_serverupdate_widgets', 'my_serverupdates_message').'</th>';
                $html .= '<th>'.$GLOBALS['Language']->getText('plugin_serverupdate_widgets', 'my_serverupdates_date').'</th>';
                $html .= '</tr>';
                foreach($commits as $commit) {
                        $html .= '<tr class="'. util_get_alt_row_color($i++).'">';
                        // Revision
                        $html .= '<td align="left">';
                        $html .= '<a href="/plugins/serverupdate/">'.$commit->getRevision().'</a>';
                        $html .= '</td>';
                    
                        $max_nb_car_message = 100;
                        // Message
                        $html .= '<td align="left">';
                        $message = $commit->getMessage();
                        if (strlen($message) > $max_nb_car_message) {
                            $message = substr($message, 0, $max_nb_car_message);
                            $message .= '(...)';
                        }
                        $html .= nl2br(htmlentities($message));
                        $html .= '</td>';
                        
                        // Date
                        $html .= '<td align="right">';
                        $html .= util_sysdatefmt_to_userdateformat(util_ISO8601_to_date($commit->getDate()));
                        $html .= '</td>';
                    
                        $html .= '</tr>';
                }
                $html .= '</table>';
            } else {
                $html .= '<a href="/plugins/serverupdate/">'.$GLOBALS['Language']->getText('plugin_serverupdate_widgets', 'my_serverupdates_up_to_date').'</a>';
            }
        } else {
            $html .= '<a href="/plugins/serverupdate/">'.$GLOBALS['Language']->getText('plugin_serverupdate_widgets', 'my_serverupdates_norepository').'</a>';
        }
        return $html;
    }
}

?>