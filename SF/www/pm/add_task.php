<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

pm_header(array('title'=>'Add a New Task',
		'help'=>'TaskSubmission.html'));

echo '<H2>Add a Task</H2>';

// First display the message preamble
$res_preamble  = db_query("SELECT pm_preamble FROM groups WHERE group_id=$group_id");

echo util_unconvert_htmlspecialchars(db_result($res_preamble,0,'pm_preamble'));
?>

<FORM ACTION="<?php echo $PHP_SELF; ?>" METHOD="POST" name="task_form" enctype="multipart/form-data">
<INPUT TYPE="HIDDEN" NAME="func" VALUE="postaddtask">
<INPUT TYPE="hidden" name="MAX_FILE_SIZE" value="2000000">
<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id; ?>">
<INPUT TYPE="HIDDEN" NAME="group_project_id" VALUE="<?php echo $group_project_id; ?>">
<INPUT TYPE="HIDDEN" NAME="bug_id" VALUE="<?php echo $bug_id; ?>">
<script language="JavaScript" src="/include/calendar.js"></script>

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
    	    <? 
    	        $field_name = "percent_complete";
                $field_value = pm_data_get_default_value($field_name);
                echo pm_field_display($field_name,$group_id,$field_value,false,false); ?>
		</TD>
		<TD>
			<B>Priority:&nbsp;</B>
    	    <? 
    	        $field_name = "priority";
                $field_value = pm_data_get_default_value($field_name);
                echo pm_field_display($field_name,$group_id,$field_value,false,false); ?>
		</td>
	</TR>

  	<TR>
		<TD COLSPAN="2"><B>Task Summary:</B>
		<BR>
    	    <? 
    	        $field_name = "summary";
                $field_value = pm_data_get_default_value($field_name);
                echo pm_field_display($field_name,$group_id,$field_value,false,false); ?>
		</td>
	</TR>
	<TR>
		<TD COLSPAN="2"><B>Task Details:</B>
		<BR>
    	    <? 
    	        $field_name = "details";
                $field_value = pm_data_get_default_value($field_name);
                echo pm_field_display($field_name,$group_id,$field_value,false,false); ?>
	</TR>
	<TR>
    		<TD COLSPAN="2"><B>Start Date:</B>
    	    <? 
    	        $field_name = "start_date";
    	        $pref_date = user_get_preference('pm_pref_date'.$group_id);
    	        if ($pref_date == 1) { 
    	        	// No date choose in the user pref
    	        	$field_value = "";
    	        } else {
	                $field_value = pm_data_get_default_value($field_name);
	            }
                echo pm_field_display($field_name,$group_id,$field_value,false,false); ?>
		 </td>

	</TR>
	<TR>
		<TD COLSPAN="2"><B>End Date:</B>
    	    <? 
    	        $field_name = "end_date";
    	        $pref_date = user_get_preference('pm_pref_date'.$group_id);
    	        if ($pref_date == 1) { 
    	        	// No date choose in the user pref
    	        	$field_value = "";
    	        } else {
	                $field_value = pm_data_get_default_value($field_name);
	            }
                echo pm_field_display($field_name,$group_id,$field_value,false,false); ?>
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
    	    <? 
    	        $field_name = "hours";
                $field_value = pm_data_get_default_value($field_name);
                echo pm_field_display($field_name,$group_id,$field_value,false,false); ?>
		</td>
	</TR>
	<TR>
      <TD COLSPAN="2">
      <hr><h3>Task Attachments <?php echo help_button('TaskUpdate.html#TaskAttachments'); ?></h3>
       <p>Optionally, you may also attach a file (e.g. a screenshot, a log file,...)</p>
      <B>Check to Upload &amp; Attach File:</B> <input type="checkbox" name="add_file" VALUE="1">
      &nbsp;&nbsp;&nbsp;
      <input type="file" name="input_file" size="40">
      <br><span class="smaller"><i>(The maximum upload file size is 2 Mb - <u>Please compress your files</u>)</i></span>
      <P>
      <B>File Description:</B>&nbsp;
      <input type="text" name="file_description" size="60" maxlength="255">
    </TR></TD>
	<TR>
		<TD COLSPAN="2" align="center"><hr>
		<INPUT TYPE="submit" value="Submit" name="submit">
		</td>
		</form>
	</TR>
</TABLE>
<?php

pm_footer(array());

?>
