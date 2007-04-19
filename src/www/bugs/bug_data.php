<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2002. All rights reserved
//
// 
//
//
//	Originally by to the SourceForge Team,1999-2000
//	Very Heavy rewrite by Laurent Julliard 2001, 2002, CodeX Team, Xerox
//

/*

	Simple way of wrapping our SQL so it can be 
	shared among the XML outputs and the PHP web front-end

	Also abstracts controls to update data

*/
function bug_data_get_all_fields ($group_id=false,$reload=false) {

    /*
           Get all the possible bug fields for this project both used and unused. If
           used then show the project specific information about field usage
           otherwise show the default usage parameter
           Make sure array element are sorted by ascending place
      */

    global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME, $AT_START;

    // Do nothing if already set and reload not forced
    if (isset($BF_USAGE_BY_ID) && !$reload) {
	return;
    }

    // Clean up the array
    $BF_USAGE_BY_ID=array();
    $BF_USAGE_BY_NAME=array();

    // First get the all the defaults. 
    $sql='SELECT bug_field.bug_field_id, field_name, display_type, '.
	'display_size,label, description,scope,required,empty_ok,keep_history,special, custom, '.
	'value_function,'.
	'group_id, use_it,show_on_add,show_on_add_members, place, custom_label,'.
	'custom_description,custom_display_size,custom_empty_ok,custom_keep_history, '.
	'custom_value_function '.
	'FROM bug_field, bug_field_usage '.
	'WHERE group_id=100  '.
	'AND bug_field.bug_field_id=bug_field_usage.bug_field_id ';

   
    $res_defaults = db_query($sql);

    // Now put all used fields in a global array for faster access
    // Index both by field_name and bug_field_id
    while ($field_array = db_fetch_array($res_defaults)) {
	$BF_USAGE_BY_ID[$field_array['bug_field_id'] ] = $field_array;
	$BF_USAGE_BY_NAME[$field_array['field_name'] ] = $field_array;
    }

    // Then select  all project specific entries
    $sql='SELECT bug_field.bug_field_id, field_name, display_type, '.
	'display_size,label, description,scope,required,empty_ok,keep_history,special, custom, '.
	'value_function,'.
	'group_id, use_it, show_on_add, show_on_add_members, place, custom_label,'.
	'custom_description,custom_display_size,custom_empty_ok,custom_keep_history, '.
	'custom_value_function '.
	'FROM bug_field, bug_field_usage '.
	'WHERE group_id='.$group_id.
	' AND bug_field.bug_field_id=bug_field_usage.bug_field_id ';

    $res_project = db_query($sql);

    // And override entries in the default array
    while ($field_array = db_fetch_array($res_project)) {
	$BF_USAGE_BY_ID[$field_array['bug_field_id'] ] = $field_array;
	$BF_USAGE_BY_NAME[$field_array['field_name'] ] = $field_array;
    }

    //Debug code
    //echo "<br>DBG - At end of bug_get_all_fields: $rows";
    //reset($BF_USAGE_BY_NAME);
    //  while (list($key, $val) = each($BF_USAGE_BY_NAME)) {
	//echo "<br>DBG - $key -> use_it: $val[use_it], $val[place],$val[value_function]";
    //}
      
    // rewind internal pointer of global arrays
    reset($BF_USAGE_BY_ID);
    reset($BF_USAGE_BY_NAME);
    $AT_START = true;
}

function cmp_place($ar1, $ar2)
{
    if ($ar1['place']< $ar2['place'])
	return -1;
    else if ($ar1['place']>$ar2['place'])
	return 1;
    return 0;
}

function cmp_place_query($ar1, $ar2)
{
    if ($ar1['place_query']< $ar2['place_query'])
	return -1;
    else if ($ar1['place_query']>$ar2['place_query'])
	return 1;
    return 0;
}

function cmp_place_result($ar1, $ar2)
{
    if ($ar1['place_result']< $ar2['place_result'])
	return -1;
    else if ($ar1['place_result']>$ar2['place_result'])
	return 1;
    return 0;
}

function bug_data_get_all_report_fields($group_id=false,$report_id=100) {
    global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;

    /*
           Get all the bug fields involved in the bug report.
	   WARNING: This function ust only be called after bug_init()
      */

    // Build the list of fields involved in this report
    $sql = "SELECT * FROM bug_report_field WHERE report_id=$report_id";
    $res = db_query($sql);
    
    while ( $arr = db_fetch_array($res) ) {
	$field = $arr['field_name'];
	$field_id = bug_data_get_field_id($field);
	$BF_USAGE_BY_NAME[$field]['show_on_query'] = 
	$BF_USAGE_BY_ID[$field_id]['show_on_query'] = $arr['show_on_query'];

	$BF_USAGE_BY_NAME[$field]['show_on_result'] = 
	$BF_USAGE_BY_ID[$field_id]['show_on_result'] = $arr['show_on_result'];

	$BF_USAGE_BY_NAME[$field]['place_query'] =
	$BF_USAGE_BY_ID[$field_id]['place_query'] = $arr['place_query'];

	$BF_USAGE_BY_NAME[$field]['place_result'] =
	$BF_USAGE_BY_ID[$field_id]['place_result'] = $arr['place_result'];

	$BF_USAGE_BY_NAME[$field]['col_width'] = 
	$BF_USAGE_BY_ID[$field_id]['col_width'] = $arr['col_width'];
    }
}

function bug_data_get_all_id_tech($group_id) {
	$sql="SELECT DISTINCT(bug.assigned_to) FROM user, bug WHERE user.user_id = bug.assigned_to AND bug.group_id = ".$group_id;
	return db_query($sql);
}

function bug_data_get_all_tech($id_arr) {
	$sql = "SELECT user_id,user_name FROM user WHERE user_id IN (".implode(",", $id_arr).")";
	return db_query($sql);
}

