<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require('pre.php');
require('../people/people_utils.php');

if ($group_id && $job_id) {

	/*
		Fill in the info to create a job
	*/
	people_header(array('title'=>'View a Job'));

	//for security, include group_id
	$sql="SELECT groups.group_name,people_job_category.name AS category_name,".
		"people_job_status.name AS status_name,people_job.title,".
		"people_job.description,people_job.date,user.user_name,user.user_id ".
		"FROM people_job,groups,people_job_status,people_job_category,user ".
		"WHERE people_job_category.category_id=people_job.category_id ".
		"AND people_job_status.status_id=people_job.status_id ".
		"AND user.user_id=people_job.created_by ".
		"AND groups.group_id=people_job.group_id ".
		"AND people_job.job_id='$job_id' AND people_job.group_id='$group_id'";
	$result=db_query($sql);
	if (!$result || db_numrows($result) < 1) {
		echo db_error();
		$feedback .= ' POSTING fetch FAILED ';
		echo '<H2>No Such Posting For This Project</H2>';
	} else {

		echo '
		<H2>'. db_result($result,0,'category_name') .' wanted for '. db_result($result,0,'group_name') .'</H2>
		<P>
		<TABLE BORDER="0" WIDTH="100%">
                <TR><TD COLSPAN="2">
			<B>'. db_result($result,0,'title') .'</B>
		</TD></TR>

		<TR><TD>
			<B>Contact Info:<BR>
			<A HREF="/sendmessage.php?touser='. db_result($result,0,'user_id') .'&subject='. urlencode( 'RE: '.db_result($result,0,'title')) .'">'. db_result($result,0,'user_name') .'</A></B>
		</TD><TD>
			<B>Status:</B><BR>
			'. db_result($result,0,'status_name') .'
		</TD></TR>

		<TR><TD>
			<B>Open Date:</B><BR>
			'. date($sys_datefmt,db_result($result,0,'date')) .'
		</TD><TD>
			<B>For Project:<BR>
			<A HREF="/project/?group_id='. $group_id .'">'. db_result($result,0,'group_name') .'</A></B>
		</TD></TR>

		<TR><TD COLSPAN="2">
			<B>Long Description:</B><P>
			'. nl2br(db_result($result,0,'description')) .'
		</TD></TR>
		<TR><TD COLSPAN="2">
		<H2>Required Skills:</H2>';

		//now show the list of desired skills
		echo '<P>'.people_show_job_inventory($job_id).'</TD></TR></TABLE>';
	}

	people_footer(array());

} else {
	/*
		Not logged in or insufficient privileges
	*/
	if (!$group_id) {
		exit_no_group();
	} else {
		exit_error('Error','Posting ID not found');
	}
}

?>
