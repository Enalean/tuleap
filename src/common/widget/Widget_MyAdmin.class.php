<?php

require_once('Widget.class.php');

/**
* Widget_MyAdmin
* 
* Personal Admin
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
*/
class Widget_MyAdmin extends Widget {
    function Widget_MyAdmin() {
        $this->Widget('myadmin');
    }
    function getTitle() {
        return $GLOBALS['Language']->getText('my_index', 'my_admin');
    }
    function getContent() {
        $GLOBALS['Language']->loadLanguageMsg('admin/admin');
        $html_my_admin = '';
        // Get the number of pending users and projects
        db_query("SELECT count(*) AS count FROM groups WHERE status='P'");
        $row = db_fetch_array();
        $pending_projects = $row['count'];
        
        if ($GLOBALS['sys_user_approval'] == 1) {
            db_query("SELECT count(*) AS count FROM user WHERE status='P'");
            $row = db_fetch_array();
            $pending_users = $row['count'];
        } else {
            db_query("SELECT count(*) AS count FROM user WHERE status='P' OR status='V'");
            $row = db_fetch_array();
            $pending_users = $row['count'];
        }
        db_query("SELECT count(*) AS count FROM user WHERE status='V'");
        $row = db_fetch_array();
        $validated_users = $row['count'];
        $i = 0;
        $html_my_admin .= '<table width="100%">';
        $html_my_admin .= '<tr class="'. util_get_alt_row_color($i++) .'"><td>'. $GLOBALS['Language']->getText('admin_main', 'pending_user',array("/admin/approve_pending_users.php?page=pending"));
        $html_my_admin .= ' <b>('. $pending_users .'</b>)</td></tr>';
        
        if ($GLOBALS['sys_user_approval'] == 1) {
            $html_my_admin .= '<tr class="'. util_get_alt_row_color($i++) .'"><td>'. $GLOBALS['Language']->getText('admin_main', 'validated_user',array("/admin/approve_pending_users.php?page=validated"));
            $html_my_admin .= ' <b>('. $validated_users .'</b>)</td></tr>';
        }
        
        $html_my_admin .= '<tr class="'. util_get_alt_row_color($i++) .'"><td>'. $GLOBALS['Language']->getText('admin_main', 'pending_group',array("/admin/approve-pending.php"));
        $html_my_admin .= ' <b>('. $pending_projects .'</b>)</td></tr>';
        
        $html_my_admin .= '</table>';
        
        return $html_my_admin;
    }
}
?>
