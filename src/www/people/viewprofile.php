<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');
require('../people/people_utils.php');

$Language->loadLanguageMsg('people/people');

if ($user_id) {

	people_header(array('title'=>$Language->getText('people_viewprofile','title')));

	//for security, include group_id
	$sql="SELECT * FROM user WHERE user_id='$user_id'";
	$result=db_query($sql);
	if (!$result || db_numrows($result) < 1) {
		echo db_error();
		$feedback .= ' '.$Language->getText('people_editprofile','edit_your_profile').' ';
		echo '<H2>'.$Language->getText('people_editprofile','no_such_user').'</H2>';
	} else {

		/*
			profile set private
		*/
		if (db_result($result,0,'people_view_skills') != 1) {
			echo '<H2>'.$Language->getText('people_viewprofile','set_private').'</H2>';
			people_footer(array());
			exit;
		}

		echo '
		<H3>'.$Language->getText('people_viewprofile','title').'</H3>
		<P>
		<TABLE BORDER="0" WIDTH="100%">
		<TR><TD>
			<B>'.$Language->getText('people_viewprofile','user_name').':</B><BR>
			'. db_result($result,0,'user_name') .'
		</TD></TR>
		<TR><TD>
			<B>'.$Language->getText('people_viewprofile','resume').':</B><BR>
			'. nl2br(db_result($result,0,'people_resume')) .'
		</TD></TR>
		<TR><TD>
		<H2>'.$Language->getText('people_viewprofile','skill_inventory').'</H2>';

		//now show the list of  skills for this person
		echo '<P>'.people_show_skill_inventory($user_id);
		echo '</TD></TR></TABLE>';
	}

	people_footer(array());

} else {
	/*
		Not logged in or insufficient privileges
	*/
	exit_error($Language->getText('global','error'),$Language->getText('people_viewprofile','user_id_not_found'));
}

?>
