<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2002. All rights reserved
//
// $Id$
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
	'display_size,label, description,scope,required,empty_ok,keep_history,special, '.
	'group_id, use_it,show_on_add,show_on_add_members, place '.
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
	'display_size,label, description,scope,required,empty_ok,keep_history,special, '.
	'group_id, use_it, show_on_add, show_on_add_members, place '.
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
    reset($BF_USAGE_BY_NAME);
    while (list($key, $val) = each($BF_USAGE_BY_NAME)) {
    	//echo "<br>DBG - $key -> use_it: $val[use_it], $val[place]";
    }
      
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
    if ($field_name == 'assigned_to') {
	    $res_value = bug_data_get_technicians($group_id);
    } else if ($field_name == 'submitted_by') {
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

function bug_data_is_special($field, $by_field_id=false) {
    global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
    return($by_field_id ? $BF_USAGE_BY_ID[$field]['special']: $BF_USAGE_BY_NAME[$field]['special']);
}

function bug_data_is_empty_ok($field, $by_field_id=false) {
    global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
    return($by_field_id ? $BF_USAGE_BY_ID[$field]['empty_ok']: $BF_USAGE_BY_NAME[$field]['empty_ok']);
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
    return($by_field_id ? $BF_USAGE_BY_ID[$field]['label'] : $BF_USAGE_BY_NAME[$field]['label']);
}

function bug_data_get_description($field, $by_field_id=false) {
    global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
    return($by_field_id ? $BF_USAGE_BY_ID[$field]['description'] : $BF_USAGE_BY_NAME[$field]['description']);
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
    return($by_field_id ? $BF_USAGE_BY_ID[$field]['keep_history'] : $BF_USAGE_BY_NAME[$field]['keep_history']);
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
	return(explode('/',$BF_USAGE_BY_ID[$field]['display_size']));
    } else {
	return(explode('/',$BF_USAGE_BY_NAME[$field]['display_size']));
    }
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
	    $BF_VALUE_BY_NAME[$field][$fv_array['value_id']] = $fv_array[1];
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

 function bug_data_update_usage($field_name, $group_id, $use_it, $rank,
				$show_on_add_members=0, $show_on_add=0)
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

    // See if this field usage exists in the table for this project
    $sql = 'SELECT bug_field_id FROM bug_field_usage '.
	"WHERE bug_field_id='$field_id' AND group_id='$group_id'";
    $result = db_query($sql);
    $rows = db_numrows($result);

    // if it does exist then update it else insert a new usage entry for this field.
    if ($rows) {
	$sql = 'UPDATE bug_field_usage '.
	    "SET use_it='$use_it',show_on_add='$show_on_add',".
	    "show_on_add_members='$show_on_add_members',place='$rank' ".
	    "WHERE bug_field_id='$field_id' AND group_id='$group_id'";
	$result = db_query($sql);
    } else {
	$sql = 'INSERT INTO  bug_field_usage '.
	    "VALUES ('$field_id','$group_id','$use_it','$show_on_add',".
	    "'$show_on_add_members','$rank')";
	$result = db_query($sql);
    }

    if (db_affected_rows($result) < 1) {
	$feedback .= ' UPDATE OF FIELD  USAGE FAILED ';
	$feedback .= ' - '.db_error();
    } else {
	$feedback .= ' Field usage updated ';
    }

}

function bug_data_get_technicians ($group_id=false) {
	$sql="SELECT user.user_id,user.user_name ".
		"FROM user,user_group ".
		"WHERE user.user_id=user_group.user_id ".
		"AND user_group.bug_flags IN (1,2) ".
		"AND user_group.group_id='$group_id' ".
		"ORDER BY user.user_name";
	return db_query($sql);
}

function bug_data_get_submitters ($group_id=false) {
	$sql="SELECT DISTINCT user.user_id,user.user_name ".
		"FROM user,bug ".
		"WHERE user.user_id=bug.submitted_by ".
		"AND bug.group_id='$group_id' ".
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
	$sql="SELECT is_dependent_on_task_id FROM bug_task_dependencies WHERE bug_id='$bug_id'";
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
	$sql="SELECT is_dependent_on_bug_id FROM bug_bug_dependencies WHERE bug_id='$bug_id'";
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

function bug_data_add_history ($field_name,$old_value,$bug_id,$type=false) {
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

    if (!$group_id || !$bug_id || !$canned_response) {
	//force inclusion of parameters
	exit_missing_param();
    }

    //get this bug from the db
    $sql="SELECT * FROM bug WHERE bug_id='$bug_id'";
    $result=db_query($sql);

    if (!((db_numrows($result) > 0) && (user_ismember(db_result($result,0,'group_id'),'B2')))) {
	//verify permissions
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
      DELETE THEN Insert the list of task dependencies 
      Also compute the task added and removed - Never mention the 'None' item
    */

    $old_dep_on_task = util_result_column_to_array (bug_data_get_dependent_tasks($bug_id));

    bug_data_update_dependent_tasks($dependent_on_task,$bug_id);

    // Add None in both lists to make sure it is never taken into account
    // in the diffs
    $old_dep_on_task[] = 100;
    $dependent_on_task[] = 100;
    list($deleted_tasks,$added_tasks) = util_double_diff_array($old_dep_on_task,$dependent_on_task);

    $changes['Dependent Tasks']['del'] = join(',',$deleted_tasks);
    $changes['Dependent Tasks']['add'] = join(',',$added_tasks);


    /*
      DELETE THEN Insert the list of bug dependencies
      Also compute the bugs added and removed - Never mention the 'None' item
    */
    
    $old_dep_on_bug = util_result_column_to_array (bug_data_get_dependent_bugs($bug_id));

    bug_data_update_dependent_bugs($dependent_on_bug,$bug_id);
    
    // Add None in both lists to make sure it is never taken into account
    // in the diffs
    $dependent_on_bug[] = 100;
    $old_dep_on_bug[] = 100;
    list($deleted_bugs, $added_bugs) = util_double_diff_array($old_dep_on_bug, $dependent_on_bug);

    $changes['Dependent Bugs']['del'] = join(',',$deleted_bugs);
    $changes['Dependent Bugs']['add'] = join(',',$added_bugs);


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

function bug_data_insert_dependent_bugs($array,$bug_id) {
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
		$sql="INSERT INTO bug_bug_dependencies VALUES ('','$bug_id','$array[$i]')";
		//echo "\n$sql";
		$result=db_query($sql);

		if (!$result) {
		    $feedback .= ' ERROR inserting dependent_bugs '.db_error();
		}
	    }
	}
    }
}

