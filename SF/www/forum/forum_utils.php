<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

/*

	Message Forums
	By Tim Perdue, Sourceforge, 11/99

	Massive rewrite by Tim Perdue 7/2000 (nested/views/save)

*/

require($DOCUMENT_ROOT.'/news/news_utils.php');

function forum_header($params) {
	global $DOCUMENT_ROOT,$HTML,$group_id,$forum_name,$thread_id,$msg_id,$forum_id,$REQUEST_URI,$sys_datefmt,$et,$et_cookie;

	$params['group']=$group_id;
	$params['toptab']='forums';

	/*

		bastardization for news

		Show icon bar unless it's a news forum

	*/
	if ($group_id == $GLOBALS['sys_news_group']) {
		//this is a news item, not a regular forum
		if ($forum_id) {
			/*
				Show this news item at the top of the page
			*/
			$sql="SELECT * FROM news_bytes WHERE forum_id='$forum_id'";
			$result=db_query($sql);


			//backwards shim for all "generic news" that used to be submitted
			//as of may, "generic news" is not permitted - only project-specific news
	       		if (db_result($result,0,'group_id') != $GLOBALS['sys_news_group']) {
				$params['group']=db_result($result,0,'group_id');
        			$params['toptab']='news';
				site_project_header($params);
			} else {
				$HTML->header($params);
				echo '
					<H2>'.$GLOBALS['sys_name'].' <A HREF="/news/">News</A></H2><P>';
			}


			echo '<TABLE><TR><TD VALIGN="TOP">';
			if (!$result || db_numrows($result) < 1) {
				echo '
					<h3>Error - this news item was not found</h3>';
			} else {
				echo '
				<B>Posted By:</B> '.user_getname( db_result($result,0,'submitted_by')).'<BR>
				<B>Date:</B> '. format_date($sys_datefmt,db_result($result,0,'date')).'<BR>
				<B>Summary:</B><A HREF="/forum/forum.php?forum_id='.db_result($result,0,'forum_id').'">'. db_result($result,0,'summary').'</A>
				<P>
				'. util_make_links( nl2br( db_result($result,0,'details')));

				echo '<P>';
			}
			echo '</TD><TD VALIGN="TOP" WIDTH="35%">';
			echo $HTML->box1_top('Latest News',0,$GLOBALS['COLOR_LTBACK2']);
			echo news_show_latest($GLOBALS['sys_news_group'],5,false);
			echo $HTML->box1_bottom();
			echo '</TD></TR></TABLE>';
		}
	} else {
		//this is just a regular forum, not a news item
		site_project_header($params);
	}

	/*
		Show horizontal forum links
	*/
	if ($forum_id && $forum_name) {
		echo '<P><H3>Discussion Forums: <A HREF="/forum/forum.php?forum_id='.$forum_id.'">'.$forum_name.'</A></H3>';
	}
	echo '<P><B>';

	if ($forum_id && user_isloggedin() ) {
	    if (forum_is_monitored($forum_id,user_getid()) )
		$msg = "Stop Monitoring Forum";
	    else 
		$msg = "Monitor Forum";
		    
		echo '<A HREF="/forum/monitor.php?forum_id='.$forum_id.'">' . 
			html_image("ic/check.png",array()).' '.$msg.' | '.
			'<A HREF="/forum/save.php?forum_id='.$forum_id.'">';
		echo  html_image("ic/save.png",array()) .' Save Place</A> | ';
	}

	echo '  <A HREF="/forum/admin/?group_id='.$group_id.'">Admin</A></B>';
	echo '<P>';
}

function forum_footer($params) {
	global $group_id,$HTML;
	/*
		if general news, show general site footer

		Otherwise, show project footer
	*/

	//backwards compatibility for "general news" which is no longer permitted to be submitted
	if ($group_id == $GLOBALS['sys_news_group']) {
		$HTML->footer($params);
	} else {
		site_project_footer($params);
	}
}

function forum_is_monitored ($forum_id, $user_id) {
    $sql="SELECT * FROM forum_monitored_forums WHERE user_id='".$user_id."' AND forum_id='$forum_id';";
    $result = db_query($sql);
    return ($result && db_numrows($result) >= 1);
}

