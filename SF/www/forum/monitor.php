<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require('pre.php');
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

		$sql="SELECT * FROM forum_monitored_forums WHERE user_id='".user_getid()."' AND forum_id='$forum_id';";

		$result = db_query($sql);

		if (!$result || db_numrows($result) < 1) {
			/*
				User is not already monitoring thread, so 
				insert a row so monitoring can begin
			*/
			$sql="INSERT INTO forum_monitored_forums (forum_id,user_id) VALUES ('$forum_id','".user_getid()."')";

			$result = db_query($sql);

			if (!$result) {
				echo "<FONT COLOR=\"RED\">Error inserting into forum_monitoring</FONT>";
			} else {
				echo "<FONT COLOR=\"RED\"><H3>Forum is now being monitored</H3></FONT>";
				echo "<P>You will now be emailed followups to this entire forum.";
				echo "<P>To turn off monitoring, simply click the <B>Monitor Forum</B> link again.";
			}

		} else {

			$sql="DELETE FROM forum_monitored_forums WHERE user_id='".user_getid()."' AND forum_id='$forum_id';";
			$result = db_query($sql);
			echo "<FONT COLOR=\"RED\"><H3>Monitoring has been turned off</H3></FONT>";
			echo "<P>You will not receive any more emails from this forum.";
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