function bug_data_get_field_predefined_values ($field, $group_id=false, $checked=false,$by_field_id=false,$active_only=true) {

    /*
             Return all possible values for a select box field
             Rk: if the checked value is given then it means that we want this value
                  in the list in any case (even if it is hidden and active_only is requested)
       */
    $field_id = ($by_field_id ? $field : bug_data_get_field_id($field));
    $field_name = ($by_field_id ? bug_data_get_field_name($field) : $field);

    // The "Assigned_to" box requires some special processing
    // because possible values  are project members) and they are
    // not stored in the bug_field_value table but in the user_group table
    if ($value_func = bug_data_get_value_function($field_name)) {

	if ($value_func == 'group_members')
	    $res_value = bug_data_get_group_members($group_id);
	else if ($value_func == 'group_admins')
	    $res_value = bug_data_get_group_admins($group_id);
	else if ($value_func == 'bug_technicians')
	    $res_value = bug_data_get_technicians($group_id);
	else if ($value_func == 'bug_submitters')
	    $res_value = bug_data_get_submitters($group_id);

    } else {

	// If only active field
	if ($active_only) {
	    if ($checked) {
		$status_cond = "AND  (status IN ('A','P') OR value_id='$checked') ";
	    } else {
		$status_cond = "AND  status IN ('A','P') ";
	    }
	}

	// CAUTION !! the fields value_id and value must be first in the
	// select statement because the output is used in the html_build_select_box
	// function

	// Look for project specific values first
	$sql="SELECT value_id,value,bug_fv_id,bug_field_id,group_id,description,order_id,status ".
	    "FROM bug_field_value ".
	    "WHERE group_id=$group_id AND bug_field_id= $field_id ".
	    $status_cond." ORDER BY order_id ASC";
	$res_value = db_query($sql);
	$rows=db_numrows($res_value);
	
	// If no specific value for this group then look for default values
	if ($rows == 0) {
	    $sql="SELECT value_id,value,bug_fv_id,bug_field_id,group_id,description,order_id,status ".
		"FROM bug_field_value ".
		"WHERE group_id=100 AND bug_field_id=$field_id ".
		$status_cond." ORDER BY order_id ASC";
	    $res_value = db_query($sql);
	    $rows=db_numrows($res_value);
	}
    }

    return($res_value);

}

function bug_data_is_custom($field, $by_field_id=false) {
    global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
    return($by_field_id ? $BF_USAGE_BY_ID[$field]['custom']: $BF_USAGE_BY_NAME[$field]['custom']);
}

function bug_data_is_special($field, $by_field_id=false) {
    global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
    return($by_field_id ? $BF_USAGE_BY_ID[$field]['special']: $BF_USAGE_BY_NAME[$field]['special']);
}

function bug_data_is_empty_ok($field, $by_field_id=false) {
    global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
    if ($by_field_id) {
	$val = $BF_USAGE_BY_ID[$field]['custom_empty_ok'];
	if (!isset($val)) { $val = $BF_USAGE_BY_ID[$field]['empty_ok']; }
    } else {
	$val = $BF_USAGE_BY_NAME[$field]['custom_empty_ok'];
	if (!isset($val)) { $val = $BF_USAGE_BY_NAME[$field]['empty_ok']; }
    }
    return($val);
}

function bug_data_is_required($field, $by_field_id=false) {
    global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
    return($by_field_id ? $BF_USAGE_BY_ID[$field]['required']: $BF_USAGE_BY_NAME[$field]['required']);
}

function bug_data_is_used($field, $by_field_id=false) {
    global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
    return($by_field_id ? $BF_USAGE_BY_ID[$field]['use_it']: $BF_USAGE_BY_NAME[$field]['use_it']);
}

function bug_data_is_showed_on_query($field) {
    global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
    return($by_field_id ? $BF_USAGE_BY_ID[$field]['show_on_query']: $BF_USAGE_BY_NAME[$field]['show_on_query']);
 
}

function bug_data_is_showed_on_result($field) {
    global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
    return($by_field_id ? $BF_USAGE_BY_ID[$field]['show_on_result']: $BF_USAGE_BY_NAME[$field]['show_on_result']);
}

function bug_data_is_showed_on_add($field, $by_field_id=false) {
    global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
    return($by_field_id ? $BF_USAGE_BY_ID[$field]['show_on_add']: $BF_USAGE_BY_NAME[$field]['show_on_add']);
}

function bug_data_is_showed_on_add_members($field, $by_field_id=false) {
    global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
    return($by_field_id ? $BF_USAGE_BY_ID[$field]['show_on_add_members']: $BF_USAGE_BY_NAME[$field]['show_on_add_members']);
}

function bug_data_is_date_field($field, $by_field_id=false) {
    return(bug_data_get_display_type($field, $by_field_id) == 'DF');
}

function bug_data_is_text_field($field, $by_field_id=false) {
    return(bug_data_get_display_type($field, $by_field_id) == 'TF');
}

function bug_data_is_text_area($field, $by_field_id=false) {
    return(bug_data_get_display_type($field, $by_field_id) == 'TA'); 
}

function bug_data_is_select_box($field, $by_field_id=false) {
    return(bug_data_get_display_type($field, $by_field_id) == 'SB');
}

function bug_data_is_username_field($field, $by_field_id=false) {
    global $BF_USAGE_BY_ID;
    if ($by_field_id) {
	$field = bug_data_get_field_name($field);
    }
    return(($field == 'assigned_to') || ($field == 'submitted_by'));
}

function bug_data_is_project_scope($field, $by_field_id=false) {
    global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
    if ($by_field_id) {
	return($BF_USAGE_BY_ID[$field]['scope'] == 'P');
    } else {
	return($BF_USAGE_BY_NAME[$field]['scope'] == 'P');
    }
}

function bug_data_is_status_closed($status) {
    return (($status == '3') || ($status == '10') );
}

function bug_data_get_field_name($field_id) {
    global $BF_USAGE_BY_ID;
    return($BF_USAGE_BY_ID[$field_id]['field_name']);
}

function bug_data_get_field_id($field_name) {
    global $BF_USAGE_BY_NAME;
    return($BF_USAGE_BY_NAME[$field_name]['bug_field_id']);
}

function bug_data_get_group_id($field, $by_field_id=false) {
    global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
    return($by_field_id ? $BF_USAGE_BY_ID[$field]['group_id'] : $BF_USAGE_BY_NAME[$field]['group_id']);
}

function bug_data_get_label($field, $by_field_id=false) {
    global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
    if ($by_field_id) {
	$lbl = $BF_USAGE_BY_ID[$field]['custom_label'];
	if (!isset($lbl)) { $lbl = $BF_USAGE_BY_ID[$field]['label']; }
    } else {
	$lbl = $BF_USAGE_BY_NAME[$field]['custom_label'];
	if (!isset($lbl)) { $lbl = $BF_USAGE_BY_NAME[$field]['label']; }
    }
    return($lbl);
}

function bug_data_get_description($field, $by_field_id=false) {
    global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
    if ($by_field_id) {
	$desc = $BF_USAGE_BY_ID[$field]['custom_description'];
	if (!isset($desc)) { $desc = $BF_USAGE_BY_ID[$field]['description']; }
    } else {
	$desc = $BF_USAGE_BY_NAME[$field]['custom_description'];
	if (!isset($desc)) { $desc = $BF_USAGE_BY_NAME[$field]['description']; }
    }
    return($desc);
}

function bug_data_get_display_type($field, $by_field_id=false) {
    global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
    return($by_field_id ? $BF_USAGE_BY_ID[$field]['display_type'] : $BF_USAGE_BY_NAME[$field]['display_type']);
}

