<?php

//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2003. All rights reserved
//
// $Id$
//
//
//  Written for CodeX by Marie-Luise Schneider
//


/** parse the first line of the csv file containing all the labels of the fields that are
 * used in the following of the file
 * @param $data (IN): array containing the field labels
 * @param $used_fields (IN): array containing all the fields that are used in this tracker
 * @param $ath (IN): the tracker
 * @param $num_columns (OUT): number of columns in the data array
 * @param $parsed_fields (OUT): array of the form (column_number => field_label) containing
 *                              all the fields parsed from $data
 * @param $predefined_values (OUT): array of the form (column_number => array of field predefined values)
 * @param $aid_column (OUT): the column in the csv file that contains the arifact id (-1 if not given)
 * @param $errors (OUT): string containing explanation what error occurred
 * @return true if parse ok, false if errors occurred
 */ 
function parse_field_names($data,$used_fields,$ath,
			   &$num_columns,&$parsed_fields,&$predefined_values,
			   &$aid_column,&$submitted_by_column,&$submitted_on_column,
			   &$errors) {
  $aid_column = -1;
  $submitted_by_column = -1;
  $submitted_on_column = -1;
  $num_columns = count($data);
  
  for ($c=0; $c < $num_columns; $c++) {
    $field_label = $data[$c];
    if (!array_key_exists($field_label,$used_fields)) {
      $errors .= "\"$field_label\" is not a known field in tracker ".$ath->getName();
      return false;
    }
    
    if ($field_label == "Artifact ID") $aid_column = $c; 
    if ($field_label == "Submitted by") $submitted_by_column = $c;
    if ($field_label == "Submitted on") $submitted_on_column = $c;
    
    $parsed_fields[$c] = $field_label;
    $curr_field = $used_fields[$field_label];

    //get already the predefined values of this field (if applicable)
    if ($curr_field != "" && 
	($curr_field->getDisplayType() == "SB" || $curr_field->getDisplayType() == "MB")) {
      $predef_val = $curr_field->getFieldPredefinedValues($ath->getID());
      $count = db_numrows($predef_val);
      unset($values);
      for ($i=0;$i<$count;$i++) {
	$values[db_result($predef_val,$i,1)] = db_result($predef_val,$i,0);
      }
      $predefined_values[$c] = $values;
      
    }
  }

  // verify if we have all mandatory fields in the case we have to create an artifact
  if ($aid_column == -1) {
    reset($used_fields);
    while (list($label,$field) = each($used_fields)) {
      //echo $label.",";
      if ($label != "Artifact ID" &&
	  $label != "Submitted on" &&
	  $label != "Submitted by" &&
	  $label != "Follow-up Comments" &&
	  $label != "Depend on" &&
	  $label != "CC List" &&
	  $label != "CC Comment" &&
	  !$field->isEmptyOk() && !in_array($label,$parsed_fields)) {
	$errors .= "\"$label\" is a mandatory field in tracker ".$ath->getName().". Please specify it in your csv file. ";
	return false;
      }
    }
  }
  return true;
}



/** check if all the values correspond to predefined values of the corresponding fields
 * @param data (IN + OUT !): for date fields we transform the given format (accepted by util_date_to_unixtime)
 *                           into format "Y-m-d"
 * @param insert: if we check values for inserting this artifact data. If so, we accept
 *                 submitted on and submitted by as "" and insert it later on 
 * @param from_update: take into account special case where column artifact_id is specified but
 *                      for this concrete artifact no aid is given
 */
