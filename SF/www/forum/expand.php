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

	if ($et != user_get_preference('forum_expand'))
	    user_set_preference('forum_expand',$et);

	/*
		Set up navigation vars
	*/
	$result=db_query("SELECT group_id,forum_name,is_public FROM forum_group_list WHERE group_forum_id='$forum_id'");

	$group_id=db_result($result,0,'group_id');
	$forum_name=db_result($result,0,'forum_name');

	forum_header(array('title'=>'Expand/Collapse Threads'));

	echo '
		<H1>Preference Set</H!>';

	if ($et==1) {
		echo '<P>Threads will now be expanded';
	} else {
		echo '<P>Threads will now be collapsed';
	}

	forum_footer(array());

} else {
	exit_not_logged_in();
}

?>
