<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

pm_header(array('title'=>'Modify A Task'));

$sql="SELECT * FROM project_task ".
	"WHERE project_task_id='$project_task_id' AND group_project_id='$group_project_id'";

$result=db_query($sql);

?>
<H2>Modify A Task In <?php echo  pm_data_get_group_name($group_project_id); ?></H2>

<FORM ACTION="<?php echo $PHP_SELF; ?>" METHOD="POST">
<INPUT TYPE="HIDDEN" NAME="func" VALUE="postmodtask">
<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id; ?>">
<INPUT TYPE="HIDDEN" NAME="group_project_id" VALUE="<?php echo $group_project_id; ?>">
<INPUT TYPE="HIDDEN" NAME="project_task_id" VALUE="<?php echo $project_task_id; ?>">

<TABLE BORDER="0" WIDTH="100%">
	<TR>    
		<TD><B>Subproject:</B>
		<BR>
		<?php echo pm_show_subprojects_box('new_group_project_id',$group_id,$group_project_id); ?>
		</TD>

		<TD><FONT SIZE="-1">
		<INPUT TYPE="submit" value="Submit Changes" name="submit"></FONT>
		</TD>
	</TR>

	<TR>
		<TD><B>Percent Complete:</B>
		<BR>
		<?php echo pm_show_percent_complete_box('percent_complete',db_result($result,0,'percent_complete')); ?>
		</TD>

		<TD><B>Priority:</B>
		<BR>
		<?php echo build_priority_select_box('priority',db_result($result,0,'priority')); ?>
		</TD>
	</TR>

  	<TR>
		<TD COLSPAN="2"><B>Task Summary:</B>
		<BR>
		<INPUT TYPE="text" name="summary" size="40" MAXLENGTH="65" VALUE="<?php echo db_result($result,0,'summary'); ?>">
		</TD>
	</TR>

	<TR>
		<TD COLSPAN="2">
		<B>Original Comment:</B>
		<P>
		<?php echo nl2br(db_result($result,0,'details')); ?>
		<P>
		<B>Add A Comment:</B>
		<BR>
		<TEXTAREA NAME="details" ROWS="5" COLS="40" WRAP="SOFT"></TEXTAREA>
		</TD>
	</TR>

	<TR>
    		<TD COLSPAN="2"><B>Start Date:</B>
		<BR>
		<?php
		echo pm_show_month_box ('start_month',date('m', db_result($result,0,'start_date')));
		echo pm_show_day_box ('start_day',date('d', db_result($result,0,'start_date')));
		echo pm_show_year_box ('start_year',date('Y', db_result($result,0,'start_date')));
		?>
		<BR><a href="calendar.php">View Calendar</a>
		</TD>
	</TR>

	<TR>
		<TD COLSPAN="2"><B>End Date:</B>
		<BR>
		<?php
		echo pm_show_month_box ('end_month',date('m', db_result($result,0,'end_date')));
		echo pm_show_day_box ('end_day',date('d', db_result($result,0,'end_date')));
		echo pm_show_year_box ('end_year',date('Y', db_result($result,0,'end_date')));
		?>
		</TD>
	</TR>

	<TR>
		<TD>
		<B>Assigned To:</B>
		<BR>
		<?php
		/*
			List of possible users that this one could be assigned to
		*/
		echo pm_multiple_assigned_box ('assigned_to[]',$group_id,$project_task_id);
		?>
		</TD>

		<TD>
		<B>Dependent On Task:</B>
		<BR>
		<?php
		/*
			List of possible tasks that this one could depend on
		*/

		echo pm_multiple_task_depend_box ('dependent_on[]',$group_project_id,$project_task_id);
		?>
		</TD>
	</TR>

	<TR>
		<TD>
		<B>Hours:</B>
		<BR>
		<INPUT TYPE="text" name="hours" size="5" VALUE="<?php echo db_result($result,0,'hours'); ?>">
		</TD>

		<TD>
		<B>Status:</B>
		<BR>
		<?php
		echo pm_status_box ('status_id',db_result($result,0,'status_id'));
		?>
		</TD>
	</TR>

	<TR>
		<TD COLSPAN="2">
			<?php echo pm_show_dependent_tasks ($project_task_id,$group_id,$group_project_id); ?>
		</TD>
	</TR>

	<TR>
		<TD COLSPAN="2">
			<?php echo pm_show_dependent_bugs ($project_task_id,$group_id,$group_project_id); ?>
		</TD>
	</TR>
 
	<TR>
		<TD COLSPAN="2">
			<?php echo pm_show_task_details ($project_task_id); ?>
		</TD>
	</TR>

	<TR>
		<TD COLSPAN="2">
			<?php echo pm_show_task_history ($project_task_id); ?>
		</TD>
	</TR>

	<TR>
		<TD COLSPAN="2" ALIGN="MIDDLE">
		<INPUT TYPE="submit" value="Submit Changes" name="submit">
		</TD>
		</form>
	</TR>

</table>
<?php

pm_footer(array());

?>
