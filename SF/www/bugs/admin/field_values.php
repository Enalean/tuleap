<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX/CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
// $Id$
//
//	Originally written by Laurent Julliard 2001, 2002, CodeX Team, Xerox
//

require($DOCUMENT_ROOT.'/include/pre.php');
require('../bug_data.php');
require('../bug_utils.php');
require('./bug_admin_utils.php');
$is_admin_page='y';

if ($group_id && (user_ismember($group_id,'B2') || user_ismember($group_id,'A'))) {

    // Initialize global bug structures
    bug_init($group_id);

    if ($post_changes) {
	// A form of some sort was posted to update or create
	// an existing value

	if ($create_value) {
	    if ($value) {
		// A form was posted to create a field value
		bug_data_create_value($field,$group_id,
				      htmlspecialchars($value),
				      htmlspecialchars($description),
				      $order_id,'A');
	    } else {
		$feedback .= ' Error: empty field value not allowed!';
	    }
	    
	} else if ($create_binding) {
		// A list binding was requested
		bug_data_create_field_binding($field,$group_id,$value_function);
	} else if ($update_value) {
	    // A form was posted to update a field value
	    if ($value) {
		bug_data_update_value($fv_id, $field, $group_id,
				      htmlspecialchars($value),
				      htmlspecialchars($description),
				      $order_id,$status);
	    } else {
		$feedback .= ' Error: empty field value not allowed!';
	    }
	    
	} else if ($create_canned) {

	    // A form was posted to create a canned response
	    $sql="INSERT INTO bug_canned_responses (group_id,title,body) ".
		" VALUES ('$group_id','". htmlspecialchars($title) . 
		"','". htmlspecialchars($body) ."')";
	    $result=db_query($sql);
	    if (!$result) {
		$feedback .= ' Error inserting canned bug response! ';
		$feedback .= ' - '.db_error();
	    } else {
		$feedback .= ' Canned bug response inserted ';
	    }	    

	} else if ($update_canned) {

	    // A form was posted to update a canned response
	    $sql="UPDATE bug_canned_responses".
		"SET title='". htmlspecialchars($title) ."', body='". htmlspecialchars($body).
		"' WHERE group_id='$group_id' AND bug_canned_id='$bug_canned_id'";
	    $result=db_query($sql);
	    if (!$result) {
		$feedback .= ' Error updating canned bug response! ';
		$feedback .= ' - '.db_error();
	    } else {
		$feedback .= ' Canned bug response updated ';
	    }	    
	}

    } /* End of post_changes */


    // Display the UI form

    if ($list_value) {

	// Display the List of values for a given bug field

	$hdr = 'Manage Field Values for  \''.bug_data_get_label($field)."'";

	bug_header_admin(array ('title'=>$hdr,
				'help' => 'BTSAdministration.html#BugBrowsingBugFieldValues'));

	echo "<H2>$hdr</H2>";

	// First check that this field is used by the project and
	// it is a select box

	$is_project_scope = bug_data_is_project_scope($field);
	$vf = bug_data_get_value_function($field);

	if ( bug_data_get_field_id($field) && 
	     bug_data_is_select_box($field)) {

	    // Only show list of values if select box is not bind to a function
	    if  (!$vf) {

		$result = bug_data_get_field_predefined_values($field, $group_id,false,false,false);
		$rows = db_numrows($result);
		
		if ($result && $rows > 0) {
		    echo "\n<H3>Existing Values</H3> (Click to modify)";
		    echo format_bug_field_values($field, $group_id, $result);
		} else {
		    echo "\n<H3>No values defined yet for ".bug_data_get_label($field)."</H3>";
		}

		// Only show the add value form if this is a project scope field
		if ($is_project_scope) {

		    echo ' <P><BR> <H3>Create a new field value'.
			help_button('BTSAdministration.html#BugCreatingaBugFieldValue').'</H3>';

		    if ($ih) {
			echo "<P>Before you create a new value make sure there isn't one in the hidden list that suits your needs.";
		    }
		    
		    echo '
      <FORM ACTION="'.$PHP_SELF.'" METHOD="POST">
      <INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
      <INPUT TYPE="HIDDEN" NAME="create_value" VALUE="y">
      <INPUT TYPE="HIDDEN" NAME="list_value" VALUE="y">
      <INPUT TYPE="HIDDEN" NAME="field" VALUE="'.$field.'">
      <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
      <P><B>Value:</B><BR>
      <INPUT TYPE="TEXT" NAME="value" VALUE="" SIZE="30" MAXLENGTH="60">
      &nbsp;&nbsp;
      <B>Rank:</B>
      <INPUT TYPE="TEXT" NAME="order_id" VALUE="" SIZE="6" MAXLENGTH="6">
       &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
     <INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">';

		    if (isset($none_rk)) {
			echo "&nbsp;&nbsp;<b> (must be &gt; $none_rk)</b><BR>";
		    }
		
		    echo '
      <P>
      <B>Description:</B> (optional)<BR>
      <TEXTAREA NAME="description" ROWS="2" COLS="65" WRAP="HARD"></TEXTAREA>
      <P>
      </FORM>';
		}

		echo '<hr align ="left">
      <h3>Or Bind the field to a list of values '.help_button('BTSAdministration.html#BugBindingFieldToValueList').'</h3>';

	    } else {

		// Offer to bind the select box only if it is a custom select box
		if ($is_project_scope && bug_data_is_custom($field)) {
		    if ($vf) {
			echo '<p>This field is currently bound to a list of values.'.help_button('BTSAdministration.html#BugBindingFieldToValueList');
		    }
		}
	    }

	    echo '
      <FORM ACTION="'.$PHP_SELF.'" METHOD="POST">
      <INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
      <INPUT TYPE="HIDDEN" NAME="create_binding" VALUE="y">
      <INPUT TYPE="HIDDEN" NAME="list_value" VALUE="y">
      <INPUT TYPE="HIDDEN" NAME="field" VALUE="'.$field.'">
      <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
      Bind To: &nbsp; 
      <SELECT NAME="value_function">
      <OPTION VALUE="none" '.(isset($vf) ? '':'SELECTED').'>None</OPTION>      
      <OPTION VALUE="group_members" '.($vf == 'group_members' ? 'SELECTED':'').'>Project Members</OPTION>
      <OPTION VALUE="group_admins" '.($vf == 'group_admins' ? 'SELECTED':'').'>Project Administrators</OPTION>
      <OPTION VALUE="bug_technicians" '.($vf == 'bug_technicians' ? 'SELECTED':'').'>Bug Technicians</OPTION>
      <OPTION VALUE="bug_submitters" '.($vf == 'bug_submitters' ? 'SELECTED':'').'>Bug Submitters</OPTION>
     </SELECT>
      &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
      </FORM>';

	} else {
	    
	    echo '<H3>The Bug field you requested \''.$field.'\' is not used by your project or you are not allowed to customize it';
	}


    } else if ($update_value) {
	// Show the form to update an existing field_value
	// Display the List of values for a given bug field

	bug_header_admin(array ('title'=>'Add/Change Field Values',
			 'help' => 'BTSAdministration.html#BugUpdatingaBugFieldValue'));

	// Get all attributes of this value
	$res = bug_data_get_field_value($fv_id);
?>
      <H2>Update a field value</H2>
      <FORM ACTION="<?php echo $PHP_SELF ?>" METHOD="POST">
      <INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
      <INPUT TYPE="HIDDEN" NAME="update_value" VALUE="y">
      <INPUT TYPE="HIDDEN" NAME="list_value" VALUE="y">
      <INPUT TYPE="HIDDEN" NAME="fv_id" VALUE="<?php echo $fv_id; ?>">
      <INPUT TYPE="HIDDEN" NAME="field" VALUE="<?php echo $field; ?>">
      <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id; ?>">
      <P><B>Value:</B><BR>
      <INPUT TYPE="TEXT" NAME="value" VALUE="<?php echo db_result($res,0,'value'); ?>" SIZE="30" MAXLENGTH="60">
      &nbsp;&nbsp;
      <B>Rank:</B>
      <INPUT TYPE="TEXT" NAME="order_id" VALUE="<?php echo db_result($res,0,'order_id'); ?>" SIZE="6" MAXLENGTH="6">
      &nbsp;&nbsp;
      <B>Status:</B>
      <SELECT NAME="status">
	   <OPTION VALUE="A">Active</OPTION>
	   <OPTION VALUE="H" <?php echo ((db_result($res,0,'status') == 'H') ? ' SELECTED':'') ?> >Hidden</OPTION>
      </SELECT>
      <P>
      <B>Description:</B> (optional)<BR>
      <TEXTAREA NAME="description" ROWS="4" COLS="65" WRAP="SOFT"><?php echo db_result($res,0,'description'); ?></TEXTAREA>
      <P>
      <INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
      </FORM>

<?php


    } else if ($create_canned) {
	/*
	  Show existing responses and UI form
	*/
	bug_header_admin(array ('title'=>'Create/Modify Canned Responses'));

	echo "<H2>Create/Modify Canned Responses</H2>";
	
	$sql="SELECT bug_canned_id,title,body FROM bug_canned_responses WHERE group_id='$group_id'";
	$result=db_query($sql);
	$rows=db_numrows($result);
	echo "<P>";

	if($result && $rows > 0) {
	    /*
	      Links to update pages
	    */
	    echo "\n<H3>Existing Responses:</H3><P>";

	    $title_arr=array();
	    $title_arr[]='Title';
	    $title_arr[]='Body (extract)';
		
	    echo html_build_list_table_top ($title_arr);

	    for ($i=0; $i < $rows; $i++) {
		echo '<TR class="'. util_get_alt_row_color($i) .'">'.
		    '<TD><A HREF="'.$PHP_SELF.'?update_canned=1&bug_canned_id='.
		    db_result($result, $i, 'bug_canned_id').'&group_id='.$group_id.'">'.
		    db_result($result, $i, 'title').'</A></TD>'.
		    '<TD>'.substr(db_result($result, $i, 'body'),0,160).
		    '<b>...</b></TD></TR>';
	    }
	    echo '</TABLE>';

	} else {
	    echo "\n<H3>No canned bug responses set up yet</H3>";
	}
	/*
	  Escape to print the add response form
	*/
?>
     <h3>Create a new response</h3>
     <P>
     Creating generic quick responses can save a lot of time when giving common responses.
     <P>
     <FORM ACTION="<?php echo $PHP_SELF; ?>" METHOD="POST">
     <INPUT TYPE="HIDDEN" NAME="create_canned" VALUE="y">
     <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id; ?>">
     <INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
     <B>Title:</B><BR>
     <INPUT TYPE="TEXT" NAME="title" VALUE="" SIZE="50" MAXLENGTH="50">
     <P>
     <B>Message Body:</B><BR>
     <TEXTAREA NAME="body" ROWS="20" COLS="65" WRAP="HARD"></TEXTAREA>
     <P>
     <INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
     </FORM>
     <?php

	
    } else if ($update_canned) {
	/*
	  Allow change of canned responses
	*/
	bug_header_admin(array ('title'=>'Modify Canned Response'));

	echo "<H2>Modify Canned Response</H2>";

	$sql="SELECT bug_canned_id,title,body FROM bug_canned_responses WHERE ".
	    "group_id='$group_id' AND bug_canned_id='$bug_canned_id'";

	$result=db_query($sql);
	echo "<P>";
	if (!$result || db_numrows($result) < 1) {
	    echo "\n<H2>No such response!</H2>";
	} else {
	    /*
	      Escape to print update form
	    */
    ?>
      <P>
      Creating generic messages can save you a lot of time when giving common responses.
      <P>
      <FORM ACTION="<?php echo $PHP_SELF; ?>" METHOD="POST">
      <INPUT TYPE="HIDDEN" NAME="update_canned" VALUE="y">
      <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id; ?>">
      <INPUT TYPE="HIDDEN" NAME="bug_canned_id" VALUE="<?php echo $bug_canned_id; ?>">
      <INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
      <B>Title:</B><BR>
      <INPUT TYPE="TEXT" NAME="title" VALUE="<?php echo db_result($result,0,'title'); ?>" SIZE="50" MAXLENGTH="50">
      <P>
      <B>Message Body:</B><BR>
      <TEXTAREA NAME="body" ROWS="20" COLS="65" WRAP="HARD"><?php echo db_result($result,0,'body'); ?></TEXTAREA>
      <P>
      <INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
      </FORM>

<?php
       }

    } else {

	bug_header_admin(array ('title'=>'Bug Administration - Field Values Management',
				'help' => 'BTSAdministration.html#BugFieldValuesManagement'));
	
	echo '<H2>Manage Field values</H2>';
	echo '<p>(Click to modify)';
	
	// Loop through the list of all used fields that are project manageable
	$i=0;
	$title_arr=array();
	$title_arr[]='Field Label';
	$title_arr[]='Description';
	$title_arr[]='Scope';
	echo html_build_list_table_top ($title_arr);
	while ( $field_name = bug_list_all_fields() ) {

	    if ( bug_data_is_select_box($field_name)
		 && ($field_name != 'submitted_by') 
		 && ($field_name != 'assigned_to')
		&& bug_data_is_used($field_name) ) {

		$scope_label  = (bug_data_is_project_scope($field_name)?
				 'Project':'System');

		echo '<TR class="'. util_get_alt_row_color($i) .'">'.
		    '<TD><A HREF="'.$PHP_SELF.'?group_id='.$group_id.'&list_value=1&field='.$field_name.'">'.bug_data_get_label($field_name).'</A></td>'.
		    "\n<td>".bug_data_get_description($field_name).'</td>'.
		    "\n<td>".$scope_label.'</td>'.
		    '</tr>';
		$i++;
	    }	
	}

	// Now the special canned response field
	echo '<TR class="'. util_get_alt_row_color($i) .'">';
	echo "<td><A HREF=\"$PHP_SELF?group_id=$group_id&create_canned=1\">Canned Responses</A></td>";
	echo "\n<td>Create or Change generic quick response messages for the bug tracking tool. Theses pre-written messages can then be used to quickly reply to bug submission. </td>";
	echo "\n<td>Project</td></tr>";
	echo '</TABLE>';
    }

    bug_footer(array());

} else {

    //browse for group first message
    if (!$group_id) {
	exit_no_group();
    } else {
	exit_permission_denied();
    }

}

?>