function bug_data_get_display_type_in_clear($field, $by_field_id=false) {
    if (bug_data_is_select_box($field, $by_field_id)) {
	return 'Select Box';
    }
    else if (bug_data_is_text_field($field, $by_field_id)) {
	return 'Text Field';
    } 
    else if (bug_data_is_text_area($field, $by_field_id)) {
	return 'Text Area';
    }
    else if (bug_data_is_date_field($field, $by_field_id)) {
	return 'Date Field';
    }
    else {
	return '?';
    }
}

function bug_data_get_keep_history($field, $by_field_id=false) {
    global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
    if ($by_field_id) {
	$val = $BF_USAGE_BY_ID[$field]['custom_keep_history'];
	if (!isset($val)) { $val = $BF_USAGE_BY_ID[$field]['keep_history']; }
    } else {
	$val = $BF_USAGE_BY_NAME[$field]['custom_keep_history'];
	if (!isset($val)) { $val = $BF_USAGE_BY_NAME[$field]['keep_history']; }
    }
    return($val);
}

function bug_data_get_place($field, $by_field_id=false) {
    global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
    return($by_field_id ? $BF_USAGE_BY_ID[$field]['place'] : $BF_USAGE_BY_NAME[$field]['place']);
}

function bug_data_get_scope($field, $by_field_id=false) {
    global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
    return($by_field_id ? $BF_USAGE_BY_ID[$field]['scope'] : $BF_USAGE_BY_NAME[$field]['scope']);
}

function bug_data_get_col_width($field, $by_field_id=false) {
    global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
    return($by_field_id ? $BF_USAGE_BY_ID[$field]['col_width'] : $BF_USAGE_BY_NAME[$field]['col_width']);
}

function bug_data_get_display_size($field, $by_field_id=false) {
    global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
    if ($by_field_id) {
	$val = $BF_USAGE_BY_ID[$field]['custom_display_size'];
	if (!isset($val)) { $val = $BF_USAGE_BY_ID[$field]['display_size']; }
    } else {
	$val = $BF_USAGE_BY_NAME[$field]['custom_display_size'];
	if (!isset($val)) { $val = $BF_USAGE_BY_NAME[$field]['display_size']; }
    }
    return(explode('/',$val));
}

function bug_data_get_value_function($field, $by_field_id=false) {
    global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
    if ($by_field_id) {
	$val = $BF_USAGE_BY_ID[$field]['custom_value_function'];
	if (!isset($val)) { $val = $BF_USAGE_BY_ID[$field]['value_function']; }
    } else {
	$val = $BF_USAGE_BY_NAME[$field]['custom_value_function'];
	if (!isset($val)) { $val = $BF_USAGE_BY_NAME[$field]['value_function']; }
    }
    return($val);
}

function bug_data_get_default_value($field,  $by_field_id=false) {
    global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
    /*
      Return the default value associated to a field_name as defined in the
      bug table (SQL definition)
      */
    if ($by_field_id) {
	$field = bug_data_get_field_name($field);
    }

    $result = db_query('describe bug '.$field);
    return (db_result($result,0,'Default'));
 
}

function bug_data_get_max_value_id($field, $group_id, $by_field_id=false) {
 
    /*
      Find the maximum value for the value_id of a field for a given group
      Return -1 if  no value exist yet
      */

    if (!$by_field_id) {
	$field_id = bug_data_get_field_id($field);
    }

    $sql="SELECT max(value_id) as max FROM bug_field_value ".
	"WHERE bug_field_id='$field_id' AND group_id='$group_id' ";
    $res = db_query($sql);
    $rows = db_numrows($res);

    // If no max value found then it means it's the first value for this field
    // in this group. Return -1 in this case
    if ($rows == 0) {
	return(-1);
    } else {
	return(db_result($res,0,'max'));
    }
  
}

function bug_data_is_value_set_empty($field, $group_id, $by_field_id=false) {

    /*
      Return true if there is an existing set of values for given field for a 
      given group and false if it is empty
      */

    if (!$by_field_id) {
	$field_id = bug_data_get_field_id($field);
    }
    $sql="SELECT value_id FROM bug_field_value ".
	"WHERE bug_field_id='$field_id' AND group_id='$group_id' ";
    $res = db_query($sql);
    $rows=db_numrows($res);

    return (($rows<=0));
}


function bug_data_copy_default_values($field, $group_id, $by_field_id=false) {
    /*
      Initialize the set of values for a given field for a given group by using
      the system default (default values belong to group_id 'None' =100)
      */

    if (!$by_field_id) {
	$field_id = bug_data_get_field_id($field);
    }
    
    // if group_id=100 (None) it is a null operation
    // because default values belong to group_id 100 by definition
    if ($group_id != 100) {

	// First delete the exisiting value if any
	$sql="DELETE FROM bug_field_value ".
	    "WHERE bug_field_id='$field_id' AND group_id='$group_id' ";
	$res = db_query($sql);

	// Second insert default values (if any) from group 'None'
	// Rk: The target table of the INSERT statement cannot appear in
	// the FROM clause of the SELECT part of the query because it's forbidden
	// in ANSI SQL to SELECT . So do it by hand !
	//   

	$sql = "SELECT value_id,value,description,order_id,status ".
	    "FROM bug_field_value ".
	    "WHERE bug_field_id='$field_id' AND group_id='100'";
	$res = db_query($sql);
	$rows = db_numrows($res);
	
	for ($i=0; $i<$rows; $i++) {

	    $value_id = addslashes(db_result($res,$i,'value_id'));
	    $value = db_result($res,$i,'value');
	    $description = addslashes(db_result($res,$i,'description'));
	    $order_id = db_result($res,$i,'order_id');
	    $status  = db_result($res,$i,'status');


	    $sql="INSERT INTO bug_field_value ".
		"(bug_field_id,group_id,value_id,value,description,order_id,status) ".
		"VALUES ('$field_id','$group_id','$value_id','$value','$description','$order_id','$status')";
	    //echo "<BR>DBG - $sql";
	    $res_insert = db_query($sql);

	    if (db_affected_rows($res_insert) < 1) {
                $feedback .= ' INSERT OF DEFAULT VALUE FAILED - ';
                $feedback .= db_error();
	    } 
	}
    }
}

function bug_data_get_cached_field_value($field,$group_id,$value_id) {
    global $BF_VALUE_BY_NAME;

    if (!isset($BF_VALUE_BY_NAME[$field][$value_id])) {
	$res = bug_data_get_field_predefined_values ($field, $group_id,false,false,false);

	while ($fv_array = db_fetch_array($res)) {
	    // $fv_array[0] has the value_id and [1] is the value
	    $BF_VALUE_BY_NAME[$field][$fv_array[0]] = $fv_array[1];
	}

	// Make sure 'None' (value_id = 100) is always in the list
	if (!isset($BF_VALUE_BY_NAME[$field][100])) {
	    $BF_VALUE_BY_NAME[$field][100] = 'None';
	}

	// If the value is still unkown the return the value_id saying unkown
	if (!isset($BF_VALUE_BY_NAME[$field][$value_id])) {
	    $BF_VALUE_BY_NAME[$field][$value_id] = " Unknown ($value_id)";
	}
	
    }

    return $BF_VALUE_BY_NAME[$field][$value_id];
}

