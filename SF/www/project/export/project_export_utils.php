<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
// $Id$

$datetime_fmt = 'Y-m-d H:i:s';
$datetime_msg = 'yyyy-mm-dd hh:mm:ss';



function tocsv($string) {

    // Escape the double quote character by doubling it
    $string = ereg_replace('"', '""', $string);

    //Surround with double quotes if there is a comma 
    // or a space in the string
    if (strchr($string,' ') || strchr($string,',')) {
	return "\"$string\"";
    } else {
	return $string;
    }

}

function build_csv_header($col_list, $lbl_list) {

    reset($col_list);
    while (list(,$col) = each($col_list)) {
	$line .= tocsv($lbl_list[$col]).',';
    }
    $line = substr($line,0,-1);
    return $line;
}

function build_csv_record($col_list, $record) {

    reset($col_list);
    while (list(,$col) = each($col_list)) {
	$line .= tocsv($record[$col]).',';
    }
    $line = substr($line,0,-1);
    return $line;
}

function insert_record_in_table($dbname, $tbl_name, $col_list, $record) {

    // Generate the values list for the INSERT statement
    reset($col_list); $values = '';
    while (list(,$col) = each($col_list)) {
	$values .= '\''.addslashes($record[$col]).'\',';
    }
    // remove excess comma at the end
    $values = substr($values,0,-1);
    
    $sql_insert = "INSERT INTO $tbl_name VALUES ( $values )";
    $res_insert = db_project_query($dbname,$sql_insert);
    
    if (!$res_insert) {
	$feedback .= '<BR>Error in Insert '.$tbl_name.':'.db_project_error();
    }

}

function display_exported_fields($col_list,$lbl_list,$dsc_list,$sample_val){
   
    $title_arr=array();
    $title_arr[]='Field name';
    $title_arr[]='Label';
    $title_arr[]='Sample Value';
    $title_arr[]='Description';

    echo html_build_list_table_top ($title_arr);
    reset($col_list);
    while (list(,$col) = each($col_list)) {
	echo '<tr bgcolor="'.util_get_alt_row_color($cnt++).'">'.
	    '<td><b>'.$col.'</b></td><td>'.$lbl_list[$col].
	    '</td><td>'.nl2br($sample_val[$col]).'</td><td>'.$dsc_list[$col].'</td></tr>';
    }

    echo '</table>';
}

function pick_a_record_at_random($result, $numrows, $col_list) {

    /* return a record from a result set at random using the column
         list passed as an argument */

    $record = array();

    // If there is an item  available pick one at random
    // and display Sample values. 
    if ($result && $numrows > 0) {
	$pickone = ($numrows <= 1 ? 0:rand(0, $numrows-1));
    }

    // Build the array with the record picked at random
    reset($col_list); $record = array();
    while (list(,$col) = each($col_list)) {
	$record[$col] = db_result($result,$pickone,$col);
    }

    return $record;
}

function prepare_textarea($textarea) {
    // Turn all HTML entities in ASCII and remove all \r characters
    // because even MS Office apps don't like it in text cells (Excel)
    return( str_replace(chr(13),"",util_unconvert_htmlspecialchars($textarea)) );
}

function prepare_bug_record($group_id, &$col_list, &$record) {

    global $datetime_fmt;
    /*
           Prepare the column values in the bug record
           Input: a row from the bug_history database (passed by
                   reference.
          Output: the same row with values transformed for export
       */

    // replace the date fields with human readable dates that
    // is also accepted as a valid format in future import
    $record['date'] = format_date($datetime_fmt,$record['date']);
    if ($record['close_date'] == 0)
	$record['close_date'] = '';
    else
	$record['close_date'] = format_date($datetime_fmt,$record['close_date']);
	
    // all text fields converted from HTML to ASCII
    reset($col_list);
    $line = '';
    while (list(,$col) = each($col_list)) {
	if (bug_data_is_text_field($col) || bug_data_is_text_area($col)) {
	    $record[$col] = prepare_textarea($record[$col]);

	} else if (bug_data_is_select_box($col) && ($col != 'severity') &&
		  !bug_data_is_username_field($col) ) {
	    // All value_ids transformed in human readable values 
	    // except severity that remains a number and usernames
	    // which are already in clear
	    $record[$col] = bug_data_get_cached_field_value($col, $group_id, $record[$col]);
	}
    }

    $bug_id = $record['bug_id'];
    $record['follow_ups'] = pe_utils_format_bug_followups($group_id,$bug_id);
    $record['is_dependent_on_task_id'] = pe_utils_format_bug_task_dependencies($group_id,$bug_id);
    $record['is_dependent_on_bug_id'] = pe_utils_format_bug_bug_dependencies($group_id,$bug_id);

}

