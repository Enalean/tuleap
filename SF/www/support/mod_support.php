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
	<H2>[ Support Request #'.$support_id.' ] '.db_result($result,0,'summary').'</H2>';

	echo '
	<FORM ACTION="'.$PHP_SELF.'" METHOD="POST" enctype="multipart/form-data">
	<INPUT TYPE="HIDDEN" NAME="func" VALUE="postmodsupport">
	<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
	<INPUT TYPE="HIDDEN" NAME="support_id" VALUE="'.$support_id.'">

	<TABLE WIDTH="100%">
	<TR>
		<TD><B>Submitted By:</B>&nbsp;'.user_getname(db_result($result,0,'submitted_by')).'</TD>
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
		<TD><B>Category:</B>&nbsp;';

	echo support_category_box ($group_id,'support_category_id',db_result($result,0,'support_category_id'));

	echo '
		</TD>
		<TD><B>Assigned To:</B>&nbsp;';

	echo support_technician_box ($group_id,'assigned_to',db_result($result,0,'assigned_to'));

	?>
	</TD></TR>

	<TR><TD>
		<B>Status:</B>&nbsp;
		<?php

		echo support_status_box ('support_status_id',db_result($result,0,'support_status_id'));

	?>
	</TD><TD>
		<B>Priority:</B>&nbsp;
		<?php
		/*
			Priority of this support request
		*/
		build_priority_select_box('priority',db_result($result,0,'priority'));
		?>
	</TD></TR>

	<TR><TD COLSPAN="2"><B>Summary:</B>&nbsp;
		<INPUT TYPE="TEXT" NAME="summary" SIZE="60" VALUE="<?php 
			echo db_result($result,0,'summary'); 
			?>" MAXLENGTH="100">
	</TD></TR>

	<TR><TD COLSPAN="2">
		<HR><B>Use Canned Response:</B>&nbsp;
		<?php
		echo support_canned_response_box ($group_id,'canned_response');
		echo '
			&nbsp;&nbsp;&nbsp;&nbsp;
			(or <A HREF="/support/admin/index.php?group_id='.$group_id.'&create_canned=1">Define a new Canned Response</A>)';
		?>
		<P>
		<B>OR Post a Follow-up Comment:</B><BR>
		<TEXTAREA NAME="details" ROWS="7" COLS="60"></TEXTAREA>
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
