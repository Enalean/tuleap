<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
// 

$datetime_fmt = 'Y-m-d H:i:s';
$datetime_msg = 'yyyy-mm-dd hh:mm:ss';

$Language->loadLanguageMsg('project/project');

function tocsv($string) {

    // Escape the double quote character by doubling it
    $string = ereg_replace('"', '""', $string);

    //Surround with double quotes if there is a comma; 
    // a space or the user separator in the string
    if (strchr($string,' ') || strchr($string,',') ||
        strchr($string, get_csv_separator()) ||
	strchr($string,"\n") ||
	strchr($string,"\t") ||
	strchr($string,"\r") ||
	strchr($string,"\0") ||
	strchr($string,"\x0B")) {
	return "\"$string\"";
    } else {
	return $string;
    }

}

/**
 * Get the CSV separator defined in the Account Maintenance preferences
 *
 * @return string the CSV separator defined by the user or "," by default if the user didn't defined it
 */
function get_csv_separator() {
    if ($u_separator = user_get_preference("user_csv_separator")) {
    } else {
        $u_separator = DEFAULT_CSV_SEPARATOR;
    }
    $separator = '';
    switch ($u_separator) {
        case 'comma':
            $separator = ",";
            break;
        case 'semicolon':
            $separator = ";";
            break;
        case 'tab':
            $separator = "\t";
            break;
        default:
            $separator = DEFAULT_CSV_SEPARATOR;
            break;
    }
    return $separator;
}

function build_csv_header($col_list, $lbl_list) {
    $line = '';
    reset($col_list);
    while (list(,$col) = each($col_list)) {
	$line .= tocsv($lbl_list[$col]).get_csv_separator();
    }
    $line = substr($line,0,-1);
    return $line;
}

function build_csv_record($col_list, $record) {
    $line = '';
    reset($col_list);
    while (list(,$col) = each($col_list)) {
	$line .= tocsv($record[$col]).get_csv_separator();
    }
    $line = substr($line,0,-1);
    return $line;
}

function insert_record_in_table($dbname, $tbl_name, $col_list, $record) {
  global $Language;
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
	$feedback .= $Language->getText('project_export_utils','ins_err',array($tbl_name,db_project_error()))." - ";
    }

}

