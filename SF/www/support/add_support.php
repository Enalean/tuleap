<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

support_header(array ('title'=>'Submit a Support Request',
		     'help' => 'SupportRequestSubmission.html'));

// First display the message preamble
$res_preamble  = db_query("SELECT support_preamble FROM groups WHERE group_id=$group_id");

echo "<H2>Submit A Support Request</H2>\n";
echo util_unconvert_htmlspecialchars(db_result($res_preamble,0,'support_preamble'));

	echo '
	<P>
	<FORM ACTION="'.$PHP_SELF.'" METHOD="POST" enctype="multipart/form-data">
	<INPUT TYPE="HIDDEN" NAME="func" VALUE="postaddsupport">
	<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
	<TABLE>
	<TR><TD VALIGN="TOP" COLSPAN="2"><B>For Project:</B>&nbsp;&nbsp;'.group_getname($group_id).'</TD></TR>
	<TR><TD VALIGN="TOP" COLSPAN="2"><B>Category:</B>&nbsp;&nbsp;';

	echo support_category_box ($group_id,'support_category_id');

	?>
	</TD></TR>

	<TR><TD COLSPAN="2"><B>Summary:</B><BR>
		<INPUT TYPE="TEXT" NAME="summary" SIZE="60" MAXLENGTH="100">
	</TD></TR>

	<TR><TD COLSPAN="2">
		<B>Detailed Description:</B>
		<P>
		<TEXTAREA NAME="details" ROWS="15" COLS="60"></TEXTAREA>
	</TD></TR>

	<TR><TD COLSPAN="2">
	<?php 
	if (!user_isloggedin()) {
		echo '<B><FONT COLOR="RED"><H2>You Are NOT Logged In</H2>
                                               <P>Please <A HREF="/account/login.php?return_to='.
		    urlencode($REQUEST_URI).
		    '">log in,</A> so followups can be emailed to you.</FONT></B><P>';

		echo '
                                                 If you <B>cannot</B> login, then enter your email address here:<P>
		       <INPUT TYPE="TEXT" NAME="user_email" SIZE="30" MAXLENGTH="35">';
	} 
	?>
		<P>
		<H3>DO NOT enter passwords in your message!</H3>
		<P>
		<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT REQUEST">
		</FORM>
		<P>
	</TD></TR>

	</TABLE>

<?php

support_footer(array());

?>
