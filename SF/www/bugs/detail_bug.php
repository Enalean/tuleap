<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

bug_header(array ('title'=>'Bug Detail: '.$bug_id));

$sql="SELECT bug_group.group_name,bug_resolution.resolution_name,bug.details,bug.summary,user.user_name AS submitted_by,".
	"user2.user_name AS assigned_to,bug.priority,bug_status.status_name,bug.date,bug_category.category_name ".
	"FROM bug,user,user user2,bug_group,bug_resolution,bug_category,bug_status WHERE bug.submitted_by=user.user_id AND bug.assigned_to=user2.user_id AND ".
	"bug.status_id=bug_status.status_id AND bug.category_id=bug_category.bug_category_id AND bug.bug_id='$bug_id' ".
	"AND bug.bug_group_id=bug_group.bug_group_id AND bug.resolution_id=bug_resolution.resolution_id";

$result=db_query($sql);

if (db_numrows($result) > 0) {

	echo '
		<H2>[ Bug #'.$bug_id.' ] '.db_result($result,0,'summary').'</H2>

	<TABLE CELLPADDING="0" WIDTH="100%">
		<TR><TD COLSPAN="2"><B>Date:</B><BR>'.date($sys_datefmt,db_result($result,0,'date')).'</TD></TR>

		<TR>
			<TD><B>Submitted By:</B><BR>'.db_result($result,0,'submitted_by').'</TD>
			<TD><B>Assigned To:</B><BR>'.db_result($result,0,'assigned_to').'</TD>
		</TR>

		<TR>
			<TD><B>Category:</B><BR>'.db_result($result,0,'category_name').'</TD>
			<TD><B>Priority:</B><BR>'.db_result($result,0,'priority').'</TD>
		</TR>

		<TR>
			<TD><B>Bug Group:</B><BR>'.db_result($result,0,'group_name').'</TD>
			<TD><B>Resolution:</B><BR>'.db_result($result,0,'resolution_name').'</TD>
		</TR>

		<TR><TD COLSPAN="2"><B>Summary:</B><BR>'.db_result($result,0,'summary').'</TD></TR>

		<TR><TD COLSPAN="2"><P><B>Original Submission:</B><BR>'. nl2br(db_result($result,0,'details')).'</TD></TR>';

	echo '
		<FORM ACTION="'.$PHP_SELF.'" METHOD="POST">
		<INPUT TYPE="HIDDEN" NAME="func" VALUE="postaddcomment">
		<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
		<INPUT TYPE="HIDDEN" NAME="bug_id" VALUE="'.$bug_id.'">

		<TR><TD COLSPAN="2"><B>Add A Comment:</B><BR>
			<TEXTAREA NAME="details" ROWS="10" COLS="60" WRAP="SOFT"></TEXTAREA>
		</TD></TR>

		<TR><TD COLSPAN="2">';

	if (!user_isloggedin()) {
		echo '<BR><B><FONT COLOR="RED"><H2>You Are NOT Logged In</H2><P>Please <A HREF="/account/login.php">log in,</A> so followups can be emailed to you.</FONT></B><P>';
	}

	echo '
			<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
			</FORM>
		</TD></TR>
		<P>

		<TR><TD COLSPAN="2">';

	echo show_bug_details($bug_id);

	?>

	<TR><TD VALIGN="TOP">
	<?php
		$result2=db_query("SELECT bug.summary ".
			"FROM bug,bug_bug_dependencies ".
			"WHERE bug.bug_id=bug_bug_dependencies.is_dependent_on_bug_id ".
			"AND bug_bug_dependencies.bug_id='$bug_id'");
		ShowResultSet($result2,'Dependent on Bug');
	?>
	</TD><TD VALIGN="TOP">
	<?php
		$result2=db_query("SELECT project_task.summary ".
			"FROM project_task,bug_task_dependencies ".
			"WHERE project_task.project_task_id=bug_task_dependencies.is_dependent_on_task_id ".
			"AND bug_task_dependencies.bug_id='$bug_id'");
		ShowResultSet($result2,'Dependent on Task');
	?>
	</TD></TR>

	<TR><TD COLSPAN="2">
		<?php echo show_dependent_bugs($bug_id,$group_id); ?>
	</TD></TR>
 
	<TR><TD COLSPAN="2">
	<?php

	show_bughistory($bug_id);

	?>
	</TD></TR></TABLE>
	<?php

} else {

	echo '
		<H1>Bug not found</H1>
	<P>
	<B>You can get this message</B> if this Project did not create bug groups/categories. 
	An admin for this project must create bug groups/categories and then modify this bug.';

}

bug_footer(array());

?>