function forum_add_monitor ($forum_id, $user_id) {
    global $feedback;

    if (forum_is_monitored($forum_id, $user_id)) {
	$feedback .= "Forum already monitored";
    } else {
	// Not already monitoring so add it.
	$sql="INSERT INTO forum_monitored_forums (forum_id,user_id) VALUES ('$forum_id','".$user_id."')";
	$result = db_query($sql);

	if (!$result) {
	    $feedback .= "Error inserting into forum_monitoring";
	    return false;
	}
    } 
    return true;
}

function forum_delete_monitor ($forum_id, $user_id) {
    global $feedback;
    $sql="DELETE FROM forum_monitored_forums WHERE user_id='".$user_id."' AND forum_id='$forum_id';";
    $result = db_query($sql);
    return true;
}

function forum_create_forum($group_id,$forum_name,$is_public=1,$create_default_message=1,$description='') {
	global $feedback;
	/*
		Adding forums to this group
	*/
	$sql="INSERT INTO forum_group_list (group_id,forum_name,is_public,description) ".
		"VALUES ('$group_id','". htmlspecialchars($forum_name) ."','$is_public','". htmlspecialchars($description) ."')";

	$result=db_query($sql);
	if (!$result) {
		$feedback .= " Error Adding Forum ";
	} else {
		$feedback .= " Forum Added ";
	}
	$forum_id=db_insertid($result);

	if ($create_default_message) {
		//set up a cheap default message
		$result2=db_query("INSERT INTO forum ".
			"(group_forum_id,posted_by,subject,body,date,is_followup_to,thread_id) ".
			"VALUES ('$forum_id','100','Welcome to $forum_name',".
			"'Welcome to $forum_name','".time()."','0','".get_next_thread_id()."')");
	}
	return $forum_id;
}

function make_links ($data="") {
	//moved make links to /include/utils.php
	util_make_links($data);
}

function get_forum_name($id) {
	/*
		Takes an ID and returns the corresponding forum name
	*/
	$sql="SELECT forum_name FROM forum_group_list WHERE group_forum_id='$id'";
	$result=db_query($sql);
	if (!$result || db_numrows($result) < 1) {
		return "Not Found";
	} else {
		return db_result($result, 0, "forum_name");
	}

}

function show_thread($thread_id,$et=0) {
	/*
		Takes a thread_id and fetches it, then invokes show_submessages to nest the threads

		$et is whether or not the forum is "expanded" or in flat mode
	*/
	global $total_rows,$sys_datefmt,$is_followup_to,$subject,$forum_id,$current_message;

	$sql="SELECT user.user_name,forum.has_followups,forum.msg_id,forum.subject,forum.thread_id,forum.body,forum.date,forum.is_followup_to ".
		"FROM forum,user WHERE forum.thread_id='$thread_id' AND user.user_id=forum.posted_by AND forum.is_followup_to='0' ".
		"ORDER BY forum.msg_id DESC;";

	$result=db_query($sql);

	$total_rows=0;

	if (!$result || db_numrows($result) < 1) {
		return 'Broken Thread';
	} else {

		$title_arr=array();
		$title_arr[]='Thread';
		$title_arr[]='Author';
		$title_arr[]='Date';

		$ret_val .= html_build_list_table_top ($title_arr);

		$rows=db_numrows($result);
		$is_followup_to=db_result($result, ($rows-1), 'msg_id');
		$subject=db_result($result, ($rows-1), 'subject');
/*
	Short - term compatibility fix. Leaving the iteration in for now -
	will remove in the future. If we remove now, some messages will become hidden

	No longer iterating here. There should only be one root message per thread now.
	Messages posted at the thread level are shown as followups to the first message
*/
		for ($i=0; $i<$rows; $i++) {
			$total_rows++;
			$ret_val .= '<TR BGCOLOR="'. util_get_alt_row_color($total_rows) .'"><TD>'. 
				(($current_message != db_result($result, $i, 'msg_id'))?'<A HREF="/forum/message.php?msg_id='.db_result($result, $i, 'msg_id').'">':'').
				'<IMG SRC="/images/msg.png" BORDER=0 HEIGHT=12 WIDTH=10> ';
			/*
				See if this message is new or not
			*/
			if (get_forum_saved_date($forum_id) < db_result($result,$i,'date')) { $ret_val .= '<B>'; }

			$ret_val .= db_result($result, $i, 'subject') .'</A></TD>'.
				'<TD>'.db_result($result, $i, 'user_name').'</TD>'.
				'<TD>'.format_date($sys_datefmt,db_result($result,$i,'date')).'</TD></TR>';
			/*
				Show the body/message if requested
			*/
			if ($et == 1) {
				$ret_val .= '
				<TR BGCOLOR="'. util_get_alt_row_color($total_rows) .'"><TD>&nbsp;</TD><TD COLSPAN=2>'.
				nl2br(db_result($result, $i, 'body')).'</TD><TR>';
			}

			if (db_result($result,$i,'has_followups') > 0) {
				$ret_val .= show_submessages($thread_id,db_result($result, $i, 'msg_id'),1,$et);
			}
		}
		$ret_val .= '</TABLE>';
	}
	return $ret_val;
}

