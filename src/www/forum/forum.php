<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

/*

	Forum written 11/99 by Tim Perdue
	Massive re-write 7/2000 by Tim Perdue (nesting/multiple views/etc)

*/

require_once('pre.php');
require('../forum/forum_utils.php');
$Language->loadLanguageMsg('forum/forum');

function forum_show_a_nested_message ($result,$row=0) {
	/*

		accepts a database result handle to display a single message
		in the format appropriate for the nested messages

		second param is which row in that result set to use

	*/
  global $sys_datefmt,$Language;

	$g_id =  db_result($result,$row,'group_id');

	if ($g_id == $GLOBALS['sys_news_group']) {
	  $f_id =  db_result($result,$row,'group_forum_id');
	  $gr = db_query("SELECT group_id FROM news_bytes WHERE forum_id='$f_id'");
	  $g_id = db_result($gr,0,'group_id');
	}

	$ret_val = '
		<TABLE BORDER="0" WIDTH="100%">
			<TR>
				<TD class="thread" NOWRAP>'.$Language->getText('forum_forum','by').': <A HREF="/users/'.
					db_result($result, $row, 'user_name') .'/">'. 
					db_result($result, $row, 'user_name') .'</A>'.
					' ( ' .db_result($result, $row, 'realname') . ' ) '.
					'<BR><A HREF="/forum/message.php?msg_id='.
					db_result($result, $row, 'msg_id') .'">'.
					'<IMG SRC="'.util_get_image_theme("msg.png").'" BORDER=0 HEIGHT=12 WIDTH=10> '.
					db_result($result, $row, 'subject') .' [ '.$Language->getText('forum_forum','reply').' ]</A> &nbsp; '.
					'<BR>'. format_date($sys_datefmt,db_result($result,$row,'date')) .'
				</TD>
			</TR>
			<TR>
				<TD>
					'. util_make_links( nl2br ( db_result($result,$row,'body') ), $g_id ) .'
				</TD>
			</TR>
		</TABLE>';
	return $ret_val;
}

function forum_show_nested_messages ($thread_id, $msg_id) {
  global $total_rows,$sys_datefmt,$Language;

	$sql="SELECT user.user_name,forum.has_followups,user.realname,user.user_id,forum.msg_id,forum.group_forum_id,forum.subject,forum.thread_id,forum.body,forum.date,forum.is_followup_to, forum_group_list.group_id ".
		"FROM forum,user,forum_group_list WHERE forum.thread_id='$thread_id' AND user.user_id=forum.posted_by AND forum.is_followup_to='$msg_id' AND forum_group_list.group_forum_id = forum.group_forum_id ".
		"ORDER BY forum.date ASC;";

	$result=db_query($sql);
	$rows=db_numrows($result);

	$ret_val='';

	if ($result && $rows > 0) {
		$ret_val .= '
			<UL>';

		/*

			iterate and show the messages in this result

			for each message, recurse to show any submessages

		*/
		for ($i=0; $i<$rows; $i++) {
			//	increment the global total count
			$total_rows++;

			//	show the actual nested message
			$ret_val .= forum_show_a_nested_message ($result,$i).'<P>';
			if (db_result($result,$i,'has_followups') > 0) {
				//	Call yourself if there are followups
				$ret_val .= forum_show_nested_messages ( $thread_id, db_result($result,$i,'msg_id') );
			}
		}
		$ret_val .= '
			</UL>';
	}

	return $ret_val;
}

$ret_val="";