function check_values($row,&$data,$used_fields,$parsed_fields,$predefined_values,&$errors,$insert,$from_update=false) {
  global $ath;
  for ($c=0; $c < count($parsed_fields); $c++) {
    $label = $parsed_fields[$c];
    $val = $data[$c];
    $field = $used_fields[$label]; 

    // check if val in predefined vals (if applicable)
    $predef_vals = $predefined_values[$c];
    if ($predef_vals) {
      if ($field->getDisplayType() == "MB") {
	$val_arr = explode(",",$val);
	while (list(,$name) = each($val_arr)) {
	  if (!array_key_exists($name,$predef_vals) && $name != 'None') {
	    $errors .= "<b>Line ".($row+1)." [</b>".implode(",",$data)."<b>]</b>:<br>value \"$name\" is not one of the predefined values of field \"$label\" (".implode(",",array_keys($predef_vals)).")";
	    return false;
	  }
	}
      } else {
	if (!array_key_exists($val,$predef_vals) && $val != 'None') {
	  if ($label == 'Severity' &&
	      (strcasecmp($val,'1') == 0 || strcasecmp($val,'5') == 0 || strcasecmp($val,9) == 0)) {
	    //accept simple ints for Severity fields instead of 1 - Ordinary,5 - Major,9 - Critical
	  } else if ($label == 'Submitted by' && $val == '') {
	    //accept and use importing user as 'submitted by'
	  } else {
	    $errors .= "<b>Line ".($row+1)." [</b>".implode(",",$data)."<b>]</b>:<br>value \"$val\" is not one of the predefined values of field \"$label\" (".implode(",",array_keys($predef_vals)).")";
	    return false;
	  }
	}
      }
    }
    
    // check whether we specify None for a field which is mandatory
    if ($field != "" && !$field->isEmptyOk() && $label != "Artifact ID") {
      if ($label == "Submitted by" ||
	   $label == "Submitted on") {
	//submitted on and submitted by are accepted as "" on inserts and
	//we put time() importing user as default
      } else {
	$is_empty = ($field->isSelectBox() ? ($val=='None') : ($val==''));
	if ($is_empty) {
	  $errors .= "<b>Line ".($row+1)." [</b>".implode(",",$data)."<b>]</b>:<br>\"$label\" is a mandatory field in tracker ".$ath->getName().". Please specify it in your csv file. ";
	  return false;
	}
      }
    }

    // for date fields: check format
    if ($field != "" && $field->isDateField()) {
      if ($label == "Submitted on" && $val == "") {
	//is ok.
      } else {
	list($unix_time,$ok) = util_date_to_unixtime($val);
	if (!ok) {
	  $errors .= "<b>Line ".($row+1)." [</b>".implode(",",$data)."<b>]</b>:<br>Date \"$val\" is not in correct format (Y-m-d)."; 
	}
	$date = format_date("Y-m-d",$unix_time);
	$data[$c] = $date;
      }
    }

  }

  // if we come from update case ( column artifact_id is specified but for this concrete artifact no aid is given)
  // we have to check whether all mandatory fields are specified and not empty
  if ($from_update) {
    while (list($label,$field) = each($used_fields)) {
      if ($label != "Artifact ID" &&
	  $label != "Submitted on" &&
	  $label != "Submitted by" &&
	  $label != "Follow-up Comments" &&
	  $label != "Depend on" &&
	  $label != "CC List" &&
	  $label != "CC Comment" &&
	  !$field->isEmptyOk() && !in_array($label,$parsed_fields)) {
	  $errors .= "<b>Line ".($row+1)." [</b>".implode(",",$data)."<b>]</b>:<br>\"$label\" is a mandatory field in tracker ".$ath->getName().". Please specify it in your csv file. ";
	  return false;
      } 
    }
  }
  
  return true;
}


/**
 * @param $from_update: take into account special case where column artifact_id is specified but
 *                      for this concrete artifact no aid is given
 */
function check_insert_artifact($row,&$data,$used_fields,$parsed_fields,$predefined_values,&$errors,$from_update=false) {
  // first make sure this isn't double-submitted
  
  $field = $used_fields["Summary"];
  $summary_col = array_search("Summary",$parsed_fields);
  $submitted_by_col = array_search("Submitted by",$parsed_fields);
  $summary = $data[$summary_col];
  if ($submitted_by_col) {
    $sub_user_name = $data[$submitted_by_col];
    //$sub_user_ids = $predefined_values[$submitted_by_col];
     $res = user_get_result_set_from_unix($sub_user_name);
     $sub_user_id = db_result($res,0,'user_id');
  } else {
    get_import_user($sub_user_id,$sub_user_name);
  }
  
  
  if ( $field && $field->isUsed() ) {
    $res=db_query("SELECT * FROM artifact WHERE submitted_by=$sub_user_id AND summary=\"$summary\"");
    if ($res && db_numrows($res) > 0) {
      $errors .= "<b>Line ".($row+1)." [</b>".implode(",",$data)."<b>]</b>:<br>User $sub_user_name has already submitted a bug with the same summary. Please don't try to import it again.";
      return false;           
    }
  }
  
  return check_values($row,$data,$used_fields,$parsed_fields,$predefined_values,$errors,true,$from_update);
}