function bug_data_get_field_value ($bug_fv_id) {
    /*
      Get all the columns associated to a given field value
      */

    $sql = "SELECT * FROM bug_field_value WHERE bug_fv_id='$bug_fv_id'";
    $res = db_query($sql);
    return($res);
}

function bug_data_is_default_value ($bug_fv_id) {
    /*
      See if this field value belongs to group None (100). In this case
      it is a so called default value.
      */

    $sql = "SELECT bug_field_id,value_id FROM bug_field_value WHERE bug_fv_id='$bug_fv_id' AND group_id='100'";
    $res = db_query($sql);
    
    return ( (db_numrows($res) >= 1) ? $res : false);
}

function bug_data_create_field_binding($field, $group_id, $value_function, $by_field_id=false) {

    global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME,$feedback;

    if (!$by_field_id) {
	$field_id = bug_data_get_field_id($field);
	$field_name = $field;
    } else {
	$field_name = bug_data_get_field_name($field);
	$field_id = $field;

    }

    // Update the value in the db and also in the cache 
    if ($value_function == 'none') {
	$db_val = 'NULL';
	unset($BF_USAGE_BY_ID[$field_id]['custom_value_function']);
	unset($BF_USAGE_BY_NAME[$field_name]['custom_value_function']);
    } else {
	$db_val = "'$value_function'";
	$BF_USAGE_BY_ID[$field_id]['custom_value_function'] = $value_function;
	$BF_USAGE_BY_NAME[$field_name]['custom_value_function'] = $value_function;
    }
    $sql = "UPDATE bug_field_usage SET custom_value_function = $db_val ".
	"WHERE bug_field_id=$field_id AND group_id=$group_id";
    $result = db_query($sql);
    

    if (!$result) {
	$feedback .= 'BINDING UPDATE FAILED ';
	$feedback .=  ' - '.db_error();
    } else {
	$feedback .= ' Binding Updated Succesfully';
    }
}

function bug_data_create_value ($field, $group_id, $value, $description,$order_id,$status='A',$by_field_id=false) {

    global $feedback;

    /*
      Insert a new value for a given field for a given group
      */

    // An empty field value is not allowed
    if (preg_match ("/^\s*$/", $value)) {
	$feedback .= 'EMPTY FIELD VALUE NOT ALLOWED';
	return;
    }

    if (!$by_field_id) {
	$field_id = bug_data_get_field_id($field);
    }

    // if group_id=100 (None) then do nothing
    // because no real project should have the group number '100'
    if ($group_id != 100) {

	// if the current value set for this project is empty 
	// then copy the default values first (if any)
	if (bug_data_is_value_set_empty($field,$group_id)) {
	    bug_data_copy_default_values($field,$group_id);
	}

	// Find the next value_id to give to this new value. (Start arbitrarily
	// at 200 if no value exists (and therefore max is undefined)
	$max_value_id = bug_data_get_max_value_id($field, $group_id);

	if ($max_value_id < 0) {
	    $value_id = 200;
	} else {
	    $value_id = $max_value_id +1;
	}


	$sql = "INSERT INTO bug_field_value ".
	    "(bug_field_id,group_id,value_id,value,description,order_id,status) ".
	    "VALUES ('$field_id','$group_id','$value_id','$value','$description','$order_id','$status')";
	db_query($sql);

        if (db_affected_rows($result) < 1) {
                $feedback .= ' INSERT FAILED ';
                $feedback .=  ' - '.db_error();
        } else {
		$feedback .= ' New field value inserted ';
	}
    }
}

function bug_data_update_value ($bug_fv_id,$field,$group_id,$value,$description,$order_id,$status='A') {

    global $feedback;
    /*
      Insert a new value for a given field for a given group
      */

    // An empty field value is not allowed
    if (preg_match ("/^\s*$/", $value)) {
	$feedback .= 'EMPTY FIELD VALUE NOT ALLOWED';
	return;
    }

    // Updating a bug field value that belong to group 100 (None) is
    // forbidden. These are default values that cannot be changed so
    // make sure to copy the default values first in the project context first

    if ($res = bug_data_is_default_value($bug_fv_id)) {
	bug_data_copy_default_values($field,$group_id);

	$arr = db_fetch_array($res);
	$where_cond = 'bug_field_id='.$arr['bug_field_id'].
	    ' AND value_id='.$arr['value_id']." AND group_id='$group_id' ";
    } else {
	$where_cond = "bug_fv_id='$bug_fv_id' AND group_id<>'100'";
    }

    // Now perform the value update
    $sql = "UPDATE bug_field_value ".
	"SET value='$value',description='$description',order_id='$order_id',status='$status' ".
	"WHERE $where_cond";
    $result = db_query($sql);

    //echo "<BR>DBG - $sql";

    if (db_affected_rows($result) < 1) {
	$feedback .= ' UPDATE OF FIELD VALUE FAILED ';
	$feedback .= ' - '.db_error();
    } else {
	$feedback .= ' New field value updated ';
    }
}

function bug_data_reset_usage($field_name,$group_id)
{
    global $feedback;
    /*
      Reset a field settings to its defaults usage (values are untouched). The defaults
      always belong to group_id 100 (None) so make sure we don;t delete entries for
      group 100
      */
    $field_id = bug_data_get_field_id($field_name);
    if ($group_id != 100) {
        $sql = "DELETE FROM bug_field_usage ".
            "WHERE group_id='$group_id' AND bug_field_id='$field_id'";
        db_query($sql);
	$feedback .= ' Field value successfully reset to defaults ';

    }
}

 function bug_data_update_usage($field_name,$group_id,$label,$description,
				$use_it,$rank,$display_size,$empty_ok=0,
				$keep_history=0,$show_on_add_members=0,$show_on_add=0)
{
    global $feedback;
    /*
      Update a field settings in the bug_usage_table
      Rk: All the show_on_xxx boolean parameters are set to 0 by default because their
           values come from checkboxes and if not checked the form variable
           is not set at all. It must be 0 to be ok with the SQL statement
      */

    // if it's a required field then make sure the use_it flag is true
    if (bug_data_is_required($field_name)) {
	$use_it = 1; }

    $field_id = bug_data_get_field_id($field_name);

    // if it's a custom field then take label into account else store NULL
    //    if (bug_data_is_custom($field_name)) {
	$lbl = isset($label) ? "'$label'" : 'NULL';
	$desc = isset($description) ? "'$description'" : 'NULL';
	$disp_size = isset($display_size) ? "'$display_size'" : 'NULL';
	$empty = isset($empty_ok) ? "'$empty_ok'" : 'NULL';
	$keep_hist = isset($keep_history) ? "'$keep_history'" : 'NULL';
	//    } else {
	//	$lbl = $desc = $disp_size = $empty = $keep_hist = "NULL";
	//    }

    // See if this field usage exists in the table for this project
    $sql = 'SELECT bug_field_id FROM bug_field_usage '.
	"WHERE bug_field_id='$field_id' AND group_id='$group_id'";
    $result = db_query($sql);
    $rows = db_numrows($result);

    // if it does exist then update it else insert a new usage entry for this field.
    if ($rows) {
	$sql = 'UPDATE bug_field_usage '.
	    "SET use_it='$use_it',show_on_add='$show_on_add',".
	    "show_on_add_members='$show_on_add_members',place='$rank', ".
	    "custom_label=$lbl,  custom_description=$desc,".
	    "custom_display_size=$disp_size,  custom_empty_ok=$empty,".
	    "custom_keep_history=$keep_hist ".
	    "WHERE bug_field_id='$field_id' AND group_id='$group_id'";
	$result = db_query($sql);
    } else {
	$sql = 'INSERT INTO  bug_field_usage  (bug_field_id, group_id,use_it,show_on_add,'.
	    'show_on_add_members,place,custom_label,custom_description,custom_displaY_size,'.
	    'custom_empty_ok, custom_keep_history) '.
	    "VALUES ('$field_id','$group_id','$use_it','$show_on_add',".
	    "'$show_on_add_members','$rank',$lbl,$desc,$disp_size,$empty,$keep_hist )";
	$result = db_query($sql);
    }

    if (db_affected_rows($result) < 1) {
	$feedback .= ' UPDATE OF FIELD  USAGE FAILED ';
	$feedback .= ' - '.db_error();
    } else {
	$feedback .= ' Field usage updated ';
    }

}

