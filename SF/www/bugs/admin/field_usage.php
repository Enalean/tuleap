<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001-2002. All Rights Reserved
// http://codex.xerox.com
//
// $Id$
//
//	Originally written by Laurent Julliard 2001, 2002, CodeX Team, Xerox
//

require('pre.php');
require('../bug_data.php');
require('../bug_utils.php');

$is_admin_page='y';

if ($group_id && (user_ismember($group_id,'B2') || user_ismember($group_id,'A'))) {

    // Initialize global bug structures
    bug_init($group_id);

    if ($post_changes) {
	// A form was posted to update a field

	if ($submit) {
	    if (isset($n1) && isset($n2)) {
		$display_size = "$n1/$n2";
	    }

	    // these are checkboxes so make sure they have value 0 if needed.
	    // 0 and 'not in the form' doesn't mean the same thing. if it is not set
	    // but is in the form it means value 0, if it is not set and not in the form
	    // it means use the system default.
	    if (!isset($keep_history) && $keep_history_here) {
		$keep_history = 0;
	    }
	    if (!isset($empty_ok) && $empty_ok_here) {
		$empty_ok = 0;
	    }

	    bug_data_update_usage($field,$group_id,$label,$description,
				  $status,$place,$display_size,$empty_ok,$keep_history,
				  $show_on_add_members,$show_on_add);
	} else if ($reset) {
	    bug_data_reset_usage($field,$group_id);
	}
	// force a re-initialization of the global structure after
	// the update and before we redisplay the field list
	bug_init($group_id);
     }

    
    if ($update_field) {

	// Show the form to change a field setting
	
	if (bug_data_is_custom($field))
	    $help = 'BTSAdministration.html#CustomBugFields';
	else
	    $help = 'BTSAdministration.html#StandardBugFields';

	bug_header_admin(array ('title'=>'Bug Administration - Modify Field Usage',
				'help' => $help));

	// Escape to display the form	    
?>
	    
      <H2>Modify a bug field</H2>
      <FORM ACTION="<?php echo $PHP_SELF ?>" METHOD="POST">
      <INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
      <INPUT TYPE="HIDDEN" NAME="field" VALUE="<?php echo $field; ?>">
      <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id; ?>">
      <P><h3>Field Label: 

<?php

     // If it is a custom field let the user change the label and description
     if (bug_data_is_custom($field)) {
	 echo '<INPUT TYPE="TEXT" NAME="label" VALUE="'.bug_data_get_label($field).
	 '" SIZE="20" MAXLENGTH="40"></h3>';
	 echo '<B>Description: </B>';
	 echo '<INPUT TYPE="TEXT" NAME="description" VALUE="'.bug_data_get_description($field).
	 '" SIZE="70" MAXLENGTH="255"><P>';	 
     } else {
	 echo bug_data_get_label($field)."</h3>\n";
     }

      echo '<B>Rank on screen:</B>';
      
      echo '<INPUT TYPE="TEXT" NAME="place" VALUE="'.bug_data_get_place($field).
	  '" SIZE="6" MAXLENGTH="6">&nbsp;&nbsp;'."\n";
      echo '<b>Status:</b>';
      // Display the Usage box (Used, Unused select box  or hardcoded "required')
      if (bug_data_is_required($field)) {
	  echo 'Required';
	  echo '<INPUT TYPE="HIDDEN" NAME="status" VALUE="1">';
      } else {
	  echo '<SELECT NAME="status">
	   <OPTION VALUE="1"'.(bug_data_is_used($field)?' SELECTED':'').'>Used</OPTION>
	   <OPTION VALUE="0"'.(bug_data_is_used($field)?'':' SELECTED').'>Unused</OPTION>
      </SELECT>';
      }

      // Customize field size only for text fields and text areas. 
      if (bug_data_is_text_field($field)) {
	  list($size,$maxlength) = bug_data_get_display_size($field);
	  echo '<P><b>Field Size...</b> (in characters)<ul>';
	  echo '<li>Visible Field Size: ';
	  echo '<INPUT TYPE="TEXT" NAME="n1" VALUE="'.$size.
	      '" SIZE="3" MAXLENGTH="3">&nbsp;&nbsp;';
	  echo '<li>Maximum length: ';
	  echo '<INPUT TYPE="TEXT" NAME="n2" VALUE="'.$maxlength.
	      '" SIZE="3" MAXLENGTH="3">&nbsp;&nbsp; (up to  255)</ul>';
      } else if (bug_data_is_text_area($field)) {
	  list($rows,$cols) = bug_data_get_display_size($field);
	  echo '<P><b>Field Size...</b><ul>';
	  echo '<li>Number of rows&nbsp;&nbsp;: ';
	  echo '<INPUT TYPE="TEXT" NAME="n1" VALUE="'.$rows.
	      '" SIZE="3" MAXLENGTH="3">&nbsp;&nbsp;';
	  echo '<li>Number of columns: ';
	  echo '<INPUT TYPE="TEXT" NAME="n2" VALUE="'.$cols.
	      '" SIZE="3" MAXLENGTH="3"></ul>';
      }
      // Remark: Date fields have a fixed size that cannot be changed
      

      // Customize Properties
      if (!bug_data_is_special($field)) { 
	  echo '<P><b>Properties...</b><ul>';
	  echo '<li>Allow Empty Value: ';
	  echo '<INPUT TYPE="CHECKBOX" NAME="empty_ok" VALUE="1" '.
	      (bug_data_is_empty_ok($field)?' CHECKED':'').'>&nbsp;&nbsp;';
	  echo '<INPUT TYPE="HIDDEN" NAME="empty_ok_here" VALUE="1">';
	  echo '<li>Keep Change History: ';
	  echo '<INPUT TYPE="CHECKBOX" NAME="keep_history" VALUE="1" '.
	      (bug_data_get_keep_history($field)?' CHECKED':'').'></ul>';
	  echo '<INPUT TYPE="HIDDEN" NAME="keep_history_here" VALUE="1">';
      }
      
      // Customize screen presence
      // It can be customized unless the field is special
      if (!bug_data_is_special($field)) {
	  
	  $addm_html = '&nbsp;&nbsp;<INPUT TYPE="CHECKBOX" NAME="show_on_add_members" VALUE="1"'.
	      (bug_data_is_showed_on_add_members($field)?' CHECKED':'').'>';
	  
	  $add_html = '&nbsp;&nbsp;<INPUT TYPE="CHECKBOX" NAME="show_on_add" VALUE="1"'.
	      (bug_data_is_showed_on_add($field)?' CHECKED':'').'>';
	  
      } else {
	  
	  // Do not let the user change these field settings but put them in the
	  // form to preserve the existing setting or use the default values
	  // imposed at the system level
	  $addm_html = '<INPUT TYPE="HIDDEN" NAME="show_on_add_members" '.
	      'VALUE="'.(bug_data_is_showed_on_add_members($field)? 1:0).'">'.
	      (bug_data_is_showed_on_add_members($field)? ': Always':': Never');
	  $add_html = '<INPUT TYPE="HIDDEN" NAME="show_on_add" '.
	      'VALUE="'.(bug_data_is_showed_on_add($field)? 1:0).'">'.
	      (bug_data_is_showed_on_add($field)? ': Always':': Never');
      }

     echo '<P><b>Display this field...</b><ul>';
     echo '<li>On the submission form used by project members'.$addm_html;
     echo '<li>On the submission form used by other users'.$add_html;


?>
      </ul>
      <P>
      <INPUT TYPE="SUBMIT" NAME="submit" VALUE="SUBMIT">
      &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
      <INPUT TYPE="SUBMIT" NAME="reset" VALUE="RESET TO DEFAULTS">
      </form>

<?php	  	
        
	bug_footer(array());

    } else {

	/*
	  Show main page
	*/
 
	bug_header_admin(array ('title'=>'Bug Administration - Field Usage',
				'help' => 'BTSAdministration.html#BugFieldUsageManagement'));
    
	echo '<H2>Bug Field Usage Administration</H2>';

	echo '<h3>List of all Available Fields</h3>';
	echo '<p>(Click to modify)';


	// Show all the fields currently available in the system
	$i=0;
	$title_arr=array();
	$title_arr[]='Field Label';
	$title_arr[]='Type';
	$title_arr[]='Description';
	$title_arr[]='Rank<br>on screen';
	$title_arr[]='Scope';
	$title_arr[]='Status';

	$hdr = html_build_list_table_top ($title_arr);

	// Build HTML ouput for  Used fields first and Unused field second
	$iu=$in=$inc=0;
	while ( $field_name = bug_list_all_fields() ) {

	    // Do not show some special fields any way in the list
	    // because there is nothing to customize in them
	    if (($field_name == 'group_id') ||
		($field_name == 'comment_type_id') || 
		($field_name == 'bug_id') || 
		($field_name == 'date') || 
		($field_name == 'close_date') || 
		($field_name == 'submitted_by') )
		{ continue; }

	    // Show Used, Unused and Required fields on separate lists
	    // SHow Unused Custom field in a separate list at the very end
	    $is_required = bug_data_is_required($field_name);
	    $is_custom = bug_data_is_custom($field_name);

	    $is_used = bug_data_is_used($field_name);
	    $status_label = ($is_required?'Required':($is_used?'Used':'Unused'));
	    
	    $scope_label  = (bug_data_get_scope($field_name)=='S'?
			     'System':'Project');
	    $place_label = ($is_used?bug_data_get_place($field_name):'-');

	    $html = '<TD><A HREF="'.$PHP_SELF.'?group_id='.$group_id.
		'&update_field=1&field='.$field_name.'">'.
		bug_data_get_label($field_name).'</A></td>'.
		"\n<td>".bug_data_get_display_type_in_clear($field_name).'</td>'.
		"\n<td>".bug_data_get_description($field_name).
		(($is_custom && $is_used) ? ' - <b>[Custom Field]</b>':'').'</td>'.
		"\n<td align =\"center\">".$place_label.'</td>'.
		"\n<td align =\"center\">".$scope_label.'</td>'.
		"\n<td align =\"center\">".$status_label.'</td>';
	    
	    if ($is_used) {
		$html = '<TR class="'. 
		    util_get_alt_row_color($iu) .'">'.$html.'</tr>';
		$iu++;
		$hu .= $html;
	    } else {
		if ($is_custom) {
		    $html = '<TR class="'. 
			util_get_alt_row_color($inc) .'">'.$html.'</tr>';
		    $inc++;
		    $hnc .= $html;
		} else {
		    $html = '<TR class="'. 
			util_get_alt_row_color($in) .'">'.$html.'</tr>';
		    $in++;
		    $hn .= $html;
		}
	    }
		
	} /* end while all fields */

	// Now print the HTML table
	if ($iu == 0) {
	    $html = '<p>No extension field in use. Choose one below.<p>'.$html;
	} else {
	    $hu= '<tr><td colspan="5"><center><b>---- USED FIELDS ----</b></center></tr>'.$hu;  
	    if ($in) {
		$hn = '<tr><td colspan="5"> &nbsp;</td></tr>'.
		    '<tr><td colspan="5"><center><b>---- UNUSED STANDARD FIELDS ----</b></center></tr>'.$hn;
	    }

	    if ($inc) {
		$hnc = '<tr><td colspan="5"> &nbsp;</td></tr>'.
		    '<tr><td colspan="5"><center><b>---- UNUSED CUSTOM FIELDS ----</b></center></tr>'.$hnc;
	    }
	}
	echo $hdr.$hu.$hn.$hnc.'</TABLE>';

?>

<?php
        
	bug_footer(array());
    }


} else {

    //browse for group first message

    if (!$group_id) {
	exit_no_group();
    } else {
	exit_permission_denied();
    }

}
?>