/** check if all the values correspond to predefined values of the corresponding fields */
function check_update_artifact($row,&$data,$aid,$used_fields,$parsed_fields,$predefined_values,&$errors) {
  global $ath;
  
  $sql = "SELECT artifact_id FROM artifact WHERE artifact_id = $aid and group_artifact_id = ".$ath->getID();
  $result = db_query($sql);
  if (db_numrows($result) == 0) {
    $errors .= "<b>Line ".($row+1)." [</b>".implode(",",$data)."<b>]</b>:<br>the specified artifact (id=$aid) does not exist in tracker ".$ath->getName();
    return false;
  }
  
  return check_values($row,$data,$used_fields,$parsed_fields,$predefined_values,$errors,false);
}



/**
 * create the html output to visualize what has been parsed
 * @param $used_fields: array containing all the fields that are used in this tracker
 * @param $parsed_fields: array of the form (column_number => field_label) containing
 *                        all the fields parsed from $data
 * @param $artifacts_data: array containing the records for each artifact to be imported
 * @param $aid_column: the column in the csv file that contains the arifact id (-1 if not given)
 * @param $submitted_by_column: the column in the csv file that contains the Submitter (-1 if not given)
 * @param $submitted_on_column: the column in the csv file that contains the artifact creation date (-1 if not given)
 */
