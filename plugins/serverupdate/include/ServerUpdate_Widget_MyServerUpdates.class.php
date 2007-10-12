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
                foreach($commits as $commit) {
                        $html .= '<tr class="'. util_get_alt_row_color($i++).'">';
                        // Révision
                        $html .= '<td align="left">';
                        $html .= '<a href="plugins/serverupdate/">'.$commit->getRevision().'</a>';
                        $html .= '</td>';
                    
                        // Date
                        $html .= '<td align="right">';
                        $html .= util_timestamp_to_userdateformat($commit->getDate(), true);
                        $html .= '</td>';
                    
                        $html .= '</tr>';
                }
                $html .= '</table>';
            } else {
                $html .= $GLOBALS['Language']->getText('plugin_serverupdate_widgets', 'my_serverupdates_up_to_date');
            }
        } else {
            $html .= $GLOBALS['Language']->getText('plugin_serverupdate_widgets', 'my_serverupdates_norepository');
        }
        return $html;
    }
}

?>