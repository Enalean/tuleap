<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

patch_header(array ('title'=>'Submit a Patch'));

	echo '
		<P>
		<H2>Submit A Patch</H2>
		<P>
		<B>Fill out the form below.</B> You can either paste your patch into the window 
		below or check the box and upload your patch.
		<P>
		<FORM ACTION="'.$PHP_SELF.'" METHOD="POST" enctype="multipart/form-data">
		<INPUT TYPE="HIDDEN" NAME="func" VALUE="postaddpatch">
		<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
		<TABLE>
		<TR><TD VALIGN="TOP" COLSPAN="2"><B>Group:</B><BR>'.group_getname($group_id).'</TD></TR>
		<TR><TD VALIGN="TOP" COLSPAN="2"><B>Category:</B><BR>';

	echo patch_category_box($group_id,'patch_category_id');

	?>
	</TD></TR>

	<TR><TD COLSPAN="2"><B>Summary:</B><BR>
		<INPUT TYPE="TEXT" NAME="summary" SIZE="45" MAXLENGTH="60">
	</TD></TR>

	<TR><TD COLSPAN="2">
		<INPUT TYPE="CHECKBOX" NAME="upload_instead" VALUE="1"> <B>Upload Patch</B>
		<P>
		<input type="file" name="uploaded_data"  size="30">
		<P>
		<B>OR Paste the patch here, instead of uploading it:</B>
		<P>
		<TEXTAREA NAME="code" ROWS="30" COLS="85" WRAP="SOFT"></TEXTAREA>
	</TD></TR>

	<TR><TD COLSPAN="2">
		<P>
		<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT PATCH">
		<P>
	<?php 
	if (!user_isloggedin()) {
		echo '<h3><FONT COLOR="RED">You Are NOT Logged In</font></H3><P><b>Please <A HREF="/account/login.php?return_to='.
		urlencode($REQUEST_URI).
		'">log in,</A> so followups can be emailed to you.</B>';
	} 
	?>
		</FORM>
	</TD></TR>

	</TABLE>

<?php

patch_footer(array());

?>