function show_parse_results($used_fields,$parsed_fields,$artifacts_data,$aid_column,$submitted_by_column,$submitted_on_column,$group_id) {
  global $ath,$PHP_SELF,$sys_datefmt;
  get_import_user($sub_user_id,$sub_user_name);
  $sub_on = format_date("Y-m-d",time());

  
  //add submitted_by and submitted_on columns only when 
  //artifact_id is not given otherwise the artifacts should
  //only be updated and we don't need to touch sub_on and sub_by
  if ($aid_column == -1 && $submitted_by_column == -1) {
    $new_sub_by_col = count($parsed_fields);
    $parsed_fields[] = "Submitted by";
  }

  if ($aid_column == -1 && $submitted_on_column == -1) {
    $new_sub_on_col = count($parsed_fields);
    $parsed_fields[] = "Submitted on";
  }

  echo '
        <FORM NAME="acceptimportdata" action="'.$PHP_SELF.'" method="POST" enctype="multipart/form-data">
        <p align="left"><INPUT TYPE="SUBMIT" NAME="submit" VALUE="IMPORT"></p>';


  echo html_build_list_table_top ($parsed_fields);

  
  for ($i=0; $i < count($artifacts_data) ; $i++) {

    $data = $artifacts_data[$i];
    if ($aid_column != -1) $aid = $data[$aid_column];

    echo '<TR class="'.util_get_alt_row_color($i).'">'."\n";
    
    for ($c=0; $c < count($parsed_fields); $c++) {
      
      $value = $data[$c];
      $width = ' class="small"';


      if ($value != "") {
	//FOLLOW_UP COMMENTS
	if ($parsed_fields[$c] == "Follow-up Comments") {
	  unset($parsed_details);
	  unset($parse_error);
	  if (parse_details($data[$c],$parsed_details,$parse_error,true)) {
	    if (count($parsed_details) > 0) {
	      echo '<TD $width><TABLE>';
	      echo '<TR class ="boxtable"><TD class="boxtitle">Date</TD><TD class="boxtitle">By</TD><TD class="boxtitle">type</TD><TD class="boxtitle">comment</TD></TR>';
	      for ($d=0; $d < count($parsed_details); $d++) {
		$arr = $parsed_details[$d];
		echo '<TR class="'.util_get_alt_row_color($d).'">';
		echo "<TD $width>".$arr['date']."</TD><TD $width>".$arr['by']."</TD><TD $width>".$arr['type']."</TD><TD $width>".$arr['comment']."</TD>";
		echo "</TR>\n";
	      }
	      echo "</TABLE></TD>";
	    } else {
	      echo "<TD $width align=\"center\">-</TD>\n";
	    }
	  } else {
	    echo "<TD $width><I>$parse_error Won't insert any follow-up comments for this artifact.</I></TD>\n";
	  }
	  
	  //DEFAULT
	} else {
	  echo "<TD $width valign=\"top\">$value</TD>\n";
	}


      } else {

	//SUBMITTED_ON
	if ($parsed_fields[$c] == "Submitted on") {
	  //if insert show default value
	  if ($aid_column == -1 || $aid == "") echo "<TD $width><I>$sub_on</I></TD>\n";
	  else echo "<TD $width valign=\"top\"><I>Unchanged</I></TD>\n";

	  //SUBMITTED_BY
	} else if ($parsed_fields[$c] == "Submitted by") {
	  if ($aid_column == -1 || $aid == "") echo "<TD $width><I>$sub_user_name</I></TD>\n";
	  else echo "<TD $width valign=\"top\"><I>Unchanged</I></TD>\n";

	  //ARTIFACT_ID
	} else if ($parsed_fields[$c] == "Artifact ID") {
	  echo "<TD $width valign=\"top\"><I>NEW</I></TD>\n";

	  //DEFAULT
	} else {
	  echo "<TD $width  valign=\"top\" align=\"center\">-</TD>\n";
	}
      }
    }
    echo "</tr>\n";
  }
  
  echo "</TABLE>\n";
  
  echo '
        <INPUT TYPE="HIDDEN" NAME="atid" VALUE="'.$ath->getID().'">
        <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
        <INPUT TYPE="HIDDEN" NAME="func" VALUE="import">
        <INPUT TYPE="HIDDEN" NAME="mode" VALUE="import">
        <INPUT TYPE="HIDDEN" NAME="aid_column" VALUE="'.$aid_column.'">
        <INPUT TYPE="HIDDEN" NAME="count_artifacts" VALUE="'.count($artifacts_data).'">';
  
  while (list(,$label) = each($parsed_fields)) {
    echo '
        <INPUT TYPE="HIDDEN" NAME="parsed_fields[]" VALUE="'.$label.'">';
  }
  

  for ($i=0; $i < count($artifacts_data); $i++) {
    $data = $artifacts_data[$i];
    for ($c=0; $c < count($data); $c++) {
      echo '
        <INPUT TYPE="HIDDEN" NAME="artifacts_data_'.$i.'_'.$c.'" VALUE="'.$data[$c].'">';
    }
  }
  
  echo '
        </FORM>';
  
}



/** parse a file in csv format containing artifacts to be imported into the db
 * @param $csv_filename (IN): the complete file name of the cvs file to be parsed
 * @param $atid (IN): the tracker id
 * @param $group_id (IN): 
 * @param $is_tmp (IN): true if cvs_file is only temporary file and we want to unlink it 
 *                      after parsing
 * @param $used_fields (OUT): the fields used in tracker $atid
 *                            array of the form (label => field)
 * @param $parsed_fields (OUT): the field labels parsed in the csv file
 *                              array of the form (column_number => field_label)
 * @param $artifacts (OUT): the artifacts with their field values parsed from the csv file
 * @param $errors (OUT): string containing explanation what error occurred
 * @return true if parse ok, false if errors occurred
 */
