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
	    bug_data_update_usage($field,$group_id,$status,$place,
				  $show_on_add_members, $show_on_add);
	} else if ($reset) {
	    bug_data_reset_usage($field,$group_id);
	}
	// force a re-initialization of the global structure after
	// the update and before we redisplay the field list
	bug_init($group_id);
     }

    
    if ($update_field) {

	// Show the form to change a field setting
	
	bug_header_admin(array ('title'=>'Bug Administration - Modify Field Usage'));

	// Escape to display the form
?>

      <H2>Modify a bug field <?php echo help_button('bug_admin_field_usage_settings',false); ?></H2>
      <FORM ACTION="<?php echo $PHP_SELF ?>" METHOD="POST">
      <INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
      <INPUT TYPE="HIDDEN" NAME="field" VALUE="<?php echo $field; ?>">
      <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id; ?>">
      <P><h3>Field Label: <?php echo bug_data_get_label($field); ?></h3>
      <B>Rank on screen:</B>
      
      <INPUT TYPE="TEXT" NAME="place" VALUE="<?php echo bug_data_get_place($field); ?>" SIZE="6" MAXLENGTH="6">
      &nbsp;&nbsp;
      <b>Status:</b>
<?php 

     if (bug_data_is_required($field)) {
	 echo 'Required';
	 echo '<INPUT TYPE="HIDDEN" NAME="status" VALUE="1">';
     } else {
	 echo '<SELECT NAME="status">
	   <OPTION VALUE="1"'.(bug_data_is_used($field)?' SELECTED':'').'>Used</OPTION>
	   <OPTION VALUE="0"'.(bug_data_is_used($field)?'':' SELECTED').'>Unused</OPTION>
      </SELECT>';
     }


	 if (!bug_data_is_required($field) && 
	     !bug_data_is_special($field_name)) {

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
 
	bug_header_admin(array ('title'=>'Bug Administration - Field Usage'));
    
	echo '<H2>Bug Field Usage Administration '.help_button('bug_admin_field_usage_list',false).'</H2>';

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
	$iu=$in=0;
	while ( $field_name = bug_list_all_fields() ) {

	    // Do not show some special fields any way
	    if (bug_data_is_special($field_name)) { 
		if ( ($field_name == 'group_id') ||
		     ($field_name == 'comment_type_id') )
		    { continue; }
	    }

	    // Show Used, Unused and Required fields on separate lists
	    // Do not show required fields any way.
	    $is_required = bug_data_is_required($field_name);

	    $is_used = bug_data_is_used($field_name);
	    $status_label = ($is_required?'Required':($is_used?'Used':'Unused'));
	    
	    $scope_label  = (bug_data_get_scope($field_name)=='S'?
			     'System':'Project');
	    $place_label = ($is_used?bug_data_get_place($field_name):'-');

	    $html = '<TD><A HREF="'.$PHP_SELF.'?group_id='.$group_id.
		'&update_field=1&field='.$field_name.'">'.
		bug_data_get_label($field_name).'</A></td>'.
		"\n<td>".bug_data_get_display_type_in_clear($field_name).'</td>'.
		"\n<td>".bug_data_get_description($field_name).'</td>'.
		"\n<td align =\"center\">".$place_label.'</td>'.
		"\n<td align =\"center\">".$scope_label.'</td>'.
		"\n<td align =\"center\">".$status_label.'</td>';
	    
	    if ($is_used) {
		$html = '<TR BGCOLOR="'. 
		    util_get_alt_row_color($iu) .'">'.$html.'</tr>';
		$iu++;
		$hu .= $html;
	    } else {
		$html = '<TR BGCOLOR="'. 
		    util_get_alt_row_color($in) .'">'.$html.'</tr>';
		$in++;
		$hn .= $html;
	    }
		
	} /* end while all fields */

	// Now print the HTML table
	if ($iu == 0) {
	    $html = '<p>No extension field in use. Choose one below.<p>'.$html;
	} else {
	    $hu= '<tr><td colspan="5"><center><b>---- USED FIELDS ----</b></center></tr>'.$hu;  
	    if ($in) {
		$hn = '<tr><td colspan="5"> &nbsp;</td></tr>'.
		    '<tr><td colspan="5"><center><b>---- UNUSED FIELDS ----</b></center></tr>'.$hn;
	    }
	}
	echo $hdr.$hu.$hn.'</TABLE>';

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
