<?php
/**
 * Copyright (c) Enalean, 2013-2018. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

$datetime_fmt = 'Y-m-d H:i:s';
$datetime_msg = 'yyyy-mm-dd hh:mm:ss';

require_once('utils.php');
require_once('common/include/SimpleSanitizer.class.php');

function tocsv($string, $csv_separator) {

    // Escape the double quote character by doubling it
    $string = str_replace('"', '""', $string);

    //Surround with double quotes if there is a comma; 
    // a space or the user separator in the string
    if (strpos($string, ' ') !== false || strpos($string, ',') !== false || strpos($string, $csv_separator) !== false ||
        strpos($string, '"') !== false || strpos($string, "\n") !== false || strpos($string, "\t") !== false ||
        strpos($string, "\r") !== false || strpos($string, "\0") !== false || strpos($string,"\x0B") !== false) {
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
        $u_separator = PFUser::DEFAULT_CSV_SEPARATOR;
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
            $separator = PFUser::DEFAULT_CSV_SEPARATOR;
            break;
    }
    return $separator;
}

function build_csv_header($col_list, $lbl_list) {
    $line      = '';
    $separator = get_csv_separator();
    reset($col_list);
    while (list(,$col) = each($col_list)) {
    	if (isset($lbl_list[$col])) {
			$line .= tocsv($lbl_list[$col], $separator) . $separator;
    	}
    }
    $line = substr($line,0,-1);
    return $line;
}

function build_csv_record($col_list, $record) {
    $line      = '';
    $separator = get_csv_separator();
    reset($col_list);
    while (list(,$col) = each($col_list)) {
	$line .= tocsv($record[$col], $separator) . $separator;
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
    db_project_query($dbname,$sql_insert);

}

function display_exported_fields($col_list,$lbl_list,$dsc_list,$sample_val,$mand_list=false){
   global $Language;

    $title_arr=array();
    $title_arr[]=$Language->getText('project_export_utils','label');
    $title_arr[]=$Language->getText('project_export_utils','sample_val');
    $title_arr[]=$Language->getText('project_admin_editugroup','desc');

    $purifier = Codendi_HTMLPurifier::instance();

    echo html_build_list_table_top ($title_arr);
    reset($col_list);
    $cnt = 0;
    while (list(,$col) = each($col_list)) {
      $star = (($mand_list && isset($mand_list[$col]) && $mand_list[$col]) ? ' <span class="highlight"><big>*</big></b></span>':'');
      echo '<tr class="'.util_get_alt_row_color($cnt++).'">'.
	'<td><b>'.$lbl_list[$col].'</b>'.$star.
	'</td><td>'.nl2br($purifier->purify($sample_val[$col])).'</td><td>'.$purifier->purify($dsc_list[$col]).'</td></tr>';
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

/**
 * Prepare the column values in the artifact record
 *
 * @param ArtifactType (tracker) $at the tracker the artifact to prepare blelong to
 * @param array{ArtifactField} $fields the fields of the artifact to export 
 * @param int $group_artifact_id the tracker ID
 * @param array $record array 'field_name' => 'field_value'
 * @param string $export type of export ('csv' or 'database' : for date format, csv will take user preference, wheareas for database the format will be mysql format.)
 */
