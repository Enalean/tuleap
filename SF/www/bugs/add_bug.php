<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

bug_header(array ('title'=>'Submit a Bug'));
$fields_per_line=2;

// First display the message preamble
$res_preamble  = db_query("SELECT bug_preamble FROM groups WHERE group_id=$group_id");

echo util_unconvert_htmlspecialchars(db_result($res_preamble,0,'bug_preamble'));

// Beginning of the submission form with fixed fields
echo '<FORM ACTION="'.$PHP_SELF.'" METHOD="POST" enctype="multipart/form-data">
	<INPUT TYPE="HIDDEN" NAME="func" VALUE="postaddbug">
	<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
	<TABLE>
	<TR><TD VALIGN="TOP" COLSPAN="'.$fields_per_line.'">
              <B>Group:</B>&nbsp;'.group_getname($group_id).'</TD></TR>';



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
	    $field_value = bug_data_get_default_value($field_name);

	    echo ($i % $fields_per_line ? '':"\n<TR>");
	    echo '<TD valign="top">'.bug_field_display($field_name,$group_id,$field_value).'</TD>';
	    $i++;
	    echo ($i % $fields_per_line ? '':"\n</TR>");
	}
    }
}

	     
// Then display all mandatory fields 

?>
      <TR><TD colspan="<?php echo $fields_per_line; ?>">
<?php echo bug_field_display('summary',$group_id,'',true); ?></td></tr>

      <TR><TD colspan="<?php echo $fields_per_line; ?>">
<?php echo bug_field_display('details',$group_id,'',true); ?></td></tr>

      <TR><TD colspan="<?php echo $fields_per_line; ?>">
      <hr><h4>Optionally, you may also attach a file (e.g. a screenshot, a log file,...)</h4>
      <B>Check to Upload &amp; Attach File:</B> <input type="checkbox" name="add_file" VALUE="1">
      &nbsp;&nbsp;&nbsp;
      <input type="file" name="input_file" size="40">
      <P>
      <B>File Description:</B>&nbsp;
      <input type="text" name="file_description" size="60" maxlength="255">
      </TR></TD>

<TR><TD COLSPAN="<?php echo $fields_per_line; ?>">
	<?php
	if (!user_isloggedin()) {
		echo '
		<h3><FONT COLOR="RED">You Are NOT logged in.</H3>
		<P> Please <A HREF="/account/login.php?return_to='.
		urlencode($REQUEST_URI).
		'">log in,</A> so followups can be emailed to you.</FONT></B>';
	}
	?>

	<P>
	<hr>
	<B><FONT COLOR="RED">Did you check to see if this bug has already been submitted?</FONT></b> (use the search box in the left menu pane)
	<P>
	<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
	<P>
	</FORM>
</TD></TR>

</TABLE>

<?php

bug_footer(array());

?>
