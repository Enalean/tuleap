<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

if ($pv) {
    help_header('Bug Detail - '.format_date($sys_datefmt,time()),false);
} else {
    bug_header(array ('title'=>'Bug Detail: '.$bug_id,
		        'help' => 'BugUpdate.html'));
}

// First check access control for updates
$res_access = db_query("SELECT bug_allow_anon FROM groups WHERE group_id=$group_id");
if (!user_isloggedin() && db_result($res_access,0,'bug_allow_anon') == 0) {
    echo '
	   <B><h2><span class="highlight">You are NOT logged in.</h2>
                 <P>This project has requested that users be logged in before submitting a bug
	   <P> Please <u><A HREF="/account/login.php?return_to='.
	  urlencode($REQUEST_URI). 
	'">log in</A></u> first.</span></B>';

    bug_footer(array());
    exit;
}

$sql="SELECT * FROM bug WHERE bug_id='$bug_id' AND group_id='$group_id'";
$fields_per_line=2;
$max_size=40;

$result=db_query($sql);

if (db_numrows($result) > 0) {
    $url = '/bugs/?func=detailbug&bug_id='.$bug_id.'&group_id='.$group_id;

    echo '<TABLE cellpadding="0" cellspacing="0" width="100%"><TR>
                 <TD valign="top">';
    echo "<H2>[ Bug #$bug_id ] ".db_result($result,0,'summary')."</H2></TD>\n";
    if (!$pv) {
	echo '<TD valign="top"><img src="'.util_get_image_theme("msg.png").'" border="0"></TD>
                  <TD align="left" valign="top">
                  <a href="'.$url.'&pv=1" target="_blank">&nbsp;Printer&nbsp;version</a>
                 </TD>';
    }
    echo '</TR>
                 </TABLE>';
?>
    <FORM ACTION="<?php echo $PHP_SELF; ?>" METHOD="POST" enctype="multipart/form-data">
 
    <INPUT TYPE="hidden" name="MAX_FILE_SIZE" value="<? echo $sys_max_size_attachment; ?>">
    <INPUT TYPE="HIDDEN" NAME="func" VALUE="postaddcomment">
    <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id; ?>">
    <INPUT TYPE="HIDDEN" NAME="bug_id" VALUE="<?php echo $bug_id; ?>">

    <TABLE CELLPADDING="0">
      <TR><TD><B>Submitted By:</B>&nbsp;<?php echo user_getname(db_result($result,0,'submitted_by')); ?></TD>
          <TD><B>Group:</B>&nbsp;<?php echo group_getname($group_id); ?></TD>
      </TR>
      <TR><TD><B>Submitted on:</B>&nbsp;<?php echo format_date($sys_datefmt,db_result($result,0,'date')); ?></TD>
          <TD>
       <?php
	  echo ($pv ? '-':'<FONT SIZE="-1"><INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Submit Changes">'); ?>
          </TD>
      </TR>
      <TR><TD COLSPAN="<?php echo $fields_per_line; ?>">&nbsp</TD></TR>

<?php
      // Now display the variable part of the field list (depend on the project)

      $i=0;
      while ( $field_name = bug_list_all_fields() ) {


	  // if the field is a special field (except summary and details) 
	  // or if not used by this project  then skip it.
	  if ( (!bug_data_is_special($field_name)  || $field_name=='summary' || $field_name=='details') &&
	       bug_data_is_used($field_name) ) {
				   
	      // display the bug field
	      // if field size is greatest than max_size chars then force it to
	      // appear alone on a new line or it won't fit in the page
	      $field_value = db_result($result,0,$field_name);
	      list($sz,) = bug_data_get_display_size($field_name);

	      $field_display = bug_field_display($field_name,$group_id,$field_value,false,true,true);
	      if ($field_name=='details') 
		  $value = util_make_links($field_display,$group_id);

	      if ($sz > $max_size) {
		  echo "\n<TR>".
		      '<TD valign="top" colspan="'.$fields_per_line.'">'.
		     $field_display.'</TD>'."\n</TR>";
		  $i=0;
	      } else {
		  echo ($i % $fields_per_line ? '':"\n<TR>");
		  echo '<TD valign="top">'.$field_display.'</TD>';
		  $i++;
		  echo ($i % $fields_per_line ? '':"\n</TR>");
	      }
	  }
      }
      
      // Now display other special fields

      // Summary first. It is a special field because it is both displayed in the
      // title of the bug form and here as a text field
?>
      <TR><TD COLSPAN="<?php echo $fields_per_line; ?>">&nbsp</TD></TR>

<?php
	  if (!$pv) {
	      echo '<TR><TD COLSPAN="'.$fields_per_line.'"><B>Add A Comment:</B><BR>';
	      echo bug_field_textarea('details','')."</TD></TR>\n";

	      echo '<TR><TD COLSPAN="'.$fields_per_line.'">';

	      if (!user_isloggedin()) {
		  echo '<BR><B><span class="highlight"><H2>You Are NOT Logged In</H2><P>Please <A HREF="/account/login.php?return_to='.
		      urlencode($REQUEST_URI).
		      '">log in,</A> so followups can be emailed to you.</span></B><P>';
	      }
	  }
?>

     </TD></TR>
     </table>

     <table>

     <TR><TD COLSPAN="<?php echo $fields_per_line; ?>">
         <h3>Follow-up Comments</h3>
        <?php echo show_bug_details($bug_id,$group_id); ?>
     </TD></TR>

     <?php if (user_isloggedin()) {
	 echo '<TR><TD COLSPAN="'.$fields_per_line.'">
                     <hr><h3>CC List '.($pv ? '':help_button('BugUpdate.html#BugCCList')).'</h3>';
	 if (!$pv) {
	   echo '<b><u>Note:</b></u> for CodeX users use their login name rather than their email addresses.<p>
	   <B>Add CC:&nbsp;</b><input type="text" name="add_cc" size="30">&nbsp;&nbsp;&nbsp;
	   <B>Comment:&nbsp;</b><input type="text" name="cc_comment" size="40" maxlength="255"><p>';
	 }
	  show_bug_cc_list($bug_id,$group_id,false,$pv);
	  echo "</TD></TR>\n";
     }
     ?>

     <TR><TD COLSPAN="<?php echo $fields_per_line; ?>">
        <hr><h3>Bug Attachments <?php if (!$pv) {echo help_button('BugUpdate.html#BugAttachments');} ?></h3>
     <?php if (!$pv) {
	 echo '<B>Check to Upload &amp; Attach File:</B> <input type="checkbox" name="add_file" VALUE="1">
                    &nbsp;&nbsp;&nbsp;
                    <input type="file" name="input_file" size="40">
                   <br><span class="smaller"><i>(The maximum upload file size is ';
         echo formatByteToMb($sys_max_size_attachment);
         echo ' Mb - <u>Please compress your files</u>)</i></span>
                   <P>
                   <B>File Description:</B>&nbsp;
                   <input type="text" name="file_description" size="60" maxlength="255">
                   <P>';
          }
	  show_bug_attached_files($bug_id,$group_id,false,$pv);
      ?>
     </TD></TR>

     <TR><TD COLSPAN="<?php echo $fields_per_line; ?>">
        <hr><h3>Bug Dependencies <?php if (!$pv) { echo help_button('BugUpdate.html#BugDependencies');} ?></h3>
     </TD></TR>

         <TR><TD VALIGN="TOP">
	<?php
		$result2=db_query("SELECT project_task.summary ".
			"FROM project_task,bug_task_dependencies ".
			"WHERE project_task.project_task_id=bug_task_dependencies.is_dependent_on_task_id ".
			"AND bug_task_dependencies.bug_id='$bug_id'");
		ShowResultSet($result2,'Dependent on Task');
	?>
	</TD><TD VALIGN="TOP">
	<?php
		$result2=db_query("SELECT bug.summary ".
			"FROM bug,bug_bug_dependencies ".
			"WHERE bug.bug_id=bug_bug_dependencies.is_dependent_on_bug_id ".
			"AND bug_bug_dependencies.bug_id='$bug_id'");
		ShowResultSet($result2,'Dependent on Bug');
	?>
	</TD></TR>

	<TR><TD COLSPAN="<?php echo $fields_per_line; ?>">
		<?php echo show_dependent_bugs($bug_id,$group_id); ?>
	</TD></TR>
 
	<TR><TD COLSPAN="<?php echo $fields_per_line; ?>">
	<hr>
	<H3>Bug Change History <?php if (!$pv) {echo help_button('BugUpdate.html#BugHistory');} ?></H3>

	<?php
	show_bughistory($bug_id,$group_id);

	?>
	</TD></TR>

	<?php if (!$pv) {
	    echo '<TR><TD COLSPAN="'.$fields_per_line.'" ALIGN="center">
	         <INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Submit Changes">';
	} ?>
	</FORM>
	</TD></TR>

	</TABLE>
	<?php

} else {

	echo '
		<H1>Bug not found</H1>
	<P>
	<B>You can get this message</B> if this Project did not create bug groups/categories. 
	An admin for this project must create bug groups/categories and then modify this bug.';

}

if ($pv)
     help_footer();
else
     bug_footer(array());

?>