if ($forum_id) {
	/*
		if necessary, insert a new message into the forum
	*/

        // Check permissions
        if (!forum_utils_access_allowed($forum_id)) {
            exit_error($Language->getText('global','error'),$Language->getText('forum_forum','forum_restricted'));            
        }
	
	//If the forum is associated to a news, check permissions on this news
	if (!forum_utils_news_access($forum_id)) {	    
	    exit_error($Language->getText('global','error'),$Language->getText('news_admin_index','permission_denied'));
	}

	if (isset($post_message)&&($post_message == 'y')) {
        //
        // MV: add management on "on post monitoring"
        if(isset($_POST['enable_monitoring']) && $_POST['enable_monitoring'] == 1) {
            if(user_isloggedin()) {
                if(!forum_is_monitored($forum_id, user_getid())) {
                    if (forum_add_monitor ($forum_id, user_getid()) ) {
                        $feedback .= $Language->getText('forum_monitor','now_monitoring');              
                    } else {
                        $feedback .= $Language->getText('forum_forum_utils','insert_err');
                    }
                }
            }
        }
		post_message($thread_id, $is_followup_to, $subject, $body, $forum_id);
	}

	/*
		set up some defaults if they aren't provided
	*/
	if ((!isset($offset)) || ($offset < 0)) {
		$offset=0;
	} 

	if (!isset($style)) {
		$style='nested';
	}

	if (!isset($max_rows) || $max_rows < 5) {
		$max_rows=25;
	}

	/*
		take care of setting up/saving prefs

		If they're logged in and a "custom set" was NOT just POSTed,
			see if they have a pref set
				if so, use it
			if it was a custom set just posted && logged in, set pref if it's changed
	*/
	if (user_isloggedin()) {
		$_pref=$style.'|'.$max_rows;
		if (isset($set)&&($set=='custom')) {
			if (user_get_preference('forum_style')) {
				$_pref=$style.'|'.$max_rows;
				if ($_pref == user_get_preference('forum_style')) {
					//do nothing - pref already stored
				} else {
					//set the pref
					user_set_preference ('forum_style',$_pref);
				}
			} else {
					//set the pref
					user_set_preference ('forum_style',$_pref);
			}
		} else {
			if (user_get_preference('forum_style')) {
				$_pref_arr=explode ('|',user_get_preference('forum_style'));
				$style=$_pref_arr[0];
				$max_rows=$_pref_arr[1];
			} else {
				//no saved pref and we're not setting 
				//one because this is all default settings
			}
		}
	}


	/*
		Set up navigation vars
	*/
	$result=db_query("SELECT group_id,forum_name,is_public FROM forum_group_list WHERE group_forum_id='$forum_id'");

	$group_id=db_result($result,0,'group_id');
	$forum_name=db_result($result,0,'forum_name');

        $params=array('title'=>group_getname($group_id).' forum: '.$forum_name,
                      'pv'   =>isset($pv)?$pv:false);
	forum_header($params);

	//private forum check
	if (db_result($result,0,'is_public') != '1') {
		if (!user_isloggedin() || !user_ismember($group_id)) {
			/*
				If this is a private forum, kick 'em out
			*/
			echo '<h1>'.$Language->getText('forum_forum','forum_restricted').'</H1>';
			forum_footer($params);
			exit;
		}
	}

//now set up the query
	if ($style == 'nested' || $style== 'threaded' ) {
		//the flat and 'no comments' view just selects the most recent messages out of the forum
		//the other views just want the top message in a thread so they can recurse.
		$threading_sql='AND forum.is_followup_to=0';
	}

	$sql="SELECT user.user_name,user.realname,forum.has_followups,user.user_id,forum.msg_id,forum.group_forum_id,forum.subject,forum.thread_id,forum.body,forum.date,forum.is_followup_to, forum_group_list.group_id ".
		"FROM forum,user,forum_group_list WHERE forum.group_forum_id='$forum_id' AND user.user_id=forum.posted_by $threading_sql AND forum_group_list.group_forum_id = forum.group_forum_id ".
		"ORDER BY forum.date DESC LIMIT $offset,".($max_rows+1);

	$result=db_query($sql);
	$rows=db_numrows($result);

	if ($rows > $max_rows) {
		$rows=$max_rows;
	}

	$total_rows=0;

	if (!$result || $rows < 1) {
		//empty forum
	  $ret_val .= $Language->getText('forum_forum','no_msg',$forum_name) .'<P>'. db_error();
	} else {

		/*

			build table header

		*/

	//create a pop-up select box listing the forums for this project
		//determine if this person can see private forums or not
		if (user_isloggedin() && user_ismember($group_id)) {
			$public_flag='0,1';
		} else {
			$public_flag='1';
		}
		if ($group_id==$GLOBALS['sys_news_group']) {
			echo '<INPUT TYPE="HIDDEN" NAME="forum_id" VALUE="'.$forum_id.'">';
		} else {
			$res=db_query("SELECT group_forum_id,forum_name ".
					"FROM forum_group_list ".
					"WHERE group_id='$group_id' AND is_public IN ($public_flag)");
			$vals=util_result_column_to_array($res,0);
			$texts=util_result_column_to_array($res,1);

			$forum_popup = html_build_select_box_from_arrays ($vals,$texts,'forum_id',$forum_id,false);
		}
	//create a pop-up select box showing options for viewing threads

		$vals=array('nested','flat','threaded','nocomments');
		$texts=array($Language->getText('forum_forum','nested'),$Language->getText('forum_forum','flat'),$Language->getText('forum_forum','threaded'),$Language->getText('forum_forum','no_comments'));

		$options_popup=html_build_select_box_from_arrays ($vals,$texts,'style',$style,false);

	//create a pop-up select box showing options for max_row count
		$vals=array(25,50,75,100);
		$texts=array($Language->getText('forum_forum','show','25'),$Language->getText('forum_forum','show','50'),$Language->getText('forum_forum','show','75'),$Language->getText('forum_forum','show','100'));

		$max_row_popup=html_build_select_box_from_arrays ($vals,$texts,'max_rows',$max_rows,false);

	//now show the popup boxes in a form
		$ret_val .= '<TABLE BORDER="0" WIDTH="50%">';
                if (!isset($pv)||(!$pv)) {
                    $ret_val .= '
				<FORM ACTION="'. $PHP_SELF .'" METHOD="POST">
				<INPUT TYPE="HIDDEN" NAME="set" VALUE="custom">
				<TR><TD><FONT SIZE="-1">'. $forum_popup .
					'</TD><TD><FONT SIZE="-1">'. $options_popup .
					'</TD><TD><FONT SIZE="-1">'. $max_row_popup .
					'</TD><TD><FONT SIZE="-1"><INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$Language->getText('forum_forum','change_view').'"></TD></TR></TABLE></FORM>';
                }

		if (($style == 'nested')||($style == 'flat')) {
			/*
				no top table row for nested threads or flat display
			*/
		} else {
			/*
				threaded or no comments

				different header for default threading
			*/

			$title_arr=array();
			$title_arr[]=$Language->getText('forum_forum','thread');
			$title_arr[]=$Language->getText('forum_forum','author');
			$title_arr[]=$Language->getText('forum_forum','date');

			$ret_val .= html_build_list_table_top ($title_arr);

		}

		$i=0;
		while (($total_rows < $max_rows) && ($i < $rows)) {
			$total_rows++;
			if ($style == 'nested') {
				/*
					New slashdot-inspired nested threads,
					showing all submessages and bodies
				*/
				//show this one message
				$ret_val .= forum_show_a_nested_message ( $result,$i ).'<BR>';

				if (db_result($result,$i,'has_followups') > 0) {
					//show submessages for this message
					$ret_val .= forum_show_nested_messages ( db_result($result,$i,'thread_id'), db_result($result,$i,'msg_id') );
				}
				$ret_val .= '<hr /><br />';
			} else if ($style == 'flat') {

				//just show the message boxes one after another

				$ret_val .= forum_show_a_nested_message ( $result,$i ).'<BR>';

			} else {
				/*
					no-comments or threaded use the "old" colored-row style

					phorum-esque threaded list of messages,
					not showing message bodies
				*/

				$ret_val .= '
					<TR class="'. util_get_alt_row_color($total_rows) .'"><TD><A HREF="/forum/message.php?msg_id='.
					db_result($result, $i, 'msg_id').'">'.
					'<IMG SRC="'.util_get_image_theme("msg.png").'" BORDER=0 HEIGHT=12 WIDTH=10> ';
				/*

					See if this message is new or not
					If so, highlite it in bold

				*/
				if (get_forum_saved_date($forum_id) < db_result($result,$i,'date')) {
					$ret_val .= '<B>';
				}
				/*
					show the subject and poster
				*/
				$ret_val .= db_result($result, $i, 'subject').'</A></TD>'.
					'<TD>'.db_result($result, $i, 'user_name').'</TD>'.
					'<TD>'.format_date($sys_datefmt,db_result($result,$i,'date')).'</TD></TR>';

				/*

					Show subjects for submessages in this thread

					show_submessages() is recursive

				*/
				if ($style == 'threaded') {
					if (db_result($result,$i,'has_followups') > 0) {
						$ret_val .= show_submessages(db_result($result, $i, 'thread_id'),
							db_result($result, $i, 'msg_id'),1,0);
					}
				}
			}

			$i++;
		}

		/*
			This code puts the nice next/prev.
		*/
            if (($offset != 0)||(db_numrows($result) > $i)) {
		if (($style == 'nested')||($style == 'flat')) {
                    $ret_val .= '<TABLE WIDTH="100%" BORDER="0">';
                }
                $ret_val .= '
				<TR class="threadbody"><TD WIDTH="50%">';
                if ($offset != 0) {
                    $ret_val .= '<B><span class="normal">
				<A HREF="javascript:history.back()">
				<B><IMG SRC="'.util_get_image_theme("t2.png").'" HEIGHT=15 WIDTH=15 BORDER=0 ALIGN=center> '.$Language->getText('forum_forum','prev_msg').'</A></B></span>';
                } else {
                    $ret_val .= '&nbsp;';
                }
                
                $ret_val .= '</TD><TD>&nbsp;</TD><TD ALIGN="RIGHT" WIDTH="50%">';
                if (db_numrows($result) > $i) {
                    $ret_val .= '<B><span class="normal">
				<A HREF="/forum/forum.php?max_rows='.$max_rows.'&style='.$style.'&offset='.($offset+$i).'&forum_id='.$forum_id.'">
				<B>'.$Language->getText('forum_forum','next_msg').' <IMG SRC="'.util_get_image_theme("t.png").'" HEIGHT=15 WIDTH=15 BORDER=0 ALIGN=center></A></span>';
                } else {
                    $ret_val .= '&nbsp;';
                }
                
		if (($style == 'nested')||($style == 'flat')) {
                    $ret_val .= '</TABLE>';
                }
            }

            // Close table when not in nested or flat mode
            if (($style != 'nested')&&($style != 'flat')) {
                    $ret_val .= '</TABLE>';
            }


	}

	echo $ret_val;

        if (!isset($pv)||!$pv) {
            echo '<P>&nbsp;<P>';
            
            echo '<h3>'.$Language->getText('forum_forum','start_new_thread').':</H3><a name="start_new_thread"></a>';
            show_post_form($forum_id);
        }

	forum_footer($params);

} else {

	forum_header(array('title'=>$Language->getText('global','error')));
	echo '<H1'.$Language->getText('forum_forum','choose_forum_first').'</H1>';
	forum_footer(array());

}

?>