function prepare_artifact_record($at,$fields,$group_artifact_id, &$record, $export) {

    global $datetime_fmt,$sys_lf,$Language;
    /* $record:          
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
			$record[$field->getName()] = SimpleSanitizer::unsanitize(join(",",$label_values));
			
		} else if ( $field->isTextArea() || ($field->isTextField() && $field->getDataType() == $field->DATATYPE_TEXT) ) {
		    // all text fields converted from HTML to ASCII
		    $record[$field->getName()] = prepare_textarea($record[$field->getName()]);
		 
		} else if ( $field->isDateField() ) {

		    // replace the date fields (unix time) with human readable dates that
		    // is also accepted as a valid format in future import
		    if ($record[$field->getName()] == '') {
				// if date undefined then set datetime to 0. Ideally should
				// NULL as well but if we pass NULL it is interpreted as a string
				// later in the process
				$record[$field->getName()] = '0';
		    } else {
		        if ($export == 'database') {
				    $record[$field->getName()] = format_date($datetime_fmt, $record[$field->getName()]);
		        } else {
		            $record[$field->getName()] = format_date(util_get_user_preferences_export_datefmt(), $record[$field->getName()]);
		        }
		    }
		} else if ( $field->isFloat() ) {
			$record[$field->getName()] = number_format($record[$field->getName()],2);
		}
    }

	// Follow ups
	$ah=new ArtifactHtml($at,$record['artifact_id']);
	$sys_lf_sav = $sys_lf;
	$sys_lf = "\n";
    $record['follow_ups'] = $ah->showFollowUpComments($at->Group->getID(),true, Artifact::OUTPUT_EXPORT);
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
    
    //CC
    $cc_list = $ah->getCCList();
    $rows = db_numrows($cc_list);
    $cc = array();
    for ($i=0; $i < $rows; $i++) {
        $cc_email = db_result($cc_list, $i, 'email');
        $cc[] = $cc_email;
    }   
    $record['cc'] = implode(',', $cc);
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
    prepare_historic_value($record, $field, $at->getID(), 'old_value');
    prepare_historic_value($record, $field, $at->getID(), 'new_value');
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

    /**
         *  Prepare the column values in the access logs  record
         *  @param: group_id: group id
         *  @param: record: a row from the access logs table (passed by  reference).
         * 	 
         *  @return: the same row with values transformed for database export
         */

function prepare_access_logs_record($group_id, &$record) {

    if (isset($record['time'])) {
    	$time = $record['time'];
    	$record['time'] = format_date('Y-m-d',$time);
    	$record['local_time'] = strftime("%H:%M", $time);
    }
    $um = UserManager::instance();
    $user = $um->getUserByUserName($record['user_name']);
    if ($user) {
        $record['user'] = $user->getRealName()."(".$user->getName().")";
    } else {
        $record['user'] = 'N/A';
    }
    //for cvs & svn access logs
    if (isset($record['day'])) {
    	$day = $record['day'];
	    $record['day'] = substr($day,0,4)."-".substr($day,4,2)."-".substr($day,6,2);
    }
}

function display_db_params () {
    global $dbname, $Language;
echo '
<table align="center" cellpadding="0">
      <tr><td>'.$Language->getText('project_export_utils','server').':</td><td>'.$GLOBALS['sys_dbhost'].'</td></tr>
      <tr><td>'.$Language->getText('project_export_utils','port').':</td><td>'.$Language->getText('project_export_utils','leave_blank').'</td></tr>
      <tr><td>'.$Language->getText('project_export_utils','db').':</td><td>'.$dbname.'</td></tr>
      <tr><td>'.$Language->getText('project_export_utils','user').':</td><td>cxuser</td></tr>
      <tr><td>'.$Language->getText('project_export_utils','passwd').':</td><td>'.$Language->getText('project_export_utils','leave_blank').'</td></tr>
</table>';
}

/**
 * @deprecated
 */
function db_project_query($dbname,$qstring,$print=0) {
  global $Language;
	if ($print) print '<br>'.$Language->getText('project_export_utils','query_is',array($dbname,$qstring)).'<br>';

	try {
	    $db = \Tuleap\DB\DBFactory::getDB($dbname);
        $db->run($qstring);
    } catch (PDOException $ex) {
	    return false;
    }
	return true;
}


function db_project_create($dbname) {
    /*
          Create the db if it does not exist and grant read access only
          to the user 'cxuser'
          CAUTION!! The codendiadm user must have GRANT privilege granted 
          for this to work. This can be done from the mysql shell with:
             $ mysql -u root mysql -p
             mysql> UPDATE user SET Grant_priv='Y' where User='codendiadm';
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

/**
 * Check if a given database already exists
 * 
 * @param String $dbname
 * 
 * @return Boolean
 */
function db_database_exist($dbname) {
    $res = db_query("SHOW DATABASES LIKE '$dbname'");
    return db_numrows($res) == 1;
}

?>