function pe_utils_format_bug_followups($group_id,$bug_id) {
    global $BUG_FU, $sys_datefmt;

    // return all the follow-up comments attached to a given bug
    // Do a big SQl query the first time and then cache the results
    // instead of doing one SQL query for each bug

    if (!isset($BUG_FU)) {


	$sql = 'SELECT DISTINCT bug_history.bug_id,bug_history.old_value,'.
	    'user.user_name AS mod_by,bug_history.date, bug_field_value.value AS type '.
	    'FROM bug_history,bug,user,bug_field_value,bug_field '.
	    'WHERE (bug.bug_id=bug_history.bug_id '.
	    "AND bug.group_id='$group_id') ".
	    'AND (bug_field_value.value_id=bug_history.type '.
	    'AND bug_field_value.bug_field_id=bug_field.bug_field_id '.
	    'AND bug_field.field_name = \'comment_type_id\'  '.
	    "AND (bug_field_value.group_id ='$group_id' OR bug_field_value.group_id ='100' )) ".
	    'AND user.user_id=bug_history.mod_by '.
	    'AND bug_history.field_name = \'details\' ORDER BY date DESC';

	$res = db_query($sql);
	if ($res) {

	    while ($row = db_fetch_array($res)) {

		$BUG_FU[$row['bug_id']] .= 
		    '=================================================='.
		    "\n".
		    'Type: '.$row['type'].'     By: '.$row['mod_by'].'      On: '.
		    format_date($sys_datefmt,$row['date'])."\n\n".
		    prepare_textarea($row['old_value']).
		    "\n";
	    }

	} else
	    $BUG_FU = array();
    }

    return $BUG_FU[$bug_id];

}

function pe_utils_format_bug_task_dependencies ($group_id,$bug_id) {
    global $BUG_TD;

    // return all the tasks a bug depends on
    // Do a big SQL query the first time and then cache the results
    // instead of doing one SQL query for each bug

    if (!isset($BUG_TD)) {

	$sql = 'SELECT bug_task_dependencies.bug_id,'.
	    'bug_task_dependencies.is_dependent_on_task_id '.
	    'FROM bug_task_dependencies, bug '.
	    'WHERE bug_task_dependencies.bug_id= bug.bug_id AND '.
	    "bug.group_id='$group_id' ";

	$res = db_query($sql);
	if ($res) {

	    while ($row = db_fetch_array($res)) {

		$tid = $row['bug_id'];

		if ( isset($BUG_TD[$tid]) ) {
		    // if this bug id entry already exists then
		    // it means there is an additional "assigned to" user
		    $BUG_TD[$tid] .= ','.$row['is_dependent_on_task_id'];  
		} else {
		    $BUG_TD[$tid] = 
			($row['is_dependent_on_task_id'] == 100 ?
			 'None' : $row['is_dependent_on_task_id']);
		}
	    }

	} else {
	    $BUG_TD = array();
	}
    }

    return $BUG_TD[$bug_id];

}

