<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

patch_header(array ('title'=>'Modify a Patch'));

$sql="SELECT * FROM patch WHERE patch_id='$patch_id' AND group_id='$group_id'";

$result=db_query($sql);

if (db_numrows($result) > 0) {

	echo '
	<H2>[ Patch #'.$patch_id.' ] '.db_result($result,0,'summary').'</H2>';

	echo '
	<FORM ACTION="'.$PHP_SELF.'" METHOD="POST" enctype="multipart/form-data">
	<INPUT TYPE="HIDDEN" NAME="func" VALUE="postmodpatch">
	<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
	<INPUT TYPE="HIDDEN" NAME="patch_id" VALUE="'.$patch_id.'">

	<TABLE WIDTH="100%">
	<TR>
		<TD><B>Submitted By:</B><BR>'.user_getname(db_result($result,0,'submitted_by')).'</TD>
		<TD WIDTH="99%"><B>Group:</B><BR>'.group_getname($group_id).'</TD>
	</TR>

	<TR>
		<TD><B>Date Submitted:</B><BR>
		'. date($sys_datefmt,db_result($result,0,'open_date')) .'
		</TD>
		<TD><FONT SIZE="-1">
		<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Submit Changes">
		</TD>
	</TR>

	<TR>
		<TD><B>Category:</B><BR>';

		echo patch_category_box($group_id,'patch_category_id',db_result($result,0,'patch_category_id'));

		echo '
		</TD>
		<TD><B>Assigned To:</B><BR>';

		echo patch_technician_box($group_id,'assigned_to',db_result($result,0,'assigned_to'))

		?>
	</TD></TR>

	<TR><TD COLSPAN="2">
		<B>Status:</B><BR>
		<?php
		echo patch_status_box('patch_status_id',db_result($result,0,'patch_status_id'))
		?>
	</TD></TR>

	<TR><TD COLSPAN="2"><B>Summary:</B><BR>
		<INPUT TYPE="TEXT" NAME="summary" SIZE="45" VALUE="<?php 
			echo db_result($result,0,'summary'); 
			?>" MAXLENGTH="60">
	</TD></TR>

	<TR><TD COLSPAN="2"><B>Add A Comment:</B><BR>
		<TEXTAREA NAME="details" ROWS="7" COLS="60" WRAP="SOFT"></TEXTAREA>
		<P>
		<B>Submitted Patch:</B><BR>
		<?php
		echo '
			<A HREF="/patch/download.php/Patch'.$patch_id.'.txt?id='.$patch_id.'"><B>View Raw Patch</B></A>
			<P>
			<INPUT TYPE="CHECKBOX" NAME="upload_new" VALUE="1"> <B>Upload Revised Patch (overwrite old)</B>
			<P>
			<input type="file" name="uploaded_data"  size="30">
			<P>';

			//comments submitted about this patch
			echo show_patch_details($patch_id); 
		?>
	</TD></TR>

	<TR><TD COLSPAN="2">
		<?php echo show_patchhistory($patch_id); ?>
	</TD></TR>

	<TR><TD COLSPAN="2" ALIGN="MIDDLE">
		<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Submit Changes">
		</FORM>
	</TD></TR>

	</TABLE>

<?php

} else {

	echo '
		<H1>Patch Not Found</H1>';
	echo db_error();
}

patch_footer(array());

?>
