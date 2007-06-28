<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

patch_header(array ('title'=>'Submit a Patch',
		    'help'=>'PatchSubmission.html'));

// First display the message preamble
$res_preamble  = db_query("SELECT patch_preamble FROM groups WHERE group_id=$group_id");

echo '<H2>Submit A Patch</H2>';
echo util_unconvert_htmlspecialchars(db_result($res_preamble,0,'patch_preamble'));

echo'		<FORM ACTION="'.$PHP_SELF.'" METHOD="POST" enctype="multipart/form-data">
        <INPUT TYPE="hidden" name="MAX_FILE_SIZE" value="'.$sys_max_size_upload.'">
		<INPUT TYPE="HIDDEN" NAME="func" VALUE="postaddpatch">
		<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
		<TABLE>
		<TR><TD VALIGN="TOP" COLSPAN="2"><B>Project:</B>&nbsp;&nbsp;'.group_getname($group_id).'</TD></TR>
		<TR><TD VALIGN="TOP" COLSPAN="2"><B>Category:</B>&nbsp;&nbsp;';

	echo patch_category_box($group_id,'patch_category_id');

	?>
	</TD></TR>

	<TR><TD COLSPAN="2"><B>Summary:</B>&nbsp;
		<INPUT TYPE="TEXT" NAME="summary" SIZE="45" MAXLENGTH="120">
	</TD></TR>

	<TR><TD COLSPAN="2">
	 <br><B>Upload the Patch (binary or text format)</B>
		<P>
		<input type="file" name="uploaded_data"  size="40">
        <br><span class="smaller"><i>(The maximum upload file size is <?php echo formatByteToMb($sys_max_size_upload); ?> Mb - <u>Please compress your files</u>)</i></span>
		<P>
		<B>OR Paste the patch here (text only), instead of uploading it:</B>
		<P>
		<TEXTAREA NAME="code" ROWS="30" COLS="85" WRAP="SOFT"></TEXTAREA>
	</TD></TR>

	<TR><TD COLSPAN="2">
		<P>
		<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT PATCH">
		<P>
	<?php 
	if (!user_isloggedin()) {
		echo '<h3><span class="highlight">You Are NOT Logged In</span></H3><P><b>Please <A HREF="/account/login.php?return_to='.
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
