<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');
require('../forum/forum_utils.php');

if (user_isloggedin()) {
	/*
		User obviously has to be logged in to save place 
	*/

	if ($forum_id) {
		/*
			First check to see if they already saved their place 
			If they have NOT, then insert a row into the db

			ELSE update the time()
		*/

		/*
			Set up navigation vars
		*/
		$result=db_query("SELECT group_id,forum_name,is_public FROM forum_group_list WHERE group_forum_id='$forum_id'");

		$group_id=db_result($result,0,'group_id');
		$forum_name=db_result($result,0,'forum_name');

		forum_header(array('title'=>'Save your place'));

		echo '
			<H2>Save Your Place</H2>';

		$sql="SELECT * FROM forum_saved_place WHERE user_id='".user_getid()."' AND forum_id='$forum_id'";

		$result = db_query($sql);

		if (!$result || db_numrows($result) < 1) {
			/*
				User is not already monitoring thread, so 
				insert a row so monitoring can begin
			*/
			$sql="INSERT INTO forum_saved_place (forum_id,user_id,save_date) VALUES ('$forum_id','".user_getid()."','".time()."')";

			$result = db_query($sql);

			if (!$result) {
				echo "<span class=\"highlight\">Error inserting into forum_saved_place</span>";
				echo db_error();
			} else {
				echo "<span class=\"highlight\"><H3>Your place was saved</H3></span>";
				echo "<P>New messages will be highlighted when you return.";
			}

		} else {
			$sql="UPDATE forum_saved_place SET save_date='".time()."' WHERE user_id='".user_getid()."' AND forum_id='$forum_id'";
			$result = db_query($sql);

			if (!$result) {
				echo "<span class=\"highlight\">Error updating time in forum_saved_place</span>";
				echo db_error();
			} else {
				echo "<span class=\"highlight\"><H3>Your place was saved</H3></span>";
				echo "<P>New messages will be highlighted when you return.";
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