function parse($csv_filename,$group_id,$is_tmp,
	       &$used_fields,&$parsed_fields,&$artifacts_data,
	       &$aid_column,&$submitted_by_column,&$submitted_on_column,
	       &$number_inserts,&$number_updates,
	       &$errors) {
  global $ath;

  $number_inserts = 0;
  $number_updates = 0;
  
  //avoid that lines with a length > 1000 will be truncated by fgetcsv
  $length = 1000;
  $array = file($csv_filename);
  for($i=0;$i<count($array);$i++) {
    if ($length < strlen($array[$i])) {
      $length = strlen($array[$i]);
    }
  }
  $length++;
  //unset($array);


  $used_fields = getUsedFields();
  
  $csv_file = fopen($csv_filename, "r");
  $row = 0;
  
  while ($data = fgetcsv($csv_file, $length, ",")) {
    // do the real parsing here
    
    //parse the first line with all the field names
    if ($row == 0) {
      $ok = parse_field_names($data,$used_fields,$ath,$num_columns,$parsed_fields,$predefined_values,
			      $aid_column,$submitted_by_column,$submitted_on_column,
			      $errors);
      
      if (!$ok) return false;
      
      //parse artifact values
    } else {
      
      //verify whether this row contains enough values
      $num = count($data);
      if ($num != $num_columns) { 
	$errors .= "<b>Line ".($row+1)." [</b>".implode(",",$data)."<b>]</b>:<br>the number of values ($num) does not match the number of fields ($num_columns).";
	$errors .= "<br>";
	return FALSE;
      }
      
      
      // if no artifact_id given, create new artifacts	
      if ($aid_column == -1) {
	$ok = check_insert_artifact($row,$data,$used_fields,$parsed_fields,$predefined_values,$errors);
	$number_inserts++;
	// if artifact_id given, verify if it exists already 
	//else send error
      } else {
	$aid = $data[$aid_column];
	if ($aid != "") {
	  $ok = check_update_artifact($row,$data,$aid,$used_fields,$parsed_fields,$predefined_values,$errors);
	  $number_updates++;
	  
	} else {
	  // have to create artifact from scratch
	  $ok = check_insert_artifact($row,$data,$used_fields,$parsed_fields,$predefined_values,$errors,true);
	  $number_inserts++;
	}	  
      }
      if (!$ok) return false;
      else $artifacts_data[] = $data;
    }
    $row++;
  }
  
  fclose($csv_file);
  if ($is_tmp) {
    unlink($csv_filename);
  }
  return true;
}




function show_errors($errors) {
  echo $errors." <br>\n";
}

function mandatory_fields($ath) {
  $art_field_fact = new ArtifactFieldFactory($ath);
  $fields =  $art_field_fact->getAllUsedFields();
  while (list(,$field) = each($fields) ) {
    if ( $field->getName() != "comment_type_id" && !$field->isEmptyOk()) {
      $mand_fields[$field->getName()] = true;
    }
  } 
  return $mand_fields;
}

function getUsedFields() {
  global $ath;
  $art_field_fact = new ArtifactFieldFactory($ath);
  $fields =  $art_field_fact->getAllUsedFields();
  while (list(,$field) = each($fields) ) {
    if ( $field->getName() != "comment_type_id" ) {
      $used_fields[$field->getLabel()] = $field;
    }
  }

  $used_fields["Follow-up Comments"] = "";
  $used_fields["Depend on"] = "";
  $used_fields["CC List"] = "";
  $used_fields["CC Comment"] = "";

  //special cases for submitted by and submitted on that can be set
  //"unused" by the user but that will nevertheless be used by CodeX
  if (array_search("Submitted by", $used_fields) === false)
    $used_fields["Submitted by"] = $art_field_fact->getFieldFromName("submitted_by");
  if (array_search("Submitted on", $used_fields) === false)
    $used_fields["Submitted on"] = $art_field_fact->getFieldFromName("open_date");

  return $used_fields;
}

function get_import_user(&$sub_user_id,&$sub_user_name) {
  global $user_id,$ath;

  $sub_user_id = $user_id;

  $techs = $ath->getTechnicians();
  $count = db_numrows($techs);
  
  for ($i=0;$i<$count;$i++) {
    if ($user_id == db_result($techs,$i,0)) $sub_user_name = db_result($techs,$i,1);
  }

  //this should not happen as we verify in tracker/index that the current user has tech perms
  if (!$sub_user_name) {
    exit_permission_denied();
  }
}