function display_exported_fields($col_list,$lbl_list,$dsc_list,$sample_val,$mand_list=false){
   global $Language;

    $title_arr=array();
    $title_arr[]=$Language->getText('project_export_utils','label');
    $title_arr[]=$Language->getText('project_export_utils','sample_val');
    $title_arr[]=$Language->getText('project_admin_editugroup','desc');

    echo html_build_list_table_top ($title_arr);
    reset($col_list);
    $cnt = 0;
    while (list(,$col) = each($col_list)) {
      $star = (($mand_list && $mand_list[$col]) ? ' <span class="highlight"><big>*</big></b></span>':'');
      echo '<tr class="'.util_get_alt_row_color($cnt++).'">'.
	'<td><b>'.$lbl_list[$col].'</b>'.$star.
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
           Input: a row from the bug table (passed by reference.
          Output: the same row with values transformed for export
       */
	
   reset($col_list);
    $line = '';
    while (list(,$col) = each($col_list)) {

 	if (bug_data_is_text_field($col) || bug_data_is_text_area($col)) {
	    // all text fields converted from HTML to ASCII
	    $record[$col] = prepare_textarea($record[$col]);

	} else if (bug_data_is_select_box($col) && ($col != 'severity') &&
		  !bug_data_is_username_field($col) ) {
	    // All value_ids transformed in human readable values 
	    // except severity that remains a number and usernames
	    // which are already in clear
	    $record[$col] = bug_data_get_cached_field_value($col, $group_id, $record[$col]);
	} else if (bug_data_is_date_field($col)) {
	    // replace the date fields (unix time) with human readable dates that
	    // is also accepted as a valid format in future import
	    if ($record[$col] == '')
		// if date undefined then set datetime to 0. Ideally should
		// NULL as well but if we pass NULL it is interpreted as a string
		// later in the process
		$record[$col] = '0';
	    else
		$record[$col] = format_date($datetime_fmt,$record[$col]);
	}
    }

    $bug_id = $record['bug_id'];
    $record['follow_ups'] = pe_utils_format_bug_followups($group_id,$bug_id);
    $record['is_dependent_on_task_id'] = pe_utils_format_bug_task_dependencies($group_id,$bug_id);
    $record['is_dependent_on_bug_id'] = pe_utils_format_bug_bug_dependencies($group_id,$bug_id);

}

function prepare_artifact_record($at,$fields,$group_artifact_id, &$record) {

    global $datetime_fmt,$sys_lf,$Language;
    /*
           Prepare the column values in the artifact record
           Input: a row from the artifact table (passed by reference.
          Output: the same row with values transformed for export
       */
	
    reset($fields);
    $line = '';
    while (list(,$field) = each($fields)) {

		if ( $field->isSelectBox() || $field->isMultiSelectBox() ) {
			$values = array();
			if ( $field->isStandardField() ) {
				$values[] = $record[$field->getName()];
			} else {
				$values = $field->getValues($record['artifact_id']);
			}
			$label_values = $field->getLabelValues($group_artifact_id,$values);
			$record[$field->getName()] = join(",",$label_values);
			
		} else if ( $field->isTextArea() || ($field->isTextField() && $field->getDataType() == $field->DATATYPE_TEXT) ) {
		    // all text fields converted from HTML to ASCII
		    $record[$field->getName()] = prepare_textarea($record[$field->getName()]);
		 
		} else if ( $field->isDateField() ) {

		    // replace the date fields (unix time) with human readable dates that
		    // is also accepted as a valid format in future import
		    if ($record[$field->getName()] == '')
				// if date undefined then set datetime to 0. Ideally should
				// NULL as well but if we pass NULL it is interpreted as a string
				// later in the process
				$record[$field->getName()] = '0';
		    else
				$record[$field->getName()] = format_date($datetime_fmt,$record[$field->getName()]);
		} else if ( $field->isFloat() ) {
			$record[$field->getName()] = number_format($record[$field->getName()],2);
		}
    }

	// Follow ups
	$ah=new ArtifactHtml($at,$record['artifact_id']);
	$sys_lf_sav = $sys_lf;
	$sys_lf = "\n";
    $record['follow_ups'] = $ah->showFollowUpComments($at->Group->getID(),true,true);
	$sys_lf = $sys_lf_sav;

	// Dependencies
    $result=$ah->getDependencies();
    $rows=db_numrows($result);
    $dependent = '';
    for ($i=0; $i < $rows; $i++) {
		$dependent_on_artifact_id = db_result($result, $i, 'is_dependent_on_artifact_id');
		$dependent .= $dependent_on_artifact_id.",";
	}
    $record['is_dependent_on'] = (($dependent !== '')?substr($dependent,0,strlen($dependent)-1):$Language->getText('global','none'));

}

function pe_utils_format_bug_followups($group_id,$bug_id) {
    global $BUG_FU, $sys_datefmt,$Language;

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
		    $Language->getText('project_export_utils','type').': '.$row['type'].'     '.$Language->getText('global','by').': '.$row['mod_by'].'      '.$Language->getText('global','on').': '.
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
    global $BUG_TD,$Language;

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
			 $Language->getText('global','none') : $row['is_dependent_on_task_id']);
		}
	    }

	} else {
	    $BUG_TD = array();
	}
    }

    return $BUG_TD[$bug_id];

}

function pe_utils_format_bug_bug_dependencies($group_id,$bug_id) {
    global $BUG_BD, $Language;

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
			 $Language->getText('global','none') : $row['is_dependent_on_bug_id']);
		}
	    }

	} else {
	    $BUG_BD = array();
	}
    }

    return $BUG_BD[$bug_id];

}

