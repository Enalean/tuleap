<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require('pre.php');
require('../pm/pm_utils.php');

if ($group_id) {

	/* if no sub project id given then it defaults to ANY (0) */
	if (!isset($group_project_id)) {
	    $group_project_id = 0;
	}

	pm_header(array('title'=>'Subprojects for '.group_getname($group_id)));

	if (user_isloggedin() && user_ismember($group_id)) {
		$public_flag='0,1';
	} else {
		$public_flag='1';
	}

	$sql="SELECT * FROM project_group_list WHERE group_id='$group_id' AND is_public IN ($public_flag)";

	$result = db_query ($sql);
	$rows = db_numrows($result); 
	if (!$result || $rows < 1) {
		echo "<H1>No Subprojects Found</H1>";
		echo "<P>
			<B>No subprojects have been set up, or you cannot view them.<P><FONT COLOR=RED>The Admin for this project ".
			"will have to set up projects using the admin page</FONT></B>";
		pm_footer(array());
		exit;
	}

	echo '
		<H3>Subprojects and Tasks</H3>
		<P>
		Choose a Subproject and you can browse/edit/add tasks to it.
		<P>';

	/*
		Put the result set (list of forums for this group) into a column with folders
	*/

	for ($j = 0; $j < $rows; $j++) { 
		echo '
		<A HREF="/pm/task.php?group_project_id='.db_result($result, $j, 'group_project_id').
		'&group_id='.$group_id.'&func=browse"><IMG SRC="/images/ic/index.png" HEIGHT=13 WIDTH=15 BORDER=0> &nbsp;'.
		db_result($result, $j, 'project_name').'</A><BR>'.
		db_result($result, $j, 'description').'<P>';
	}

	//If there is more than one subproject offer the option
	//to see them all at once
	if ($rows > 1) {
		echo '
		<A HREF="/pm/task.php?group_project_id=0'.
		'&group_id='.$group_id.'&func=browse"><IMG SRC="/images/ic/index.png" HEIGHT=13 WIDTH=15 BORDER=0> &nbsp;'.
		'All tasks in all subprojects</A><BR>'.
		'See all the tasks regardless of the sub-project they belong to.<P>';
	}

} else {
	pm_header(array('title'=>'Choose a Group First'));
	echo '<H1>Error - choose a group first</H1>';
}
pm_footer(array()); 

?>
