<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

patch_header(array ('title'=>'Modify a Patch',
		    'help'=>'PatchProcessing.html'));

$sql="SELECT * FROM patch WHERE patch_id='$patch_id' AND group_id='$group_id'";

$result=db_query($sql);

if (db_numrows($result) > 0) {

	echo '
	<H2>[ Patch #'.$patch_id.' ] '.db_result($result,0,'summary').'</H2>';

	echo '
	<FORM ACTION="'.$PHP_SELF.'" METHOD="POST" enctype="multipart/form-data">
    <INPUT TYPE="hidden" name="MAX_FILE_SIZE" value="2000000">
	<INPUT TYPE="HIDDEN" NAME="func" VALUE="postmodpatch">
	<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
	<INPUT TYPE="HIDDEN" NAME="patch_id" VALUE="'.$patch_id.'">

	<TABLE WIDTH="100%">
	<TR>
		<TD><B>Submitted By:&nbsp;</B>'.user_getname(db_result($result,0,'submitted_by')).'</TD>
		<TD><B>Group:&nbsp;</B>'.group_getname($group_id).'</TD>
	</TR>

	<TR>
		<TD><B>Submitted on:&nbsp;</B>
		'. format_date($sys_datefmt,db_result($result,0,'open_date')) .'
		</TD>
		<TD><FONT SIZE="-1">
		<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Submit Changes">
		</TD>
	</TR>

	<TR>
		<TD><B>Category:</B>';

		echo patch_category_box($group_id,'patch_category_id',db_result($result,0,'patch_category_id'));

		echo '
		</TD>
		<TD><B>Assigned To:</B>';

		echo patch_technician_box($group_id,'assigned_to',db_result($result,0,'assigned_to'))

		?>
	</TD></TR>

	<TR><TD COLSPAN="2">
		<B>Status:</B>&nbsp;
		<?php
		echo patch_status_box('patch_status_id',db_result($result,0,'patch_status_id'))
		?>
	</TD></TR>

	<TR><TD COLSPAN="2"><B>Summary:</B>&nbsp;
		<INPUT TYPE="TEXT" NAME="summary" SIZE="60" VALUE="<?php 
			echo db_result($result,0,'summary'); 
			?>" MAXLENGTH="180">
	</TD></TR>

	<TR><TD COLSPAN="2"><hr><B>Add a follow-up comment:</B><BR>
		<TEXTAREA NAME="details" ROWS="7" COLS="60" WRAP="SOFT"></TEXTAREA>
		<P>
		<B>Submitted Patch:&nbsp;</B>
		<?php

		if (db_result($result,0,'filename')) {
		    echo db_result($result,0,'filename')." (".
		    sprintf("%d",db_result($result,0,'filesize')/1024)." KB)";
		} else {
		    echo "Text File";
		}

		echo '&nbsp;&nbsp;<A HREF="/patch/download.php/Patch'.$patch_id.'.txt?group_id='.$group_id.'&patch_id='.$patch_id.'">
                                          <B>[View Raw Patch]</B></A>';
		echo'
			<P>
			 <B>You can also upload a revised version of the Patch (overwrite old)</B>
			<P>
			<input type="file" name="uploaded_data"  size="40">
            <br><span class="smaller"><i>(The maximum upload file size is 2 Mo)</i></span>
			<P>';

			//comments submitted about this patch
			echo show_patch_details($patch_id); 
		?>
	</TD></TR>

	<TR><TD COLSPAN="2">
		<?php echo show_patchhistory($patch_id); ?>
	</TD></TR>

	<TR><TD COLSPAN="2" ALIGN="center">
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
