<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require('pre.php');
require('../forum/forum_utils.php');

if ($group_id) {

	forum_header(array('title'=>'Forums for '.group_getname($group_id)));

	if (user_isloggedin() && user_ismember($group_id)) {
		$public_flag='0,1';
	} else {
		$public_flag='1';
	}

	$sql="SELECT * FROM forum_group_list WHERE group_id='$group_id' AND is_public IN ($public_flag);";

	$result = db_query ($sql);

	$rows = db_numrows($result); 

	if (!$result || $rows < 1) {
		echo '<H1>No forums found for '.group_getname($group_id).'</H1>';
		forum_footer(array());
		exit;
	}

	echo '<H3>Discussion Forums</H3>
		<P>Choose a forum and you can browse, search, and post messages.<P>';

	/*
		Put the result set (list of forums for this group) into a column with folders
	*/

	for ($j = 0; $j < $rows; $j++) { 
		echo '<A HREF="forum.php?forum_id='.db_result($result, $j, 'group_forum_id').'">'.
			'<IMG SRC="/images/ic/cfolder15.png" HEIGHT=13 WIDTH=15 BORDER=0> &nbsp;'.
			db_result($result, $j, 'forum_name').'</A> ';
		//message count
		echo '('.db_result(db_query("SELECT count(*) FROM forum WHERE group_forum_id='".db_result($result, $j, 'group_forum_id')."'"),0,0).' msgs)';
		echo "<BR>\n";
		echo db_result($result,$j,'description').'<P>';
	}
	forum_footer(array());

} else {

	exit_no_group();

}

?>