function prepare_bug_history_record($group_id, &$col_list, &$record) {

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
	$record['old_value']= bug_data_get_cached_field_value($record['field_name'],$group_id,$record['old_value']);
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
	$record['type'] = bug_data_get_cached_field_value('comment_type_id',$group_id,$record['type']);
    } else {
	$record['type']='';
    }

}


function prepare_artifact_history_record($at, $art_field_fact, &$record) {
  
  global $datetime_fmt;
  
  /*
           Prepare the column values in the bug history  record
           Input: a row from the bug_history database (passed by
                   reference.
          Output: the same row with values transformed for export
  */
  
  // replace the modification date field with human readable dates 
  $record['date'] = format_date($datetime_fmt,$record['date']);
  
  $field = $art_field_fact->getFieldFromName($record['field_name']);
  if ( $field ) {
    prepare_historic_value(&$record, $field, $at->getID(), 'old_value');
    prepare_historic_value(&$record, $field, $at->getID(), 'new_value');
  }	else {
  	if (preg_match("/^(lbl_)/",$record['field_name']) && preg_match("/(_comment)$/",$record['field_name'])) {
  		$record['field_name'] = "comment";
  		$record['label'] = "comment";
  	}
  }
  
  
  // Decode the comment type value. If null make sure there is
  // a blank entry in the array
  if (isset($record['type'])) {
    $field = $art_field_fact->getFieldFromName('comment_type_id');
    if ( $field ) {
      $values[] = $record['type'];
      $label_values = $field->getLabelValues($at->getID(),$values);
      $record['type'] = join(",",$label_values);
    }
  } else {
    $record['type']='';
  }
  
}

function prepare_historic_value(&$record, $field, $group_artifact_id, $name) {
  if ( $field->isSelectBox() ) {
    $record[$name] = $field->getValue($group_artifact_id, $record[$name]);
    
  } else if ( $field->isDateField() ) {
    
    // replace the date fields (unix time) with human readable dates that
    // is also accepted as a valid format in future import
    if ($record[$name] == '')
      // if date undefined then set datetime to 0. Ideally should
      // NULL as well but if we pass NULL it is interpreted as a string
      // later in the process
      $record[$name] = '0';
    else
      $record[$name] = format_date($GLOBALS['datetime_fmt'],$record[$name]);
    
  } else if ( $field->isFloat() ) {
    $record[$name] = number_format($record[$name],2);
    
  } else {
    // all text fields converted from HTML to ASCII
    $record[$name] = prepare_textarea($record[$name]);
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

    case 'percent_complete':
	$record['old_value'] = ($record['old_value']-1000);
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
    
    $record['percent_complete'] = ($record['percent_complete']-1000);

}

function project_export_makesalt($type=CRYPT_SALT_LENGTH) {
  switch($type) {
   case 12:
     $saltlen=8; $saltprefix='$1$'; $saltsuffix='$'; break;
   case 2:
   default: 
     // by default, fall back on Standard DES (should work everywhere)
     $saltlen=2; $saltprefix=''; $saltsuffix=''; break;
   #
  }
  $salt='';
  while(strlen($salt)<$saltlen) $salt.= chr(rand(64,126));
  return $saltprefix.$salt.$saltsuffix;
}



function codex_crypt($id,$salt,$type=CRYPT_SALT_LENGTH) {
  return substr(crypt($id,$salt),$type); 
}

function prepare_survey_responses_record($group_id, &$record, $salt) {

    global $datetime_fmt;

    /*
           Prepare the column values in the task  record
           Input: a row from the project_task table (passed by
                   reference.
          Output: the same row with values transformed for database export
       */

    $record['date'] = format_date($datetime_fmt,$record['date']);
    $record['reponse'] = prepare_textarea($record['response']);
    
    //compute encrypted user_id
    $enc_user_id = codex_crypt($record['user_id'],$salt);  
    $record['user_id'] = $enc_user_id;
 
}

    /**
         *  Prepare the column values in the access logs  record
         *  @param: group_id: group id
         *  @param: record: a row from the access logs table (passed by  reference).
         * 	 
         *  @return: the same row with values transformed for database export
         */

function prepare_access_logs_record($group_id, &$record) {

    $time = $record['time'];    
    $record['time'] = format_date('Y-m-d',$time);    
    $record['local_time'] = strftime("%H:%M", $time);
    $uid = user_getid_from_email($record['email']);
    $record['user'] = user_getrealname($uid)."(".user_getname($uid).")";
    //for cvs & svn access logs
    $day = $record['day'];
    $record['day'] = substr($day,0,4)."-".substr($day,4,2)."-".substr($day,6,2);

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
    global $TASK_FU, $sys_datefmt,$Language;

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
		    $Language->getText('global','by').': '.$row['mod_by'].'      '.$Language->getText('global','on').': '.
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
    global $TASK_TD,$Language;

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
			 $Language->getText('global','none') : $row['is_dependent_on_task_id']);
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
    global $SR_MSG, $sys_datefmt,$Language;

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
		    $Language->getText('global','by').': '.$row['from_email'].'      '.$Language->getText('global','on').': '.
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
    global $sys_default_domain, $dbname, $Language;
    list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);