// People who have Tech and Tech&Admin permission 
function bug_data_get_technicians ($group_id=false) {
	$sql="SELECT user.user_id,user.user_name ".
		"FROM user,user_group ".
		"WHERE (user.user_id=user_group.user_id ".
		"AND user_group.bug_flags IN (1,2) ".
		"AND user_group.group_id='$group_id' ) ".
		"ORDER BY user.user_name";
	return db_query($sql);
}

// People who have once submitted a bug
function bug_data_get_submitters ($group_id=false) {
	$sql="SELECT DISTINCT user.user_id,user.user_name ".
		"FROM user,bug ".
		"WHERE (user.user_id=bug.submitted_by ".
		"AND bug.group_id='$group_id') ".
		"ORDER BY user.user_name";
	return db_query($sql);
}

//
// People who are project members
function bug_data_get_group_members ($group_id=false) {
	$sql="SELECT user.user_id,user.user_name ".
		"FROM user,user_group ".
		"WHERE (user.user_id=user_group.user_id ".
		"AND user_group.group_id='$group_id') ".
		"ORDER BY user.user_name";
	return db_query($sql);
}

//
// People who are project admins
function bug_data_get_group_admins ($group_id=false) {
	$sql="SELECT user.user_id,user.user_name ".
		"FROM user,user_group ".
		"WHERE (user.user_id=user_group.user_id ".
		"AND user_group.group_id='$group_id') OR user.user_id=100 ".
	        "AND user_group.admin_flags = 'A' ".
		"ORDER BY user.user_name";
	return db_query($sql);
}

function bug_data_get_tasks ($group_id=false) {
	/*
		Get the tasks for this project
	*/
	$sql="SELECT project_task.project_task_id,project_task.summary ".
	"FROM project_task,project_group_list ".
	"WHERE project_task.group_project_id=project_group_list.group_project_id ".
	"AND project_task.status_id <> '3' ".
	"AND project_group_list.group_id='$group_id' ORDER BY project_task_id DESC LIMIT 100";
	return db_query($sql);
}

function bug_data_get_dependent_tasks ($bug_id=false, $notin=false) {
	/*
		Get the list of ids this is dependent on
	*/
	$sql="SELECT btd.is_dependent_on_task_id,pt.summary,pt.group_project_id FROM bug_task_dependencies btd, project_task pt WHERE btd.bug_id='$bug_id' AND btd.is_dependent_on_task_id!=100 AND btd.is_dependent_on_task_id = pt.project_task_id";
	if ($notin) {
	    $sql .= ' AND is_dependent_on_task_id NOT IN ('. join(',',$notin).')';
	}
	return db_query($sql);
}

function bug_data_get_valid_bugs ($group_id=false,$bug_id='') {
	$sql="SELECT bug_id,summary ".
		"FROM bug ".
		"WHERE group_id='$group_id' ".
		"AND bug_id <> '$bug_id' AND bug.resolution_id <> '2' ORDER BY bug_id DESC LIMIT 200";
	return db_query($sql);
}

function bug_data_get_dependent_bugs ($bug_id=false, $notin=false) {
	/*
		Get the list of ids this is dependent on
	*/
	$sql="SELECT bbd.is_dependent_on_bug_id,b.summary FROM bug_bug_dependencies bbd, bug b WHERE bbd.bug_id='$bug_id' AND bbd.is_dependent_on_bug_id = b.bug_id AND bbd.is_dependent_on_bug_id != 100";
	if ($notin) {
	    $sql .= ' AND is_dependent_on_bug_id NOT IN ('. join(',',$notin).')';
	}
	return db_query($sql);
}

function bug_data_get_followups ($bug_id=false) {
    $sql="SELECT DISTINCT bug_history.bug_history_id,bug_history.field_name,bug_history.old_value,bug_history.date,user.user_name,bug_field_value.value AS comment_type ".
	"FROM bug_history,bug_field_value,bug_field,bug,user ".
	"WHERE bug_history.bug_id='$bug_id' ".
	"AND bug_history.field_name = 'details' ".
	"AND bug_history.mod_by=user.user_id ".
	"AND bug_history.bug_id=bug.bug_id ".
	"AND bug_history.type = bug_field_value.value_id ".
	"AND bug_field_value.bug_field_id = bug_field.bug_field_id ".
	"AND (bug_field_value.group_id = bug.group_id OR bug_field_value.group_id = '100') ".
	"AND  bug_field.field_name = 'comment_type_id' ".
	"ORDER BY bug_history.date DESC";

	return db_query($sql);
}

function bug_data_get_commenters($bug_id) {
    $sql="SELECT DISTINCT mod_by FROM bug_history ".
	"WHERE bug_history.bug_id='$bug_id' ".
	"AND bug_history.field_name = 'details' ";
    return db_query($sql);
}

function bug_data_get_history ($bug_id=false) {
	$sql="select bug_history.field_name,bug_history.old_value,bug_history.date,bug_history.type,user.user_name ".
		"FROM bug_history,user ".
		"WHERE bug_history.mod_by=user.user_id ".
		"AND bug_history.field_name <> 'details' ".
		"AND bug_id='$bug_id' ORDER BY bug_history.date DESC";
	return db_query($sql);
}

