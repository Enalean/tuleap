<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

/*

	Message Forums
	By Tim Perdue, Sourceforge, 11/99

	Massive rewrite by Tim Perdue 7/2000 (nested/views/save)

*/

require_once('www/news/news_utils.php');
require_once('common/mail/Mail.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('common/user/UserHelper.class.php');
require_once('common/reference/ReferenceManager.class.php');

function forum_header($params) {
    global $HTML,$group_id,$forum_name,$thread_id,$msg_id,$forum_id,$et,$et_cookie,$Language;
    $hp = Codendi_HTMLPurifier::instance(); 
    $uh = new UserHelper();
  
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
			$sql="SELECT * FROM news_bytes WHERE forum_id=".db_ei($forum_id);
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
				<B>'.$Language->getText('forum_forum_utils','posted_by').':</B> '.
                $hp->purify($uh->getDisplayNameFromUserId(db_result($result,0,'submitted_by')), CODENDI_PURIFIER_CONVERT_HTML) .
				'<BR>
				<B>'.$Language->getText('forum_forum','date').':</B> '. format_date($GLOBALS['Language']->getText('system', 'datefmt'),db_result($result,0,'date')).'<BR>
				<B>'.$Language->getText('forum_forum_utils','summary').':</B><A HREF="/forum/forum.php?forum_id='.db_result($result,0,'forum_id').'">'. db_result($result,0,'summary').'</A>
				<P>
				'. util_make_links( nl2br( db_result($result,0,'details')), $group_id);

				echo '<P>';
				
                $crossref_fact= new CrossReferenceFactory($forum_id, ReferenceManager::REFERENCE_NATURE_NEWS, $group_id);
                $crossref_fact->fetchDatas();
                if ($crossref_fact->getNbReferences() > 0) {
                    echo '<b> '.$Language->getText('cross_ref_fact_include','references').'</b>';
                    $crossref_fact->DisplayCrossRefs();
                }
				
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
            if (user_monitor_forum($forum_id,user_getid()) ) {
                $msg = $Language->getText('forum_forum_utils','stop_monitor');
            } else {
                $msg = $Language->getText('forum_forum_utils','monitor');
            }
	        echo '<A HREF="/forum/monitor.php?forum_id='.$forum_id.'">';
            echo html_image("ic/monitor_forum.png",array()).' '.$msg.'</A> | ';
				
			echo '<A HREF="/forum/monitor_thread.php?forum_id='.$forum_id.'"> '.html_image("ic/monitor_thread.png",array()).$Language->getText('forum_forum_utils','monitor_thread').'</A> | ';
			        
			echo '<A HREF="/forum/save.php?forum_id='.$forum_id.'">';
	    	echo  html_image("ic/save.png",array()) .' '.$Language->getText('forum_forum_utils','save_place').'</A> | ';
        	print ' <a href="forum.php?forum_id='. $forum_id .'#start_new_thread">';
	    	echo  html_image("ic/thread.png",array()) .' '.$Language->getText('forum_forum_utils','start_thread').'</A> | ';
        	if (isset($msg_id) && $msg_id) {
            	echo "<A HREF='".$_SERVER['PHP_SELF']."?msg_id=$msg_id&pv=1'><img src='".util_get_image_theme("msg.png")."' border='0'>&nbsp;".$Language->getText('global','printer_version')."</A> | ";
	    	} else {
            	echo "<A HREF='".$_SERVER['PHP_SELF']."?forum_id=$forum_id&pv=1'><img src='".util_get_image_theme("msg.png")."' border='0'>&nbsp;".$Language->getText('global','printer_version')."</A> | ";
            }
	    }

        // The forum admin link is only displayed for the forum moderators
        if (user_ismember($group_id, 'F2')) {
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

function user_monitor_forum ($forum_id, $user_id) {
    
    $sql = sprintf('SELECT NULL'.
    				' FROM forum_monitored_forums'.
    				' WHERE user_id = %d'.
    				' AND forum_id = %d',
    				db_ei($user_id),db_ei($forum_id));    
    $result = db_query($sql);
    return ($result && db_numrows($result) >= 1);
}

function forum_is_monitored($forum_id) {
	$sql = sprintf('SELECT NULL'.
					' FROM forum_monitored_forums'.
					' WHERE forum_id = %d',
					db_ei($forum_id));
	$res = db_query($sql);
	return ($res && db_numrows($res) >= 1); 				
}

function forum_add_monitor ($forum_id, $user_id) {
  global $feedback,$Language;

    if (user_monitor_forum($forum_id, $user_id)) {
		$feedback .= $Language->getText('forum_forum_utils','forum_monitored');
    } else {
		// Not already monitoring so add it.		 
		$sql="INSERT INTO forum_monitored_forums (forum_id,user_id) VALUES (".db_ei($forum_id).",".db_ei($user_id).")";
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
    $sql="DELETE FROM forum_monitored_forums WHERE user_id=".db_ei($user_id)." AND forum_id=".db_ei($forum_id);
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
		"VALUES (".db_ei($group_id).",'".db_es(htmlspecialchars($forum_name))."',".db_ei($is_public).",'".db_es(htmlspecialchars($description))."')";

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
	    $pm = ProjectManager::instance();
        $group_obj  = $pm->getProject($group_id);
	    if ($group_obj && is_object($group_obj)) {
	      $group_name = $group_obj->getPublicName();
	    }
	    
	    //set up a cheap default message
	    $result2=db_query("INSERT INTO forum ".
			      "(group_forum_id,posted_by,subject,body,date,is_followup_to,thread_id) ".
			      "VALUES (".db_ei($forum_id).",100,'".db_es($Language->getText('forum_forum_utils','welcome_to', array($group_name))." ".htmlspecialchars($forum_name))."',".
			      "'".db_es($Language->getText('forum_forum_utils','welcome_to', array($group_name))." ".htmlspecialchars($forum_name))."','".time()."',0,'".get_next_thread_id()."')");
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
	$sql="SELECT forum_name FROM forum_group_list WHERE group_forum_id=".db_ei($id);
	$result=db_query($sql);
	if (!$result || db_numrows($result) < 1) {
		return $Language->getText('forum_forum_utils','not_found');
	} else {
		return db_result($result, 0, "forum_name");
	}

}

function get_forum_group_id($id) {
	/* 
		Takes an ID and returns the corresponding forum group_id
	*/
	$sql="SELECT group_id FROM forum_group_list WHERE group_forum_id=".db_ei($id);
	$result=db_query($sql);
	if (!$result || db_numrows($result) < 1) {
		return null;
	} else {
		return db_result($result, 0, "group_id");
	}

}

function show_thread($thread_id,$et=0) {
  global $Language;
	/*
		Takes a thread_id and fetches it, then invokes show_submessages to nest the threads

		$et is whether or not the forum is "expanded" or in flat mode
	*/
	global $total_rows,$is_followup_to,$subject,$forum_id,$current_message;
	
    $ret_val = '';
	$sql="SELECT user.user_name,forum.has_followups,forum.msg_id,forum.subject,forum.thread_id,forum.body,forum.date,forum.is_followup_to ".
		"FROM forum,user WHERE forum.thread_id='".db_ei($thread_id)."' AND user.user_id=forum.posted_by AND forum.is_followup_to='0' ".
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

			$poster = UserManager::instance()->getUserByUserName(db_result($result, $i, 'user_name'));
			$ret_val .= db_result($result, $i, 'subject') .'</A></TD>'.
				'<TD>'.UserHelper::instance()->getLinkOnUser($poster).'</TD>'.
				'<TD>'.format_date($GLOBALS['Language']->getText('system', 'datefmt'),db_result($result,$i,'date')).'</TD></TR>';
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
	global $total_rows,$forum_id,$current_message;

	$sql="SELECT user.user_name,forum.has_followups,forum.msg_id,forum.subject,forum.thread_id,forum.body,forum.date,forum.is_followup_to ".
		"FROM forum,user WHERE forum.thread_id=".db_ei($thread_id)." AND user.user_id=forum.posted_by AND forum.is_followup_to=".db_ei($msg_id)." ".
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

			$poster = UserManager::instance()->getUserByUserName(db_result($result, $i, 'user_name'));
			$ret_val .= db_result($result, $i, 'subject').'</A></TD>'.
				'<TD>'.UserHelper::instance()->getLinkOnUser($poster).'</TD>'.
				'<TD>'.format_date($GLOBALS['Language']->getText('system', 'datefmt'),db_result($result,$i,'date')).'</TD></TR>';

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
		$sql="SELECT save_date FROM forum_saved_place WHERE user_id='".user_getid()."' AND forum_id=".db_ei($forum_id);
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
		
		$request =& HTTPRequest::instance();
		if (!$group_forum_id) {
			exit_error($Language->getText('global','error'),$Language->getText('forum_forum_utils','post_without_id'));
		}
		if (!$body || !$subject) {
			exit_error($Language->getText('global','error'),$Language->getText('forum_forum_utils','include_body_and_subject'));
		}

	//see if that message has been posted already for people that double-post
		$res3=db_query("SELECT * FROM forum ".
			"WHERE is_followup_to=".db_ei($is_followup_to)." ".
			"AND subject='".  db_es(htmlspecialchars($subject)) ."' ".
			"AND group_forum_id=".db_ei($group_forum_id)." ".
            "AND body='".db_es($body)."' ".
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
				$res2=db_query("SELECT * FROM forum WHERE msg_id=".db_ei($is_followup_to)." AND thread_id=".db_ei($thread_id)." AND group_forum_id=".db_ei($group_forum_id));
				if (db_numrows($res2) > 0) {
					if (db_result($res2,0,'has_followups') > 0) {
						//parent already is marked with followups
					} else {
						//mark the parent with followups as an optimization later
						db_query("UPDATE forum SET has_followups='1' WHERE msg_id=".db_ei($is_followup_to)." AND thread_id=".db_ei($thread_id)." AND group_forum_id=".db_ei($group_forum_id));
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
			"VALUES (".db_ei($group_forum_id).", '".user_getid()."', '".db_es(htmlspecialchars($subject))."', '".db_es(htmlspecialchars($body))."', '".time()."',".db_ei($is_followup_to).",".db_ei($thread_id).")";

		$result=db_query($sql);

		if (!$result) {
			echo $Language->getText('forum_forum_utils','insert_fail');
			echo db_error();
			$feedback .= ' '.$Language->getText('forum_forum_utils','post_failed').' ';
		} else {
            $feedback .= ' '.$Language->getText('forum_forum_utils','msg_posted').' ';
		}

		$msg_id=db_insertid($result);
		
        // extract cross reference in the message
        $reference_manager =& ReferenceManager::instance();
        $g_id = get_forum_group_id($group_forum_id);
        $GLOBALS['group_id'] = $g_id;   // don't know why group_id is not set in forum (needed for references)
        $reference_manager->extractCrossRef($subject,$msg_id,ReferenceManager::REFERENCE_NATURE_FORUMMESSAGE, $g_id);
        $reference_manager->extractCrossRef($body,$msg_id,ReferenceManager::REFERENCE_NATURE_FORUMMESSAGE, $g_id);
        
		if ($request->isPost() && $request->existAndNonEmpty('enable_monitoring')) {
		    forum_thread_add_monitor($group_forum_id, $thread_id, user_getid());
		} else {
		    forum_thread_delete_monitor_by_user($group_forum_id, $msg_id, user_getid());
		}
		handle_monitoring($group_forum_id,$thread_id,$msg_id);

	} else {

		echo '
			<H3>'.$Language->getText('forum_forum_utils','could_post_if_logged').'</H3>';

	}

}

function show_post_form($forum_id, $thread_id=0, $is_followup_to=0, $subject="") {
  global $Language;

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
		<TEXTAREA NAME="body" VALUE="" ROWS="10" COLS="80" WRAP="SOFT"></TEXTAREA>
	  </TD></TR>
	  <TR><TD COLSPAN="2" ALIGN="center">
		<B><span class="highlight"><?php echo $Language->getText('forum_forum_utils','html_displays_as_text'); ?></span></B>
	  </TR>
	  <?php 	  
	      if (user_monitor_forum($forum_id,user_getid())) {
	          $disabled = "disabled";
		      $checked = "checked";
	      } else {
	      	  $disabled = "";
	      	  if ($thread_id == 0) {
	      		  $checked = "checked";
	      	  } else {	      	
		          if (user_monitor_forum_thread($thread_id, user_getid())) {
		              $checked = "checked";
		          } else {
		              $checked = "";
		          }
	      	  }
	      }
	      echo '
	           <TR><TD align="right"><INPUT TYPE="checkbox" NAME="enable_monitoring" VALUE="1" '.$disabled.' '.$checked.'></TD>
	           <TD> '.$GLOBALS['Language']->getText('forum_forum_utils','monitor_this_thread').'</TD>
	           </TR>'; 
	 ?>
          <TR><td>&nbsp;</td><TD ALIGN="left"> </TR>
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
		echo "\n\n<H3>".$Language->getText('forum_forum_utils','log_to_post',"/account/login.php?return_to=".urlencode($_SERVER['REQUEST_URI'])).'</H3>';
		echo "</CENTER>";
	}

}

function handle_monitoring($forum_id,$thread_id,$msg_id) {
    global $feedback,$sys_lf,$Language;
	/*
		Checks to see if anyone is monitoring this forum
		If someone is, it sends them the message in email format
	*/

	$res=news_read_permissions($forum_id);
	if ((db_numrows($res) < 1)) {	    
	    //check if there are users monitoring specific threads
	    $sql = sprintf('(SELECT user.email FROM forum_monitored_forums,user'
			    .' WHERE forum_monitored_forums.user_id=user.user_id'
			    .' AND forum_monitored_forums.forum_id=%d'
			    .' AND ( user.status="A" OR user.status="R" ))'
			    .' UNION (SELECT user.email FROM forum_monitored_threads,user'
			    .' WHERE forum_monitored_threads.user_id=user.user_id'
			    .' AND forum_monitored_threads.forum_id=%d'
			    .' AND forum_monitored_threads.thread_id=%d'
			    .' AND ( user.status="A" OR user.status="R" ))',
			    db_ei($forum_id),db_ei($forum_id),db_ei($thread_id));
	} else {
	    //we are dealing with private news, only project members are allowed to monitor
	    $qry1 = "SELECT group_id FROM news_bytes WHERE forum_id=".db_ei($forum_id);
	    $res1 = db_query($qry1);
	    $gr_id = db_result($res1,0,'group_id');
	    $sql = "SELECT user.email from forum_monitored_forums,user_group,user". 
		    " WHERE forum_monitored_forums.forum_id=".db_ei($forum_id)." AND user_group.group_id=".db_ei($gr_id).
		    " AND forum_monitored_forums.user_id=user_group.user_id AND user_group.user_id=user.user_id";		    		    
	}
		
	$result=db_query($sql);
	$rows=db_numrows($result);

	if ($result && $rows > 0) {
		$tolist=implode(result_column_to_array($result),', ');

		$sql="SELECT groups.unix_group_name,user.user_name,user.realname,forum_group_list.forum_name,".
			"forum.group_forum_id,forum.thread_id,forum.subject,forum.date,forum.body ".
			"FROM forum,user,forum_group_list,groups ".
			"WHERE user.user_id=forum.posted_by ".
			"AND forum_group_list.group_forum_id=forum.group_forum_id ".
			"AND groups.group_id=forum_group_list.group_id ".
			"AND forum.msg_id=".db_ei($msg_id);

		$result = db_query ($sql);

		if ($result && db_numrows($result) > 0) {
            list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);
            $mail = new Mail();
            $mail->setFrom($GLOBALS['sys_noreply']);
            $mail->setSubject("[" . db_result($result,0,'unix_group_name'). " - " . util_unconvert_htmlspecialchars(db_result($result,0,'forum_name'))." - ". db_result($result,0, 'user_name') ."] " . util_unconvert_htmlspecialchars(db_result($result,0,'subject')));
            $mail->setBcc($tolist);
            
	        $url1 = get_server_url()."/forum/monitor.php?forum_id=".$forum_id;
	        $url2 = get_server_url()."/forum/monitor_thread.php?forum_id=".$forum_id;
	        $body = $Language->getText('forum_forum_utils','read_and_respond').": ".
			    "\n".get_server_url()."/forum/message.php?msg_id=".$msg_id.
		        "\n".$Language->getText('global','by').' '. db_result($result,0, 'user_name') .' ('.db_result($result,0, 'realname').')' .
			    "\n\n" . util_unconvert_htmlspecialchars(db_result($result,0, 'body')).
			    "\n\n______________________________________________________________________".
			    "\n".$Language->getText('forum_forum_utils','stop_monitor_explain',array($url1,$url2));
                $mail->setBody($body);
            
			if ($mail->send()) {
                $feedback .= ' - '.$Language->getText('forum_forum_utils','mail_sent');		
            } else {//ERROR
                $feedback .= ' - '.$GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin'])); 
            }
		
	        if (forum_is_monitored($forum_id) || forum_thread_is_monitored($thread_id)) {
	        	$feedback .= ' - '.$Language->getText('forum_forum_utils','people_monitoring');	        	
	        }
	        
	    } else {
		    $feedback .= ' '.$Language->getText('forum_forum_utils','mail_not_sent').' ';
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

	$sql="SELECT msg_id FROM forum WHERE is_followup_to=".db_ei($msg_id)." AND group_forum_id=".db_ei($forum_id);
	$result=db_query($sql);
	$rows=db_numrows($result);
	$count=1;

	for ($i=0;$i<$rows;$i++) {
		$count += recursive_delete(db_result($result,$i,'msg_id'),$forum_id);
	}
	$sql="DELETE FROM forum WHERE msg_id=".db_ei($msg_id)." AND group_forum_id=".db_ei($forum_id);
	$toss=db_query($sql);

	return $count;
}

function forum_utils_access_allowed($forum_id) {

    $result=db_query("SELECT group_id,is_public FROM forum_group_list WHERE group_forum_id=".db_ei($forum_id));

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
    

    $qry1 = "SELECT group_id FROM news_bytes WHERE forum_id=".db_ei($forum_id);
    $res1 = db_query($qry1);
    
    if ($res1 && db_numrows($res1) > 0) {
    
        //if the forum is accessed from Summary page (Latest News section), the group_id variable is not set 
	$g_id = db_result($res1,0,'group_id');    
        
	return permission_is_authorized('NEWS_READ',intval($forum_id), user_getid(), $g_id);
    }
    
    return true;
}

function forum_utils_get_styles() {
    return array('nested','flat','threaded','nocomments');
}

function forum_thread_monitor($mthread, $user_id, $forum_id) {
    /*
	Set user-specific thread monitoring settings	
         */
	 
    if ($mthread == NULL) {
	//no thread is monitored    
        $del = sprintf('DELETE FROM forum_monitored_threads'
			.' WHERE user_id=%d'
			.' AND forum_id=%d',
			db_ei($user_id),db_ei($forum_id));	
	$res = db_query($del);
    } else {
        $sql = sprintf('SELECT user.user_name,user.realname,forum.has_followups,user.user_id,forum.msg_id,forum.group_forum_id,forum.subject,forum.thread_id,forum.body,forum.date,forum.is_followup_to,forum_group_list.group_id'
		        .' FROM forum,user,forum_group_list'
			.' WHERE forum.group_forum_id=%d'
			.' AND user.user_id=forum.posted_by'
			.' AND forum.is_followup_to=0'
			.' AND forum_group_list.group_forum_id = forum.group_forum_id'
			.' ORDER BY forum.date DESC',
			db_ei($forum_id));
        $result=db_query($sql);
        while ($rows = db_fetch_array($result)) {
            $thread_id = $rows['thread_id'];
	    if (in_array($thread_id,$mthread)) {
	        if (! user_monitor_forum_thread($thread_id, $user_id)) {
	            $qry1 = sprintf('INSERT INTO forum_monitored_threads'
				    .' (forum_id, thread_id, user_id)'
				    .' VALUES (%d,%d,%d)',
				    db_ei($forum_id),db_ei($thread_id),db_ei($user_id));
		    $res1 = db_query($qry1);
	        }
	    } else {
	        if (user_monitor_forum_thread($thread_id, $user_id)) {
		    $qry2 = sprintf('DELETE FROM forum_monitored_threads'
				    .' WHERE forum_id=%d'
				    .' AND thread_id=%d'
				    .' AND user_id=%d',
				    db_ei($forum_id),db_ei($thread_id),db_ei($user_id));
	            $res2 = db_query($qry2);  
	        }
	    }	
        }
    }
    
    return true;

}

function user_monitor_forum_thread($thread_id, $user_id) {
    /*
	    Check if thread (thread_id) is monitored by user (user_id)
         */
	 
    $sql = sprintf('SELECT NULL FROM forum_monitored_threads'
		    .' WHERE user_id = %d'
		    .' AND thread_id = %d',
		    db_ei($user_id),db_ei($thread_id));
    $result = db_query($sql);
    return ($result && db_numrows($result) >= 1);

}

function forum_thread_is_monitored($thread_id) {
	
	$sql = sprintf('SELECT NULL'.
					' FROM forum_monitored_threads'.
					' WHERE thread_id = %d',
					db_ei($thread_id));
	$res = db_query($sql);
	return ($res && db_numrows($res) >= 1);				
}

function forum_thread_add_monitor($forum_id, $thread_id, $user_id) {
    /*
	    Add thread monitor settings for user (user_id)
         */
    
    if (! user_monitor_forum_thread($thread_id,$user_id)) {
        $sql = sprintf('INSERT INTO forum_monitored_threads'
			.' (forum_id, thread_id, user_id)'
			.' VALUES (%d,%d,%d)',
			db_ei($forum_id),db_ei($thread_id),db_ei($user_id));
        $res = db_query($sql);
        if (! $res) {
    		return false; 
    	}
    }
    return true;    
	    
}

 function forum_thread_delete_monitor_by_user($forum_id,$msg_id, $user_id) {
    /*
	    Delete thread monitor settings for user (user_id)
         */
    
    $sql = sprintf('SELECT * FROM forum'
		    .' WHERE group_forum_id=%d'
		    .' AND msg_id=%d',
		    db_ei($forum_id),db_ei($msg_id));
    $res = db_query($sql);    
    $thread_id = db_result($res,0,'thread_id');
    $qry = sprintf('DELETE FROM forum_monitored_threads'
           .' WHERE forum_id=%d'
           .' AND thread_id=%d'
           .' AND user_id=%d',
           db_ei($forum_id),db_ei($thread_id), db_ei($user_id));
    $result= db_query($qry);
    return true;
}

     function forum_thread_delete_monitor($forum_id,$msg_id) {
              /*
                Delete a thread monitor settings.
              */
              $sql = sprintf('SELECT * FROM forum'
                              .' WHERE group_forum_id=%d'
                              .' AND msg_id=%d',
                              db_ei($forum_id),db_ei($msg_id));
              $res = db_query($sql);
              $thread_id = db_result($res,0,'thread_id');
              $qry = sprintf('DELETE FROM forum_monitored_threads'
                     .' WHERE forum_id=%d'
                     .' AND thread_id=%d',
                     db_ei($forum_id),db_ei($thread_id));
              $result= db_query($qry);
              return true;
          }
          

?>