/** get already the predefined values of this field (if applicable) 
 * @param $used_fields: array containing all the fields that are used in this tracker
 * @param $parsed_fields (OUT): array of the form (column_number => field_label) containing
 *                              all the fields parsed from $data
 * @return $predefined_values: array of the form (column_number => array of field predefined values)
*/
function getPredefinedValues($used_fields,$parsed_fields) {
  global $ath;

  for ($c=0; $c < count($parsed_fields); $c++) {
    $field_label = $parsed_fields[$c];
    $curr_field = $used_fields[$field_label];
    if ($curr_field != "" && 
	($curr_field->getDisplayType() == "SB" || $curr_field->getDisplayType() == "MB")) {
      $predef_val = $curr_field->getFieldPredefinedValues($ath->getID());
      $count = db_numrows($predef_val);
      for ($i=0;$i<$count;$i++) {
	$values[db_result($predef_val,$i,1)] = db_result($predef_val,$i,0);
      }
      $predefined_values[$c] = $values;
    }
  }
  return $predefined_values;
}



/** assume that the 
 * @param details (IN): details have the form that we get when exporting details in csv format
 *                      (see ArtifactHtml->showDetails(ascii = true))
 * @param parsed_details (OUT): an array (#detail => array2), where array2 is of the form
 *                              ("date" => date, "by" => user, "type" => comment-type, "comment" => comment-string)
 * @param for_parse_report (IN): if we parse the details to show them in the parse report then we keep the labels
 *                               for users and comment-types
 */
function parse_details($details,&$parsed_details,&$errors,$for_parse_report=false) {
  global $sys_lf, $art_field_fact, $ath, $sys_datefmt,$user_id;

  //echo "<br>\n";
  $comments = split("------------------------------------------------------------------",$details);

  $i = 0;
  while (list(,$comment) = each($comments)) {
    $i++;
    if ($i == 1) {
      //skip first line
      continue;
    }
    $comment = trim($comment);
    
    //skip the "Date: "
    if (strpos($comment, "Date:") === false) {
      //if no date given, consider this whole string as the comment
      if ($for_parse_report) {
	$date= format_date($sys_datefmt,time());
	get_import_user($sub_user_id,$sub_user_name);
	$arr["date"] = "<I>$date</I>";
	$arr["by"] = "<I>$sub_user_name</I>";
	$arr["type"] = "<I>None</I>";
      } else {
	$arr["date"] = time();
	$arr["by"] = $user_id;
	$arr["type"] = 100;
      }
      $arr["comment"] = $comment;
      $parsed_details[] = $arr;
      continue;
    }
    $comment = substr($comment, 6);
    $by_position = strpos($comment,"By: ");
    if ($by_position === false) {
      $errors .= "You must specify an originator for follow-up comment #".($i-1)." ($comment).";
      return false;
    }
    $date_str = trim(substr($comment, 0, $by_position));
    //echo "$date_str<br>";
    if ($for_parse_report) $date = $date_str;
    else list($date,$ok) = util_sysdatefmt_to_unixtime($date_str);
    //echo "$date<br>";
    //skip "By: "
    $comment = substr($comment, ($by_position + 4));

    $by = strtok($comment," \n\t\r\0\x0B");
    $comment = trim(substr($comment,strlen($by)));

    if (!$for_parse_report) {
      $res = user_get_result_set_from_unix($by);
      if (db_numrows($res) > 0) {
	$by = db_result($res,0,'user_id');
      } else if (validate_email($by)) {
	//ok, $by remains what it is
      } else {
	$errors .= "\"$by\" specified as originator of follow-up comment #".($i-1)." is neither a user name nor a valid email address.";
	return false;
      }
    }

    //see if there is comment-type or none
    $type_end_pos = strpos($comment,"]");
    if (strpos($comment,"[") == 0 &&  $type_end_pos!= false) {
      $comment_type = substr($comment, 1, ($type_end_pos-1));
      
      //check whether this is really a valid comment_type
      $c_type_field = $art_field_fact->getFieldFromName('comment_type_id');
      if ($c_type_field) {
	$predef_val = $c_type_field->getFieldPredefinedValues($ath->getID());
	$count = db_numrows($predef_val);
	for ($p=0;$p<$count;$p++) {
	  if ($comment_type == db_result($predef_val,$p,1)) {
	    $comment_type_id = db_result($predef_val,$p,0);
	    $comment = trim(substr($comment,($type_end_pos+1)));
	    break;
	  }
	}
      }
    }

    if (!$comment_type_id) {
      if ($for_parse_report) $comment_type_id = 'None';
      else $comment_type_id = 100;
    } else if ($for_parse_report) {
      $comment_type_id = $comment_type;
    }
    
    $arr["date"] = $date;
    $arr["by"] = $by;
    $arr["type"] = $comment_type_id;
    $arr["comment"] = $comment;
    $parsed_details[] = $arr;
    unset($comment_type_id);
  }
  
  return true;
}