function bug_data_get_attached_files ($bug_id=false) {
	$sql="SELECT bug_file_id,filename,filesize,description,date,user.user_name ".
		"FROM bug_file,user ".
		"WHERE submitted_by=user.user_id ".
		"AND bug_id='$bug_id' ORDER BY date DESC";
	return db_query($sql);
}

function bug_data_get_cc_list ($bug_id=false) {
    $sql="SELECT bug_cc_id,bug_cc.email,bug_cc.added_by,bug_cc.comment,bug_cc.date,user.user_name ".
	    "FROM bug_cc,user ".
	    "WHERE added_by=user.user_id ".
	    "AND bug_id='$bug_id' ORDER BY date DESC";
    return db_query($sql);
}

function bug_data_add_history ($field_name,$old_value,$bug_id,$type=false) {

    // If field is not to be kept in bug change history then do nothing
    if (!bug_data_get_keep_history($field_name)) { return; }

	/*
		handle the insertion of history for these parameters
	*/
	if (!user_isloggedin()) {
		$user=100;
	} else {
		$user=user_getid();
	}

	// If type has a value add it into the sql statement (this is only for
	// the follow up comments (details field)
	if ($type) {
	    $fld_type = ',type'; $val_type = ",'$type'";
	} else {
	    // No comment type specified for a followup comment
	    // so force it to None (100)
	    if ($field_name == 'details') {
		$fld_type = ',type'; $val_type = ",'100'";
	    }
	}
		

	$sql="insert into bug_history(bug_id,field_name,old_value,mod_by,date $fld_type) ".
	    "VALUES ('$bug_id','$field_name','$old_value','$user','".time()."' $val_type)";
	return db_query($sql);
}


//
//       Handles security
//
function bug_data_handle_update ($group_id,$bug_id,$dependent_on_task,
				 $dependent_on_bug,$canned_response,$vfl,
				 &$changes)
{
    global $feedback;

    /*
      Update a bug. Rk: vfl is an variable list of fields, Vary from one project to another
      return true if bug updated, false if nothing changed or DB update failed
    */
    // make sure  required fields are not empty
    if (!$group_id || !$bug_id || !$canned_response || 
	(bug_check_empty_fields($vfl) == false) ) {
	exit_missing_param();
    }

    //get this bug from the db
    $sql="SELECT * FROM bug WHERE bug_id='$bug_id'";
    $result=db_query($sql);

    if (!((db_numrows($result) > 0) && (user_ismember(db_result($result,0,'group_id'),'B1')))) {
	//verify permissions - Must have Tech permission
	exit_permission_denied();
    }

    /*
      See which fields changed during the modification
      and if we must keep history then do it. Also add them to the update
      statement
    */
    $changes = array();
    reset($vfl);
    while (list($field,$value) = each($vfl)) {
	
	// skip over special fields  except for summary which in this 
	// particular case can be processed normally
	if (bug_data_is_special($field) && ($field != 'summary')) {
	    continue; }

	$old_value = db_result($result,0,$field);
	$is_text = (bug_data_is_text_field($field) || bug_data_is_text_area($field));
	if  ($is_text) {
	    $differ = ($old_value != stripslashes(htmlspecialchars($value))); 
	} else if (bug_data_is_date_field($field)) {
	    // if it's a date we must convert the format to unix time
	    list($value,$ok) = util_date_to_unixtime($value);
	    $differ = ($old_value != $value);
	} else {
	    $differ = ($old_value != $value);
	}

	if ($differ) {
	    if ($is_text) {
		$upd_list .= "$field='".htmlspecialchars($value)."',";
		bug_data_add_history($field,addslashes($old_value),$bug_id);
		$value = stripslashes($value);
	    } else {
		$upd_list .= "$field='$value',";
		bug_data_add_history($field,$old_value,$bug_id);
	    }

	    // Keep track of the change
	    $changes[$field]['del']=
		bug_field_display($field,$group_id,$old_value,false,false,true,true);
	    $changes[$field]['add']=
		bug_field_display($field,$group_id,$value,false,false,true,true);
	}
    }

    // Details field history is handled a little differently. Followup comments
    // are added in the bug history along with the comment type.
    // 
    // If a canned response is given it overrides anything typed in the followup
    // comment text area (aka details area). 
    $details = $vfl['details'];
    if ($canned_response != 100) {
	$sql="SELECT * FROM bug_canned_responses WHERE bug_canned_id='$canned_response'";
	$res3=db_query($sql);

	if ($res3 && db_numrows($res3) > 0) {
	    $details = addslashes(util_unconvert_htmlspecialchars(db_result($res3,0,'body')));
	    $feedback .= ' Canned Response Used ';
	} else {
	    $feedback .= ' Unable to use Canned Response ';
	    $feedback .= ' - '.db_error();
	}
    }

    // Details field history is handled a little differently. Followup comments
    // are added in the bug history along with the comment type.
    if ($details != '') {
	bug_data_add_history ('details',htmlspecialchars($details)
			      ,$bug_id, $vfl['comment_type_id']);
	$changes['details']['add'] = stripslashes($details);
	$changes['details']['type'] =
	    bug_data_get_value('comment_type_id',$group_id, $vfl['comment_type_id']);
    }
    /*
      Enter the timestamp if we are changing to closed or declined
    */
    if (bug_data_is_status_closed($vfl['status_id'])) {
	$now=time();
	$upd_list .= "close_date='$now',";
	bug_data_add_history ('close_date',db_result($result,0,'close_date'),$bug_id);
    }

    /*
      Insert the list of task dependencies 
      Also compute the task added - Never mention the 'None' item
    */

	if ( $dependent_on_task != "" ) {
		$added_tasks = explode(",",$dependent_on_task);
	    if (count($added_tasks)>0) {
			bug_data_insert_dependent_tasks($group_id,$added_tasks,$bug_id);
			$changes['Dependent Tasks']['add'] = join(',',$added_tasks);
		}
	}
	
    /*
      Insert the list of bug dependencies
      Also compute the bugs added - Never mention the 'None' item
    */
    
	if ( $dependent_on_bug != "" ) {
		$added_bugs = explode(",",$dependent_on_bug);
	    if (count($added_bugs)>0) {
			bug_data_insert_dependent_bugs($group_id,$added_bugs,$bug_id);
			$changes['Dependent Bugs']['add'] = join(',',$added_bugs);
		}
	}

    /*
      Finally, build the full SQL query and update the bug itself (if need be)
    */

    $result = true;
    if ($upd_list) {
	// strip the excess comma at the end of the update field list
	$upd_list = substr($upd_list,0,-1);

	$sql="UPDATE bug SET $upd_list ".
	    " WHERE bug_id='$bug_id' AND group_id='$group_id'";

	// echo "DBG - update sql : $sql<br>";
	$result=db_query($sql);
    }

    if (!$result) {
	exit_error('UPDATE FAILED','UPDATE FAILED');
	return false;
    } else {
	$feedback .= " Successfully Updated Bug ";
	return true;
    }

}

