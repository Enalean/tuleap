<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

bug_header(array ('title'=>'Submit a Bug'));

echo '<FORM ACTION="'.$PHP_SELF.'" METHOD="POST">
	<INPUT TYPE="HIDDEN" NAME="func" VALUE="postaddbug">
	<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
	<TABLE>
	<TR><TD VALIGN="TOP" COLSPAN="2"><B>Group:</B><BR>'.group_getname($group_id).'</TD></TR>
	<TR><TD VALIGN="TOP"><B>Category:</B><BR>';

/*
	List of possible categories for this project
*/
echo bug_category_box ('category_id',$group_id);

echo '</TD>
	<TD><B>Bug Group:</B><BR>';

/*
	List of possible bug_groups for this project 
*/
echo bug_group_box ('bug_group_id',$group_id);

echo '</TD></TR>';

if (user_ismember($group_id,'A')) {

	echo '
		<TR><TD><B>Priority:</B><BR>';

	/*
		Priority of this bug
	*/
	echo build_priority_select_box('priority',db_result($result,0,'priority'));

	echo '</TD>
	<TD><B>Assigned To:</B><BR>';
	/*
		List of people that can be assigned this bug
	*/
	echo bug_technician_box ('assigned_to',$group_id,db_result($result,0,'assigned_to'));

	echo '</TD></TR>';

}

?>

<TR><TD COLSPAN="2"><B>Summary:</B><BR>
	<INPUT TYPE="TEXT" NAME="summary" SIZE="60" MAXLENGTH="100">
</TD></TR>

<TR><TD COLSPAN="2"><B>Details:</B><BR>
	<TEXTAREA NAME="details" ROWS="15" COLS="60" WRAP="SOFT"></TEXTAREA>
</TD></TR>

<TR><TD COLSPAN="2">
	<?php
	if (!user_isloggedin()) {
		echo '
		<h3><FONT COLOR="RED">You Are NOT logged in.</H3>
		<P> Please <A HREF="/account/login.php">log in,</A> so followups can be emailed to you.</FONT></B>';
	}
	?>

	<P>
	<B><FONT COLOR="RED">Did you check to see if this has already been submitted?</FONT></B>
	<P>
	<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
	<P>
	</FORM>
</TD></TR>

</TABLE>

<?php

bug_footer(array());

?>
