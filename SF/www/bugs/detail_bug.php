<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

bug_header(array ('title'=>'Bug Detail: '.$bug_id));

$sql="SELECT * FROM bug WHERE bug_id='$bug_id' AND group_id='$group_id'";
$fields_per_line=2;

$result=db_query($sql);

if (db_numrows($result) > 0) {

?>
    <H2>[ Bug #<?php echo $bug_id.' ] '.db_result($result,0,'summary');?></H2>

    <TABLE CELLPADDING="0" WIDTH="100%">
      <TR><TD><B>Submitted By:</B>&nbsp;<?php echo user_getname(db_result($result,0,'submitted_by')); ?></TD>
          <TD><B>Group:</B>&nbsp;<?php echo group_getname($group_id); ?></TD>
      </TR>
      <TR><TD><B>Submitted on:</B>&nbsp;<?php echo date($sys_datefmt,db_result($result,0,'date')); ?></TD>
          <TD><FONT SIZE="-1"><INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Submit Changes"></TD>
      </TR>
      <TR><TD COLSPAN="<?php echo $fields_per_line; ?>">&nbsp</TD></TR>

<?php
      // Now display the variable part of the field list (depend on the project)

      $i=0;
      while ( $field_name = bug_list_all_fields() ) {

	  // if the field is a special field or if not used byt his project 
	  // then skip it.
	  if ( !bug_data_is_special($field_name) &&
	       bug_data_is_used($field_name) ) {
				   
	      // display the bug field
	      $field_value = db_result($result,0,$field_name);
	      echo ($i % $fields_per_line ? '':"\n<TR>");
	      echo '<TD>'.bug_field_display($field_name,$group_id,$field_value,false,true).'</TD>';
	      $i++;
	      echo ($i % $fields_per_line ? '':"\n</TR>");
	  }
      }
      
      // Now display other special fields

      // Summary first. It is a special field because it is both displayed in the
      // title of the bug form and here as a text field
?>
      <TR><TD COLSPAN="<?php echo $fields_per_line; ?>">&nbsp</TD></TR>

     <TR><TD COLSPAN="<?php echo $fields_per_line; ?>">
<?php echo bug_field_display('summary',$group_id,db_result($result,0,'summary'),false,true); ?>
     </TD></TR>

     <TR><TD COLSPAN="<?php echo $fields_per_line; ?>">
<?php echo bug_field_display('details',$group_id,
			util_make_links(nl2br(db_result($result,0,'details'))),true,true); ?>
     </TD></TR>

     <FORM ACTION="<?php echo $PHP_SELF; ?>" METHOD="POST">
         <INPUT TYPE="HIDDEN" NAME="func" VALUE="postaddcomment">
         <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id; ?>">
         <INPUT TYPE="HIDDEN" NAME="bug_id" VALUE="<?php echo $bug_id; ?>">

         <TR><TD COLSPAN="<?php echo $fields_per_line; ?>"><B>Add A Comment:</B><BR>
     <?php echo bug_field_textarea('details',''); ?>
         </TD></TR>

         <TR><TD COLSPAN="<?php echo $fields_per_line; ?>">

<?php
	if (!user_isloggedin()) {
		echo '<BR><B><FONT COLOR="RED"><H2>You Are NOT Logged In</H2><P>Please <A HREF="/account/login.php?return_to='.
		urlencode($REQUEST_URI).
		'">log in,</A> so followups can be emailed to you.</FONT></B><P>';
	}
?>

     <INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
     </FORM>
     </TD></TR>
     <P>

     <TR><TD COLSPAN="<?php echo $fields_per_line; ?>">
        <?php echo show_bug_details($bug_id); ?>

         <TR><TD VALIGN="TOP">
	<?php
		$result2=db_query("SELECT bug.summary ".
			"FROM bug,bug_bug_dependencies ".
			"WHERE bug.bug_id=bug_bug_dependencies.is_dependent_on_bug_id ".
			"AND bug_bug_dependencies.bug_id='$bug_id'");
		ShowResultSet($result2,'Dependent on Bug');
	?>
	</TD><TD VALIGN="TOP">
	<?php
		$result2=db_query("SELECT project_task.summary ".
			"FROM project_task,bug_task_dependencies ".
			"WHERE project_task.project_task_id=bug_task_dependencies.is_dependent_on_task_id ".
			"AND bug_task_dependencies.bug_id='$bug_id'");
		ShowResultSet($result2,'Dependent on Task');
	?>
	</TD></TR>

	<TR><TD COLSPAN="<?php echo $fields_per_line; ?>">
		<?php echo show_dependent_bugs($bug_id,$group_id); ?>
	</TD></TR>
 
	<TR><TD COLSPAN="<?php echo $fields_per_line; ?>">
	<?php

	show_bughistory($bug_id,$group_id);

	?>
	</TD></TR></TABLE>
	<?php

} else {

	echo '
		<H1>Bug not found</H1>
	<P>
	<B>You can get this message</B> if this Project did not create bug groups/categories. 
	An admin for this project must create bug groups/categories and then modify this bug.';

}

bug_footer(array());

?>