function pe_utils_format_bug_bug_dependencies($group_id,$bug_id) {
    global $BUG_BD;

    // return all the bugs a bug depends on
    // Do a big SQL query the first time and then cache the results
    // instead of doing one SQL query for each bug

    if (!isset($BUG_BD)) {

	$sql = 'SELECT bug_bug_dependencies.bug_id,'.
	    'bug_bug_dependencies.is_dependent_on_bug_id '.
	    'FROM bug_bug_dependencies, bug '.
	    'WHERE bug_bug_dependencies.bug_id= bug.bug_id AND '.
	    "bug.group_id='$group_id' ";

	$res = db_query($sql);
	if ($res) {

	    while ($row = db_fetch_array($res)) {

		$tid = $row['bug_id'];

		if ( isset($BUG_BD[$tid]) ) {
		    // if this bug id entry already exists then
		    // it means there is an additional "assigned to" user
		    $BUG_BD[$tid] .= ','.$row['is_dependent_on_bug_id'];  
		} else {
		    $BUG_BD[$tid] = 
			($row['is_dependent_on_bug_id'] == 100 ?
			 'None' : $row['is_dependent_on_bug_id']);
		}
	    }

	} else {
	    $BUG_BD = array();
	}
    }

    return $BUG_BD[$bug_id];

}

function prepare_bug_history_record(&$record) {

    global $datetime_fmt;

    /*
           Prepare the column values in the bug history  record
           Input: a row from the bug_history database (passed by
                   reference.
          Output: the same row with values transformed for export
       */

    // replace the modification date field with human readable dates 
    $record['date'] = format_date($datetime_fmt,$record['date']);

    if (bug_data_is_select_box($record['field_name']) &&
	($record['field_name'] != 'severity') ) {
	
	// asking for the value of a value_id for each new
	// history record can become a significant bottleneck
	// for projects with lots of bugs. A solution is to cache
	// the values when we get them the first time (either here
	// or in the bug_data_get_value function
	$record['old_value']= bug_data_get_value($record['field_name'],$group_id,$record['old_value']);
    } else {
	// Make close date human readable
	if ($record['field_name'] == 'close_date') {
	    if ($record['old_value'] == 0)
		$record['old_value'] = '';
	    else
		$record['old_value'] = format_date($datetime_fmt,$record['old_value']);
	}
	
	// revert HTML entities to ASCII code in text fields
	if ( ($record['field_name'] == 'summary') ||
	     ($record['field_name'] == 'details') ) {
	    $record['old_value'] = prepare_textarea($record['old_value']);
	}
    }

    // Decode the comment type value. If null make sure there is
    // a blank entry in the array
    if (isset($record['type'])) {
	$record['type'] = bug_data_get_value('comment_type_id',$group_id,$record['type']);
    } else {
	$record['type']='';
    }

}

function prepare_task_history_record(&$record) {

    global $datetime_fmt;

    /*
           Prepare the column values in the task history  record
           Input: a row from the project_history database (passed by
                   reference.
          Output: the same row with values transformed for database
       */

    // replace the modification date field with human readable dates
    $record['date'] = format_date($datetime_fmt,$record['date']);

    switch ($record['field_name']) {

    case 'start_date':
    case 'end_date':
	if ($record['old_value'] == 0)
	    $record['old_value'] = '';
	else
	    $record['old_value'] = format_date($datetime_fmt,$record['old_value']);
	break;

    case 'summary':
    case 'details':
	$record['old_value'] = prepare_textarea($record['old_value']);
	break;

    case 'status_id':
	$record['old_value'] = pm_data_get_status_name($record['old_value']);
	break;

    case 'subproject_id':
	$record['old_value'] = pm_data_get_group_name($record['old_value']);
	break;

    default:
	break;
    }

}

function prepare_task_record($group_id, &$record) {

    global $datetime_fmt;

    /*
           Prepare the column values in the task  record
           Input: a row from the project_task table (passed by
                   reference.
          Output: the same row with values transformed for database export
       */

    if ($record['start_date'] == 0)
	$record['start_date'] = '';
    else
	$record['start_date'] = format_date($datetime_fmt,$record['start_date']);

    if ($record['end_date'] == 0)
	$record['end_date'] = '';
    else
	$record['end_date'] = format_date($datetime_fmt,$record['end_date']);

    $record['summary'] = prepare_textarea($record['summary']);
    $record['details'] = prepare_textarea($record['details']);

    $task_id = $record['project_task_id'];
    $record['assigned_to'] = pe_utils_format_task_assignees ($group_id,$task_id);
    $record['follow_ups'] = pe_utils_format_task_followups ($group_id,$task_id);
    $record['is_dependent_on_task_id'] = pe_utils_format_task_dependencies($group_id,$task_id);

}