function show_submessages($thread_id, $msg_id, $level,$et=0) {
	/*
		Recursive. Selects this message's id in this thread, 
		then checks if any messages are nested underneath it. 
		If there are, it calls itself, incrementing $level
		$level is used for indentation of the threads.
	*/
	global $total_rows,$sys_datefmt,$forum_id,$current_message;

	$sql="SELECT user.user_name,forum.has_followups,forum.msg_id,forum.subject,forum.thread_id,forum.body,forum.date,forum.is_followup_to ".
		"FROM forum,user WHERE forum.thread_id='$thread_id' AND user.user_id=forum.posted_by AND forum.is_followup_to='$msg_id' ".
		"ORDER BY forum.msg_id ASC;";

	$result=db_query($sql);
	$rows=db_numrows($result);

	if ($result && $rows > 0) {
		for ($i=0; $i<$rows; $i++) {
			/*
				Is this row's background shaded or not?
			*/
			$total_rows++;

			$ret_val .= '
				<TR BGCOLOR="'. util_get_alt_row_color($total_rows) .'"><TD NOWRAP>';
			/*
				How far should it indent?
			*/
			for ($i2=0; $i2<$level; $i2++) {
				$ret_val .= ' &nbsp; &nbsp; &nbsp; ';
			}

			/*
				If it this is the message being displayed, don't show a link to it
			*/
			$ret_val .= (($current_message != db_result($result, $i, 'msg_id'))?
				'<A HREF="/forum/message.php?msg_id='.db_result($result, $i, 'msg_id').'">':'').
				'<IMG SRC="/images/msg.png" BORDER=0 HEIGHT=12 WIDTH=10> ';
			/*
				See if this message is new or not
			*/
			if (get_forum_saved_date($forum_id) < db_result($result,$i,'date')) { $ret_val .= '<B>'; }

			$ret_val .= db_result($result, $i, 'subject').'</A></TD>'.
				'<TD>'.db_result($result, $i, 'user_name').'</TD>'.
				'<TD>'.format_date($sys_datefmt,db_result($result,$i,'date')).'</TD></TR>';

			/*
				Show the body/message if requested
			*/
			if ($et == 1) {
				$ret_val .= '
					<TR BGCOLOR="'. util_get_alt_row_color($total_rows) .'"><TD>&nbsp;</TD><TD COLSPAN=2>'.
					nl2br(db_result($result, $i, 'body')).'</TD><TR>';
			}

			if (db_result($result,$i,'has_followups') > 0) {
				/*
					Call yourself, incrementing the level
				*/
				$ret_val .= show_submessages($thread_id,db_result($result, $i, 'msg_id'),($level+1),$et);
			}
		}
	}
	return $ret_val;
}

function get_next_thread_id() {
	/*
		Get around limitation in MySQL - Must use a separate table with an auto-increment
	*/
	$result=db_query("INSERT INTO forum_thread_id VALUES ('')");

	if (!$result) {
		echo '<H1>Error!</H1>';
		echo db_error();
		exit;
	} else {
		return db_insertid($result);
	}
}

