<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

patch_header(array ('title'=>'Patch Detail: '.$patch_id));

$sql="SELECT patch.code,patch.summary,user.user_name AS submitted_by,".
	"user2.user_name AS assigned_to,patch_status.status_name,patch.open_date,patch_category.category_name ".
	"FROM patch,user,user user2,patch_category,patch_status ".
	"WHERE patch.submitted_by=user.user_id ".
	"AND patch.assigned_to=user2.user_id ".
	"AND patch.patch_status_id=patch_status.patch_status_id ".
	"AND patch.patch_category_id=patch_category.patch_category_id ".
	"AND patch.patch_id='$patch_id'";

$result=db_query($sql);

if (db_numrows($result) > 0) {

	echo '
		<H2>[ Patch #'.$patch_id.' ] '. db_result($result,0,'summary') .'</H2>

	<TABLE CELLPADDING="0" WIDTH="100%">
		<TR><TD COLSPAN="2"><B>Date:</B><BR>'.format_date($sys_datefmt,db_result($result,0,'open_date')).'</TD></TR>

		<TR>
			<TD><B>Submitted By:</B><BR>'.db_result($result,0,'submitted_by').'</TD>
			<TD><B>Assigned To:</B><BR>'.db_result($result,0,'assigned_to').'</TD>
		</TR>

		<TR>
			<TD><B>Category:</B><BR>'.db_result($result,0,'category_name').'</TD>
			<TD><B>Status:</B><BR>'.db_result($result,0,'status_name').'</TD>
		</TR>

		<TR><TD COLSPAN="2"><B>Summary:</B><BR>'. db_result($result,0,'summary') .'</TD></TR>';

	echo '
		<TR><TD COLSPAN="2"><P><B>Patch:</B><BR>
		<A HREF="/patch/download.php/Patch'.$patch_id.'.txt?patch_id='.$patch_id.'"><B>View Raw Patch</B></A>
		</TD></TR>';

	echo '
		<FORM ACTION="'.$PHP_SELF.'" METHOD="POST" enctype="multipart/form-data">

		<TR><TD COLSPAN="2">
			<B>Only the original submittor can upload a new version on this page.</B>
			<P>
			<INPUT TYPE="CHECKBOX" NAME="upload_new" VALUE="1"> <B>Upload Revised Patch (overwrite old)</B>
			<P>
			<input type="file" name="uploaded_data"  size="30">
			<INPUT TYPE="HIDDEN" NAME="func" VALUE="postaddcomment">
			<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
			<INPUT TYPE="HIDDEN" NAME="patch_id" VALUE="'.$patch_id.'">
			<P>
			<B>Add A Comment:</B><BR>
			<TEXTAREA NAME="details" ROWS="10" COLS="60" WRAP="SOFT"></TEXTAREA>
		</TD></TR>

		<TR><TD COLSPAN="2">';

	if (!user_isloggedin()) {
		echo '<BR><B><FONT COLOR="RED"><H3>You Are NOT Logged In</H3><P>Please <A HREF="/account/login.php">log in,</A> so followups can be emailed to you.</FONT></B><P>';
	}

	echo '
			<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
			</FORM>
		</TD></TR>
		<P>

		<TR><TD COLSPAN="2">';

	echo show_patch_details($patch_id);

	?>

	<TR><TD COLSPAN="2">
	<?php

	show_patchhistory($patch_id);

	?>
	</TD></TR></TABLE>
	<?php

} else {

	echo '
		<H1>Patch not found</H1>
	<P>
	<B>You can get this message</B> if this Project did not create patch groups/categories. 
	An admin for this project must create patch groups/categories and then modify this patch.';

}

patch_footer(array());

?>
