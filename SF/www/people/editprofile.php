<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');
require('../people/people_utils.php');

if (user_isloggedin()) {

	if ($update_profile) {
		/*
			update the job's description, status, etc
		*/
		if (!$people_resume) {
			//required info
			exit_error('error - missing info','Fill in all required fields');
		}

		$sql="UPDATE user SET people_view_skills='$people_view_skills',people_resume='$people_resume' ".
			"WHERE user_id='".user_getid()."'";
		$result=db_query($sql);
		if (!$result || db_affected_rows($result) < 1) {
			$feedback .= ' User update FAILED ';
			echo db_error();
		} else {
			$feedback .= ' User updated successfully ';
		}

	} else if ($add_to_skill_inventory) {
		/*
			add item to job inventory
		*/
		if ($skill_id==100 || $skill_level_id==100 || $skill_year_id==100) {
			//required info
			exit_error('error - missing info','Fill in all required fields');
		}
		people_add_to_skill_inventory($skill_id,$skill_level_id,$skill_year_id);

	} else if ($update_skill_inventory) {
		/*
			Change Skill level, experience etc.
		*/
		if ($skill_level_id==100 || $skill_year_id==100  || !$skill_inventory_id) {
			//required info
			exit_error('error - missing info','Fill in all required fields');
		}

		$sql="UPDATE people_skill_inventory SET skill_level_id='$skill_level_id',skill_year_id='$skill_year_id' ".
			"WHERE user_id='". user_getid() ."' AND skill_inventory_id='$skill_inventory_id'";
		$result=db_query($sql);
		if (!$result || db_affected_rows($result) < 1) {
			$feedback .= ' User Skill update FAILED ';
			echo db_error();
		} else {
			$feedback .= ' User Skill updated successfully ';
		}

	} else if ($delete_from_skill_inventory) {
		/*
			remove this skill from this job
		*/
		if (!$skill_inventory_id) {
			//required info
			exit_error('error - missing info','Fill in all required fields');
		}

		$sql="DELETE FROM people_skill_inventory WHERE user_id='". user_getid() ."' AND skill_inventory_id='$skill_inventory_id'";
		$result=db_query($sql);
		if (!$result || db_affected_rows($result) < 1) {
			$feedback .= ' User Skill Delete FAILED ';
			echo db_error();
		} else {
			$feedback .= ' User Skill Deleted successfully ';
		}

	}

	/*
		Fill in the info to create a job
	*/
	people_header(array('title'=>'Edit Your Profile'));

	//for security, include group_id
	$sql="SELECT * FROM user WHERE user_id='". user_getid() ."'";
	$result=db_query($sql);
	if (!$result || db_numrows($result) < 1) {
		echo db_error();
		$feedback .= ' User fetch FAILED ';
		echo '<H2>No Such User</H2>';
	} else {

		echo '
		<H2>Edit Your Profile</H2>
		<P>
		Now you can edit/change the list of your skills and your resume. 
		The list of skills can then be matched with the list of jobs in 
		our system. 
		<P>
		<FORM ACTION="'.$PHP_SELF.'" METHOD="POST">
		<P>
		The following option determines if others can see your resume online. If they can\'t, you
		can still enter your skills, and search for matching jobs.
		<P>
		<B>Publicly Viewable:</B><BR>
		<INPUT TYPE="RADIO" NAME="people_view_skills" VALUE="0" '. ((db_result($result,0,'people_view_skills')==0)?'CHECKED':'') .'> <B>No</B><BR>
		<INPUT TYPE="RADIO" NAME="people_view_skills" VALUE="1" '. ((db_result($result,0,'people_view_skills')==1)?'CHECKED':'') .'> <B>Yes</B><BR>
		<P>
		Give us some information, either a resume, or an explanation of your experience.
		<P>
		<B>Resume / Description of Experience:</B><BR>
		<TEXTAREA NAME="people_resume" ROWS="15" COLS="60" WRAP="SOFT">'. db_result($result,0,'people_resume') .'</TEXTAREA>
		<P>
		<INPUT TYPE="SUBMIT" NAME="update_profile" VALUE="Update Profile">
		</FORM>';

		//now show the list of desired skills
		echo '<P>'.people_edit_skill_inventory( user_getid() );

		echo '<P><FORM ACTION="/account/" METHOD="POST"><INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Finished"></FORM>'; 
	}

	people_footer(array());

} else {
	/*
		Not logged in
	*/
	exit_not_logged_in();
}

?>
