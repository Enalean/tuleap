<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');
require('../forum/forum_utils.php');
$Language->loadLanguageMsg('forum/forum');

if (user_isloggedin()) {

	if ($et != user_get_preference('forum_expand'))
	    user_set_preference('forum_expand',$et);

	/*
		Set up navigation vars
	*/
	$result=db_query("SELECT group_id,forum_name,is_public FROM forum_group_list WHERE group_forum_id='$forum_id'");

	$group_id=db_result($result,0,'group_id');
	$forum_name=db_result($result,0,'forum_name');

	forum_header(array('title'=>$Language->getText('forum_expand','expand_threads')));

	echo '
		<H1>'.$Language->getText('forum_expand','prefs').'</H!>';

	if ($et==1) {
		echo '<P>'.$Language->getText('forum_expand','now_expand');
	} else {
		echo '<P>'.$Language->getText('forum_expand','now_collaps');
	}

	forum_footer(array());

} else {
	exit_not_logged_in();
}

?>
