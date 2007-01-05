<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');
require('../forum_utils.php');
$Language->loadLanguageMsg('forum/forum');

$is_admin_page='y';

if ($group_id && (user_ismember($group_id, 'F2'))) {

    if (isset($post_changes)) {
        /*
         Update the DB to reflect the changes
        */

        if (isset($delete)) {
                    /*
                     Deleting messages or threads
                    */
                    
                    // First, check if the message exists
                    $sql="SELECT forum_group_list.group_id, forum.group_forum_id FROM forum,forum_group_list ".
                        "WHERE forum.group_forum_id=forum_group_list.group_forum_id AND forum.msg_id='$msg_id'";

                    $result=db_query($sql);

                    if (db_numrows($result) > 0) {
                        $message_group_id=db_result($result,0,'group_id');
                        $forum_id =  db_result($result,0,'group_forum_id');
                        
                        $authorized_to_delete_message=false;

                        // Then, check if the message belongs to a news or a forum
                        if ($message_group_id == $GLOBALS['sys_news_group']) {
                            // This message belongs to a news item.
                            // Check that the news belongs to the same project
                            $gr = db_query("SELECT group_id FROM news_bytes WHERE forum_id='$forum_id'");
                            if (db_result($gr,0,'group_id')==$group_id) {
                                // authorized to delete the message
                                $authorized_to_delete_message=true;
                            }
                        } else if ($message_group_id == $group_id) {
                            // the message belongs to this group's forums
                            $authorized_to_delete_message=true;
                        }

                        if ($authorized_to_delete_message) {
			  //delete monitor settings on the corresponding thread, before deleting the message
			  forum_thread_delete_monitor($forum_id,$msg_id);
			  $feedback .= $Language->getText('forum_admin_index','msgs_del',recursive_delete($msg_id,$forum_id));
                        } else {
                            $feedback .= ' '.$Language->getText('forum_admin_index','msg_not_in_group').' ';
                        }
                    } else {
                        $feedback .= ' '.$Language->getText('forum_admin_index','msg_not_found').' ';
                    }
        } else if (isset($add_forum)) {
			/*
				Adding forums to this group
			*/
		        if (!$forum_name || $forum_name == '' || !$description || $description == '') {
			  $feedback .= exit_error($Language->getText('global','error'),$Language->getText('forum_admin_index','params_missing'));
		        } else {
			    $fid = forum_create_forum($group_id,$forum_name,$is_public,1,$description,$thread_monitor_enable);
			    if ($is_monitored) {
			       forum_add_monitor($fid, user_getid());
			    }
		        }

        } else if (isset($change_status)) {
			/*
				Change a forum to public/private
			*/
			$sql="UPDATE forum_group_list SET is_public='$is_public',forum_name='". htmlspecialchars($forum_name) ."',".
				"description='". htmlspecialchars($description) ."' ,thread_monitored='$thread_monitor_enable' ".
				"WHERE group_forum_id='$group_forum_id' AND group_id='$group_id'";
			$result=db_query($sql);
			if (!$result || db_affected_rows($result) < 1) {
				$feedback .= ' '.$Language->getText('forum_admin_index','upd_err').' ';
			} else {
				$feedback .= ' '.$Language->getText('forum_admin_index','upd_success').' ';
			}
		}

	} 

    if (isset($delete)) {
		/*
			Show page for deleting messages
		*/
		forum_header(array('title'=>$Language->getText('forum_admin_index','del_a_msg'),
				   'help' => 'WebForums.html'));

		echo '
			<H2>'.$Language->getText('forum_admin_index','del_a_msg').'</H2>

			<h2><span class="highlight">'.$Language->getText('forum_admin_index','delete_warn').'</span></h2>
			<FORM METHOD="POST" ACTION="'.$PHP_SELF.'">
			<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
			<INPUT TYPE="HIDDEN" NAME="delete" VALUE="y">
			<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
			<B>'.$Language->getText('forum_admin_index','enter_msg_id').'</B><BR>
			<INPUT TYPE="TEXT" NAME="msg_id" VALUE="">
			<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$Language->getText('global','btn_submit').'">
			</FORM>';

		forum_footer(array());

    } else if (isset($add_forum)) {
		/*
			Show the form for adding forums
		*/
		forum_header(array('title'=>$Language->getText('forum_admin_index','add_a_forum'),
				   'help' => 'WebForums.html'));

		$sql="SELECT forum_name FROM forum_group_list WHERE group_id='$group_id'";
		$result=db_query($sql);
		ShowResultSet($result,$Language->getText('forum_admin_index','existing_forums'), false, $showheaders = false);

		echo '
			<P>
			<H2>'.$Language->getText('forum_admin_index','add_a_forum').'</H2>

			<FORM METHOD="POST" ACTION="'.$PHP_SELF.'">
			<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
			<INPUT TYPE="HIDDEN" NAME="add_forum" VALUE="y">
			<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
			<B>'.$Language->getText('forum_admin_index','forum_name').':</B><BR>
			<INPUT TYPE="TEXT" NAME="forum_name" VALUE="" SIZE="30" MAXLENGTH="50"><BR>
			<B>'.$Language->getText('forum_admin_index','description').':</B><BR>
			<INPUT TYPE="TEXT" NAME="description" VALUE="" SIZE="60" MAXLENGTH="255"><BR>
			<P><B>'.$Language->getText('forum_admin_index','is_public').'</B><BR>
			<INPUT TYPE="RADIO" NAME="is_public" VALUE="1" CHECKED> '.$Language->getText('global','yes').' &nbsp;&nbsp;&nbsp;&nbsp;
			<INPUT TYPE="RADIO" NAME="is_public" VALUE="0"> '.$Language->getText('global','no').'<P>
			<P><B>'.$GLOBALS['Language']->getText('forum_admin_index','enable_thread_monitoring').'</B><BR>
			<INPUT TYPE="RADIO" NAME="thread_monitor_enable" VALUE="1" CHECKED> '.$Language->getText('global','yes').' &nbsp;&nbsp;&nbsp;&nbsp;
			<INPUT TYPE="RADIO" NAME="thread_monitor_enable" VALUE="0"> '.$Language->getText('global','no').'<P>			
			<P><B>'.$Language->getText('forum_admin_index','monitor').'</B><BR>
                                                      '.$Language->getText('forum_admin_index','monitor_recommendation').' <br>
			<INPUT TYPE="RADIO" NAME="is_monitored" VALUE="1" CHECKED> '.$Language->getText('global','yes').' &nbsp;&nbsp;&nbsp;&nbsp;
			<INPUT TYPE="RADIO" NAME="is_monitored" VALUE="0"> '.$Language->getText('global','no').'<P>
			<P>
			<B><span class="highlight">'.$Language->getText('forum_admin_index','once_added_no_delete').'</span></B>
			<P>
			<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$Language->getText('forum_admin_index','add_this_forum').'">
			</FORM>';

		forum_footer(array());

    } else if (isset($change_status)) {
		/*
			Change a forum to public/private
		*/
		forum_header(array('title'=>$Language->getText('forum_admin_index','change_status'),
				   'help' => 'WebForums.html'));

		$sql="SELECT * FROM forum_group_list WHERE group_id='$group_id'";
		$result=db_query($sql);
		$rows=db_numrows($result);

		if (!$result || $rows < 1) {
			echo '
				<H2>'.$Language->getText('forum_admin_index','forum_not_found').'</H2>
				<P>
				'.$Language->getText('forum_admin_index','none_found_for_group');
		} else {
			echo '
			<H2>'.$Language->getText('forum_admin_index','update_f_status').'</H2>
			<P>
			'.$Language->getText('forum_admin_index','private_explain').'<P>';

			$title_arr=array();
			$title_arr[]=$Language->getText('forum_admin_index','forum');
			$title_arr[]=$Language->getText('global','status');
			$title_arr[]=$GLOBALS['Language']->getText('forum_admin_index','thread_monitoring');
			$title_arr[]=$Language->getText('forum_admin_index','update');
		
			echo html_build_list_table_top ($title_arr);

			for ($i=0; $i<$rows; $i++) {
				echo '
					<TR class="'.util_get_alt_row_color($i) .'"><TD>'.db_result($result,$i,'forum_name').'</TD>';
				echo '
					<FORM ACTION="'.$PHP_SELF.'" METHOD="POST">
					<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
					<INPUT TYPE="HIDDEN" NAME="change_status" VALUE="y">
					<INPUT TYPE="HIDDEN" NAME="group_forum_id" VALUE="'.db_result($result,$i,'group_forum_id').'">
					<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
					<TD>
						<FONT SIZE="-1">
						<B>'.$Language->getText('forum_admin_index','is_public').'</B><BR>
						<INPUT TYPE="RADIO" NAME="is_public" VALUE="1"'.((db_result($result,$i,'is_public')=='1')?' CHECKED':'').'> '.$Language->getText('global','yes').'<BR>
						<INPUT TYPE="RADIO" NAME="is_public" VALUE="0"'.((db_result($result,$i,'is_public')=='0')?' CHECKED':'').'> '.$Language->getText('global','no').'<BR>
						<INPUT TYPE="RADIO" NAME="is_public" VALUE="9"'.((db_result($result,$i,'is_public')=='9')?' CHECKED':'').'> '.$Language->getText('forum_admin_index','deleted').'<BR>
					</TD><TD valign="top">
						<B>'.$GLOBALS['Language']->getText('forum_admin_index','enable_thread_monitoring').'</B><BR>
						<INPUT TYPE="RADIO" NAME="thread_monitor_enable" VALUE="1"'.((db_result($result,$i,'thread_monitored')=='1')?' CHECKED':'').'> '.$GLOBALS['Language']->getText('global','yes').'<BR>
						<INPUT TYPE="RADIO" NAME="thread_monitor_enable" VALUE="0"'.((db_result($result,$i,'thread_monitored')=='0')?' CHECKED':'').'> '.$GLOBALS['Language']->getText('global','no').'<BR>						
					</TD><TD>
						<FONT SIZE="-1">
						<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$Language->getText('global','btn_submit').'">
					</TD></TR>
					<TR class="'. util_get_alt_row_color($i) .'"><TD COLSPAN="4">
						<B>'.$Language->getText('forum_admin_index','forum_name').':</B><BR>
						<INPUT TYPE="TEXT" NAME="forum_name" VALUE="'. db_result($result,$i,'forum_name').'" SIZE="30" MAXLENGTH="50"><BR>
						<B>'.$Language->getText('forum_admin_index','description').':</B><BR>
						<INPUT TYPE="TEXT" NAME="description" VALUE="'. db_result($result,$i,'description') .'" SIZE="60" MAXLENGTH="255"><BR>
					</TD></TR></FORM>';
			}
			echo '</TABLE>';
		}

		forum_footer(array());

	} else {
		/*
			Show main page for choosing 
			either moderotor or delete
		*/
		forum_header(array('title'=>$Language->getText('forum_admin_index','forum_admin'),
				   'help' => 'WebForums.html'));

		echo '
			<H2>'.$Language->getText('forum_admin_index','forum_admin').'</H2>
			<P>
			<A HREF="'.$PHP_SELF.'?group_id='.$group_id.'&add_forum=1">'.$Language->getText('forum_admin_index','add_forum').'</A><BR>
			<A HREF="'.$PHP_SELF.'?group_id='.$group_id.'&delete=1">'.$Language->getText('forum_admin_index','del_msg').'</A><BR>
			<A HREF="'.$PHP_SELF.'?group_id='.$group_id.'&change_status=1">'.$Language->getText('forum_admin_index','update_forum_status').'</A>';

		forum_footer(array());
	}

} else {
	/*
		Not logged in or insufficient privileges
	*/
	if (!$group_id) {
		exit_no_group();
	} else {
		exit_permission_denied();
	}
}
?>
