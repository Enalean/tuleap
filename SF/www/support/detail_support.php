<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

support_header(array ('title'=>'Support Request Detail: '.$support_id));

$sql="SELECT support.summary,user.user_name AS submitted_by,support.priority,".
	"user2.user_name AS assigned_to,support_status.status_name,support.open_date,support_category.category_name ".
	"FROM support,user,user user2,support_category,support_status ".
	"WHERE support.submitted_by=user.user_id ".
	"AND support.assigned_to=user2.user_id ".
	"AND support.support_status_id=support_status.support_status_id ".
	"AND support.support_category_id=support_category.support_category_id ".
	"AND support.support_id='$support_id'";

$result=db_query($sql);

if (db_numrows($result) > 0) {

	echo '
		<H2>[ Support Request #'.$support_id.' ] '.db_result($result,0,'summary').'</H2>

	<TABLE CELLPADDING="0" WIDTH="100%">
	 <TR>
		<TD><B>Submitted By:</B>&nbsp;'.db_result($result,0,'submitted_by').'</TD>
		<TD><B>Group:</B>&nbsp;'.group_getname($group_id).'</TD>
	 </TR>

	<TR>
		<TD><B>Submitted on:</B>&nbsp;
		'. date($sys_datefmt,db_result($result,0,'open_date')) .'
		</TD>
		<TD><FONT SIZE="-1">
		<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Submit Changes">
		</TD>
	</TR>

		<TR>
		                 <TD><B>Category:</B>&nbsp;'.db_result($result,0,'category_name').'</TD>
			<TD><B>Assigned to:</B>&nbsp;'.db_result($result,0,'assigned_to').'</TD>
		</TR>

		<TR>

			<TD><B>Status:</B>&nbsp;'.db_result($result,0,'status_name').'</TD>
			<TD><B>Priority:</B>&nbsp;'.db_result($result,0,'priority').'</TD>
		</TR>

		<TR><TD COLSPAN="2"><B>Summary:</B>&nbsp;'.db_result($result,0,'summary').'</TD></TR>';

	echo '
		<FORM ACTION="'.$PHP_SELF.'" METHOD="POST">

		<TR><TD COLSPAN="2">
			<FORM ACTION="'.$PHP_SELF.'" METHOD="POST">
			<INPUT TYPE="HIDDEN" NAME="func" VALUE="postaddcomment">
			<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
			<INPUT TYPE="HIDDEN" NAME="support_id" VALUE="'.$support_id.'">
			<P>
			<B>Add A Comment:</B><BR>
			<TEXTAREA NAME="details" ROWS="10" COLS="60"></TEXTAREA>
		</TD></TR>

		<TR><TD COLSPAN="2">';

	if (!user_isloggedin()) {
		echo '
			<h3><FONT COLOR="RED">Please <A HREF="/account/login.php">log in!</A></FONT></h3><BR>
			If you <B>cannot</B> login, then enter your email address here:<P>
			<INPUT TYPE="TEXT" NAME="user_email" SIZE="30" MAXLENGTH="35">';

	}

	echo '
			<P>
			<H3>DO NOT enter passwords in your message!</H3>
			<P>
			<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
			</FORM>
		</TD></TR>
		<P>

		<TR><TD COLSPAN="2">';

	echo show_support_details($support_id);

	?>

		<TR><TD COLSPAN="2">
	<?php

	show_supporthistory($support_id);

	?>
		</TD></TR>
	</TABLE>
	<?php

} else {

	echo '
		<H1>Support Request not found</H1>
	<P>
	<B>You can get this message</B> if this Project did not create support groups/categories. 
	An admin for this project must create support groups/categories and then modify this support.';
	echo db_error();

}

support_footer(array());

?>
