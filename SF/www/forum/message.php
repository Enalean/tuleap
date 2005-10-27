<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');
require('../forum/forum_utils.php');
$Language->loadLanguageMsg('forum/forum');



if ($msg_id) {
 
	/*
		Figure out which group this message is in, for the sake of the admin links
	*/
	$result=db_query("SELECT forum_group_list.group_id,forum_group_list.forum_name,forum.group_forum_id,forum.thread_id ".
		"FROM forum_group_list,forum WHERE forum_group_list.group_forum_id=forum.group_forum_id AND forum.msg_id='$msg_id'");

	$group_id=db_result($result,0,'group_id');
	$forum_id=db_result($result,0,'group_forum_id');
	$thread_id=db_result($result,0,'thread_id');
	$forum_name=db_result($result,0,'forum_name');

        // Check permissions
        if (!forum_utils_access_allowed($forum_id)) {
            exit_error($Language->getText('global','error'),$Language->getText('forum_forum','forum_restricted'));            
        }

	forum_header(array('title'=>db_result($result,0,'subject')));

	echo "<P>";

	$sql="SELECT user.user_name,forum.group_forum_id,forum.thread_id,forum.subject,forum.date,forum.body ".
		"FROM forum,user WHERE user.user_id=forum.posted_by AND forum.msg_id='$msg_id';";

	$result = db_query ($sql);

	if (!$result || db_numrows($result) < 1) {
		/*
			Message not found
		*/
		return 'message not found.\n';
	}

	$title_arr=array();
	$title_arr[]='Message: '.$msg_id;

	echo html_build_list_table_top ($title_arr);

	echo "<TR><TD class=\"threadmsg\">\n";
	echo $Language->getText('forum_message','by').": ".db_result($result,0, "user_name")."<BR>";
	echo $Language->getText('forum_message','date').": ".format_date($sys_datefmt,db_result($result,0, "date"))."<BR>";
	echo $Language->getText('forum_message','subject').": ". db_result($result,0, "subject")."<P>";
	echo util_make_links(nl2br(db_result($result,0, 'body')), $group_id);
	echo "</TD></TR></TABLE>";

	/*
		Show entire thread
	*/
	echo '<BR>&nbsp;<P><H3>'.$Language->getText('forum_message','thread_view').'</H3>';

	//highlight the current message in the thread list
	$current_message=$msg_id;
	echo show_thread(db_result($result,0, 'thread_id'));

	/*
		Show post followup form
	*/

	echo '<P>&nbsp;<P>';
	echo '<CENTER><h3>'.$Language->getText('forum_message','post_followup').'</h3></CENTER>';

	show_post_form(db_result($result, 0, 'group_forum_id'),db_result($result, 0, 'thread_id'), $msg_id, db_result($result,0, 'subject'));

} else {
    exit_error($Language->getText('global','error'),$Language->getText('forum_message','choose_msg_first'));            

}

forum_footer(array()); 

?>
