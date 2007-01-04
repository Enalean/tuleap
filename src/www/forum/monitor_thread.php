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

if($mthread) {
    //set user-specific thread monitoring preferences
    forum_thread_monitor($mthread, $user_id, $forum_id);
}

if ($forum_id) {

    //Check if thread monitoring is enabled in this forum
    if (! thread_monitoring_is_enabled($forum_id)) {
        exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('forum_monitor_thread','thread_monitor_disabled'));
    }
    
    // Check permissions
    if (!forum_utils_access_allowed($forum_id)) {
        exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('forum_forum','forum_restricted'));            
    }

    //If the forum is associated to a private news, non-allowed users shouldn't be able to save their places in this forum
    $qry = "SELECT * FROM news_bytes WHERE forum_id='$forum_id'";
    $res = db_query($qry);
    if (db_numrows($res) > 0) {
        if (!forum_utils_news_access($forum_id)) {	    
	    exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('news_admin_index','permission_denied'));
	}
    }

    $result=db_query("SELECT group_id,forum_name,is_public FROM forum_group_list WHERE group_forum_id='$forum_id'");
    $group_id=db_result($result,0,'group_id');
    $forum_name=db_result($result,0,'forum_name');
    
    $params=array('title'=>group_getname($group_id).' forum: '.$forum_name,
                      'pv'   =>isset($pv)?$pv:false);
    forum_header($params);
    
    if ((!isset($offset)) || ($offset < 0)) {
	$offset=0;
    } 

    if (!isset($max_rows) || $max_rows < 5) {
	$max_rows=25;
    }  
    
    $sql="SELECT user.user_name,user.realname,forum.has_followups,user.user_id,forum.msg_id,forum.group_forum_id,forum.subject,forum.thread_id,forum.body,forum.date,forum.is_followup_to, forum_group_list.group_id ".
	"FROM forum,user,forum_group_list WHERE forum.group_forum_id='$forum_id' AND user.user_id=forum.posted_by AND forum.is_followup_to=0 AND forum_group_list.group_forum_id = forum.group_forum_id ".
	"ORDER BY forum.date DESC LIMIT $offset,".($max_rows+1);

    $result=db_query($sql);
    $rows=db_numrows($result);

    if ($rows > $max_rows) {
	$rows=$max_rows;
    }

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

        $total_rows=0;
        $i=0;
        while (($total_rows < $max_rows) && ($i < $rows)) {
	    $thr_id = db_result($result, $i, 'thread_id');
	    if (forum_thread_is_monitored($thr_id, user_getid())) {
	        $monitored = "CHECKED";
	    } else {
	        $monitored = "";
	    }
	    
	    $total_rows++;  
	    $ret_val .= '
		        <TR class="'. util_get_alt_row_color($total_rows) .'">'.
			'<TD align="center"><FORM NAME="thread_monitor" action="'.$PHP_SELF.'" METHOD="POST">'.
			'<INPUT TYPE="hidden" NAME="thread_id" VALUE="'.$thr_id.'">'.
			'<INPUT TYPE="hidden" NAME="user_id" VALUE="'.user_getid().'">'.
			'<INPUT TYPE="hidden" NAME="forum_id" VALUE="'.$forum_id.'">'.
			'<INPUT TYPE="checkbox" NAME="mthread[]" VALUE="'.$thr_id.'" '.$monitored.'></TD>'.
			'<TD><A HREF="/forum/message.php?msg_id='.
		        db_result($result, $i, 'msg_id').'">'.
		        '<IMG SRC="'.util_get_image_theme("msg.png").'" BORDER=0 HEIGHT=12 WIDTH=10> ';
	    $ret_val .= db_result($result, $i, 'subject').'</A></TD>'.
			'<TD>'.db_result($result, $i, 'user_name').'</TD>'.
			'<TD>'.format_date($GLOBALS['sys_datefmt'],db_result($result,$i,'date')).'</TD></TR>';	
  	    $i++;
        }
	$ret_val .= '</TABLE><P><INPUT TYPE="submit" NAME="submit"></FORM>';
    }	
    
    echo $ret_val;
    
    forum_footer($params);

} else {

    forum_header(array('title'=>$GLOBALS['Language']->getText('global','error')));
    echo '<H1'.$GLOBALS['Language']->getText('forum_forum','choose_forum_first').'</H1>';
    forum_footer(array());

}
  
?>