/**
 * prepare our $data record so that we can use standard artifact methods to create, update, ...
 * the imported artifact
 */
function prepare_vfl($data,$used_fields,$parsed_fields,$predefined_values,&$artifact_depend_id,&$add_cc,&$cc_comment,&$details) {
  for ($c=0; $c < count($data); $c++) {
    $label = $parsed_fields[$c];
    if ($label == "Follow-up Comments") {
      $field_name = "details";
      if ($data[$label] != "" && trim($data[$label]) != "No Followups Have Been Posted") {
	$details = $data[$label];
      }
      continue;
    } else if ($label == "Original Submission") {
      $field_name = "original_submission";
    } else if ($label == "Depend on") {
      $depends = $data[$label];
      if ($depends != "None" && $depends != "") {
	$artifact_depend_id = $depends;
      } else {
	//we have to delete artifact_depend_ids if nothing has been specified
	$artifact_depend_id = "None";
      }
      continue;
    } else if ($label == "CC List") {
      if ($data[$label] != "" && $data[$label] != "None")
      $add_cc = $data[$label];
      else $add_cc = "";
      continue;
    } else if ($label == "CC Comment") {
      $cc_comment = $data[$label];
      continue;
    } else {
      $field = $used_fields[$label];
      $field_name = $field->getName();
    }
    $imported_value = $data[$label];
    
    // transform imported_value into format that can be inserted into db
    unset($value);
    $predef_vals = $predefined_values[$c];
    if ($predef_vals) {
      if ($field && $field->getDisplayType() == "MB") {
	$val_arr = explode(",",$imported_value);
	while (list(,$name) = each($val_arr)) {
	  if ($name == 'None') $value[] = 100;
	  else $value[] = $predef_vals[$name];
	}
      } else {
	if ($imported_value == 'None') $value = 100;
	else $value = $predef_vals[$imported_value];

	//special case for severity where we allow to specify
	// 1 instead of "1 - Ordinary"
	// 5 instead of "5 - Major"
	// 9 intead of "9 - Critical"
	if ($label == "Severity" &&
	    (strcasecmp($imported_value,'1') == 0 ||
	     strcasecmp($imported_value,'5') == 0 ||
	     strcasecmp($imported_value,'9') == 0)) {
	  $value = $imported_value;
	}
      }
      $vfl[$field_name] = $value; 
    } else {
      $vfl[$field_name] = $imported_value;
    }
  }
  return $vfl;
}



