<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

bug_header(array ('title'=>'Modify a Bug'));

$sql="SELECT * FROM bug WHERE bug_id='$bug_id' AND group_id='$group_id'";
$fields_per_line=2;

$result=db_query($sql);

if (db_numrows($result) > 0) {

    // First display some  internal fields - Cannot be modified by the user
?>
    <H2>[ Bug #<?php echo $bug_id.' ] '.db_result($result,0,'summary');?></H2>

    <FORM ACTION="<?php echo $PHP_SELF; ?>" METHOD="POST">
    <INPUT TYPE="HIDDEN" NAME="func" VALUE="postmodbug">
    <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id;; ?>">
    <INPUT TYPE="HIDDEN" NAME="bug_id" VALUE="<?php echo $bug_id; ?>">

    <TABLE WIDTH="100%" cellpadding="0">
      <TR><TD><B>Submitted By:</B>&nbsp;<?php echo user_getname(db_result($result,0,'submitted_by')); ?></TD>
          <TD><B>Group:</B>&nbsp;<?php echo group_getname($group_id); ?></TD>
      </TR>
      <TR><TD><B>Submitted on:</B>&nbsp;<?php  echo date($sys_datefmt,db_result($result,0,'date')); ?></TD>
          <TD><FONT SIZE="-1"><INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Submit Changes"></TD>
      </TR>

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
	      echo '<TD>'.bug_field_display($field_name,$group_id,$field_value).'</TD>';
	      $i++;
	      echo ($i % $fields_per_line ? '':"\n</TR>");
	  }
      }
      
      // Now display other special fields

      // Summary first. It is a special field because it is both displayed in the
      // title of the bug form and here as a text field
?>
      <TR><TD colspan="<?php echo $fields_per_line; ?>">
<?php echo bug_field_display('summary',$group_id,db_result($result,0,'summary')); ?>
      </td></tr>

      <TR><TD colspan="<?php echo $fields_per_line; ?>" align="top"><HR></td></TR>
      <TR><TD COLSPAN="<?php echo $fields_per_line; ?>"><B>Use a Canned Response:</B>&nbsp;
      <?php
      echo bug_canned_response_box ($group_id,'canned_response');
      echo '&nbsp;&nbsp;&nbsp;<A HREF="/bugs/admin/index.php?group_id='.$group_id.'&create_canned=1">Or define a new Canned Response</A><P>';
      ?>
      </TD></TR>


      <TR><TD COLSPAN="<?php echo $fields_per_line; ?>">
      <P><B>Or post a followup comment of any given type:</B>
      <?php echo bug_field_box('comment_type_id','',$group_id,'',true,'None'); ?><BR>
      <?php echo bug_field_textarea('details',''); ?>
      <P>
      <B>Original Submission:</B><BR>
      <?php
      echo util_make_links(nl2br(db_result($result,0,'details')));
      echo "<P>";
      echo show_bug_details($bug_id); 
      ?>
      </TD></TR>
      <TR><TD colspan="<?php echo $fields_per_line; ?>"><HR NoShade></td></TR>

	<TR><TD VALIGN="TOP">
	<B>Dependent on Task:</B><BR>
	<?php 
	/*
		Dependent on Task........
	*/

	echo bug_multiple_task_depend_box ('dependent_on_task[]',$group_id,$bug_id);

	?>
	</TD><TD VALIGN="TOP">
	<B>Dependent on Bug:</B><BR>
	<?php
	/*
		Dependent on Bug........
	*/
	echo bug_multiple_bug_depend_box ('dependent_on_bug[]',$group_id,$bug_id)

	?>
	</TD></TR>

	<TR><TD COLSPAN="<?php echo $fields_per_line; ?>">
		<?php echo show_dependent_bugs($bug_id,$group_id); ?>
	</TD></TR>

	<TR><TD COLSPAN="<?php echo $fields_per_line; ?>">
		<?php echo show_bughistory($bug_id,$group_id); ?>
	</TD></TR>

	<TR><TD COLSPAN="<?php echo $fields_per_line; ?>" ALIGN="MIDDLE">
		<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Submit Changes">
		</FORM>
	</TD></TR>

	</TABLE>

<?php

} else {

	echo '
	<H1>Bug Not Found</H1>';
	echo db_error();
}

bug_footer(array());

?>