function get_forum_saved_date($forum_id) {
	/*
		return the save_date for this user
	*/
	global $forum_saved_date;

	if ($forum_saved_date) {
		return $forum_saved_date;
	} else {
		$sql="SELECT save_date FROM forum_saved_place WHERE user_id='".user_getid()."' AND forum_id='$forum_id';";
		$result = db_query($sql);
		if ($result && db_numrows($result) > 0) {
			$forum_saved_date=db_result($result,0,'save_date');
			return $forum_saved_date;
		} else {
			//highlight new messages from the past week only
			$forum_saved_date=(time()-604800);
			return $forum_saved_date;
		}
	}
}

function post_message($thread_id, $is_followup_to, $subject, $body, $group_forum_id) {
	global $feedback;
	if (user_isloggedin()) {
		if (!$group_forum_id) {
			exit_error('Error','Trying to post without a forum ID');
		}
		if (!$body || !$subject) {
			exit_error('Error','Must include a message body and subject');
		}

	//see if that message has been posted already for all the idiots that double-post
		$res3=db_query("SELECT * FROM forum ".
			"WHERE is_followup_to='$is_followup_to' ".
			"AND subject='".  htmlspecialchars($subject) ."' ".
			"AND group_forum_id='$group_forum_id' ".
			"AND posted_by='". user_getid() ."'");

		if (db_numrows($res3) > 0) {
			//already posted this message
			exit_error('Error','You appear to be double-posting this message, since it has the same subject and followup information as a prior post.');
		} else {
			echo db_error();
		}

		if (!$thread_id) {
			$thread_id=get_next_thread_id();
			$is_followup_to=0;
		} else {
			if ($is_followup_to) {
				//increment the parent's followup count if necessary
				$res2=db_query("SELECT * FROM forum WHERE msg_id='$is_followup_to' AND thread_id='$thread_id' AND group_forum_id='$group_forum_id'");
				if (db_numrows($res2) > 0) {
					if (db_result($result,0,'has_followups') > 0) {
						//parent already is marked with followups
					} else {
						//mark the parent with followups as an optimization later
						db_query("UPDATE forum SET has_followups='1' WHERE msg_id='$is_followup_to' AND thread_id='$thread_id' AND group_forum_id='$group_forum_id'");
					}
				} else {
					exit_error('Error','Trying to followup to a message that doesn\'t exist.');
				}
			} else {
				//should never happen except with shoddy browsers or mucking with the HTML form
				exit_error('Error','No followup ID present when trying to post to an existing thread.');
			}
		}

		$sql="INSERT INTO forum (group_forum_id,posted_by,subject,body,date,is_followup_to,thread_id) ".
			"VALUES ('$group_forum_id', '".user_getid()."', '".htmlspecialchars($subject)."', '".htmlspecialchars($body)."', '".time()."','$is_followup_to','$thread_id')";

		$result=db_query($sql);

		if (!$result) {
			echo "INSERT FAILED";
			echo db_error();
			$feedback .= ' Posting Failed ';
		} else {
			$feedback .= ' Message Posted ';
		}

		$msg_id=db_insertid($result);
		handle_monitoring($group_forum_id,$msg_id);

	} else {

		echo '
			<H3>You could post if you were logged in</H3>';

	}

}

function show_post_form($forum_id, $thread_id=0, $is_followup_to=0, $subject="") {
    global $REQUEST_URI;

	if (user_isloggedin()) {
		if ($subject) {
			//if this is a followup, put a RE: before it if needed
			if (!eregi('RE:',$subject,$test)) {
				$subject ='RE: '.$subject;
			}
		}

		?>
		<CENTER>
		<FORM ACTION="/forum/forum.php" METHOD="POST">
		<INPUT TYPE="HIDDEN" NAME="post_message" VALUE="y">
		<INPUT TYPE="HIDDEN" NAME="forum_id" VALUE="<?php echo $forum_id; ?>">
		<INPUT TYPE="HIDDEN" NAME="thread_id" VALUE="<?php echo $thread_id; ?>">
		<INPUT TYPE="HIDDEN" NAME="msg_id" VALUE="<?php echo $is_followup_to; ?>">
		<INPUT TYPE="HIDDEN" NAME="is_followup_to" VALUE="<?php echo $is_followup_to; ?>">
		<TABLE><TR><TD><B>Subject:</TD><TD>
		<INPUT TYPE="TEXT" NAME="subject" VALUE="<?php echo $subject; ?>" SIZE="45" MAXLENGTH="45">
		</TD></TR>
		<TR><TD><B>Message:</TD><TD>
		<TEXTAREA NAME="body" VALUE="" ROWS="10" COLS="60" WRAP="SOFT"></TEXTAREA>
		</TD></TR>
		<TR><TD COLSPAN="2" ALIGN="MIDDLE">
		<B><FONT COLOR="RED">HTML tags will display in your post as text</FONT></B>
		<BR>
		<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Post Comment">
		</TD></TR></TABLE>
		</FORM>
		</CENTER>
		<?php

	} else {
		echo "<CENTER>";
		echo "\n\n<H3><A HREF=\"/account/login.php?return_to=".urlencode($REQUEST_URI).
		"\"><u>Log in first</u></A><FONT COLOR=\"RED\"> to post messages</FONT></H3>";
		echo "</CENTER>";
	}

}

