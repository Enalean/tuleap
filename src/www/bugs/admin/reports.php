<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX/CodeX Team, 2002. All Rights Reserved
// http://codex.xerox.com
//
// $Id$
//
//	Originally written by Laurent Julliard 2001, 2002, CodeX Team, Xerox
//

require_once('pre.php');
require('../bug_data.php');
require('../bug_utils.php');
$is_admin_page='y';

if (!$group_id) {
	exit_no_group();
}

//must be logged in to define personal reports
session_require(array('isloggedin'=>'1'));

// Initialize global bug structures
bug_init($group_id);

if ($post_changes) {

    if ($update_report) {
	// Updat report name and description and delete old report entries 
	$res = db_query("DELETE FROM bug_report_field WHERE report_id=$report_id");
	$res = db_query("UPDATE bug_report SET name='$rep_name', description='$rep_desc',scope='$rep_scope' WHERE report_id=$report_id");
    }
    
    else if ($create_report) {
	// Create a new report entry
	$res = db_query('INSERT INTO bug_report (group_id,user_id,name,description,scope)'.
			"VALUES ('$group_id','".user_getid()."','$rep_name',".
			"'$rep_desc','$rep_scope')");
	$report_id = db_insertid($res);
    }

    // And now insert all the field entries in the bug_report_field table
    $sql = 'INSERT INTO bug_report_field (report_id, field_name,'.
	'show_on_query,show_on_result,place_query,place_result,col_width) VALUES ';

    while ( $field = bug_list_all_fields() ) {

	$cb_search = 'CBSRCH_'.$field;
	$cb_report = 'CBREP_'.$field;
	$tf_search = 'TFSRCH_'.$field;
	$tf_report = 'TFREP_'.$field;
	$tf_colwidth = 'TFCW_'.$field;

	if ($$cb_search || $$cb_report || $$tf_search || $$tf_report) {

	    $cb_search_val = ($$cb_search ? '1':'0');
	    $cb_report_val = ($$cb_report ? '1':'0');
	    $tf_search_val = ($$tf_search ? '\''.$$tf_search.'\'' : 'NULL');
	    $tf_report_val = ($$tf_report ? '\''.$$tf_report.'\'' : 'NULL');
	    $tf_colwidth_val = ($$tf_colwidth? '\''.$$tf_colwidth.'\'' : 'NULL');
	    $sql .= "('$report_id','$field',$cb_search_val,$cb_report_val,".
		"$tf_search_val,$tf_report_val,$tf_colwidth_val),";
	}
    }
    $sql = substr($sql,0,-1);
    //echo "<br> DBG SQL = $sql";

    $res = db_query($sql);
    $verb = ($create_report ? 'create' : 'update');
    if ($res)
	$feedback .= "Report '$rep_name' $verb".'d successfully';
    else
	$feedback .= "Failed to $verb Report '$rep_name'";
	
} /* End of post_changes */

else if ($delete_report) {

    db_query("DELETE FROM bug_report WHERE report_id=$report_id");
    db_query("DELETE FROM bug_report_field WHERE report_id=$report_id");
    
}


// Display the UI forms

