<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require ('pre.php');
require ('../forum/forum_utils.php');

if ($thread_id) {

	if ($post_message == "y") {
		post_message($thread_id, $is_followup_to, $subject, $body, $forum_id);
	}

	/*
		Set up navigation vars
	*/
	$result=db_query("SELECT forum_group_list.group_id,forum_group_list.forum_name,forum.group_forum_id ".
		"FROM forum_group_list,forum WHERE forum_group_list.group_forum_id=forum.group_forum_id AND forum.thread_id='$thread_id'");

	$group_id=db_result($result,0,'group_id');
	$forum_id=db_result($result,0,'group_forum_id');
	$forum_name=db_result($result,0,'forum_name');

	forum_header(array('title'=>'View Thread'));

	echo show_thread($thread_id,$et);

	echo '<P>&nbsp;<P>';
	echo '<CENTER><h3>Post to this thread</H3></CENTER>';
	show_post_form($forum_id,$thread_id,$is_followup_to,$subject);

	forum_footer(array());

} else {

	forum_header(array('title'=>'Choose Thread'));
	echo '<H2>Sorry, Choose A Thread First</H2>';
	forum_footer(array());

}

?>
