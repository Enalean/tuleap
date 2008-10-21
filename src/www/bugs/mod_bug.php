<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 


$sql="SELECT * FROM bug WHERE bug_id='$bug_id' AND group_id='$group_id'";
$fields_per_line=2;
$max_size=40;

$result=db_query($sql);

if (db_numrows($result) > 0) {

    // Prepare all the necessary fields in case the user wants to 
    // Create a new task based on this bug

    // assigned_to is passed along
    $assigned_to = db_result($result,0,'assigned_to');

    // Check if hours is used. If so pass it along as well
    if ( bug_data_is_used('hours') ) {
        $hours = db_result($result,0,'hours');
    } else {
        $hours = '';
    }
    
    // Insert a reference to the originating bug in the task description
    $url = '/bugs/?func=detailbug&bug_id='.$bug_id.'&group_id='.$group_id;
    $task_details = db_result($result,0,'details')."\n\nSee bug #$bug_id";
    $GLOBALS['HTML']->includeCalendarScripts();
    bug_header(array ('title'=>'Modify a Bug',
                      'create_task'=>'Create task',
                      'summary' => db_result($result,0,'summary'),
                      'details' => $task_details,
                      'assigned_to' => $assigned_to,
                      'hours' => $hours,
                      'bug_id' => $bug_id,
		      'help' => 'BugUpdate.html'
                      ));
    
    // First display some  internal fields - Cannot be modified by the user
?>
    <TABLE cellpadding="0" cellspacing="0" width="100%"><TR>
    <TD valign="top">
    <H2>[ Bug #<?php echo $bug_id.' ] '.db_result($result,0,'summary');?></H2>
    </TD>
    <TD valign="top"><img src="<?php echo util_get_image_theme("msg.png");?>" border="0" valign="top"></TD>
    <TD align="left" valign="top">
    <a href="<?php echo $url.'&pv=1';?>" target="_blank">Printer&nbsp;version</a>
    </TD>
    </TR>
    </TABLE>

    <FORM ACTION="<?php echo $PHP_SELF; ?>" METHOD="POST" enctype="multipart/form-data" NAME="bug_form">
    <INPUT TYPE="hidden" name="MAX_FILE_SIZE" value="<? echo $sys_max_size_attachment; ?>">
    <INPUT TYPE="HIDDEN" NAME="func" VALUE="postmodbug">
    <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id;; ?>">
    <INPUT TYPE="HIDDEN" NAME="bug_id" VALUE="<?php echo $bug_id; ?>">
    <TABLE cellpadding="0">
      <TR><TD><B>Submitted By:</B>&nbsp;</td><td><?php echo user_getname(db_result($result,0,'submitted_by')); ?></TD>
          <TD><B>Group:</B>&nbsp;</td><td><?php echo group_getname($group_id); ?></TD>
      </TR>
      <TR><TD><B>Submitted on:</B>&nbsp;</td><td><?php  echo format_date($GLOBALS['Language']->getText('system', 'datefmt'),db_result($result,0,'date')); ?></TD>
          <TD colspan="2"><FONT SIZE="-1"><INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Submit Changes"></TD>
      </TR>
      <TR><TD COLSPAN="<?php echo 2*$fields_per_line; ?>">&nbsp</TD></TR>

<?php
      // Now display the variable part of the field list (depend on the project)

      $i=0;
      while ( $field_name = bug_list_all_fields() ) {

	  // if the field is a special field (except summary and details) 
	  // or if not used by this project  then skip it.
	  if ( (!bug_data_is_special($field_name) || $field_name=='summary' || $field_name=='details') &&
	       bug_data_is_used($field_name) ) {
				   
	      // display the bug field
	      // if field size is greatest than max_size chars then force it to
	      // appear alone on a new line or it won't fit in the page
	      $field_value = db_result($result,0,$field_name);
	      list($sz,) = bug_data_get_display_size($field_name);
	      $label = bug_field_label_display($field_name,$group_id,false,false);
	      // original submission field must be displayed read-only
	      if ($field_name=='details') 
		  $value = util_make_links(bug_field_display($field_name,$group_id,$field_value,false,false,true),$group_id);
	      else
		  $value = bug_field_display($field_name,$group_id,$field_value,false,false);
	      $star = (bug_data_is_empty_ok($field_name) ? '':'<span class="highlight"><big>*</big></b></span>');

	      if ($sz > $max_size) {
		  echo "\n<TR>".
		      '<TD valign="top">'.$label.$star.'</td>'.
		      '<TD valign="top" colspan="'.(2*$fields_per_line-1).'">'.
		      $value.'</TD>'.		      
		      "\n</TR>";
		  $i=0;
	      } else {
		  echo ($i % $fields_per_line ? '':"\n<TR>");
		  echo '<TD valign="top">'.$label.$star.'</td>'.
		      '<TD valign="top">'.$value.'</TD>';
		  $i++;
		  echo ($i % $fields_per_line ? '':"\n</TR>");
	      }
	  }
      }
      
      // Now display other special fields

?>
      </table>

      <table cellspacing="0">
      <TR><TD colspan="2" align="top"><HR></td></TR>
      <TR><TD>
      <h3>Follow-up Comments <?php echo help_button('BugUpdate.html#BugComments'); ?></h3></td>
      <TD>
	 <INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Submit Changes">
      </td></tr>
      
      <tr><TD colspan="2" align="top">
      <B>Use a Canned Response:</B>&nbsp;
      <?php
      echo bug_canned_response_box ($group_id,'canned_response');
      echo '&nbsp;&nbsp;&nbsp;<A HREF="/bugs/admin/field_values.php?group_id='.$group_id.'&create_canned=1">Or define a new Canned Response</A>';
      ?>
      </TD></TR>
 
      <TR><TD colspan="2">
      <P><B>Comment Type:</B>
      <?php echo bug_field_box('comment_type_id','',$group_id,'',true,'None'); ?><BR>
      <?php
      echo bug_field_textarea('details',''); 
      echo '<p>';
      echo show_bug_details($bug_id,$group_id);
      ?>
      </td></tr>

      <TR><TD colspan="2"><hr></td></tr>

      <TR><TD colspan="2">
      <h3>CC List <?php echo help_button('BugUpdate.html#BugCCList'); ?></h3>
	  <b><u>Note:</b></u> for CodeX users use their login name rather than their email addresses.<p>
	  <B>Add CC:&nbsp;</b><input type="text" name="add_cc" size="30">&nbsp;&nbsp;&nbsp;
	  <B>Comment:&nbsp;</b><input type="text" name="cc_comment" size="40" maxlength="255"><p>
	  <?php show_bug_cc_list($bug_id, $group_id); ?>
      </TD></TR>

      <TR><TD colspan="2"><hr></td></tr>

      <TR><TD colspan="2">

      <h3>Bug Attachments <?php echo help_button('BugUpdate.html#BugAttachments'); ?></h3>
       <B>Check to Upload&hellip;  <input type="checkbox" name="add_file" VALUE="1">
      &nbsp;&hellip;&amp; Attach File:</B>
      <input type="file" name="input_file" size="40">
      <br><span class="small"><i>(The maximum upload file size is <?php echo formatByteToMb($sys_max_size_attachment); ?> Mb - <u>Please compress your files</u>)</i></span>
      <P>
      <B>File Description:</B>&nbsp;
      <input type="text" name="file_description" size="60" maxlength="255">
      <P>
      <?php show_bug_attached_files($bug_id,$group_id); ?>
      </TD></TR>

      <TR><TD colspan="2"><hr></td></tr>

      <TR ><TD colspan="2"">
      <h3>Bug Dependencies <?php echo help_button('BugUpdate.html#BugDependencies'); ?></h3>
      </td></TR>

	<TR><TD colspan="2">
		<table width="100%" border="0" cellspacing="0" cellpadding="2">
		<tr><td>
			<B>Dependent on Task:</B>
		</td><td>
			<B>Dependent on Bug:</B>
		</td></tr>
		<tr><td valign="top">
	<?php 
	/*
		Dependent on Task........
	*/

	echo bug_show_task_dependencies ($group_id,$bug_id);

	?>
		</td>
		<td valign="top">
	<?php
	/*
		Dependent on Bug........
	*/
	echo bug_show_bug_dependencies ($group_id,$bug_id);

	?>
		</td><tr>
		<tr><td>
			<B>Add Task ID:</B>&nbsp;
			<input type="text" name="task_id_dependent" size="20" maxlength="255">
			<br><i>(Fill your list using the comma as separator)</i>
		</TD>
		<TD>
			<B>Add Bug ID:</B>&nbsp;
			<input type="text" name="bug_id_dependent" size="20" maxlength="255">
			<br><i>(Fill your list using the comma as separator)</i>
		</TD></TR>
	</table>
	
	<TR><TD colspan="2" >
		<br>
		<?php echo show_dependent_bugs($bug_id,$group_id); ?>
	</TD></TR>

        <TR><TD colspan="2"><hr></td></tr>

	<TR><TD colspan="2" >
	<H3>Bug Change History <?php echo help_button('BugUpdate.html#BugHistory'); ?></H3>
		<?php echo show_bughistory($bug_id,$group_id); ?>
	</TD></TR>

	<TR><TD colspan="2" ALIGN="center">
		<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Submit Changes">
		</FORM>
	</TD></TR>

	</TABLE>

<?php

} else {

    bug_header(array ('title'=>'Modify a Bug'));
    
	echo '
	<H1>Bug Not Found</H1>';
	echo db_error();
}

bug_footer(array());

?>