function bug_data_insert_dependent_bugs($group_id,$array,$bug_id) {
    global $feedback;
	/*
		Insert the list of dependencies
	*/
    $depend_count=count($array);
    if ($depend_count < 1) {
		//if no tasks selected, insert task "none"
		$sql="INSERT INTO bug_bug_dependencies VALUES ('','$bug_id','100')";
		$result=db_query($sql);
    } else {
		for ($i=0; $i<$depend_count; $i++) {
		    if (($depend_count > 1) && ($array[$i]==100)) {
				//don't insert the row if there's more
				//than 1 item selected and this item is the "none task"
			} else {
				// Check if bug_id/bug id already exists
				$sql = "SELECT * FROM bug_bug_dependencies WHERE bug_id='$bug_id' AND is_dependent_on_bug_id='$array[$i]'";
				$result=db_query($sql);
				if (db_numrows($result) <= 0) {
					// Check if bug id is from the current group_id 
					$sql = "SELECT * FROM bug WHERE bug_id='$array[$i]' AND group_id='$group_id'";
					$result=db_query($sql);
					if ($result && db_numrows($result) > 0) {
						$sql="INSERT INTO bug_bug_dependencies VALUES ('','$bug_id','$array[$i]')";
						//echo "\n$sql";
						$result=db_query($sql);
						if (!$result) {
						    $feedback .= ' ERROR inserting dependent_bugs '.db_error();
						}
					} else {
						$feedback .= " ERROR during inserting dependencies  - Bug #'$array[$i]' is not part of this project";
					}
				}
			}
		}
    }
}

function bug_data_insert_dependent_tasks($group_id,$array,$bug_id) {
    global $feedback;
    /*
      Insert the list of dependencies
    */
    $depend_count=count($array);
    if ($depend_count < 1) {
		//if no tasks selected, insert task "none"
		$sql="INSERT INTO bug_task_dependencies VALUES ('','$bug_id','100')";
		$result=db_query($sql);
    } else {
		for ($i=0; $i<$depend_count; $i++) {
		    if (($depend_count > 1) && ($array[$i]==100)) {
			//don't insert the row if there's more
			//than 1 item selected and this item is the "none task"
			} else {
				// Check if bug_id/task id already exists
				$sql = "SELECT * FROM bug_task_dependencies WHERE bug_id='$bug_id' AND is_dependent_on_task_id='$array[$i]'";
				$result=db_query($sql);
				if (db_numrows($result) <= 0) {
					// Check if task id is from the current group_id 
					$sql = "SELECT * FROM project_task pt, project_group_list pgl WHERE pt.project_task_id='$array[$i]' AND pt.group_project_id=pgl.group_project_id AND pgl.group_id='$group_id'";
					$result=db_query($sql);
					if ($result && db_numrows($result) > 0) {
						$sql="INSERT INTO bug_task_dependencies VALUES ('','$bug_id','$array[$i]')";
						//echo "\n$sql";
						$result=db_query($sql);
						if (!$result) {
						    $feedback .= ' ERROR inserting dependent_tasks '.db_error();
						}
					} else {
						$feedback .= " ERROR during inserting dependencies - Task #'$array[$i]' is not part of this project";
					}
				}
		    }
		}
    }
}

function bug_data_update_dependent_tasks($group_id,$array,$bug_id) {
    /*
      DELETE THEN Insert the list of dependencies
    */
    $toss=db_query("DELETE FROM bug_task_dependencies WHERE bug_id='$bug_id'");
    bug_data_insert_dependent_tasks($group_id,$array,$bug_id);
}

/**
 * Create a bug (submit a new bug)
 * 
 * Warning : this function does not add the CC neither the files in the database. It just make some checks on the validity of the values.
 *
 * @global int $sys_max_size_attachment the max size of an attached file
 * @global string $feedback the feedback string
 * @param int $group_id the ID of the group the bug will be added in
 * @param array $vfl the array of value <-> field pair.
 * @param string $add_cc the string of added cc names or emails (here for checks to stop the bug creation before inserting in the DB if needed)
 * @param boolean $add_file a boolean to say if there is an attached file or not (here for checks to stop the bug creation before inserting in the DB if needed)
 * @param string $attached_file the path of the attached file (here for checks to stop the bug creation before inserting in the DB if needed)
 * @return int the bug ID if the creation is a success
 */
function bug_data_create_bug($group_id,$vfl, $add_cc=false, $add_file=false, $attached_file=false) {
    global $feedback, $sys_max_size_attachment;

    //we don't force them to be logged in to submit a bug
    if (!user_isloggedin()) {
	$user=100;
    } else {
	$user=user_getid();
    }

    // make sure  required fields are not empty
    if (bug_check_empty_fields($vfl) == false) {
	exit_missing_param();
    }

    //first make sure this wasn't double-submitted
    $res=db_query("SELECT * FROM bug WHERE submitted_by='$user' AND summary='".$vfl['summary']);
    if ($res && db_numrows($res) > 0) {
	$feedback = ' ERROR - DOUBLE SUBMISSION. You have already submitted a bug with the same summary. Please don\'t do that ';
	return;		
    }

    if ($add_cc) {
        // check that the CC adress are correct
        $arr_email = util_split_emails($add_cc);
        if (! bug_validate_cc_list($arr_email, $message)) {
            exit_error("Error - The CC list is invalid", $message);
        }
    }
    if ($add_file && $attached_file) {
        // check that the attached files are correct
        $data_attached_file = addslashes(fread( fopen($attached_file, 'r'), filesize($attached_file)));
        if ((strlen($data_attached_file) < 1) || (strlen($data_attached_file) > $sys_max_size_attachment)) {
            $feedback .= " - File not attached: File size must be less than ".formatByteToMb($sys_max_size_attachment)." Mbytes";
            return false;
        }
    }
    
    // Finally, create the bug itself
    // Remark: this SQL query only sets up the values for fields used by
    // this project. For other unused fields we assume that the DB will set
    // up an appropriate default value (see bug table definition)

    // build the variable list of fields and values
    reset($vfl);
    while ( list($field,$value) = each($vfl)) {
	if (bug_data_is_special($field)) { continue; }
	$vfl_cols .= ','.$field;
	if (bug_data_is_text_area($field) || bug_data_is_text_field($field)) {
	    $value = htmlspecialchars($value);
	} else if (bug_data_is_date_field($field)) {
	    // if it's a date we must convert the format to unix time
	    list($value,$ok) = util_date_to_unixtime($value);
	}
	$vfl_values .= ',\''.$value.'\'';
    }    

    // Add all special fields that were not handled in the previous block
    $fixed_cols = 'close_date,group_id,submitted_by,date,summary,details';
    $fixed_values = "'0','$group_id','$user','".time()."','".
	htmlspecialchars($vfl['summary'])."','".htmlspecialchars($vfl['details'])."'";


    $sql="INSERT INTO bug ($fixed_cols $vfl_cols) VALUES ($fixed_values $vfl_values)";
    //echo "DBG - SQL insert bug: $sql";
    $result=db_query($sql);
    $bug_id=db_insertid($result);

    if (!$bug_id) {
	$feedback = 'INSERT new bug failed. Report to the Administrator<br>'.
	    'SQL statement:<br>'.$sql.'<br>';
    }

    //now return the bug_id
    return $bug_id;
}

