<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

support_header(array ('title'=>'Modify a Support Request'));

$sql="SELECT * FROM support WHERE support_id='$support_id' AND group_id='$group_id'";

$result=db_query($sql);

if (db_numrows($result) > 0) {

	echo '
	<H2>[ Support #'.$support_id.' ] '.db_result($result,0,'summary').'</H2>';

	echo '
	<FORM ACTION="'.$PHP_SELF.'" METHOD="POST" enctype="multipart/form-data">
	<INPUT TYPE="HIDDEN" NAME="func" VALUE="postmodsupport">
	<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
	<INPUT TYPE="HIDDEN" NAME="support_id" VALUE="'.$support_id.'">

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

	echo support_category_box ($group_id,'support_category_id',db_result($result,0,'support_category_id'));

	echo '
		</TD>
		<TD><B>Assigned To:</B><BR>';

	echo support_technician_box ($group_id,'assigned_to',db_result($result,0,'assigned_to'));

	?>
	</TD></TR>

	<TR><TD>
		<B>Status:</B><BR>
		<?php

		echo support_status_box ('support_status_id',db_result($result,0,'support_status_id'));

	?>
	</TD><TD>
		<B>Priority:</B><BR>
		<?php
		/*
			Priority of this support request
		*/
		build_priority_select_box('priority',db_result($result,0,'priority'));
		?>
	</TD></TR>

	<TR><TD COLSPAN="2"><B>Summary:</B><BR>
		<INPUT TYPE="TEXT" NAME="summary" SIZE="45" VALUE="<?php 
			echo db_result($result,0,'summary'); 
			?>" MAXLENGTH="60">
	</TD></TR>

	<TR><TD COLSPAN="2">
		<B>Use Canned Response:</B><BR>
		<?php
		echo support_canned_response_box ($group_id,'canned_response');
		echo '
			<P>
			<A HREF="/support/admin/index.php?group_id='.$group_id.'&create_canned=1">Define Custom Responses</A>';
		?>
		<P>
		<B>OR Attach A Comment:</B><BR>
		<TEXTAREA NAME="details" ROWS="7" COLS="60" WRAP="HARD"></TEXTAREA>
		<P>
		<?php
			echo show_support_details($support_id); 
		?>
	</TD></TR>

	<TR><TD COLSPAN="2">
		<?php 
			echo show_supporthistory($support_id); 
		?>
	</TD></TR>

	<TR><TD COLSPAN="2" ALIGN="MIDDLE">
		<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Submit Changes">
		</FORM>
	</TD></TR>

	</TABLE>

<?php

} else {

	echo '
		<H1>Support Request Not Found</H1>';
	echo db_error();
}

support_footer(array());

?>
