<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

bug_header(array ('title'=>'Modify a Bug'));

$sql="SELECT * FROM bug WHERE bug_id='$bug_id' AND group_id='$group_id'";

$result=db_query($sql);

if (db_numrows($result) > 0) {

	echo "\n<H2>[ Bug #$bug_id ] ".db_result($result,0,"summary")."</H2>";

	echo "<FORM ACTION=\"$PHP_SELF\" METHOD=\"POST\">\n".
		"<INPUT TYPE=\"HIDDEN\" NAME=\"func\" VALUE=\"postmodbug\">\n".
		"<INPUT TYPE=\"HIDDEN\" NAME=\"group_id\" VALUE=\"$group_id\">\n".
		"<INPUT TYPE=\"HIDDEN\" NAME=\"bug_id\" VALUE=\"$bug_id\">";

	echo	"\n<TABLE WIDTH=\"100%\">
	<TR><TD><B>Submitted By:</B><BR>".user_getname(db_result($result,0,"submitted_by"))."</TD>
		<TD><B>Group:</B><BR>".group_getname($group_id)."</TD></TR>

	<TR>
		<TD><B>Date Submitted:</B><BR>
		". date($sys_datefmt,db_result($result,0,'date')) ."
		</TD>
		<TD><FONT SIZE=\"-1\">
		<INPUT TYPE=\"SUBMIT\" NAME=\"SUBMIT\" VALUE=\"Submit Changes\">
		</TD>
	</TR>

	<TR><TD><B>Category:</B><BR>\n";
	/*
		List of bug_categories for this project.
	*/
	echo  bug_category_box ('category_id',$group_id,db_result($result,0,'category_id'));

	echo "</TD><TD><B>Priority:</B><BR>\n";

	/*
		Priority of this bug
	*/
	echo build_priority_select_box('priority',db_result($result,0,'priority'));

	?>
	</TD></TR>

	<TR><TD><B>Group:</B><BR>
	<?php
	/*
		List of possible bug_groups for this project
	*/
	echo bug_group_box ('bug_group_id',$group_id,db_result($result,0,'bug_group_id'));

	?>
	</TD><TD><B>Resolution:</B><BR>
	<?php
	/*
		List of possible bug_resolutions
	*/
	echo bug_resolution_box ('resolution_id',db_result($result,0,'resolution_id'));

	?>
	</TD></TR>
	<TR><TD>
		<B>Assigned To:</B><BR>
		<?php

		/*
			List of people that can be assigned this bug
		*/
		echo bug_technician_box ('assigned_to',$group_id,db_result($result,0,'assigned_to'));
		?>
	</TD>
	<TD>
		<B>Status:</B><BR>
		<?php
		/*
			Status of this bug
		*/
		echo bug_status_box ('status_id',db_result($result,0,'status_id'));
		?>
	</TD></TR>

	<TR><TD COLSPAN="2"><B>Summary:</B><BR>
		<INPUT TYPE="TEXT" NAME="summary" SIZE="45" VALUE="<?php 
			echo db_result($result,0,'summary'); 
			?>" MAXLENGTH="60">
	</TD></TR>

	<TR><TD COLSPAN="2"><B>Use a Canned Response:</B><BR>
		<?php
		echo bug_canned_response_box ($group_id,'canned_response');
		echo '
			<P>
			<A HREF="/bugs/admin/index.php?group_id='.$group_id.'&create_canned=1">Define Custom Responses</A>';
		?>
	</TD></TR>


	<TR><TD COLSPAN="2"><B>Add Comment:</B><BR>
		<TEXTAREA NAME="details" ROWS="7" COLS="60" WRAP="SOFT"></TEXTAREA>
		<P>
		<B>Original Submission:</B><BR>
		<?php
			echo nl2br(db_result($result,0,'details'));

			echo "<P>";

			echo show_bug_details($bug_id); 
		?>
	</TD></TR>

	<TR><TD VALIGN="TOP">
	<B>Dependent on Task:</B><BR>
	<?php 
	/*
		Dependent on Task........
	*/

	echo bug_multiple_task_depend_box ('dependent_on_task[]',$group_id,$bug_id);

	?>
	</TD><TD VALIGN="TOP">
	<B>Dependent on Bug:</B><BR>
	<?php
	/*
		Dependent on Bug........
	*/
	echo bug_multiple_bug_depend_box ('dependent_on_bug[]',$group_id,$bug_id)

	?>
	</TD></TR>

	<TR><TD COLSPAN="2">
		<?php echo show_dependent_bugs($bug_id,$group_id); ?>
	</TD></TR>

	<TR><TD COLSPAN="2">
		<?php echo show_bughistory($bug_id); ?>
	</TD></TR>

	<TR><TD COLSPAN="2" ALIGN="MIDDLE">
		<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Submit Changes">
		</FORM>
	</TD></TR>

	</TABLE>

<?php

} else {

	echo '
		<H1>Bug Not Found</H1>';
	echo db_error();
}

bug_footer(array());

?>