function bug_data_get_value($field,$group_id,$value_id,$by_field_id=false) {
    /*
      simply return the value associated with a given value_id
      for a given field of a given group. If associated value not
      found then return value_id itself.
      By doing so if this function is called by mistake on a field with type
      text area or text field then it returns the text itself.
    */

    // close_date and assigned_to fields are special select box fields
    if ($value_func = bug_data_get_value_function($field)) {
	// For now all of our value functions returns users so there is no need
	// to make a test for the type of value function it is
	// if ($value_func == '...')
	return user_getname($value_id);
    } else if (bug_data_is_date_field($field)) {
	return format_date($sys_datefmt,$value_id);
    }

    if ($by_field_id) {
	$field_id = $field;
    } else {
	$field_id = bug_data_get_field_id($field);
    }

    // Look for project specific values first...
    $sql="SELECT * FROM bug_field_value ".
	"WHERE  bug_field_id='$field_id' AND group_id='$group_id' ".
	"AND value_id='$value_id'";
    $result=db_query($sql);
    if ($result && db_numrows($result) > 0) {
	return db_result($result,0,'value');
    } 

    // ... if it fails, look for system wide default values (group_id=100)...
    $sql="SELECT * FROM bug_field_value ".
	"WHERE  bug_field_id='$field_id' AND group_id='100' ".
	"AND value_id='$value_id'";
    $result=db_query($sql);
    if ($result && db_numrows($result) > 0) {
	return db_result($result,0,'value');
    } 
    
    // No value found for this value id !!!
    return $value_id.'(Error - Not Found)';

}

function bug_data_get_canned_responses ($group_id) {
    /*
      Show defined and site-wide responses
    */
    $sql="SELECT bug_canned_id,title,body FROM bug_canned_responses WHERE ".
	"(group_id='$group_id' OR group_id='0')";

    // return handle for use by select box
    return db_query($sql);
}

function bug_data_get_reports($group_id, $user_id) {
    
    // If user is unknown then get only project-wide and system wide reports
    // else get personal reports in addition  project-wide and system wide.
    $sql = 'SELECT report_id,name FROM bug_report WHERE ';
    if (!$user_id || ($user_id == 100)) {
	$sql .= "(group_id=$group_id AND scope='P') OR scope='S' ".
	    'ORDER BY report_id';
    } else {
	$sql .= "(group_id=$group_id AND (user_id=$user_id OR scope='P')) OR ".
	    "scope='S' ORDER BY scope,report_id";
    }
    //echo "DBG sql report = $sql";
    return db_query($sql);
}

function bug_data_get_notification($user_id) {
    $sql = "SELECT role_id,event_id,notify FROM bug_notification WHERE user_id='$user_id'";
    return db_query($sql);
}

function bug_data_get_notification_with_labels($user_id) {
    $sql = 'SELECT role_label,event_label,notify FROM bug_notification_role, bug_notification_event,bug_notification '.
	"WHERE bug_notification.role_id=bug_notification_role.role_id AND ".
	"bug_notification.event_id=bug_notification_event.event_id AND user_id='$user_id'";
    return db_query($sql);
}

function bug_data_get_notification_roles() {
    $sql = 'SELECT * FROM bug_notification_role ORDER BY rank ASC;';
    return db_query($sql);
}

function bug_data_get_notification_events() {
    $sql = 'SELECT * FROM bug_notification_event ORDER BY rank ASC;';
    return db_query($sql);
}

function bug_data_delete_notification($user_id) {
    $sql = "DELETE FROM bug_notification WHERE user_id='$user_id'";
    return db_query($sql);
}

function bug_data_insert_notification($user_id, $arr_roles, $arr_events,
				    $arr_notification) {
    $sql = 'INSERT INTO bug_notification (user_id,role_id,event_id,notify) VALUES ';

    $num_roles = count($arr_roles);
    $num_events = count($arr_events);
    for ($i=0; $i<$num_roles; $i++) {
	$role_id = $arr_roles[$i]['role_id'];
 	for ($j=0; $j<$num_events; $j++) { 
	    $event_id = $arr_events[$j]['event_id'];
 	    $sql .= "('$user_id','$role_id','$event_id','".$arr_notification[$role_id][$event_id]."'),"; 
 	} 
     } 
     $sql = substr($sql,0,-1); // remove extra comma at the end 
     return db_query($sql); 
}

function bug_data_get_watchers($user_id) {
    $sql = "SELECT user_id FROM bug_watcher WHERE watchee_id='$user_id'";
    return db_query($sql);    
}

function bug_data_get_watchees($user_id) {
    $sql = "SELECT watchee_id FROM bug_watcher WHERE user_id='$user_id'";
    return db_query($sql);    
}

function bug_data_insert_watchees($user_id, $arr_watchees) {
    $sql = 'INSERT INTO bug_watcher (user_id,watchee_id) VALUES ';
    $num_watchees = count($arr_watchees);
    for ($i=0; $i<$num_watchees; $i++) {
	$sql .= "('$user_id','".$arr_watchees[$i]."'),";
    } 
    $sql = substr($sql,0,-1); // remove extra comma at the end 
    return db_query($sql);
}

function bug_data_delete_watchees($user_id) {
    $sql = "DELETE FROM bug_watcher WHERE user_id='$user_id'";
    return db_query($sql);
}

function bug_data_delete_dependent_task($bug_id=false,$is_dependent_on_task_id=false) {
	global $feedback;
	
    // Delete the dependency
    $res = db_query("DELETE FROM bug_task_dependencies WHERE bug_id=$bug_id AND is_dependent_on_task_id=$is_dependent_on_task_id");
    if (db_affected_rows($res) <= 0) {
		$feedback .= "Error deleting dependency : ".db_error($res);
    } else {
		$feedback .= "Dependency successfully deleted";
    }
}

function bug_data_delete_dependent_bug($bug_id=false,$is_dependent_on_bug_id=false) {
	global $feedback;

    // Delete the dependency
    $res = db_query("DELETE FROM bug_bug_dependencies WHERE bug_id=$bug_id AND is_dependent_on_bug_id=$is_dependent_on_bug_id");
    if (db_affected_rows($res) <= 0) {
		$feedback .= "Error deleting dependency : ".db_error($res);
    } else {
		$feedback .= "Dependency successfully deleted";
    }
}

?>