echo '
<table align="center" cellpadding="0">
      <tr><td>'.$Language->getText('project_export_utils','server').':</td><td>'.$host.'</td></tr>
      <tr><td>'.$Language->getText('project_export_utils','port').':</td><td>'.$Language->getText('project_export_utils','leave_blank').'</td></tr>
      <tr><td>'.$Language->getText('project_export_utils','db').':</td><td>'.$dbname.'</td></tr>
      <tr><td>'.$Language->getText('project_export_utils','user').':</td><td>cxuser</td></tr>
      <tr><td>'.$Language->getText('project_export_utils','passwd').':</td><td>'.$Language->getText('project_export_utils','leave_blank').'</td></tr>
</table>';
}

function db_project_query($dbname,$qstring,$print=0) {
  global $Language;
	if ($print) print '<br>'.$Language->getText('project_export_utils','query_is',array($dbname,$qstring)).'<br>';
	//$GLOBALS['db_project_qhandle'] = @mysql_db_query($dbname,$qstring);
	
	// Changes by SL Enhance access to databases and project data
	// mysql_db_query is now deprecated and has been replaced by 
	//mysql_select_db then mysql_query
	//
	// Select the project database
	$db = @mysql_select_db($dbname);
	if (!$db){
		die('Can\'t connect to ' . $dbname . 'database' . db_error());
	} else{
	$GLOBALS['db_project_qhandle'] = @mysql_query($qstring);
	
	// Switch back to system database
	$dbname = $GLOBALS['sys_dbname']; 
	$db = @mysql_select_db($dbname);
	if (!$db) die ('Can\'t switch back to system database'. db_error());
	}
	return $GLOBALS['db_project_qhandle'];
}


function db_project_create($dbname) {
    /*
          Create the db if it does not exist and grant read access only
          to the user 'cxuser'
          CAUTION!! The codexadm user must have GRANT privilege granted 
          for this to work. This can be done from the mysql shell with:
             $ mysql -u root mysql -p
             mysql> UPDATE user SET Grant_priv='Y' where User='codexadm';
             mysql> FLUSH PRIVILEGES;
     */

    // make sure the database name is not the same as the 
    // system database name !!!!
    if ($dbname != $GLOBALS['sys_dbname']) {
       db_query('CREATE DATABASE IF NOT EXISTS `'.$dbname.'`');
       db_project_query($dbname,'GRANT SELECT ON `'.$dbname.'`.* TO cxuser@\'%\'');
	return true;
    } else {
	return false;
    }
}

function db_project_error() {
	return @mysql_error();
}

?>
