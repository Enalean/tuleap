<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require('pre.php');
require('../people/people_utils.php');

if ($user_id) {

	people_header(array('title'=>'View a User Profile'));

	//for security, include group_id
	$sql="SELECT * FROM user WHERE user_id='$user_id'";
	$result=db_query($sql);
	if (!$result || db_numrows($result) < 1) {
		echo db_error();
		$feedback .= ' User fetch FAILED ';
		echo '<H2>No Such User</H2>';
	} else {

		/*
			profile set private
		*/
		if (db_result($result,0,'people_view_skills') != 1) {
			echo '<H2>This User Has Set His/Her Profile to Private</H2>';
			people_footer(array());
			exit;
		}

		echo '
		<H3>View A User Profile</H3>
		<P>
		<TABLE BORDER="0" WIDTH="100%">
		<TR><TD>
			<B>User Name:</B><BR>
			'. db_result($result,0,'user_name') .'
		</TD></TR>
		<TR><TD>
			<B>Resume:</B><BR>
			'. nl2br(db_result($result,0,'people_resume')) .'
		</TD></TR>
		<TR><TD>
		<H2>Skill Inventory</H2>';

		//now show the list of  skills for this person
		echo '<P>'.people_show_skill_inventory($user_id);
		echo '</TD></TR></TABLE>';
	}

	people_footer(array());

} else {
	/*
		Not logged in or insufficient privileges
	*/
	exit_error('Error','user_id not found.');
}

?>
