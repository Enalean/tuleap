<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

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
				  $show_on_query,$show_on_result,
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

      <H2>Modify a bug field</H2>
      <FORM ACTION="<?php echo $PHP_SELF ?>" METHOD="POST">
      <INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
      <INPUT TYPE="HIDDEN" NAME="field" VALUE="<?php echo $field; ?>">
      <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id; ?>">
      <P><h3>Field Label: <?php echo bug_data_get_label($field); ?></h3>
      <B>Rank on screen:</B>
      <INPUT TYPE="TEXT" NAME="place" VALUE="<?php echo bug_data_get_place($field); ?>" SIZE="6" MAXLENGTH="6">
      &nbsp;&nbsp;
      <B>Status:</B>
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
?>
      <P>
      <b>Display this field...</b>
      <ul>
<?php
      if (bug_data_is_select_box($field)) {
	echo '<li>As a selection box in a bug database search&nbsp;
	    <INPUT TYPE="CHECKBOX" NAME="show_on_query" VALUE="1"'.
	    (bug_data_is_showed_on_query($field)?' CHECKED':'').'>';
      }

	echo '<li>As a column in the report table of a search result &nbsp;
         <INPUT TYPE="CHECKBOX" NAME="show_on_result" VALUE="1"'.
	    (bug_data_is_showed_on_result($field)?' CHECKED':'').'>';

      if (!bug_data_is_required($field)&& !bug_data_is_special($field_name)) {
	echo '<li>On the form used by project members to submit a new bug&nbsp;
         <INPUT TYPE="CHECKBOX" NAME="show_on_add_members" VALUE="1"'.
        (bug_data_is_showed_on_add_members($field)?' CHECKED':'').'>
	<li>On the form used by other CodeX users to submit a new bug&nbsp;
         <INPUT TYPE="CHECKBOX" NAME="show_on_add" VALUE="1"'.
        (bug_data_is_showed_on_add($field)?' CHECKED':'').'>';
      }
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
    
	echo '<H2>Bug Field Usage Administration</H2>';

	echo '<h3>List of all Available Fields</h3>'.
	    '<p>The CodeX bug tracking system allows you to define what fields you want to use in the Bug Tracking System of this project. Click on a field to change its status (Used/Unused) and tune some other parameters. Required fields can only be tuned, they cannot be deactivated.<p>';


	// show all required fields plus all those explicitely asked by the project
    // show label, description, place,show_on_select,show_on_result, show_on_add, show_on_add_members

	// Loop through the list of project manageable fields
	$i=0;
	$title_arr=array();
	$title_arr[]='Field Label';
	$title_arr[]='Description';
	$title_arr[]='Rank<br>on screen';
	$title_arr[]='Scope';
	$title_arr[]='Status';

	$hdr = html_build_list_table_top ($title_arr);

	// Build HTML ouput for  Used fields first and Unused field second
	$iu=$in=0;
	while ( $field_name = bug_list_all_fields() ) {

	    // Do not show special fields any way.
	    if (bug_data_is_special($field_name)) { continue; }

	    // Show Used, Unused and Required fields on separate lists
	    // Do not show required fields any way.
	    $is_required = bug_data_is_required($field_name);

	    $is_used = bug_data_is_used($field_name);
	    $status_label = ($is_required?'Required':($is_used?'Used':'Unused'));
	    
	    $scope_label  = (bug_data_get_scope($field_name)=='S'?
			     'CodeX':'Project');
	    $place_label = ($is_used?bug_data_get_place($field_name):'-');

	    $html = '<TD><A HREF="'.$PHP_SELF.'?group_id='.$group_id.
		'&update_field=1&field='.$field_name.'">'.
		bug_data_get_label($field_name).'</A></td>'.
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
	<P><B>Some Help:</b>
        <ul type="compact">
        <li><b>Scope:</b> when equals to 'CodeX' it means that the possible values for this field are defined once for all for the CodeX site globally. Fields with scope 'Project' can be assigned a set of values at the project level. See the "Field values" item in the menu bar above.
        <li><b>Rank</b>: the rank number allows you to place the field with respect to the others. The fields with smaller values will appear first on the screen (bug submission form, query report,...)
      </ul>

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
