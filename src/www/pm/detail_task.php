<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

pm_header(array('title'=>'View A Task',
		'help'=>'TaskUpdate.html'));

// Test if we have the group_project_id in the arguments
if ( !isset($group_project_id)||($group_project_id == 0) ) {
    $group_project_id = pm_data_get_group_project_id($project_task_id);
}

$sql="SELECT * FROM project_task ".
	"WHERE project_task_id='$project_task_id' AND group_project_id='$group_project_id'";

$result=db_query($sql);

?>
<H2>[ Task #<?php echo $project_task_id.'] '.db_result($result,0,'summary');?></H2>

<TABLE CELLPADDING="0" WIDTH="100%">
      <TR><TD><B>Created by:</B>&nbsp;<?php echo user_getname(db_result($result,0,'created_by')); ?></TD>
          <TD><B>Group:</B>&nbsp;<?php echo group_getname($group_id); ?></TD>
      </TR>

	  <TR><TD COLSPAN="2">&nbsp</TD></TR>

	  <tr>
	     <td colspan="2"><b>Subproject: </b>&nbsp;
	     <?php echo pm_data_get_group_name($group_project_id);?></td>
	  </tr>

	<TR>
		<TD><B>Percent Complete:</B>&nbsp;
		<?php echo (db_result($result,0,'percent_complete')-1000); ?>%
		</TD>
		<TD><B>Priority:</B>
		&nbsp;
		<?php echo db_result($result,0,'priority'); ?>
		</TD>
	</TR>
	<TR>
    		<TD><B>Start Date:</B>
		&nbsp;
		<?php echo format_date('Y-m-d', db_result($result,0,'start_date')); ?>
		</TD>

		<TD><B>End Date:</B>
		&nbsp;
		<?php echo format_date('Y-m-d', db_result($result,0,'end_date')); ?>
		</TD>
	</TR>
	<TR>
		<TD>
		<B>Effort:</B>
		<?php echo db_result($result,0,'hours'); ?>
		</TD>

		<TD>
		<B>Status:</B>
		<?php
		echo pm_data_get_status_name(db_result($result,0,'status_id'));
		?>
		</TD>
	</TR>

	  <TR><TD COLSPAN="2">&nbsp</TD></TR>

  	<TR>
		<TD COLSPAN="2"><B>Summary:</B>
		<?php echo db_result($result,0,'summary'); ?>
		</TD>
	</TR>

	<TR>
		<TD COLSPAN="2">
		<B>Original Comment:</B>
		<br>
		<?php echo util_make_links(nl2br(db_result($result,0,'details')), $group_id); ?>
		</TD>
	</TR>

    <TR><TD colspan="2"><hr></td></tr>

	<TR>
		<TD COLSPAN="2">
        	<H3>Follow-up Comments</H3>
			<?php echo pm_show_task_details ($project_task_id, $group_id); ?>
		</TD>
	</TR>


    <TR><TD colspan="2"><hr><H3>Assigned To and Dependent On Task</H3></td></tr>

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

    <TR><TD colspan="2"><hr></td></tr>
     <?php 
	  echo '<TR><TD COLSPAN="2"><h3>CC List '.help_button('BugUpdate.html#BugCCList').'</h3>';
	  show_task_cc_list($project_task_id, $group_id);
	  echo "</TD></TR>\n";
     ?>

      <TR><TD colspan="2"><hr></td></tr>

      <TR><TD colspan="2">

      <h3>Task Attachments <?php echo help_button('BugUpdate.html#BugAttachments'); ?></h3>
      <?php show_pm_attached_files($project_task_id,$group_id); ?>
      </TD></TR>

    <TR><TD colspan="2"><hr></td></tr>

	<TR>
		<TD COLSPAN="2">
			<?php echo pm_show_dependent_tasks ($project_task_id,$group_id,$group_project_id); ?>
		</TD>
	</TR>

    <TR><TD colspan="2"><hr></td></tr>

	<TR>
		<TD COLSPAN="2">
			<?php echo pm_show_dependent_bugs ($project_task_id,$group_id,$group_project_id); ?>
		</TD>
	</TR>

     <?php if (user_isloggedin()) {
	 echo '<TR><TD COLSPAN="2">
                     <hr><h3>CC List '.help_button('BugUpdate.html#BugCCList').'</h3>
	   <b><u>Note:</b></u> for CodeX users use their login name rather than their email addresses.<p>
	   <B>Add CC:&nbsp;</b><input type="text" name="add_cc" size="30">&nbsp;&nbsp;&nbsp;
	   <B>Comment:&nbsp;</b><input type="text" name="cc_comment" size="40" maxlength="255"><p>';
	  show_task_cc_list($project_task_id, $group_id);
	  echo "</TD></TR>\n";
     }
     ?>

    <TR><TD colspan="2"><hr></td></tr>

	<TR>
		<TD COLSPAN="2">
    	<H3>Task change history</H3>
			<?php echo pm_show_task_history ($project_task_id); ?>
		</TD>
	</TR>

</table>
<?php

pm_footer(array());

?>