function bug_data_update_dependent_bugs($array,$bug_id) {
    /*
      DELETE THEN Insert the list of dependencies
    */
    $toss=db_query("DELETE FROM bug_bug_dependencies WHERE bug_id='$bug_id'");
    bug_data_insert_dependent_bugs($array,$bug_id);
}

function bug_data_insert_dependent_tasks($array,$bug_id) {
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
		$sql="INSERT INTO bug_task_dependencies VALUES ('','$bug_id','$array[$i]')";
		//echo "\n$sql";
		$result=db_query($sql);

		if (!$result) {
		    $feedback .= ' ERROR inserting dependent_tasks '.db_error();
		}
	    }
	}
    }
}

function bug_data_update_dependent_tasks($array,$bug_id) {
    /*
      DELETE THEN Insert the list of dependencies
    */
    $toss=db_query("DELETE FROM bug_task_dependencies WHERE bug_id='$bug_id'");
    bug_data_insert_dependent_tasks($array,$bug_id);
}

function bug_data_create_bug($group_id,$vfl) {
    global $feedback;

    //we don't force them to be logged in to submit a bug
    if (!user_isloggedin()) {
	$user=100;
    } else {
	$user=user_getid();
    }

    // make sure minimally required params are not empty
    if (!$group_id || !$vfl['summary'] || !$vfl['details']) {
	exit_missing_param();
    }

    //first make sure this wasn't double-submitted
    $res=db_query("SELECT * FROM bug WHERE submitted_by='$user' AND summary='".$vfl['summary']);
    if ($res && db_numrows($res) > 0) {
	$feedback = ' ERROR - DOUBLE SUBMISSION. You have already submitted a bug with the same summary. Please don\'t do that ';
	return 0;		
    }

    // Finally, create the bug itself
    // Remark: this SQL query only sets up the values for fields used by
    // this project. For other unused fields we assume that the DB will set
    // up an appropriate default value (see bug table definition)

    // build the variable list of fiels and values
    reset($vfl);
    while ( list($field,$value) = each($vfl)) {
	if (bug_data_is_special($field)) { continue; }
	$vfl_cols .= ','.$field;
	if (bug_data_is_text_area($field) ||
	    bug_data_is_text_field($field)) {
	    $value = htmlspecialchars($value);
	}
	$vfl_values .= ',\''.$value.'\'';
    }    

    // Add all special fields that were not handled in the previous block
    $fixed_cols = 'close_date,group_id,status_id,submitted_by,date,summary,details';
    $fixed_values = "'0','$group_id','1','$user','".time()."','".
	htmlspecialchars($vfl['summary'])."','".htmlspecialchars($vfl['details'])."'";


    $sql="INSERT INTO bug ($fixed_cols $vfl_cols) VALUES ($fixed_values $vfl_values)";
    //echo "DBG - SQL insert bug: $sql";
    $result=db_query($sql);
    $bug_id=db_insertid($result);

    if (!$bug_id) {
	$feedback = 'INSERT new bug failed. Report to the Administrator<br>'.
	    'SQL statement:<br>'.$sql.'<br>';
    }

    /*
      set up the default rows in the dependency table
      both rows will be dependent on id=100
    */
    bug_data_insert_dependent_bugs($array,$bug_id);
    bug_data_insert_dependent_tasks($array,$bug_id);

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
    if (($field == 'assigned_to') || ($field == 'submitted_by')) {
	return user_getname($value_id);
    } else if (bug_data_is_date_field($field)) {
	return date($sys_datefmt,$value_id);
    }

    // If the field is a CodeX wide field (scope=CodeX) then look for 
    // values assigned to group None anyway not to any specific group
    if (!bug_data_is_project_scope($field, $by_field_id)) {
	$group_id = 100;
    }

    if ($by_field_id) {
	$field_id = $field;
    } else {
	$field_id = bug_data_get_field_id($field);
    }

    $sql="SELECT * FROM bug_field_value ".
	"WHERE  bug_field_id='$field_id' AND group_id='$group_id' ".
	"AND value_id='$value_id'";
    $result=db_query($sql);
    if ($result && db_numrows($result) > 0) {
	return db_result($result,0,'value');
    } 

    // else try and see if we can find a value for group None
    if ($group_id != 100) {
	$sql="SELECT * FROM bug_field_value ".
	    "WHERE  bug_field_id='$field_id' AND group_id='100' ".
	    "AND value_id='$value_id'";
	$result=db_query($sql);
	if ($result && db_numrows($result) > 0) {
	    return db_result($result,0,'value');
	} 
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
?>
