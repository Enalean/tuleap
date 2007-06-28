<?php

require_once('Widget.class.php');
require_once('common/rss/RSS.class.php');

/**
* Widget_MyProjects
* 
* PROJECT LIST
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
*/
class Widget_MyProjects extends Widget {
    function Widget_MyProjects() {
        $this->Widget('myprojects');
    }
    function getTitle() {
        return $GLOBALS['Language']->getText('my_index', 'my_projects');
    }
    function getContent() {
        $html_my_projects = '';
        $result = db_query("SELECT groups.group_name,"
            . "groups.group_id,"
            . "groups.unix_group_name,"
            . "groups.status,"
            . "groups.is_public,"
            . "user_group.admin_flags "
            . "FROM groups,user_group "
            . "WHERE groups.group_id=user_group.group_id "
            . "AND user_group.user_id='". user_getid() ."' "
            . "AND groups.status='A' ORDER BY group_name");
        $rows=db_numrows($result);
        if (!$result || $rows < 1) {
            $html_my_projects .= $GLOBALS['Language']->getText('my_index', 'not_member');
            $html_my_projects .= db_error();
        } else {
            
            $html_my_projects .= '<table style="width:100%">';
            for ($i=0; $i<$rows; $i++) {
                $html_my_projects .= '
                    <TR class="'. util_get_alt_row_color($i) .'"><TD WIDTH="99%">'.
                    '<A href="/projects/'. db_result($result,$i,'unix_group_name') .'/">'.
                    db_result($result,$i,'group_name') .'</A>';
                if ( db_result($result,$i,'admin_flags') == 'A' ) {
                    $html_my_projects .= ' <small><A HREF="/project/admin/?group_id='.db_result($result,$i,'group_id').'">['.$GLOBALS['Language']->getText('my_index', 'admin_link').']</A></small>';
                }
                if ( db_result($result,$i,'is_public') == 0 ) {
                    $html_my_projects .= ' (*)';
                    $private_shown = true;
                }
                if ( db_result($result,$i,'admin_flags') == 'A' ) {
                    // User can't exit of project if she is admin
                    $html_my_projects .= '</td><td>&nbsp;</td></TR>';
                } else {
                    $html_my_projects .= '</TD>'.
                    '<td><A href="rmproject.php?group_id='. db_result($result,$i,'group_id').
                    '" onClick="return confirm(\''.$GLOBALS['Language']->getText('my_index', 'quit_proj').'\')">'.
                    '<IMG SRC="'.util_get_image_theme("ic/trash.png").'" HEIGHT="16" WIDTH="16" BORDER="0"></A></TD></TR>';
                }
            }
            
            if (isset($private_shown) && $private_shown) {
                $html_my_projects .= '
                <TR class="'. util_get_alt_row_color($i) .'"><TD colspan="2" class="small">'.
                '(*)&nbsp;'.$GLOBALS['Language']->getText('my_index', 'priv_proj').'</td></tr>';
            }
            $html_my_projects .= '</table>';
        }
        return $html_my_projects;
    }
    function hasRss() {
        return true;
    }
    function displayRss() {
        $rss = new RSS(array(
            'title'       => 'CodeX - MyProjects',
            'description' => 'My projects',
            'link'        => get_server_url(),
            'language'    => 'en-us',
            'copyright'   => 'Copyright Xerox',
            'pubDate'     => gmdate('D, d M Y G:i:s',time()).' GMT',
        ));
        $result = db_query("SELECT groups.group_name,"
            . "groups.group_id,"
            . "groups.unix_group_name,"
            . "groups.status,"
            . "groups.is_public,"
            . "user_group.admin_flags "
            . "FROM groups,user_group "
            . "WHERE groups.group_id=user_group.group_id "
            . "AND user_group.user_id='". user_getid() ."' "
            . "AND groups.status='A' ORDER BY group_name");
        $rows=db_numrows($result);
        if (!$result || $rows < 1) {
            $rss->addItem(array(
                'title'       => 'Error',
                'description' => $GLOBALS['Language']->getText('my_index', 'not_member') . db_error(),
                'link'        => get_server_url()
            ));
        } else {
            for ($i=0; $i<$rows; $i++) {
                $title = db_result($result,$i,'group_name');
                if ( db_result($result,$i,'is_public') == 0 ) {
                    $title .= ' (*)';
                }
                
                $desc = 'Project: '. get_server_url() .'/project/admin/?group_id='.db_result($result,$i,'group_id') ."<br />\n";
                if ( db_result($result,$i,'admin_flags') == 'A' ) {
                    $desc .= 'Admin: '. get_server_url() .'/project/admin/?group_id='.db_result($result,$i,'group_id');
                }
                
                $rss->addItem(array(
                    'title'       => $title,
                    'description' => $desc,
                    'link'        => get_server_url() .'/projects/'. db_result($result,$i,'unix_group_name')
                ));
            }
        }
        $rss->display();
    }
}
?>
