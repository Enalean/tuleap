<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

pm_header(array('title'=>'Add a New Task',
		'help'=>'TaskSubmission.html'));

echo '<H2>Add A Task</H2>';

// First display the message preamble
$res_preamble  = db_query("SELECT pm_preamble FROM groups WHERE group_id=$group_id");

echo util_unconvert_htmlspecialchars(db_result($res_preamble,0,'pm_preamble'));
?>

<FORM ACTION="<?php echo $PHP_SELF; ?>" METHOD="POST">
<INPUT TYPE="HIDDEN" NAME="func" VALUE="postaddtask">
<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id; ?>">
<INPUT TYPE="HIDDEN" NAME="group_project_id" VALUE="<?php echo $group_project_id; ?>">
<INPUT TYPE="HIDDEN" NAME="bug_id" VALUE="<?php echo $bug_id; ?>">

<TABLE BORDER="0" WIDTH="100%">
	<TR>
		<TD colspan ="2">
			<B>Subproject:&nbsp;</B>
			<?php echo pm_subprojects_box('group_project_id',$group_id,$group_project_id,false,'',true,'*** Select One ***');?>
		</TD>
	</TR>
	<TR>
		<TD>
			<B>Percent Complete:&nbsp;</B>
			<?php echo pm_show_percent_complete_box(); ?>
		</TD>
		<TD>
			<B>Priority:&nbsp;</B>
			<?php echo build_priority_select_box(); ?>
		</td>
	</TR>

  	<TR>
		<TD COLSPAN="2"><B>Task Summary:</B>
		<BR>
		<INPUT TYPE="text" name="summary" size="60" MAXLENGTH="100" value="<? echo stripslashes($summary) ?>">
		</td>
	</TR>
	<TR>
		<TD COLSPAN="2"><B>Task Details:</B>
		<BR>
		<TEXTAREA NAME="details" ROWS="5" COLS="60" WRAP="SOFT"><? echo stripslashes($details) ?></TEXTAREA></td>
	</TR>
	<TR>
    		<TD COLSPAN="2"><B>Start Date:</B>
		<?php

		$pref_date = user_get_preference('pm_pref_date'.$group_id);
                if ($pref_date == 1) {
                    $day = $month = $year = 0;
                } else {
                    list(,,,$day,$month,$year) = localtime(time());
		    $month += 1;
		    $year +=1900;
		}

		echo pm_show_month_box ('start_month',$month);
		echo pm_show_day_box ('start_day',$day);
		echo pm_show_year_box ('start_year',$year);
		?>
			<BR><a href="calendar.php">View Calendar</a>
		 </td>

	</TR>
	<TR>
		<TD COLSPAN="2"><B>End Date:</B>
		<?php
		echo pm_show_month_box ('end_month',$month);
		echo pm_show_day_box ('end_day',$day);
		echo pm_show_year_box ('end_year',$year);
		?>
		</td>

	</TR>
	<TR>
		<TD>
		<B>Assigned To:</B>
		<BR>
		<?php
		if ( $assigned_to != '' ) {
		    echo pm_multiple_assigned_box ('assigned_to[]',$group_id,false,array($assigned_to));
		} else {
		    echo pm_multiple_assigned_box ('assigned_to[]',$group_id);
		}
		?>
		</td>
		<TD>
		<B>Dependent On Task:</B>
		<BR>
		<?php
		// Now show all tasks
	    echo pm_multiple_task_depend_box ('dependent_on[]',$group_id,false);
		?>
		</TD>
	</TR>
	<TR>
		<TD COLSPAN="2"><B>Hours:</B>
		<BR>
		<INPUT TYPE="text" name="hours" size="5" value="<? echo $hours ?>">
		</td>
	</TR>
	<TR>
		<TD COLSPAN="2">
		<INPUT TYPE="submit" value="Submit" name="submit">
		</td>
		</form>
	</TR>
</TABLE>
<?php

pm_footer(array());

?>
