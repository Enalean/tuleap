<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

pm_header(array('title'=>'View A Task'));

$sql="SELECT * FROM project_task ".
	"WHERE project_task_id='$project_task_id' AND group_project_id='$group_project_id'";

$result=db_query($sql);

?>
<H2>View A Task In <?php echo  pm_data_get_group_name($group_project_id); ?></H2>

<TABLE BORDER="0" WIDTH="100%">
	<TR>
		<TD><B>Percent Complete:</B>
		<BR>
		<?php echo db_result($result,0,'percent_complete'); ?>%
		</TD>

		<TD><B>Priority:</B>
		<BR>
		<?php echo db_result($result,0,'priority'); ?>
		</TD>
	</TR>

  	<TR>
		<TD COLSPAN="2"><B>Task Summary:</B>
		<BR>
		<?php echo db_result($result,0,'summary'); ?>
		</TD>
	</TR>

	<TR>
		<TD COLSPAN="2">
		<B>Original Comment:</B>
		<P>
		<?php echo nl2br(db_result($result,0,'details')); ?>
		</TD>
	</TR>

	<TR>
    		<TD COLSPAN="2"><B>Start Date:</B>
		<BR>
		<?php echo date('Y-m-d', db_result($result,0,'start_date')); ?>
		</TD>
	</TR>

	<TR>
		<TD COLSPAN="2"><B>End Date:</B>
		<BR>
		<?php echo date('Y-m-d', db_result($result,0,'end_date')); ?>
		</TD>
	</TR>

	<TR>
		<TD VALIGN="TOP">
		<?php
		/*
			Get the list of ids this is assigned to and convert to array
			to pass into multiple select box
		*/

		$result2=db_query("SELECT user.user_name AS User_Name FROM user,project_assigned_to ".
			"WHERE user.user_id=project_assigned_to.assigned_to_id AND project_task_id='$project_task_id'");
		ShowResultSet($result2,'Assigned To');
		?>
		</TD>
		<TD VALIGN="TOP">
		<?php
		/*
			Get the list of ids this is dependent on and convert to array
			to pass into multiple select box
		*/
		$result2=db_query("SELECT project_task.summary FROM project_dependencies,project_task ".
			"WHERE is_dependent_on_task_id=project_task.project_task_id AND project_dependencies.project_task_id='$project_task_id'");
		ShowResultSet($result2,'Dependent On Task');
		?>
		</TD>
	</TR>

	<TR>
		<TD>
		<B>Hours:</B>
		<BR>
		<?php echo db_result($result,0,'hours'); ?>
		</TD>

		<TD>
		<B>Status:</B>
		<BR>
		<?php
		echo pm_data_get_status_name(db_result($result,0,'status_id'));
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

</table>
<?php

pm_footer(array());

?>
