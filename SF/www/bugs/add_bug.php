<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

bug_header(array ('title'=>'Submit a Bug'));
$fields_per_line=2;
$max_size = 40;

// First display the message preamble
$res_preamble  = db_query("SELECT bug_preamble FROM groups WHERE group_id=$group_id");

echo util_unconvert_htmlspecialchars(db_result($res_preamble,0,'bug_preamble'));

// Beginning of the submission form with fixed fields
echo '<FORM ACTION="'.$PHP_SELF.'" METHOD="POST" enctype="multipart/form-data" NAME="bug_form">
	<INPUT TYPE="HIDDEN" NAME="func" VALUE="postaddbug">
	<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
	<TABLE cellpadding="0">
	<TR><TD VALIGN="TOP" COLSPAN="'.(2*$fields_per_line).'">
                  <B>Group:</B>&nbsp;'.group_getname($group_id).'</TD></TR>
                 <script language="JavaScript" src="/include/calendar.js"></script>';



// Now display the variable part of the field list (depend on the project)

$i=0;
$is_bugadmin = user_ismember($group_id,'B2');

while ( $field_name = bug_list_all_fields() ) {

    // if the field is a special field or if not used by this project 
    // then skip it. Plus only show fields allowed on the bug submit_form 
    if ( !bug_data_is_special($field_name) &&
	 bug_data_is_used($field_name) ) {

	if  (($is_bugadmin && bug_data_is_showed_on_add_members($field_name)) ||
	     (!$is_bugadmin && bug_data_is_showed_on_add($field_name)) ) {
	    
	    // display the bug field with its default value
	    // if field size is greatest than max_size chars then force it to
	    // appear alone on a new line or it won't fit in the page
	    $field_value = bug_data_get_default_value($field_name);

	    list($sz,) = bug_data_get_display_size($field_name);
	    if ($sz > $max_size) {
		echo "\n<TR>".
		    '<TD valign="middle">'.bug_field_label_display($field_name,$group_id,false,false).'</td>'.
		    '<TD valign="middle" colspan="'.(2*$fields_per_line-1).'">'.
		    bug_field_display($field_name,$group_id,$field_value,false,false).'</TD>'.		      
		    "\n</TR>";
		$i=0;
	    } else {
		echo ($i % $fields_per_line ? '':"\n<TR>");
		  echo '<TD valign="middle">'.bug_field_label_display($field_name,$group_id,false,false).'</td>'.
		      '<TD valign="middle">'.bug_field_display($field_name,$group_id,$field_value,false,false).'</TD>';
		$i++;
		echo ($i % $fields_per_line ? '':"\n</TR>");
	    }
	}
    }
}

	     
// Then display all mandatory fields 

?>
      <TR><TD colspan="<?php echo 2*$fields_per_line; ?>">
<?php echo bug_field_display('summary',$group_id,'',true); ?></td></tr>

      <TR><TD colspan="<?php echo 2*$fields_per_line; ?>">
<?php echo bug_field_display('details',$group_id,'',true); ?></td></tr>

      <TR><TD colspan="<?php echo 2*$fields_per_line; ?>">

	<?php
	if (!user_isloggedin()) {
		echo '
		<B><h2><FONT COLOR="RED">You Are NOT logged in.</H2>
		<P> Please <A HREF="/account/login.php?return_to='.
		urlencode($REQUEST_URI).
		'">log in,</A> so followups can be emailed to you.</FONT></B>';
	}
	?>

      <hr><h4>Optionally, you may also attach a file (e.g. a screenshot, a log file,...)</h4>
      <B>Check to Upload &amp; Attach File:</B> <input type="checkbox" name="add_file" VALUE="1">
      &nbsp;&nbsp;&nbsp;
      <input type="file" name="input_file" size="40">
      <P>
      <B>File Description:</B>&nbsp;
      <input type="text" name="file_description" size="60" maxlength="255">
      </TR></TD>

<TR><TD COLSPAN="<?php echo 2*$fields_per_line; ?>">
	<P>
	<hr>
	<B><FONT COLOR="RED">Did you check to see if this bug has already been submitted?</FONT></b> (use the search box in the left menu pane)
	<P><center>
	<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
	</center>
	</FORM>
</TD></TR>

</TABLE>

<?php

bug_footer(array());

?>