function handle_monitoring($forum_id,$msg_id) {
	global $feedback;
	/*
		Checks to see if anyone is monitoring this forum
		If someone is, it sends them the message in email format
	*/

	$sql="SELECT user.email from forum_monitored_forums,user ".
		"WHERE forum_monitored_forums.user_id=user.user_id AND forum_monitored_forums.forum_id='$forum_id'";

	$result=db_query($sql);
	$rows=db_numrows($result);

	if ($result && $rows > 0) {
		$tolist=implode(result_column_to_array($result),', ');

		$sql="SELECT groups.unix_group_name,user.user_name,forum_group_list.forum_name,".
			"forum.group_forum_id,forum.thread_id,forum.subject,forum.date,forum.body ".
			"FROM forum,user,forum_group_list,groups ".
			"WHERE user.user_id=forum.posted_by ".
			"AND forum_group_list.group_forum_id=forum.group_forum_id ".
			"AND groups.group_id=forum_group_list.group_id ".
			"AND forum.msg_id='$msg_id'";

		$result = db_query ($sql);

		if ($result && db_numrows($result) > 0) {
			$body = "To: noreply@$GLOBALS[HTTP_HOST]".
				"\nBCC: $tolist".
				"\nSubject: [" .db_result($result,0,'unix_group_name'). " - " . db_result($result,0,'forum_name')."] " . 
					util_unconvert_htmlspecialchars(db_result($result,0,'subject')).
				"\n\nRead and respond to this message at: ".
				"\nhttp://$GLOBALS[sys_default_domain]/forum/message.php?msg_id=".$msg_id.
				"\nBy: " . db_result($result,0, 'user_name') .
				"\n\n" . util_unconvert_htmlspecialchars(db_result($result,0, 'body')).
				"\n\n______________________________________________________________________".
				"\nYou are receiving this email because you elected to monitor this forum.".
				"\nTo stop monitoring this forum, login and visit: ".
				"\nhttp://$GLOBALS[sys_default_domain]/forum/monitor.php?forum_id=$forum_id";

			exec ("/bin/echo \"". util_prep_string_for_sendmail($body) ."\" | /usr/sbin/sendmail -fnoreply@$GLOBALS[HTTP_HOST] -t -i &");

			$feedback .= ' email sent - people monitoring ';
		} else {
			$feedback .= ' email not sent - people monitoring ';
			echo db_error();
		}
	} else {
		$feedback .= ' email not sent - no one monitoring ';
		echo db_error();
	}
}

function recursive_delete($msg_id,$forum_id) {
	/*
		Take a message id and recurse, deleting all followups
	*/

	if ($msg_id=='' || $msg_id=='0' || (strlen($msg_id) < 1)) {
		return 0;
	}

	$sql="SELECT msg_id FROM forum WHERE is_followup_to='$msg_id' AND group_forum_id='$forum_id'";
	$result=db_query($sql);
	$rows=db_numrows($result);
	$count=1;

	for ($i=0;$i<$rows;$i++) {
		$count += recursive_delete(db_result($result,$i,'msg_id'),$forum_id);
	}
	$sql="DELETE FROM forum WHERE msg_id='$msg_id' AND group_forum_id='$forum_id'";
	$toss=db_query($sql);

	return $count;
}
?>
