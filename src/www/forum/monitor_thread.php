<?php

/* 
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Mohamed CHAARI, 2006. STMicroelectronics.
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */


require_once('pre.php');
require('../forum/forum_utils.php');
$GLOBALS['Language']->loadLanguageMsg('forum/forum');

if ( !user_isloggedin()) {
    exit_not_logged_in();
    return;
}

if(array_key_exists('submit', $_POST) && isset($_POST['submit'])) {
    //set user-specific thread monitoring preferences
    if (forum_thread_monitor($_POST['mthread'], $_POST['user_id'], $_POST['forum_id'])) {
        $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('forum_monitor_thread','monitor_success'); 
    } else {
        $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('forum_monitor_thread','monitor_fail'); 
    }
}

if (isset($_REQUEST['forum_id'])) {

    //Check if thread monitoring is enabled in this forum
    if (! thread_monitoring_is_enabled($forum_id)) {
        exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('forum_monitor_thread','thread_monitor_disabled'));
    }
    
    // Check permissions
    if (!forum_utils_access_allowed($forum_id)) {
        exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('forum_forum','forum_restricted'));            
    }

    $qry = sprintf('SELECT group_id,forum_name,is_public'
		    .' FROM forum_group_list'
		    .' WHERE group_forum_id=%d',
		    $forum_id);
    $result=db_query($qry);
    $group_id=db_result($result,0,'group_id');
    $forum_name=db_result($result,0,'forum_name');
    
    $params=array('title'=>group_getname($group_id).' forum: '.$forum_name,
                      'pv'   =>isset($pv)?$pv:false);
    forum_header($params);
    
    $sql = sprintf('SELECT user.user_name,user.realname,forum.has_followups,user.user_id,forum.msg_id,forum.group_forum_id,forum.subject,forum.thread_id,forum.body,forum.date,forum.is_followup_to, forum_group_list.group_id'
		    .' FROM forum,user,forum_group_list'
		    .' WHERE forum.group_forum_id=%d'
		    .' AND user.user_id=forum.posted_by'
		    .' AND forum.is_followup_to=0'
		    .' AND forum_group_list.group_forum_id = forum.group_forum_id',
		    $forum_id);
    $result=db_query($sql);
    $rows=db_numrows($result);    

    if (!$result || $rows < 1) {
	//empty forum
	$ret_val .= $GLOBALS['Language']->getText('forum_forum','no_msg',$forum_name) .'<P>'. db_error();
    } else {
        $title_arr=array();
        $title_arr[]=$GLOBALS['Language']->getText('forum_monitor_thread','tmonitor');
	$title_arr[]=$GLOBALS['Language']->getText('forum_forum','thread');
        $title_arr[]=$GLOBALS['Language']->getText('forum_forum','author');
	$title_arr[]=$GLOBALS['Language']->getText('forum_forum','date');

        $ret_val .= html_build_list_table_top ($title_arr);
	
	if (forum_is_monitored ($forum_id, user_getid())) {
	    $disabled = "disabled";
	} else {
	    $disabled = "";
	}
        
        $i=0;
        while ($i < $rows) {
	    $thr_id = db_result($result, $i, 'thread_id');
	    if (forum_thread_is_monitored($thr_id, user_getid())) {
	        $monitored = "CHECKED";
	    } else {
	        $monitored = "";
	    }
	    
	    $ret_val .= '<script language="JavaScript">
		        <!--
			function checkAll(val) {
			    al=document.thread_monitor;
			    len = al.elements.length;
                            var i=0;
                            for(i=0 ; i<len ; i++) {
                                if (al.elements[i].name==\'mthread[]\') {al.elements[i].checked=val;}
                            }
			}
		       //-->
		       </script>';

	    $ret_val .= '
		        <TR class="'. util_get_alt_row_color($i) .'">'.
			'<TD align="center"><FORM NAME="thread_monitor" action="'.$PHP_SELF.'" METHOD="POST">'.
			'<INPUT TYPE="hidden" NAME="thread_id" VALUE="'.$thr_id.'">'.
			'<INPUT TYPE="hidden" NAME="user_id" VALUE="'.user_getid().'">'.
			'<INPUT TYPE="hidden" NAME="forum_id" VALUE="'.$forum_id.'">'.
			'<INPUT TYPE="checkbox" '.$disabled.' NAME="mthread[]" VALUE="'.$thr_id.'" '.$monitored.'></TD>'.
			'<TD><A HREF="/forum/message.php?msg_id='.
		        db_result($result, $i, 'msg_id').'">'.
		        '<IMG SRC="'.util_get_image_theme("msg.png").'" BORDER=0 HEIGHT=12 WIDTH=10> ';
	    $ret_val .= db_result($result, $i, 'subject').'</A></TD>'.
			'<TD>'.db_result($result, $i, 'user_name').'</TD>'.
			'<TD>'.format_date($GLOBALS['sys_datefmt'],db_result($result,$i,'date')).'</TD></TR>';	
  	    $i++;
        }
	$ret_val .= '</TABLE><a href="javascript:checkAll(1)">'.$GLOBALS['Language']->getText('tracker_include_report','check_all_items').'</a>'.
               	    ' - <a href="javascript:checkAll(0)">'.$GLOBALS['Language']->getText('tracker_include_report','clear_all_items').' </a>'.
		    '<P><INPUT TYPE="submit" '.$disabled.' NAME="submit"></FORM>';
    }	
    
    echo $ret_val;
    
    if (forum_is_monitored ($forum_id, user_getid())) {  
        $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('forum_monitor_thread','notice');
    }
    
    forum_footer($params);

} else {

    forum_header(array('title'=>$GLOBALS['Language']->getText('global','error')));
    $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('forum_forum','choose_forum_first');
    forum_footer(array());

}
  
?>