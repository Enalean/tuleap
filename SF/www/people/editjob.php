<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require('pre.php');
require('../people/people_utils.php');

if ($group_id && (user_ismember($group_id, 'A'))) {

	if ($add_job) {
		/*
			create a new job
		*/
		if (!$title || !$description || $category_id==100) {
			//required info
			exit_error('error - missing info','Fill in all required fields');
		}
		$sql="INSERT INTO people_job (group_id,created_by,title,description,date,status_id,category_id) ".
			"VALUES ('$group_id','". user_getid() ."','$title','$description','".time()."','1','$category_id')";
		$result=db_query($sql);
		if (!$result || db_affected_rows($result) < 1) {
			$feedback .= ' JOB insert FAILED ';
			echo db_error();
		} else {
			$job_id=db_insertid($result);
			$feedback .= ' JOB inserted successfully ';
		}

	} else if ($update_job) {
		/*
			update the job's description, status, etc
		*/
		if (!$title || !$description || $category_id==100 || $status_id==100 || !$job_id) {
			//required info
			exit_error('error - missing info','Fill in all required fields');
		}

		$sql="UPDATE people_job SET title='$title',description='$description',status_id='$status_id',category_id='$category_id' ".
			"WHERE job_id='$job_id' AND group_id='$group_id'";
		$result=db_query($sql);
		if (!$result || db_affected_rows($result) < 1) {
			$feedback .= ' JOB update FAILED ';
			echo db_error();
		} else {
			$feedback .= ' JOB updated successfully ';
		}

	} else if ($add_to_job_inventory) {
		/*
			add item to job inventory
		*/
		if ($skill_id==100 || $skill_level_id==100 || $skill_year_id==100  || !$job_id) {
			//required info
			exit_error('error - missing info','Fill in all required fields');
		}

		if (people_verify_job_group($job_id,$group_id)) {
			people_add_to_job_inventory($job_id,$skill_id,$skill_level_id,$skill_year_id);
			$feedback .= ' JOB update successful ';
		} else {
			$feedback .= ' JOB update failed - wrong project_id ';
		}

	} else if ($update_job_inventory) {
		/*
			Change Skill level, experience etc.
		*/
		if ($skill_level_id==100 || $skill_year_id==100  || !$job_id || !$job_inventory_id) {
			//required info
			exit_error('error - missing info','Fill in all required fields');
		}

		if (people_verify_job_group($job_id,$group_id)) {
			$sql="UPDATE people_job_inventory SET skill_level_id='$skill_level_id',skill_year_id='$skill_year_id' ".
				"WHERE job_id='$job_id' AND job_inventory_id='$job_inventory_id'";
			$result=db_query($sql);
			if (!$result || db_affected_rows($result) < 1) {
				$feedback .= ' JOB skill update FAILED ';
				echo db_error();
			} else {
				$feedback .= ' JOB skill updated successfully ';
			}
		} else {
			$feedback .= ' JOB skill update failed - wrong project_id ';
		}

	} else if ($delete_from_job_inventory) {
		/*
			remove this skill from this job
		*/
		if (!$job_id) {
			//required info
			exit_error('error - missing info','Fill in all required fields');
		}

		if (people_verify_job_group($job_id,$group_id)) {
			$sql="DELETE FROM people_job_inventory WHERE job_id='$job_id' AND job_inventory_id='$job_inventory_id'";
			$result=db_query($sql);
			if (!$result || db_affected_rows($result) < 1) {
				$feedback .= ' JOB skill delete FAILED ';
				echo db_error();
			} else {
				$feedback .= ' JOB skill deleted successfully ';
			}
		} else {
			$feedback .= ' JOB skill delete failed - wrong project_id ';
		}

	}

	/*
		Fill in the info to create a job
	*/
	people_header(array('title'=>'Edit a job for your project'));

	//for security, include group_id
	$sql="SELECT * FROM people_job WHERE job_id='$job_id' AND group_id='$group_id'";
	$result=db_query($sql);
	if (!$result || db_numrows($result) < 1) {
		echo db_error();
		$feedback .= ' POSTING fetch FAILED ';
		echo '<H2>No Such posting For This Project</H2>';
	} else {

		echo '
		<H3>Select Required Skills</H3>
		<P>
		Now you can edit/change the list of skills attached to this posting. 
		Developers will be able to match their skills with your requirements. 
		<P>
		All postings are automatically closed after two weeks.
		<P>
		<FORM ACTION="'.$PHP_SELF.'" METHOD="POST">
		<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
		<INPUT TYPE="HIDDEN" NAME="job_id" VALUE="'.$job_id.'">
		<B>Category:</B><BR>
		'. people_job_category_box('category_id',db_result($result,0,'category_id')) .'
		<P>
		<B>Status:</B><BR>
		'. people_job_status_box('status_id',db_result($result,0,'status_id')) .'
		<P>
		<B>Short Description:</B><BR>
		<INPUT TYPE="TEXT" NAME="title" VALUE="'. db_result($result,0,'title') .'" SIZE="40" MAXLENGTH="60">
		<P>
		<B>Long Description:</B><BR>
		<TEXTAREA NAME="description" ROWS="10" COLS="60" WRAP="SOFT">'. db_result($result,0,'description') .'</TEXTAREA>
		<P>
		<INPUT TYPE="SUBMIT" NAME="update_job" VALUE="Update Descriptions">
		</FORM>';

		//now show the list of desired skills
		echo '<P>'.people_edit_job_inventory($job_id,$group_id);
		echo '<P><FORM ACTION="/people/" METHOD="POST"><INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Finished"></FORM>';

	}

	people_footer(array());

} else {
	/*
		Not logged in or insufficient privileges
	*/
	if (!$group_id) {
		exit_no_group();
	} else {
		exit_permission_denied();
	}
}

?>
