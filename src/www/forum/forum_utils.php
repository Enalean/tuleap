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

require_once('www/news/news_utils.php');
require_once('common/mail/Mail.class.php');
require_once('common/include/HTTPRequest.class.php');

$GLOBALS['Language']->loadLanguageMsg('forum/forum');

function forum_header($params) {
  global $HTML,$group_id,$forum_name,$thread_id,$msg_id,$forum_id,$REQUEST_URI,$sys_datefmt,$et,$et_cookie,$Language;

	$params['group']=$group_id;
	$params['toptab']='forum';
    $params['help'] = 'WebForums.html';

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
				$group_id = db_result($result,0,'group_id');
				site_project_header($params);
			} else {
                $HTML->header($params);
                echo '
					<H2>'.$GLOBALS['sys_name'].' <A HREF="/news/">'.$Language->getText('forum_forum_utils','news').'</A></H2><P>';
			}


			echo '<TABLE><TR><TD VALIGN="TOP">';
			if (!$result || db_numrows($result) < 1) {
				echo '
					<h3>'.$Language->getText('forum_forum_utils','news_not_found').'</h3>';
			} else {
				echo '
				<B>'.$Language->getText('forum_forum_utils','posted_by').':</B> '.user_getname( db_result($result,0,'submitted_by')).'<BR>
				<B>'.$Language->getText('forum_forum','date').':</B> '. format_date($sys_datefmt,db_result($result,0,'date')).'<BR>
				<B>'.$Language->getText('forum_forum_utils','summary').':</B><A HREF="/forum/forum.php?forum_id='.db_result($result,0,'forum_id').'">'. db_result($result,0,'summary').'</A>
				<P>
				'. util_make_links( nl2br( db_result($result,0,'details')), $group_id);

				echo '<P>';
			}
			echo '</TD><TD VALIGN="TOP" WIDTH="35%">';
			echo $HTML->box1_top($Language->getText('forum_forum_utils','proj_latest_news'),0);
			echo news_show_latest(db_result($result,0,'group_id'),5,false);
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
        echo '<P><H3>'.$Language->getText('forum_forum_utils','discuss_forum').': <A HREF="/forum/forum.php?forum_id='.$forum_id.'">'.$forum_name.'</A></H3>';
    }

    if (!isset($params['pv']) || (isset($params['pv']) && !$params['pv'])) {
        echo '<P><B>';
    
        $request =& HTTPRequest::instance();
        if ($forum_id && user_isloggedin() && !$request->exist('delete')) {
            if (forum_is_monitored($forum_id,user_getid()) )
                $msg = $Language->getText('forum_forum_utils','stop_monitor');
            else 
                $msg = $Language->getText('forum_forum_utils','monitor');
            
            echo '<A HREF="/forum/monitor.php?forum_id='.$forum_id.'">';
            echo html_image("ic/check.png",array()).' '.$msg.'</A> | '.
                        '<A HREF="/forum/save.php?forum_id='.$forum_id.'">';
            echo  html_image("ic/save.png",array()) .' '.$Language->getText('forum_forum_utils','save_place').'</A> | ';
                    print ' <a href="forum.php?forum_id='. $forum_id .'#start_new_thread">';
            echo  html_image("ic/thread.png",array()) .' '.$Language->getText('forum_forum_utils','start_thread').'</A> | ';
            if (isset($msg_id) && $msg_id) {
                echo "<A HREF='".$_SERVER['PHP_SELF']."?msg_id=$msg_id&pv=1'><img src='".util_get_image_theme("msg.png")."' border='0'>&nbsp;".$Language->getText('global','printer_version')."</A> | ";
            } else {
                echo "<A HREF='".$_SERVER['PHP_SELF']."?forum_id=$forum_id&pv=1'><img src='".util_get_image_theme("msg.png")."' border='0'>&nbsp;".$Language->getText('global','printer_version')."</A> | ";
            }
        }
        
        // The forum admin link is only displayed for the forum administrators (and the project administrator of course)
        if (user_ismember($group_id, 'A') || user_ismember($group_id, 'F2')) {
            echo '  <A HREF="/forum/admin/?group_id='.$group_id.'">'.$Language->getText('forum_forum_utils','admin').'</A></B>';
            if (isset($params['help']) && $params['help']) {
                echo ' | ';
            }
        }
        
        if (isset($params['help']) && $params['help']) {
            echo help_button($params['help'],false,$Language->getText('global','help'));
        }
        echo '</B><P>';
    }
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
  global $feedback,$Language;

    if (forum_is_monitored($forum_id, $user_id)) {
	$feedback .= $Language->getText('forum_forum_utils','forum_monitored');
    } else {
	// Not already monitoring so add it.
	$sql="INSERT INTO forum_monitored_forums (forum_id,user_id) VALUES ('$forum_id','".$user_id."')";
	$result = db_query($sql);

	if (!$result) {
	    $feedback .= $Language->getText('forum_forum_utils','insert_err');
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

/**
 * @return forum_id = -1 if error
 */
function forum_create_forum($group_id,$forum_name,$is_public=1,$create_default_message=1,$description='', $need_feedback = true) {
  global $feedback,$Language;
	/*
		Adding forums to this group
	*/
	$sql="INSERT INTO forum_group_list (group_id,forum_name,is_public,description) ".
		"VALUES ('$group_id','". htmlspecialchars($forum_name) ."','$is_public','". htmlspecialchars($description) ."')";

	$result=db_query($sql);
	if (!$result) {
		if ($need_feedback) {
            $feedback .= ' '.$Language->getText('forum_forum_utils','add_err', array($forum_name)).' ';
        }
		return -1;
	} else {
	  if ($need_feedback) {
        $GLOBALS['Response']->addFeedback('info', $Language->getText('forum_forum_utils','forum_added', array($forum_name)));
      }
	
	  $forum_id=db_insertid($result);
	  
	  if ($create_default_message) {
	    //Get the name of the group
	    $group_name = "";
	    $group_obj  = group_get_object($group_id);
	    if ($group_obj && is_object($group_obj)) {
	      $group_name = $group_obj->getPublicName();
	    }
	    
	    //set up a cheap default message
	    $result2=db_query("INSERT INTO forum ".
			      "(group_forum_id,posted_by,subject,body,date,is_followup_to,thread_id) ".
			      "VALUES ('$forum_id','100','".addslashes($Language->getText('forum_forum_utils','welcome_to', array($group_name)))." $forum_name',".
			      "'".addslashes($Language->getText('forum_forum_utils','welcome_to', array($group_name)))." $forum_name','".time()."','0','".get_next_thread_id()."')");
	  }
	  return $forum_id;
	}
}

function make_links ($data="") {
	//moved make links to /include/utils.php
	util_make_links($data);
}

function get_forum_name($id) {
  global $Language;
	/*
		Takes an ID and returns the corresponding forum name
	*/
	$sql="SELECT forum_name FROM forum_group_list WHERE group_forum_id='$id'";
	$result=db_query($sql);
	if (!$result || db_numrows($result) < 1) {
		return $Language->getText('forum_forum_utils','not_found');
	} else {
		return db_result($result, 0, "forum_name");
	}

}

function show_thread($thread_id,$et=0) {
  global $Language;
	/*
		Takes a thread_id and fetches it, then invokes show_submessages to nest the threads

		$et is whether or not the forum is "expanded" or in flat mode
	*/
	global $total_rows,$sys_datefmt,$is_followup_to,$subject,$forum_id,$current_message;
    $ret_val = '';
	$sql="SELECT user.user_name,forum.has_followups,forum.msg_id,forum.subject,forum.thread_id,forum.body,forum.date,forum.is_followup_to ".
		"FROM forum,user WHERE forum.thread_id='$thread_id' AND user.user_id=forum.posted_by AND forum.is_followup_to='0' ".
		"ORDER BY forum.msg_id DESC;";

	$result=db_query($sql);

	$total_rows=0;

	if (!$result || db_numrows($result) < 1) {
		return $Language->getText('forum_forum_utils','broken_thread');
	} else {

		$title_arr=array();
		$title_arr[]=$Language->getText('forum_forum','thread');
		$title_arr[]=$Language->getText('forum_forum','author');
		$title_arr[]=$Language->getText('forum_forum','date');

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
			$ret_val .= '<TR class="'. util_get_alt_row_color($total_rows) .'"><TD>'. 
				(($current_message != db_result($result, $i, 'msg_id'))?'<A HREF="/forum/message.php?msg_id='.db_result($result, $i, 'msg_id').'">':'').
				'<IMG SRC="'.util_get_image_theme("msg.png").'" BORDER=0 HEIGHT=12 WIDTH=10> ';
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
				<TR class="'. util_get_alt_row_color($total_rows) .'"><TD>&nbsp;</TD><TD COLSPAN=2>'.
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
    $ret_val = '';
	if ($result && $rows > 0) {
		for ($i=0; $i<$rows; $i++) {
			/*
				Is this row's background shaded or not?
			*/
			$total_rows++;

			$ret_val .= '
				<TR class="'. util_get_alt_row_color($total_rows) .'"><TD NOWRAP>';
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
				'<IMG SRC="'.util_get_image_theme("msg.png").'" BORDER=0 HEIGHT=12 WIDTH=10> ';
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
					<TR class="'. util_get_alt_row_color($total_rows) .'"><TD>&nbsp;</TD><TD COLSPAN=2>'.
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
  global $Language;
	/*
		Get around limitation in MySQL - Must use a separate table with an auto-increment
	*/
	$result=db_query("INSERT INTO forum_thread_id VALUES ('')");

	if (!$result) {
		echo '<H1>'.$Language->getText('global','error').'!</H1>';
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
  global $feedback,$Language;
	if (user_isloggedin()) {
		if (!$group_forum_id) {
			exit_error($Language->getText('global','error'),$Language->getText('forum_forum_utils','post_without_id'));
		}
		if (!$body || !$subject) {
			exit_error($Language->getText('global','error'),$Language->getText('forum_forum_utils','include_body_and_subject'));
		}

	//see if that message has been posted already for all the idiots that double-post
		$res3=db_query("SELECT * FROM forum ".
			"WHERE is_followup_to='$is_followup_to' ".
			"AND subject='".  htmlspecialchars($subject) ."' ".
			"AND group_forum_id='$group_forum_id' ".
            "AND body='$body' ".
			"AND posted_by='". user_getid() ."'");

		if (db_numrows($res3) > 0) {
			//already posted this message
			exit_error($Language->getText('global','error'),$Language->getText('forum_forum_utils','do_not_double_post'));
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
					if (db_result($res2,0,'has_followups') > 0) {
						//parent already is marked with followups
					} else {
						//mark the parent with followups as an optimization later
						db_query("UPDATE forum SET has_followups='1' WHERE msg_id='$is_followup_to' AND thread_id='$thread_id' AND group_forum_id='$group_forum_id'");
					}
				} else {
					exit_error($Language->getText('global','error'),$Language->getText('forum_forum_utils','msg_not_exist'));
				}
			} else {
				//should never happen except with shoddy browsers or mucking with the HTML form
				exit_error($Language->getText('global','error'),$Language->getText('forum_forum_utils','no_folowup_id'));
			}
		}

		$sql="INSERT INTO forum (group_forum_id,posted_by,subject,body,date,is_followup_to,thread_id) ".
			"VALUES ('$group_forum_id', '".user_getid()."', '".htmlspecialchars($subject)."', '".htmlspecialchars($body)."', '".time()."','$is_followup_to','$thread_id')";

		$result=db_query($sql);

		if (!$result) {
			echo $Language->getText('forum_forum_utils','insert_fail');
			echo db_error();
			$feedback .= ' '.$Language->getText('forum_forum_utils','post_failed').' ';
		} else {
			$feedback .= ' '.$Language->getText('forum_forum_utils','msg_posted').' ';
		}

		$msg_id=db_insertid($result);
		handle_monitoring($group_forum_id,$msg_id);

	} else {

		echo '
			<H3>'.$Language->getText('forum_forum_utils','could_post_if_logged').'</H3>';

	}

}

function show_post_form($forum_id, $thread_id=0, $is_followup_to=0, $subject="") {
  global $REQUEST_URI,$Language;

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
        <TABLE>
          <TR>
            <TD>		
		<INPUT TYPE="HIDDEN" NAME="post_message" VALUE="y">
		<INPUT TYPE="HIDDEN" NAME="forum_id" VALUE="<?php echo $forum_id; ?>">
		<INPUT TYPE="HIDDEN" NAME="thread_id" VALUE="<?php echo $thread_id; ?>">
		<INPUT TYPE="HIDDEN" NAME="msg_id" VALUE="<?php echo $is_followup_to; ?>">
		<INPUT TYPE="HIDDEN" NAME="is_followup_to" VALUE="<?php echo $is_followup_to; ?>">
		<B><?php echo $Language->getText('forum_forum_utils','subj'); ?>:
            </TD><TD>
		<INPUT TYPE="TEXT" NAME="subject" VALUE="<?php echo $subject; ?>" CLASS="textfield_medium">
          </TD></TR>
	  <TR><TD><B><?php echo $Language->getText('forum_forum_utils','msg'); ?>:
            </TD><TD>
		<TEXTAREA NAME="body" VALUE="" ROWS="10" COLS="60" WRAP="SOFT"></TEXTAREA>
	  </TD></TR>
	  <TR><TD COLSPAN="2" ALIGN="center">
		<B><span class="highlight"><?php echo $Language->getText('forum_forum_utils','html_displays_as_text'); ?></span></B>
	  </TR>
          <TR><td>&nbsp;</td><TD ALIGN="left">
<?php
if(forum_is_monitored($forum_id,user_getid())){
    print '<EM>'.$Language->getText('forum_forum_utils','on_post_monitoring').'</EM>';
}
else {
    print $Language->getText('forum_forum_utils','on_post_not_monitoring', array('<INPUT TYPE="checkbox" name="enable_monitoring" value="1">'));
}
?>               

	  </TR>
	  <TR><TD COLSPAN="2" ALIGN="center">
		<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="<?php echo $Language->getText('forum_forum_utils','post_comment'); ?>">
             </TD>
             <TD VALIGN="top">              
             </TD>
          </TR>
	</TABLE>
        </FORM>
		<?php

	} else {
		echo "<CENTER>";
		echo "\n\n<H3>".$Language->getText('forum_forum_utils','log_to_post',"/account/login.php?return_to=".urlencode($REQUEST_URI)).'</H3>';
		echo "</CENTER>";
	}

}

function handle_monitoring($forum_id,$msg_id) {
    global $feedback,$sys_lf,$Language;
	/*
		Checks to see if anyone is monitoring this forum
		If someone is, it sends them the message in email format
	*/

	$res=news_read_permissions($forum_id);
	if ((db_numrows($res) < 1)) {
	    $sql="SELECT user.email from forum_monitored_forums,user ".
		"WHERE forum_monitored_forums.user_id=user.user_id AND forum_monitored_forums.forum_id='$forum_id' AND ( user.status='A' OR user.status='R' )";
	} else {
	    //we are dealing with private news, only project members are allowed to monitor
	    $qry1 = "SELECT group_id FROM news_bytes WHERE forum_id='$forum_id'";
	    $res1 = db_query($qry1);
	    $gr_id = db_result($res1,0,'group_id');
	    $sql = "SELECT user.email from forum_monitored_forums,user_group,user". 
		    " WHERE forum_monitored_forums.forum_id='$forum_id' AND user_group.group_id='$gr_id'".
		    " AND forum_monitored_forums.user_id=user_group.user_id AND user_group.user_id=user.user_id";		    		    
	}
		
	$result=db_query($sql);
	$rows=db_numrows($result);

	if ($result && $rows > 0) {
		$tolist=implode(result_column_to_array($result),', ');
		//echo $tolist;
		$sql="SELECT groups.unix_group_name,user.user_name,user.realname,forum_group_list.forum_name,".
			"forum.group_forum_id,forum.thread_id,forum.subject,forum.date,forum.body ".
			"FROM forum,user,forum_group_list,groups ".
			"WHERE user.user_id=forum.posted_by ".
			"AND forum_group_list.group_forum_id=forum.group_forum_id ".
			"AND groups.group_id=forum_group_list.group_id ".
			"AND forum.msg_id='$msg_id'";

		$result = db_query ($sql);

		if ($result && db_numrows($result) > 0) {
            list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);
            $mail =& new Mail();
            $mail->setFrom($GLOBALS['sys_noreply']);
            $mail->setSubject("[" . db_result($result,0,'unix_group_name'). " - " . db_result($result,0,'forum_name')." - ". db_result($result,0, 'user_name') ."] " . util_unconvert_htmlspecialchars(db_result($result,0,'subject')));
            $mail->setBcc($tolist);
            
		    
		    $body = $Language->getText('forum_forum_utils','read_and_respond').": ".
			"\n".get_server_url()."/forum/message.php?msg_id=".$msg_id.
		      "\n".$Language->getText('global','by').' '. db_result($result,0, 'user_name') .' ('.db_result($result,0, 'realname').')' .
			"\n\n" . util_unconvert_htmlspecialchars(db_result($result,0, 'body')).
			"\n\n______________________________________________________________________".
			"\n".$Language->getText('forum_forum_utils','stop_monitor_explain').": ".
			"\n".get_server_url()."/forum/monitor.php?forum_id=$forum_id";
            $mail->setBody($body);
            
			if ($mail->send()) {
                $feedback .= ' - '.$Language->getText('forum_forum_utils','mail_sent');		
            } else {//ERROR
                $feedback .= ' - '.$GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin'])); 
            }

			$feedback .= ' - '.$Language->getText('forum_forum_utils','people_monitoring').' ';
		} else {
			$feedback .= ' '.$Language->getText('forum_forum_utils','mail_not_sent').' - '.$Language->getText('forum_forum_utils','people_monitoring').' ';
			echo db_error();
		}
	} else {
		$feedback .= ' '.$Language->getText('forum_forum_utils','mail_not_sent').' - '.$Language->getText('forum_forum_utils','no_one_monitoring').' ';
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

function forum_utils_access_allowed($forum_id) {

    $result=db_query("SELECT group_id,is_public FROM forum_group_list WHERE group_forum_id='$forum_id'");

    if (db_result($result,0,'is_public') != '1') {
        $forum_group_id=db_result($result,0,'group_id');
        if (!user_isloggedin() || !user_ismember($forum_group_id)) {
            // If this is a private forum, kick 'em out
            return false;
        }
    }
    return true;
}

function forum_utils_news_access($forum_id) {
    /*
	Takes a forum_id (associated to a news) and checks if the user is allowed to access the corresponding forum   	 
         */
    
    //cast input
    $_forum_id = (int) $forum_id;
    
    $qry1 = "SELECT group_id FROM news_bytes WHERE forum_id='$_forum_id'";
    $res1 = db_query($qry1);
    
    if ($res1 && db_numrows($res1) > 0) {
    
        //if the forum is accessed from Summary page (Latest News section), the group_id variable is not set 
	$g_id = db_result($res1,0,'group_id');    
        
	return permission_is_authorized('NEWS_READ',$_forum_id, user_getid(), $g_id);
    }
    
    return true;
}

?>
