<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

pm_header(array('title'=>'Modify a Task',
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

<FORM ACTION="<?php echo $PHP_SELF; ?>" METHOD="POST" name="task_form" enctype="multipart/form-data">
<INPUT TYPE="HIDDEN" NAME="func" VALUE="postmodtask">
<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id; ?>">
<INPUT TYPE="HIDDEN" NAME="old_group_project_id" VALUE="<?php echo $group_project_id; ?>">
<INPUT TYPE="HIDDEN" NAME="project_task_id" VALUE="<?php echo $project_task_id; ?>">
<INPUT TYPE="hidden" name="MAX_FILE_SIZE" value="<? echo $sys_max_size_attachment; ?>">
<script language="JavaScript" src="/scripts/calendar.js"></script>

<TABLE BORDER="0" CELLPADDING="0" WIDTH="100%">
	<TR>    
                <TD><B>Subproject:</B>
                &nbsp;
		<?php echo pm_subprojects_box('group_project_id',$group_id,$group_project_id); ?>
		</TD>

		<TD>
		<DIV class="vspace-bottom">
		<FONT SIZE="-1">
		<INPUT TYPE="submit" value="Submit Changes" name="submit">
		</FONT>
		</DIV>
		</TD>
	</TR>

	<TR>
		<TD><B>Percent Complete:</B>
		&nbsp;
		<?php
            echo pm_field_display("percent_complete",$group_id,db_result($result,0,'percent_complete'),false,false);
		?>
		</TD>

		<TD><B>Priority:</B>
		&nbsp;
		<?php
            echo pm_field_display("priority",$group_id,db_result($result,0,'priority'),false,false);
		?>
		</TD>
	</TR>

	<TR>
		<TD>
		<B>Effort:</B>
		&nbsp;
		<?php
            echo pm_field_display("hours",$group_id,db_result($result,0,'hours'),false,false);
		?>
		</TD>

		<TD>
		<B>Status:</B>
		&nbsp;
		<?php
            echo pm_field_display("status_id",$group_id,db_result($result,0,'status_id'),false,false);
		?>
		</TD>
	</TR>

	<TR>
    		<TD><B>Start Date:</B>
		<BR>
		<?php
            echo pm_field_display("start_date",$group_id,db_result($result,0,'start_date'),false,false);
		?>
		</TD>

		<TD rowspan="2">
		<B>Assigned To:</B>
		<BR><FONT SIZE="-1">
		<?php
		/*
			List of possible users that this one could be assigned to
		*/
		echo pm_multiple_assigned_box ('assigned_to[]',$group_id,$project_task_id);
		?></FONT>
		</TD>
        </tr>
	<tr>
		<TD><B>End Date:</B>
		<BR>
		<?php
            echo pm_field_display("end_date",$group_id,db_result($result,0,'end_date'),false,false);
		?>
		</TD>
	</TR>

  	<TR>
		<TD COLSPAN="2"><B>Task Summary:</B>
		<BR>
		<INPUT TYPE="text" name="summary" size="60" MAXLENGTH="100" VALUE="<?php echo db_result($result,0,'summary'); ?>">
		</TD>
	</TR>

  	<TR>
		<TD COLSPAN="2">
		<B>Original Comment:</B><BR>
		<?php
			echo util_make_links(nl2br(db_result($result,0,'details')), $group_id);
		?>
		</TD>
	</TR>

	<TR><TD colspan="2" align="top"><HR></td></TR>

	<TR>
		<TD COLSPAN="2">
    	<H3>Follow-up Comments <? echo help_button('TaskUpdate.html#TaskCommentS'); ?></H3>
		<TEXTAREA NAME="details" ROWS="5" COLS="60" WRAP="SOFT"></TEXTAREA>
		</TD>
	</TR>
 
	<TR>
		<TD COLSPAN="2">
			<?php echo pm_show_task_details ($project_task_id, $group_id); ?>
		</TD>
	</TR>

    <TR><TD colspan="2"><hr></td></tr>

     <?php 
	 echo '<TR><TD COLSPAN="2">
                     <h3>CC List '.help_button('TaskUpdate.html#TaskCCList').'</h3>
	   <b><u>Note:</b></u> for CodeX users use their login name rather than their email addresses.<p>
	   <B>Add CC:&nbsp;</b><input type="text" name="add_cc" size="30">&nbsp;&nbsp;&nbsp;
	   <B>Comment:&nbsp;</b><input type="text" name="cc_comment" size="40" maxlength="255"><p>';
	  show_task_cc_list($project_task_id, $group_id);
	  echo "</TD></TR>\n";
     
     ?>

      <TR><TD colspan="2"><hr></td></tr>

      <TR><TD colspan="2">

      <h3>Task Attachments <?php echo help_button('TaskUpdate.html#TaskAttachments'); ?></h3>
       <B>Check to Upload&hellip;  <input type="checkbox" name="add_file" VALUE="1">
      &nbsp;&hellip;&amp; Attach File:</B>
      <input type="file" name="input_file" size="40">
      <br><span class="smaller"><i>(The maximum upload file size is <?php echo formatByteToMb($sys_max_size_attachment); ?> Mb - <u>Please compress your files</u>)</i></span>
      <P>
      <B>File Description:</B>&nbsp;
      <input type="text" name="file_description" size="60" maxlength="255">
      <P>
      <?php show_pm_attached_files($project_task_id,$group_id); ?>
      </TD></TR>

    <TR><TD colspan="2"><hr></td></tr>

	<TR>
		<TD colspan="2">
    	<H3>Task dependencies <?php echo help_button('TaskUpdate.html#TaskDependencies'); ?></H3>
		<B>Dependent On Task:</B>
		<BR>
		<?php
		/*
			List of possible tasks that this one could depend on
		*/

		//Now Show all tasks
		echo pm_multiple_task_depend_box ('dependent_on[]',$group_id,false,$project_task_id);
		?>
		</TD>
	</TR>

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

    <TR><TD colspan="2"><hr></td></tr>

	<TR>
		<TD COLSPAN="2">
    	<H3>Task change history <?php echo help_button('TaskUpdate.html#TaskHistory'); ?></H3>
			<?php echo pm_show_task_history ($project_task_id); ?>
		</TD>
	</TR>

	<TR>
		<TD COLSPAN="2" ALIGN="center">
		<INPUT TYPE="submit" value="Submit Changes" name="submit">
		</TD>
		</form>
	</TR>

</table>
<?php

pm_footer(array());

?>