function prepare_survey_responses_record($group_id, &$record) {

    global $datetime_fmt;

    /*
           Prepare the column values in the task  record
           Input: a row from the project_task table (passed by
                   reference.
          Output: the same row with values transformed for database export
       */

    $record['date'] = format_date($datetime_fmt,$record['date']);
    $record['reponse'] = prepare_textarea($record['response']);
 
}

function pe_utils_format_task_assignees ($group_id,$task_id) {
    
    global $TASK_AT;

    // return all the people's name assigned to a given task
    // Do a big SQl query the first time and then cache the results
    // instead of doing one SQL query for each task

    if (!isset($TASK_AT)) {

	$sql = 'SELECT project_assigned_to.project_task_id,'.
	    'user.user_name AS assigned_to '.
	    'FROM project_assigned_to,project_task,project_group_list,user '.
	    'WHERE (project_task.project_task_id=project_assigned_to.project_task_id '.
	    'AND project_task.group_project_id=project_group_list.group_project_id '.
	    "AND project_group_list.group_id='$group_id') AND ".
	    'user.user_id=project_assigned_to.assigned_to_id';

	$res = db_query($sql);
	if ($res) {

	    while ($row = db_fetch_array($res)) {

		$tid = $row['project_task_id'];

		if ( isset($TASK_AT[$tid]) ) {
		    // if this task id entry already exists then
		    // it means there is an additional "assigned to" user
		    $TASK_AT[$tid] .= ','.$row['assigned_to'];  
		} else {
		    $TASK_AT[$tid] = $row['assigned_to'];
		}
	    }

	} else {
	    $TASK_AT = array();
	}
    }

    return $TASK_AT[$task_id];

}

function pe_utils_format_task_followups ($group_id,$task_id) {
    global $TASK_FU, $sys_datefmt;

    // return all the follow-up comments attached to a given task
    // Do a big SQl query the first time and then cache the results
    // instead of doing one SQL query for each task

    if (!isset($TASK_FU)) {


	$sql = 'SELECT project_history.project_task_id,project_history.old_value,'.
	    'user.user_name AS mod_by,project_history.date '.
	    'FROM project_history,project_task,project_group_list,user '.
	    'WHERE (project_task.project_task_id=project_history.project_task_id '.
	    'AND project_task.group_project_id=project_group_list.group_project_id '.
	    "AND project_group_list.group_id='$group_id') ".
	    'AND user.user_id=project_history.mod_by '.
	    'AND project_history.field_name = \'details\' ORDER BY date DESC';

	$res = db_query($sql);
	if ($res) {

	    while ($row = db_fetch_array($res)) {

		$TASK_FU[$row['project_task_id']] .= 
		    '=================================================='."\n".
		    'By: '.$row['mod_by'].'      On: '.
		    format_date($sys_datefmt,$row['date'])."\n\n".
		    prepare_textarea($row['old_value']).
		    "\n";
	    }

	} else
	    $TASK_FU = array();
    }

    return $TASK_FU[$task_id];

}

function pe_utils_format_task_dependencies ($group_id,$task_id) {
    global $TASK_TD;

    // return all the tasks a task depends on
    // Do a big SQL query the first time and then cache the results
    // instead of doing one SQL query for each task

    if (!isset($TASK_TD)) {

	$sql = 'SELECT project_dependencies.project_task_id,'.
	    'project_dependencies.is_dependent_on_task_id '.
	    'FROM project_dependencies, project_task, project_group_list '.
	    'WHERE project_dependencies.project_task_id= project_task.project_task_id AND '.
	    'project_task.group_project_id=project_group_list.group_project_id AND '.
	    "project_group_list.group_id='$group_id' ";

	$res = db_query($sql);
	if ($res) {

	    while ($row = db_fetch_array($res)) {

		$tid = $row['project_task_id'];

		if ( isset($TASK_TD[$tid]) ) {
		    // if this task id entry already exists then
		    // it means there is an additional "assigned to" user
		    $TASK_TD[$tid] .= ','.$row['is_dependent_on_task_id'];  
		} else {
		    $TASK_TD[$tid] = 
			($row['is_dependent_on_task_id'] == 100 ?
			 'None' : $row['is_dependent_on_task_id']);
		}
	    }

	} else {
	    $TASK_TD = array();
	}
    }

    return $TASK_TD[$task_id];

}

