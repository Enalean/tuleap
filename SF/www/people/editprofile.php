<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');
require('../people/people_utils.php');

$Language->loadLanguageMsg('people/people');

if (user_isloggedin()) {


	if ($update_profile) {
		/*
			update the job's description, status, etc
		*/
		if (!$people_resume) {
			//required info
			exit_error($Language->getText('people_editjob','error_missing'),$Language->getText('people_editjob','fill_in'));
		}

		$sql="UPDATE user SET people_view_skills='$people_view_skills',people_resume='$people_resume' ".
			"WHERE user_id='".user_getid()."'";
		$result=db_query($sql);
		if (!$result || db_affected_rows($result) < 1) {
			$feedback .= ' '.$Language->getText('people_editprofile','update_failed').' ';
			echo db_error();
		} else {
			$feedback .= ' '.$Language->getText('people_editprofile','update_ok').' ';
		}

	} else if ($add_to_skill_inventory) {
		/*
			add item to job inventory
		*/
		if ($skill_id==100 || $skill_level_id==100 || $skill_year_id==100) {
			//required info
			exit_error($Language->getText('people_editjob','error_missing'),$Language->getText('people_editjob','fill_in'));
		}
		people_add_to_skill_inventory($skill_id,$skill_level_id,$skill_year_id);

	} else if ($update_skill_inventory) {
		/*
			Change Skill level, experience etc.
		*/
		if ($skill_level_id==100 || $skill_year_id==100  || !$skill_inventory_id) {
			//required info
			exit_error($Language->getText('people_editjob','error_missing'),$Language->getText('people_editjob','fill_in'));
		}

		$sql="UPDATE people_skill_inventory SET skill_level_id='$skill_level_id',skill_year_id='$skill_year_id' ".
			"WHERE user_id='". user_getid() ."' AND skill_inventory_id='$skill_inventory_id'";
		$result=db_query($sql);
		if (!$result || db_affected_rows($result) < 1) {
			$feedback .= ' '.$Language->getText('people_editprofile','skill_update_failed').' ';
			echo db_error();
		} else {
			$feedback .= ' '.$Language->getText('people_editprofile','skill_update_ok').' ';
		}

	} else if ($delete_from_skill_inventory) {
		/*
			remove this skill from this job
		*/
		if (!$skill_inventory_id) {
			//required info
			exit_error($Language->getText('people_editjob','error_missing'),$Language->getText('people_editjob','fill_in'));
		}

		$sql="DELETE FROM people_skill_inventory WHERE user_id='". user_getid() ."' AND skill_inventory_id='$skill_inventory_id'";
		$result=db_query($sql);
		if (!$result || db_affected_rows($result) < 1) {
			$feedback .= ' '.$Language->getText('people_editprofile','skill_delete_failed').' ';
			echo db_error();
		} else {
			$feedback .= ' '.$Language->getText('people_editprofile','skill_delete_ok').' ';
		}

	}

	/*
		Fill in the info to create a job
	*/
	people_header(array('title'=>$Language->getText('people_editprofile','edit_your_profile')));

	//for security, include group_id
	$sql="SELECT * FROM user WHERE user_id='". user_getid() ."'";
	$result=db_query($sql);
	if (!$result || db_numrows($result) < 1) {
		echo db_error();
		$feedback .= ' '.$Language->getText('people_editprofile','user_fetch_failed').' ';
		echo '<H2>'.$Language->getText('people_editprofile','no_such_user').'</H2>';
	} else {

		echo '
		<H2>'.$Language->getText('people_editprofile','edit_your_profile').'</H2>
		<P>
		'.$Language->getText('people_editprofile','skill_explain').'
		<P>
		<FORM ACTION="'.$PHP_SELF.'" METHOD="POST">
		<P>
		'.$Language->getText('people_editprofile','public_view_explain').'
		<P>
		<B>'.$Language->getText('people_editprofile','publicly_viewable').':</B><BR>
		<INPUT TYPE="RADIO" NAME="people_view_skills" VALUE="0" '. ((db_result($result,0,'people_view_skills')==0)?'CHECKED':'') .'> <B>'.$Language->getText('global','no').'</B><BR>
		<INPUT TYPE="RADIO" NAME="people_view_skills" VALUE="1" '. ((db_result($result,0,'people_view_skills')==1)?'CHECKED':'') .'> <B>'.$Language->getText('global','yes').'</B><BR>
		<P>
		'.$Language->getText('people_editprofile','give_us_info').'
		<P>
		<B>'.$Language->getText('people_editprofile','resume').':</B><BR>
		<TEXTAREA NAME="people_resume" ROWS="15" COLS="60" WRAP="SOFT">'. db_result($result,0,'people_resume') .'</TEXTAREA>
		<P>
		<INPUT TYPE="SUBMIT" NAME="update_profile" VALUE="'.$Language->getText('people_editprofile','update_profile').'">
		</FORM>';

		//now show the list of desired skills
		echo '<P>'.people_edit_skill_inventory( user_getid() );

		echo '<P><FORM ACTION="/account/" METHOD="POST"><INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$Language->getText('people_editjob','finished').'"></FORM>'; 
	}

	people_footer(array());

} else {
	/*
		Not logged in
	*/
	exit_not_logged_in();
}

?>