if ($new_report) {

    bug_header_admin(array ('title'=>'Create A New Bug Report',
			    'help' => 'BTSAdministration.html#BugReportSetting'));
    
    echo '<H2>Create a New Bug Report</H2>';
  
    // display the table of all fields that can be included in the report
    $title_arr=array();
    $title_arr[]='Field Label';
    $title_arr[]='Description';
    $title_arr[]='Use as a Search Criteria';
    $title_arr[]='Rank on Search';
    $title_arr[]='Use as a Report Column';
    $title_arr[]='Rank on Report';	
    $title_arr[]='Column width (optional)';	

    echo'	
	<FORM ACTION="'.$PHP_SELF.'" METHOD="POST">
	   <INPUT TYPE="HIDDEN" NAME="create_report" VALUE="y">
	   <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
	   <INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
	   <B>Name:</B>
	   <INPUT TYPE="TEXT" NAME="rep_name" VALUE="" SIZE="20" MAXLENGTH="20">
	   &nbsp;&nbsp;&nbsp;&nbsp;<B>Scope: </B>';
    
    if (user_ismember($group_id,'A'))
	echo '<SELECT NAME="rep_scope">
                        <OPTION VALUE="I">Personal</OPTION>
                        <OPTION VALUE="P">Project</OPTION>
                        </SELECT>';
    else
	echo 'Personal <INPUT TYPE="HIDDEN" NAME="rep_scope" VALUE="I">';


    echo ' <P>
	    <B>Description: </B>
	     <INPUT TYPE="TEXT" NAME="rep_desc" VALUE="" SIZE="50" MAXLENGTH="120">
                  <P>';

    echo html_build_list_table_top ($title_arr);
    $i=0;
    while ( $field = bug_list_all_fields() ) {

	// Do not show fields not used by the project
	if ( !bug_data_is_used($field)) { continue; }

	// Do not show some special fields any way 
	if (bug_data_is_special($field)) { 
	    if ( ($field == 'group_id') ||
		 ($field == 'comment_type_id') )
		{ continue; }
	}

	$cb_search = 'CBSRCH_'.$field;
	$cb_report = 'CBREP_'.$field;
	$tf_search = 'TFSRCH_'.$field;
	$tf_report = 'TFREP_'.$field;
	$tf_colwidth = 'TFCW_'.$field;
	echo '<TR class="'. util_get_alt_row_color($i) .'">';
	
	echo "\n<td>".bug_data_get_label($field).'</td>'.
	    "\n<td>".bug_data_get_description($field).'</td>'.
	    "\n<td align=\"center\">".'<input type="checkbox" name="'.$cb_search.'" value="1"></td>'.
	    "\n<td align=\"center\">".'<input type="text" name="'.$tf_search.'" value="" size="5" maxlen="5"></td>'.	    
	    "\n<td align=\"center\">".'<input type="checkbox" name="'.$cb_report.'" value="1"></td>'.
	    "\n<td align=\"center\">".'<input type="text" name="'.$tf_report.'" value="" size="5" maxlen="5"></td>'.	    
	    "\n<td align=\"center\">".'<input type="text" name="'.$tf_colwidth.'" value="" size="5" maxlen="5"></td>'.	    
	    '</tr>';
	$i++;
    }
    echo '</TABLE>'.
	'<P><CENTER><INPUT TYPE="SUBMIT" NAME="submit" VALUE="SUBMIT"></CENTER>'.
	'</FORM>';

} else if ($show_report) {


    bug_header_admin(array ('title'=>'Modify a Bug Report',
			    'help' => 'BTSAdministration.html#BugReportSetting'));
    
    echo '<H2>Modify a Bug Report</H2>';

    // fetch the report to update
    $sql = "SELECT * FROM bug_report WHERE report_id=$report_id";
    $res=db_query($sql);
    $rows = db_numrows($res);
    if (!$rows) {
	exit_error('Error',"Unknown Report ID ($report_id)");
    }

    // make sure this user has the right to modify the bug report
    if ( (db_result($res,0,'scope') == 'P') && 
	 !user_ismember($group_id,'A')) {
	exit_permission_denied();
    }

    $sql_fld = "SELECT * FROM bug_report_field WHERE report_id=$report_id";
    $res_fld=db_query($sql_fld);    
	
    // Build the list of fields involved in this report
    while ( $arr = db_fetch_array($res_fld) ) {
	$fld[$arr['field_name']] = $arr;
    }
	    
    // display the table of all fields that can be included in the report
    // along with their current state in this report
    $title_arr=array();
    $title_arr[]='Field Label';
    $title_arr[]='Description';
    $title_arr[]='Use as a Search Criteria';
    $title_arr[]='Rank on Search';
    $title_arr[]='Use as a Report Column';
    $title_arr[]='Rank on Report';	
    $title_arr[]='Column width (optional)';	
	
    echo '<FORM ACTION="'.$PHP_SELF.'" METHOD="POST">
	   <INPUT TYPE="HIDDEN" NAME="update_report" VALUE="y">
	   <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
	   <INPUT TYPE="HIDDEN" NAME="report_id" VALUE="'.$report_id.'">
	   <INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
	   <B>Name: </B>
	   <INPUT TYPE="TEXT" NAME="rep_name" VALUE="'.db_result($res,0,'name').'" SIZE="20" MAXLENGTH="20">
                 &nbsp;&nbsp;&nbsp;&nbsp;<B>Scope: </B>';
    $scope = db_result($res,0,'scope');
    if (user_ismember($group_id,'A'))
	echo '<SELECT NAME="rep_scope">
                        <OPTION VALUE="I"'.($scope=='I' ? 'SELECTED':'').'>Personal</OPTION>
                        <OPTION VALUE="P"'.($scope=='P' ? 'SELECTED':'').'>Project</OPTION>
                        </SELECT>';
    else
	echo ($scope=='P' ? 'Project':'Personal').
	    '<INPUT TYPE="HIDDEN" NAME="rep_scope" VALUE="'.$scope.'">';

    echo '
	    <P>
	    <B>Description:</B>
	    <INPUT TYPE="TEXT" NAME="rep_desc" VALUE="'.db_result($res,0,'description').'" SIZE="50" MAXLENGTH="120">
                  <P>';

    echo html_build_list_table_top ($title_arr);
    $i=0;
    while ( $field = bug_list_all_fields() ) {

	// Do not show fields not used by the project
	if ( !bug_data_is_used($field)) { continue; }

	// Do not show some special fields any way 
	if (bug_data_is_special($field)) { 
	    if ( ($field == 'group_id') ||
		 ($field == 'comment_type_id') )
		{ continue; }
	}

	$cb_search = 'CBSRCH_'.$field;
	$cb_report = 'CBREP_'.$field;
	$tf_search = 'TFSRCH_'.$field;
	$tf_report = 'TFREP_'.$field;
	$tf_colwidth = 'TFCW_'.$field;

	$cb_search_chk = ($fld[$field]['show_on_query'] ? 'CHECKED':'');
	$cb_report_chk = ($fld[$field]['show_on_result'] ? 'CHECKED':'');
	$tf_search_val = $fld[$field]['place_query'];
	$tf_report_val = $fld[$field]['place_result'];
	$tf_colwidth_val = $fld[$field]['col_width'];

	echo '<TR class="'. util_get_alt_row_color($i) .'">';
	
	echo "\n<td>".bug_data_get_label($field).'</td>'.
	    "\n<td>".bug_data_get_description($field).'</td>'.
	    "\n<td align=\"center\">".'<input type="checkbox" name="'.$cb_search.'" value="1" '.$cb_search_chk.' ></td>'.
	    "\n<td align=\"center\">".'<input type="text" name="'.$tf_search.'" value="'.$tf_search_val.'" size="5" maxlen="5"></td>'.	    
	    "\n<td align=\"center\">".'<input type="checkbox" name="'.$cb_report.'" value="1" '.$cb_report_chk.' ></td>'.
	    "\n<td align=\"center\">".'<input type="text" name="'.$tf_report.'" value="'.$tf_report_val.'" size="5" maxlen="5"></td>'.	    
	    "\n<td align=\"center\">".'<input type="text" name="'.$tf_colwidth.'" value="'.$tf_colwidth_val.'" size="5" maxlen="5"></td>'.	    
	    '</tr>';
	$i++;
    }
    echo '</TABLE>'.
	'<P><CENTER><INPUT TYPE="SUBMIT" NAME="submit" VALUE="SUBMIT"></CENTER>'.
	'</FORM>';

} else {

    // Front page
    bug_header_admin(array ('title'=>'Bug Administration - Report Management',
			    'help' => 'BTSAdministration.html#BugReportManagement'));
	
    echo '<H2>Manage Bug Reports</H2>';
    
    $sql = "SELECT * FROM bug_report WHERE group_id=$group_id ".
	' AND (user_id='.user_getid().' OR scope=\'P\')';
    $res=db_query($sql);
    $rows = db_numrows($res);
    //echo "<br> DBG sql = $sql";

    if ($rows) {
	// Loop through the list of all bug report
	$title_arr=array();
	$title_arr[]='ID';
	$title_arr[]='Report name';
	$title_arr[]='Description';
	$title_arr[]='Scope';
	$title_arr[]='Delete?';
	
	echo '<p>(Click to modify)';
	echo html_build_list_table_top ($title_arr);
	$i=0;
	while ($arr = db_fetch_array($res)) {
	    
	    echo '<TR class="'. util_get_alt_row_color($i) .'"><TD>';
	    
	    if ( ($arr['scope']=='P') && !user_ismember($group_id,'A') )
		echo $arr['report_id'];
	    else 
		echo '<A HREF="'.$PHP_SELF.'?group_id='.$group_id.
		    '&show_report=1&report_id='.$arr['report_id'].'">'.
		    $arr['report_id'].'</A>';
	    
	    echo "</td>\n<td>".$arr['name'].'</td>'.
		"\n<td>".$arr['description'].'</td>'.
		"\n<td align=\"center\">".(($arr['scope']=='P') ? 'Project':'Personal').'</td>'.
		"\n<td align=\"center\">";

	    if ( ($arr['scope']=='P') && !user_ismember($group_id,'A') )
		echo '-';
	    else
		echo '<A HREF="'.$PHP_SELF.'?group_id='.$group_id.
		'&delete_report=1&report_id='.$arr['report_id'].
		'" onClick="return confirm(\'Delete this Report?\')">'.
		    '<img src="'.util_get_image_theme("ic/trash.png").'" border="0"></A>';

	    echo '</td></tr>';
	    $i++;
	} 
	echo '</TABLE>';
    } else {
	echo '<p><h3>No bug report defined yet.</h3>';
    }

    echo '<P> You can <A HREF="'.$PHP_SELF.'?group_id='.$group_id.
	'&new_report=1"><b>Create a New Bug Report</b></A>.';
}

bug_footer(array());

?>
