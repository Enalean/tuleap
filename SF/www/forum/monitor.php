<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');
require('../forum/forum_utils.php');

if (user_isloggedin()) {
	/*
		User obviously has to be logged in to monitor
		a thread
	*/

	if ($forum_id) {
		/*
			First check to see if they are already monitoring
			this thread. If they are, say so and quit.
			If they are NOT, then insert a row into the db
		*/

		/*
			Set up navigation vars
		*/
		$result=db_query("SELECT group_id,forum_name,is_public FROM forum_group_list WHERE group_forum_id='$forum_id'");

		$group_id=db_result($result,0,'group_id');
		$forum_name=db_result($result,0,'forum_name');

		forum_header(array('title'=>'Monitor a forum'));

		echo '
			<H2>Monitor a Forum</H2>';

		if (forum_is_monitored($forum_id, user_getid())) {

		    // If already monitored then stop monitoring
		    forum_delete_monitor ($forum_id, user_getid());
		    echo "<span class=\"highlight\"><H3>Monitoring has been turned off</H3></span>";
		    echo "<P>You will not receive any more emails from this forum.";
		} else {
		    // Not yet monitored so add it
		    if (forum_add_monitor ($forum_id, user_getid()) ) {
			echo "<span class=\"highlight\"><H3>Forum is now being monitored</H3></span>";
			echo "<P>You will now be emailed followups to this entire forum.";
			echo "<P>To turn off monitoring, simply click the <B>Monitor Forum</B> link again.";	
		    } else {
			echo "<span class=\"highlight\">Error inserting into forum_monitoring</span>";
		    }
		}
		forum_footer(array());

	} else {
		forum_header(array('title'=>'Choose a forum First'));
		echo '
			<H1>Error - Choose a forum First</H1>';
		forum_footer(array());
	} 

} else {
	exit_not_logged_in();
}
?>