/** check if all the values correspond to predefined values of the corresponding fields */
function insert_artifact($row,$data,$used_fields,$parsed_fields,$predefined_values,&$errors) {
  global $ath;
  
  //prepare everything to be able to call the artifacts create method
  $ah=new ArtifactHtml($ath);
  if (!$ah || !is_object($ah)) {
    exit_error('ERROR','Artifact Could Not Be Created');
  } else {
    // Check if a user can submit a new without loggin
    if ( !user_isloggedin() && !$ath->allowsAnon() ) {
      exit_not_logged_in();
      return;
    }
    
    //
    //  make sure this person has permission to add artifacts
    //
    if (!$ath->userIsTech() && !$ath->isPublic() ) {
      exit_permission_denied();
    }
    
    $vfl = prepare_vfl($data,$used_fields,$parsed_fields,$predefined_values,$artifact_depend_id,$add_cc,$cc_comment,$details);
   

    // Artifact creation                
    if (!$ah->create($vfl,true,$row)) {
      exit_error('ERROR',$ah->getErrorMessage());
    }
    //handle dependencies and such stuff ...
    if ($artifact_depend_id) {
      if (!$ah->addDependencies($artifact_depend_id,$changes,false)) {
	$errors .= "Problem inserting dependent artifacts (artifact id =".$ah->getID().").<br> ";
	//return false;
      }
    }
    if ($add_cc) {
      if (!$ah->addCC($add_cc,$cc_comment,$changes)) {
	$errors .= "Problem adding CC List (artifact id=".$ah->getID().").<br> ";
      }
    }

    if ($details) {
      if (parse_details($details,$parsed_details,$errors)) {
	if (!$ah->addDetails($parsed_details)) {
	  $errors .= "Problem inserting follow_up comments (artifact id=".$ah->getID().").<br> ";
	  return false;
	}
      } else {
	return false;
      }
    }
  }
  return true;
}




function update_artifact($row,$data,$aid,$used_fields,$parsed_fields,$predefined_values,$errors) {
  global $ath, $feedback;

  $ah=new ArtifactHtml($ath,$aid);
  if (!$ah || !is_object($ah)) {
    exit_error('ERROR','Artifact Could Not Be Created');
  } else if ($ah->isError()) {
    exit_error('ERROR',$ah->getErrorMessage());
  } else {
    
    // Check if users can update anonymously
    if ( $ath->allowsAnon() == 0 && !user_isloggedin() ) {
      exit_not_logged_in();
    }
    
    if ( !$ah->ArtifactType->userIsTech() ) {
      exit_permission_denied();
      return;
    }
    
    $vfl = prepare_vfl($data,$used_fields,$parsed_fields,$predefined_values,$artifact_depend_id,$add_cc,$cc_comment,$details);

    //data control layer
    if (!$ah->handleUpdate($artifact_depend_id,100,$changes,false,$vfl,true)) {
      exit_error('ERROR',$feedback);
    }
    if ($add_cc) {
      if (!$ah->updateCC($add_cc,$cc_comment)) {
	$errors .= "Problem adding CC List (artifact id=".$ah->getID(). ").<br> ";
      }
    }

    if ($details) {
      if (parse_details($details,$parsed_details,$errors)) {
	if (!$ah->addDetails($parsed_details)) {
	  $errors .= "Problem inserting follow_up comments (artifact id=".$ah->getID(). ").<br> ";
	  return false;
	}
      } else {
	return false;
      }
    }
  }
  return true;
}


/**
 * Insert or update the imported artifacts into the db
 * @param parsed_fields: array of the form (column_number => field_label) containing
 *                              all the fields parsed from $data
 * @param artifacts_data: all artifacts in an array. artifacts are in the form array(field_label => value) 
 * @param $aid_column: the column in the csv file that contains the arifact id (-1 if not given)
 * @param $errors (OUT): string containing explanation what error occurred
 * @return true if parse ok, false if errors occurred
 */
function update_db($parsed_fields,$artifacts_data,$aid_column,&$errors) {
  global $ath;
  
  $used_fields = getUsedFields();
  $predefined_values = getPredefinedValues($used_fields,$parsed_fields);
  
  for ($i=0; $i < count($artifacts_data); $i++) {
    $data = $artifacts_data[$i];
    if ($aid_column == -1) {
      $ok = insert_artifact($row,$data,$used_fields,$parsed_fields,$predefined_values,$errors);
      
      // if artifact_id given, verify if it exists already 
      //else send error
    } else {
      $aid = $data['Artifact ID'];
      if ($aid != "") {
	$ok = update_artifact($row,$data,$aid,$used_fields,$parsed_fields,$predefined_values,$errors);
	
      } else {
	// have to create artifact from scratch
	$ok = insert_artifact($row,$data,$used_fields,$parsed_fields,$predefined_values,$errors);
      }	  
    }
    if (!$ok) return false;
  }
  return true;
  
}



?>