function prepare_support_request_record($group_id, &$record) {

    global $datetime_fmt;

    /*
           Prepare the column values in the SR record
           Input: a row from the support table (passed by reference)
          Output: the same row with values transformed for database export
       */

    if ($record['open_date'] == 0)
	$record['open_date'] = '';
    else
	$record['open_date'] = format_date($datetime_fmt,$record['open_date']);

    if ($record['close_date'] == 0)
	$record['close_date'] = '';
    else
	$record['close_date'] = format_date($datetime_fmt,$record['close_date']);

    $record['summary'] = prepare_textarea($record['summary']);
    $sr_id = $record['support_id'];
    $record['follow_ups'] = pe_utils_format_sr_messages($group_id,$sr_id);

}

function pe_utils_format_sr_messages($group_id,$sr_id) {
    global $SR_MSG, $sys_datefmt;

    // return all the follow-up comments attached to a given bug
    // Do a big SQl query the first time and then cache the results
    // instead of doing one SQL query for each bug

    if (!isset($SR_MSG)) {


	$sql = 'SELECT support_messages.support_id, support_messages.from_email,'.
	    'support_messages.date,support_messages.body '.
	    'FROM support_messages, support '.
	    'WHERE (support_messages.support_id=support.support_id AND '.
	    "support.group_id='$group_id') ".
	    'ORDER BY date DESC';

	$res = db_query($sql);
	if ($res) {

	    while ($row = db_fetch_array($res)) {

		$SR_MSG[$row['support_id']] .= 
		    '=================================================='.
		    "\n".
		    'By: '.$row['from_email'].'      On: '.
		    format_date($sys_datefmt,$row['date'])."\n\n".
		    prepare_textarea($row['body']).
		    "\n";
	    }

	} else
	    $SR_MSG = array();
    }

    return $SR_MSG[$sr_id];

}

function display_db_params () {
    global $sys_default_domain, $dbname;
?>
<table align="center" cellpadding="0">
      <tr><td>Server:</td><td><?php echo $sys_default_domain; ?></td></tr>
      <tr><td>Port:</td><td>(leave it blank)</td></tr>
      <tr><td>Database:</td><td><?php echo $dbname; ?></td></tr>
      <tr><td>User:</td><td>cxuser</td></tr>
      <tr><td>Password:</td><td>(leave it blank)</td></tr>
</table>
<?php
}

function db_project_query($dbname,$qstring,$print=0) {
	if ($print) print "<br>Query is: $qstring<br>";
	$GLOBALS['db_project_qhandle'] = @mysql_db_query($dbname,$qstring);
	return $GLOBALS['db_project_qhandle'];
}


function db_project_create($dbname) {

    /*
          Create the db if it does not exist and grant read access only
          to the user 'cxuser'
          CAUTION!! The sourceforge user must have GRANT privilege granted 
          for this to work. This can be done from the mysql shell with:
             $ mysql -u root mysql -p
             mysql> UPDATE user SET Grant_priv='Y' where User='sourceforge';
             mysql> FLUSH PRIVILEGES;
     */

    // make sure the database name is not the same as the 
    // system database name !!!!
    if ($dbname != $GLOBALS['sys_dbname']) {
	mysql_create_db($dbname);
	db_project_query($dbname,'GRANT SELECT ON '.$dbname.'.* TO cxuser@\'%\'');
	return true;
    } else {
	return false;
    }
}

function db_project_error() {
	return @mysql_error();
}

?>
