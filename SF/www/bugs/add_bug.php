<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

bug_header(array ('title'=>'Submit a Bug',
		  'help' => 'BugSubmission.html'));
$fields_per_line=2;
$max_size = 40;

// First check access control and display the message preamble if ok
$res = db_query("SELECT bug_preamble,bug_allow_anon FROM groups WHERE group_id=$group_id");

if (!user_isloggedin() && db_result($res,0,'bug_allow_anon') == 0) {
    echo '
	   <B><h2><span class="highlight">You are NOT logged in.</h2>
                 <P>This project has requested that users be logged in before submitting a bug
	   <P> Please <u><A HREF="/account/login.php?return_to='.
	  urlencode($REQUEST_URI). 
	'">log in</A></u> first.</span></B>';

    bug_footer(array());
    exit;
}

echo util_unconvert_htmlspecialchars(db_result($res,0,'bug_preamble'));

// Beginning of the submission form with fixed fields
echo '<FORM ACTION="'.$PHP_SELF.'" METHOD="POST" enctype="multipart/form-data" NAME="bug_form">
    <INPUT TYPE="hidden" name="MAX_FILE_SIZE" value="'.$sys_max_size_attachment.'">
	<INPUT TYPE="HIDDEN" NAME="func" VALUE="postaddbug">
	<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
	<TABLE cellpadding="0">
	<TR><TD VALIGN="TOP" COLSPAN="'.(2*$fields_per_line).'">
                  <B>Group:</B>&nbsp;'.group_getname($group_id).'</TD></TR>
                 <script language="JavaScript" src="/scripts/calendar.js"></script>';



// Now display the variable part of the field list (depend on the project)

$i=0;
$is_member = user_ismember($group_id);

while ( $field_name = bug_list_all_fields() ) {

    // if the field is a special field (except summary and original description)
    // or if not used by this project  then skip it. 
    // Plus only show fields allowed on the bug submit_form 
    if ( (!bug_data_is_special($field_name) || $field_name=='summary' || $field_name=='details') &&
	 bug_data_is_used($field_name) ) {

	if  (($is_member && bug_data_is_showed_on_add_members($field_name)) ||
	     (!$is_member && bug_data_is_showed_on_add($field_name)) ) {
	    
	    // display the bug field with its default value
	    // if field size is greatest than max_size chars then force it to
	    // appear alone on a new line or it won't fit in the page
	    $field_value = bug_data_get_default_value($field_name);
	    list($sz,) = bug_data_get_display_size($field_name);
	    $label = bug_field_label_display($field_name,$group_id,false,false);
	    $value = bug_field_display($field_name,$group_id,$field_value,false,false);
	    $star = (bug_data_is_empty_ok($field_name) ? '':'<span class="highlight"><big>*</big></b></span>');

	    if ($sz > $max_size) {
		echo "\n<TR>".
		    '<TD valign="middle">'.$label.$star.'</td>'.
		    '<TD valign="middle" colspan="'.(2*$fields_per_line-1).'">'.
		    $value.'</TD>'.		      
		    "\n</TR>";
		$i=0;
	    } else {
		echo ($i % $fields_per_line ? '':"\n<TR>");
		  echo '<TD valign="middle">'.$label.$star.'</td>'.
		      '<TD valign="middle">'.$value.'</TD>';
		$i++;
		echo ($i % $fields_per_line ? '':"\n</TR>");
	    }
	}
    }
}

	     
// Then display all mandatory fields 

?>
     <TR><TD colspan="<?php echo 2*$fields_per_line; ?>"><hr>

      <h3>CC List <?php echo help_button('BugUpdate.html#BugCCList'); ?></h3>
	  <b><u>Note:</b></u> for CodeX users use their login name rather than their email addresses.<p>
	  <B>Add CC:&nbsp;</b><input type="text" name="add_cc" size="30">&nbsp;&nbsp;&nbsp;
	  <B>Comment:&nbsp;</b><input type="text" name="cc_comment" size="40" maxlength="255"><p>
      </TD></TR>


      <TR><TD colspan="<?php echo 2*$fields_per_line; ?>">

	<?php
	if (!user_isloggedin()) {
		echo '
		<B><h2><span class="highlight">You Are NOT logged in.</H2>
		<P> Please <A HREF="/account/login.php?return_to='.
		urlencode($REQUEST_URI).
		'">log in,</A> so followups can be emailed to you.</span></B>';
	}
	?>

      <hr><h3>Bug Attachments <?php echo help_button('BugUpdate.html#BugAttachments'); ?></h3>
       <p>Optionally, you may also attach a file (e.g. a screenshot, a log file,...)</p>
      <B>Check to Upload &amp; Attach File:</B> <input type="checkbox" name="add_file" VALUE="1">
      &nbsp;&nbsp;&nbsp;
      <input type="file" name="input_file" size="40">
      <br><span class="small"><i>(The maximum upload file size is <?php echo formatByteToMb($sys_max_size_attachment); ?> Mb - <u>Please compress your files</u>)</i></span>
      <P>
      <B>File Description:</B>&nbsp;
      <input type="text" name="file_description" size="60" maxlength="255">
      </TR></TD>

<TR><TD COLSPAN="<?php echo 2*$fields_per_line; ?>">
	<P>
	<hr>
	<B><span class="highlight">Did you check to see if this bug has already been submitted?</span></b> (use the search box in the left menu pane)
	<P><center>
	<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
	</center>
	</FORM>
</TD></TR>

</TABLE>

<?php

bug_footer(array());

?>
