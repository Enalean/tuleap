<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('common/include/Error.class.php');
require_once('common/include/Codendi_HTMLPurifier.class.php');
require_once('Tracker_NotificationsManager.class.php');
require_once('common/include/SimpleSanitizer.class.php');

class Tracker_Import extends Error {

  /** the tracker we are working on */
  var $ath;

  /** the tracker field factory for our tracker */
  var $art_field_fact;

  /** the group our tracker is part of */
  var $group;


  /** information about the tracker
   * used all along
   * the fields used in tracker $atid
   * array of the form (label => field)
   */
   var $used_fields;

   /** information about the tracker
    * used all along
    * array of the form (column_number => array of field predefined values)
    */
   var $predefined_values; 

   

   /** information parsed from the import file
    *  the number of columns in the parsed csv file
    */
   var $num_columns;

   /** information parsed from the import file
    * the column in the csv file that contains the arifact id (-1 if not given)
    */
   var $aid_column;

   /** information parsed from the import file
    * the column in the csv file that contains the artifact submitter
    */
   var $submitted_by_column;

   /** information parsed from the import file
    * the column in the csv file that contains the artifact submission date
    */
   var $submitted_on_column;
   
   /** information parsed from the import file
    * array of the form (column_number => field_label) containing
    * all the fields in the parsed csv file
    */
   var $parsed_labels;
   

  /** information parsed from the import file
  

  /** some localization hack */
  var $lbl_list;
  var $dsc_list;

  /**
   *  Constructor.
   *
   *      @return boolean success.
   */
  function Tracker_Import($ath,$art_field_fact,$group) {
    // Error constructor
    $this->Error();


    $this->ath = $ath;
    $this->art_field_fact = $art_field_fact;
    $this->group = $group;

    $this->localizeLabels();
    $this->used_fields = $this->getUsedFields();


    $this->aid_column = -1;
    $this->submitted_by_column = -1;
    $this->submitted_on_column = -1;
    $this->parsed_labels = array();
  }

  function localizeLabels() {
    // TODO: Localize this properly by adding those 4 fields to the artifact table
    // (standard fields) and the artifact field table with a special flag and make sure
    // all tracker scripts handle them properly
    // For now make a big hack!! (see import.php func=showformat)
    $submitted_field = $this->art_field_fact->getFieldFromName('submitted_by');
    if (strstr($submitted_field->getLabel(),"ubmit")) {
      // Assume English
      $this->lbl_list['follow_ups'] = 'Follow-up Comments';
      $this->lbl_list['is_dependent_on'] = 'Depend on';
      $this->lbl_list['add_cc'] = 'CC List';
      $this->lbl_list['cc_comment'] = 'CC Comment';

      $this->dsc_list['follow_ups'] = 'All follow-up comments in one chunck of text';
      $this->dsc_list['is_dependent_on'] = 'List of artifacts this artifact depends on';
      $this->dsc_list['add_cc'] = 'List of persons to receive a carbon-copy (CC) of the email notifications (in addition to submitter, assignees, and commenters)';
      $this->dsc_list['cc_comment'] = 'Explain why these CC names were added and/or who they are';

    } else {
      // Assume French
      $this->lbl_list['follow_ups'] = 'Commentaires';
      $this->lbl_list['is_dependent_on'] = 'Depend de';
      $this->lbl_list['add_cc'] = 'Liste CC';
      $this->lbl_list['cc_comment'] = 'Commentaire CC';

      $this->dsc_list['follow_ups'] = 'Tout le fil de commentaires en un seul bloc de texte';
      $this->dsc_list['is_dependent_on'] = 'Liste des artefacts dont celui-ci depend';
      $this->dsc_list['add_cc'] = 'Liste des pesonnes recevant une copie carbone (CC) des notifications e-mail (en plus de la personne qui l\'a soumis, a qui on l\'a confie ou qui a poste un commentaire)';
      $this->dsc_list['cc_comment'] = 'Explique pourquoi ces personnes sont en CC ou qui elles sont';
    }
  }


function getUsedFields() {
    $fields =  $this->art_field_fact->getAllUsedFields();
    while (list(,$field) = each($fields) ) {
      if ( $field->getName() != "comment_type_id" ) {
	$used_fields[$field->getLabel()] = $field;
      }
    }
    
    // TODO: Localize this properly by adding those 4 fields to the artifact table
    // (standard fields) and the artifact field table with a special flag and make sure
    // all tracker scripts handle them properly
    // For now reuse localizeLabels hack
    $used_fields[$this->lbl_list['follow_ups']] = "";
    $used_fields[$this->lbl_list['is_dependent_on']] = "";
    $used_fields[$this->lbl_list['add_cc']] = "";
    $used_fields[$this->lbl_list['cc_comment']] = "";
    
    //special cases for submitted by and submitted on that can be set
    //"unused" by the user but that will nevertheless be used by Codendi
    $submitted_by_field = $this->art_field_fact->getFieldFromName('submitted_by');
    $submitted_by_label = $submitted_by_field->getLabel();
    if (array_key_exists($submitted_by_label, $used_fields) === false)
      $used_fields[$submitted_by_label] = $submitted_by_field;

    $open_date_field = $this->art_field_fact->getFieldFromName("open_date");
    $open_date_label = $open_date_field->getLabel();
    if (array_key_exists($open_date_label, $used_fields) === false)
      $used_fields[$open_date_label] = $open_date_field; 
    
    return $used_fields;
  }



  /** parse the first line of the csv file containing all the labels of the fields that are
   * used in the following of the file
   * @param $data (IN): array containing the field labels
   * @return true if parse ok, false if errors occurred
   */ 
  function parseFieldNames($data) {
    global $Language;
    
    $this->num_columns = count($data);

    for ($c=0; $c < $this->num_columns; $c++) {
        $field_label = SimpleSanitizer::sanitize($data[$c]);
      if (!array_key_exists($field_label,$this->used_fields)) {
	$this->setError($Language->getText('plugin_tracker_import_utils','field_not_known',array($field_label,$this->ath->getName())));
	return false;
      }
      
      $field = $this->used_fields[$field_label];
      if ($field) {
	$field_name = $field->getName();
	if ($field_name == "artifact_id") $this->aid_column = $c; 
	if ($field_name == "submitted_by") $this->submitted_by_column = $c;
	if ($field_name == "open_date") $this->submitted_on_column = $c;
      }
      $this->parsed_labels[$c] = $field_label;
      
    }

    if (!$this->checkMandatoryFields()) { 
      return false;
    }

    
    return true;
  }

  function checkMandatoryFields() {
    global $Language;
    // verify if we have all mandatory fields in the case we have to create an artifact
    if ($this->aid_column == -1) {
      reset($this->used_fields);
      
      while (list($label,$field) = each($this->used_fields)) {
	//echo $label.",";
	if ($field) {
	  $field_name = $field->getName();
	  if ($field_name != "artifact_id" &&
	      $field_name != "open_date" &&
	      $field_name != "submitted_by" &&
	      $label != $this->lbl_list['follow_ups'] &&
	      $label != $this->lbl_list['is_dependent_on'] &&
	      $label != $this->lbl_list['add_cc'] &&
	      $label != $this->lbl_list['cc_comment'] &&
	      !$field->isEmptyOk() && !in_array($label,$this->parsed_labels)) {
	    
	    $this->setError($Language->getText('plugin_tracker_import_utils','field_mandatory',array($label,$this->ath->getName())));
	    return false;
	  }
	}
      }
    }
    return true;
  }
  
  /**
   * check whether val is one of the prefdefined values of this field
   * @param field: the field concerned
   * @param field_name: the fields field_name
   * @param label: the fields label
   * @param val: the csv value to check
   * @param predef_vals: array containing all predefined values of this field
   * @param row: row number in csv file (for error reporting)
   * @param data: array containing the parsed csv file (for error reporting)
   */
  function checkPredefinedValues($field,$field_name,$label,$val,$predef_vals,$row,$data) {
    global $Language;
    $hp = Codendi_HTMLPurifier::instance();
    if ($field->getDisplayType() == "MB") {
      $val_arr = explode(",",$val);
      while (list(,$name) = each($val_arr)) {
	if (!array_key_exists($name,$predef_vals) && $name != $Language->getText('global','none')) {
	  $this->setError($Language->getText('plugin_tracker_import_utils','not_a_predefined_value',array(
          $row+1,
          $hp->purify(implode(",",$data), CODENDI_PURIFIER_CONVERT_HTML),
          $hp->purify($name, CODENDI_PURIFIER_CONVERT_HTML) ,
          $hp->purify($label, CODENDI_PURIFIER_CONVERT_HTML) ,
          $hp->purify(implode(",",array_keys($predef_vals)), CODENDI_PURIFIER_CONVERT_HTML))));
	  return false;
	}
      }
    } else {
      if (!array_key_exists($val,$predef_vals) && $val != $Language->getText('global','none') && $val != "") {
	if (($field_name == 'severity') &&
	    (strcasecmp($val,'1') == 0 || strcasecmp($val,'5') == 0 || strcasecmp($val,9) == 0)) {
	  //accept simple ints for Severity fields instead of 1 - Ordinary,5 - Major,9 - Critical
	  //accept simple ints for Priority fields instead of 1 - Lowest,5 - Medium,9 - Highest
	} else if ($field_name == 'submitted_by' && 
		   (($val == $Language->getText('global','none') && $this->ath->allowsAnon()) ||
		    $val == "" ||
		    user_getemail_from_unix($val) != $Language->getText('include_user','not_found'))) {
	  //accept anonymous user, use importing user as 'submitted by', or simply make sure that user is a known user
	} else {
	  $this->setError($Language->getText('plugin_tracker_import_utils','not_a_predefined_value',array(
          $row+1,
          $hp->purify(implode(",",$data), CODENDI_PURIFIER_CONVERT_HTML),
          $hp->purify($val, CODENDI_PURIFIER_CONVERT_HTML) ,
          $hp->purify($label, CODENDI_PURIFIER_CONVERT_HTML) ,
          $hp->purify(implode(",",array_keys($predef_vals)), CODENDI_PURIFIER_CONVERT_HTML))));
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
   * @param from_update: take into account special case where column tracker_id is specified but
   *                      for this concrete artifact no aid is given
   */
  function checkValues($row,&$data,$insert,$from_update=false) {
    global $Language;
    $hp = Codendi_HTMLPurifier::instance();
    for ($c=0; $c < count($this->parsed_labels); $c++) {
      $label = $this->parsed_labels[$c];
      $val = $data[$c];
      $field = $this->used_fields[$label];
      if ($field) $field_name = $field->getName();
      
      // check if val in predefined vals (if applicable)
      unset($predef_vals);
      if (isset($this->predefined_values[$c])) {$predef_vals = $this->predefined_values[$c];}
      if (isset($predef_vals)) {
	if (!$this->checkPredefinedValues($field,$field_name,$label,$val,$predef_vals,$row,$data)) {
	  return false;
	}
      }
      
      // check whether we specify None for a field which is mandatory
      if ($field && !$field->isEmptyOk() &&
	  $field_name != "artifact_id") {
	if ($field_name == "submitted_by" ||
	    $field_name == "open_date") {
	  //submitted on and submitted by are accepted as "" on inserts and
	  //we put time() importing user as default
	} else {
	  
	  $is_empty = ( ($field->isSelectBox() || $field->isMultiSelectBox()) ? ($val==$Language->getText('global','none')) : ($val==''));

	  if ($is_empty) {
	    $this->setError($Language->getText('plugin_tracker_import_utils','field_mandatory_and_current',array(
            $row+1,
            $hp->purify(implode(",",$data), CODENDI_PURIFIER_CONVERT_HTML),
            $hp->purify($label, CODENDI_PURIFIER_CONVERT_HTML) ,
            $hp->purify(SimpleSanitizer::unsanitize($this->ath->getName()), CODENDI_PURIFIER_CONVERT_HTML) ,
            $hp->purify($val, CODENDI_PURIFIER_CONVERT_HTML) )));
	    return false;
	  }
	}
      }
      
      // for date fields: check format
      if ($field && $field->isDateField()) {
	if ($field_name == "open_date" && $val == "") {
	  //is ok.
	} else {
	  
	  if ($val == "-" || $val == "") {
	    //ok. transform it by hand into 0 before updating db
	    $data[$c] = "";
	  } else {
	    list($unix_time,$ok) = util_importdatefmt_to_unixtime($val);
	    if (!$ok) {
	      $this->setError($Language->getText('plugin_tracker_import_utils','incorrect_date',array(
              $row+1,
              $hp->purify(implode(",",$data), CODENDI_PURIFIER_CONVERT_HTML) ,
              $hp->purify($val, CODENDI_PURIFIER_CONVERT_HTML) )));
	      return false;
	    }
	    $date = format_date("Y-m-d",$unix_time);
	    $data[$c] = $date;
	  }
	}
      }
    } // end of for parsed_labels


    if (!$insert && $label == $this->lbl_list['follow_ups']) {
      /* check whether we need to remove known follow-ups */
      
    }
    
    // if we come from update case ( column tracker_id is specified but for this concrete artifact no aid is given)
    // we have to check whether all mandatory fields are specified and not empty
    if ($from_update) {

      
      while (list($label,$field) = each($this->used_fields)) {
	if ($field) $field_name = $field->getName();
	
	if ($field) {
          if ($field_name != "artifact_id" &&
              $field_name != "open_date" &&
              $field_name != "submitted_by" &&
              $label != $this->lbl_list['follow_ups'] &&
              $label != $this->lbl_list['is_dependent_on'] &&
              $label != $this->lbl_list['add_cc'] &&
              $label != $this->lbl_list['cc_comment'] &&
              !$field->isEmptyOk() && !in_array($label,$this->parsed_labels)) {
	    
	    $this->setError($Language->getText('plugin_tracker_import_utils','field_mandatory_and_line',array(
            $row+1,
            $hp->purify(implode(",",$data), CODENDI_PURIFIER_CONVERT_HTML) ,
            $hp->purify($label, CODENDI_PURIFIER_CONVERT_HTML) ,
            $hp->purify(SimpleSanitizer::unsanitize($this->ath->getName()), CODENDI_PURIFIER_CONVERT_HTML) )));
	    return false;
          }
	}
      }
      
      
    }//end from_update
    
    return true;
  }
  
  
  /**
   * @param $from_update: take into account special case where column tracker_id is specified but
   *                      for this concrete artifact no aid is given
   */
  function checkInsertArtifact($row,&$data,$from_update=false) {
    global $Language;
    $hp = Codendi_HTMLPurifier::instance();
    // first make sure this isn't double-submitted
    
    //$field = $used_fields["Summary"];
    $summary_field = $this->art_field_fact->getFieldFromName('summary');
    $summary_label = $summary_field->getLabel();
    $summary_col = array_search($summary_label,$this->parsed_labels);
    
    $submitted_by_field = $this->art_field_fact->getFieldFromName('submitted_by');
    $submitted_by_label = $submitted_by_field->getLabel();
    $summary = htmlspecialchars($data[$summary_col]);
    if ($this->submitted_by_column != -1) {
      $sub_user_name = $data[$this->submitted_by_column];
      //$sub_user_ids = $predefined_values[$submitted_by_col];
      $res = user_get_result_set_from_unix($sub_user_name);
      $sub_user_id = db_result($res,0,'user_id');
    } else {
      $this->getImportUser($sub_user_id,$sub_user_name);
    }
    
  
    if ( $summary_field && $summary_field->isUsed() ) {
    $res=db_query("SELECT * FROM artifact WHERE group_artifact_id = ". db_ei($this->ath->getID()) .
		  " AND submitted_by=".  db_ei($sub_user_id) ." AND summary='".  db_es($summary) ."'");
    if ($res && db_numrows($res) > 0) {
      $this->setError($Language->getText('plugin_tracker_import_utils','already_submitted',array(
          $row+1,
          $hp->purify(implode(",",$data), CODENDI_PURIFIER_CONVERT_HTML) ,
          $sub_user_name,
          $hp->purify(util_unconvert_htmlspecialchars($summary), CODENDI_PURIFIER_CONVERT_HTML) )));
      return false;           
    }
  }
  
    return $this->checkValues($row,$data,true,$from_update);
  }



  /** check if all the values correspond to predefined values of the corresponding fields */
  function checkUpdateArtifact($row,&$data,$aid) {
    global $Language;
    $hp = Codendi_HTMLPurifier::instance();
    $sql = "SELECT tracker_id FROM artifact WHERE tracker_id = $aid and group_artifact_id = ".$this->ath->getID();
    $result = db_query($sql);
    if (db_numrows($result) == 0) {
      $this->setError($Language->getText('plugin_tracker_import_utils','art_not_exists',array(
          $row+1,
          $hp->purify(implode(",",$data), CODENDI_PURIFIER_CONVERT_HTML) ,
          $aid,
          $hp->purify(SimpleSanitizer::unsanitize($this->ath->getName()), CODENDI_PURIFIER_CONVERT_HTML) )));
      return false;
    }
    
    return $this->checkValues($row,$data,false);
  }
  


  /** parse a file in csv format containing artifacts to be imported into the db
   * @param $csv_filename (IN): the complete file name of the cvs file to be parsed
   * @param $is_tmp (IN): true if cvs_file is only temporary file and we want to unlink it 
   *                      after parsing
   * @param $artifacts (OUT): the artifacts with their field values parsed from the csv file
   * @return true if parse ok, false if errors occurred
   */
  function parse($csv_filename,$is_tmp,
		 &$artifacts_data,
		 &$number_inserts,&$number_updates) {
    global $Language;
    $hp = Codendi_HTMLPurifier::instance();
    
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
    
    
  
  $csv_file = fopen($csv_filename, "r");
  $row = 0;
  
  while ($data = fgetcsv($csv_file, $length, get_csv_separator())) {
    // do the real parsing here
    
    //parse the first line with all the field names
    if ($row == 0) {
      $ok = $this->parseFieldNames($data);
      
      if (!$ok) return false;
      

      // get already predefined values for fields
      $this->getPredefinedValues();

      //parse artifact values
    } else {
      
      //verify whether this row contains enough values
      $num = count($data);
      if ($num != $this->num_columns) { 
	$data_details = "";
	foreach ($data as $key => $value) {
	  if ($data_details != "") $data_details .= ", ";
	  $data_details .= "[".$this->parsed_labels[$key]."] => $value";
	}
	reset($data);
	$this->setError($Language->getText('plugin_tracker_import_utils','column_mismatch',array(
        $row+1,
        $hp->purify($data_details, CODENDI_PURIFIER_CONVERT_HTML) ,
        $num,
        $this->num_columns)));
	return FALSE;
      }
      
      
      // if no tracker_id given, create new artifacts	
      if ($this->aid_column == -1) {
	$ok = $this->checkInsertArtifact($row,$data);
	$number_inserts++;
	// if tracker_id given, verify if it exists already 
	//else send error
      } else {
	$aid = $data[$this->aid_column];
	if ($aid != "") {
	  $ok = $this->checkUpdateArtifact($row,$data,$aid);
	  $number_updates++;
	  
	} else {
	  // have to create artifact from scratch
	  $ok = $this->checkInsertArtifact($row,$data,true);
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



  function mandatoryFields() {
    $fields =  $this->art_field_fact->getAllUsedFields();
    while (list(,$field) = each($fields) ) {
      if ( $field->getName() != "comment_type_id" && !$field->isEmptyOk()) {
	$mand_fields[$field->getName()] = true;
      }
    } 
    return $mand_fields;
  }

  

  function getImportUser(&$sub_user_id,&$sub_user_name) {
    global $user_id;
    
    $sub_user_id = $user_id;
    
    if (!$this->ath->userIsAdmin()) {
      exit_permission_denied();
    } else {
      $sub_user_name = user_getname();
    }
  }


  function getPredefinedValues() {
    for ($c=0; $c < sizeof($this->parsed_labels); $c++) {
      $field_label = $this->parsed_labels[$c];
      $field = $this->used_fields[$field_label];
      if ($field) {
	$this->setPredefinedValue($field,$c);
      }
    }
  }
  
  /**
   * set the predefined values of the field parsed at column column_number
   */
  function setPredefinedValue($field,$column_number) {
    
    if ($field && 
	($field->getDisplayType() == "SB" || $field->getDisplayType() == "MB")) {
      
      //special case for submitted by
      if ($field->getName() == "submitted_by") {
	// simply put nothing in predefined values for submitted_by
	// as we accept all logged users, even None for allow-anon trackers
	
	//for all other fields not submitted by
      } else {
	
	$predef_val = $field->getFieldPredefinedValues($this->ath->getID());
	$count = db_numrows($predef_val);
	for ($i=0;$i<$count;$i++) {
        $values[SimpleSanitizer::unsanitize(db_result($predef_val,$i,1))] = db_result($predef_val,$i,0);
	}
	$this->predefined_values[$column_number] = $values;
      }
    }
  }


  function checkCommentExist($arr,$art_id) {
    if (!$art_id || $art_id == 0 || $art_id == '0') return false;

    /*
     we can not use those escaped strings to compare with what is in the DB
     because of escaped \n and \r
     if (function_exists('mysql_real_escape_string')) {
      echo "comment: ".$arr['comment']." <br>\n";
      $comment = mysql_real_escape_string($arr['comment']);
      echo "escaped: $comment <br>\n";
    } else {
      $comment = mysql_escape_string($arr['comment']);
    }
    */

    $comment = str_replace("'","\'",$arr['comment']);

    $res=db_query("SELECT * FROM tracker_history WHERE tracker_id = ". db_ei($art_id) ." AND field_name = 'comment' AND old_value = '". db_es($comment) ."'");

    if ($res && db_numrows($res) > 0) {
      return true;
    } else {
      return false;
    }
  }

  /** assume that the 
   * @param followup_comments (IN): comments have the form that we get when exporting follow-up comments in csv format
   *                      (see Tracker_Html->showFollowUpComments(ascii = true))
   * @param parsed_comments (OUT): an array (#detail => array2), where array2 is of the form
   *                              ("date" => date, "by" => user, "type" => comment-type, "comment" => comment-string)
   * @param for_parse_report (IN): if we parse the follow-up comments to show them in the parse report then we keep the labels
   *                               for users and comment-types
   */
  function parseFollowUpComments($followup_comments,&$parsed_comments,$art_id,$for_parse_report=false) {
    global $sys_lf, $user_id,$Language;
    
    //echo "<br>\n";
    $comments = split("------------------------------------------------------------------",$followup_comments);
    
    $i = 0;
    while (list(,$comment) = each($comments)) {
      $i++;
      if (($i == 1) && 
	  ( (count($comments) > 1) || 
	    (trim($comment) == $Language->getText('plugin_tracker_import_utils','no_followups')) ) ) {
	//skip first line
	continue;
      }
      $comment = trim($comment);
      
      //skip the "Date: "
      if (strpos($comment, $Language->getText('plugin_tracker_import_utils','date').":") === false) {
	//if no date given, consider this whole string as the comment
	
	//try nevertheless if we can apply legacy Bug and Task export format
	if ($this->parseLegacyDetails($followup_comments,$parsed_comments,$for_parse_report)) {
	  return true;
	} else {
	  if ($for_parse_report) {
	    $date= format_date($GLOBALS['Language']->getText('system', 'datefmt'),time());
	    $this->getImportUser($sub_user_id,$sub_user_name);
	    $arr["date"] = "<I>$date</I>";
	    $arr["by"] = "<I>$sub_user_name</I>";
	    $arr["type"] = "<I>".$Language->getText('global','none')."</I>";
	  } else {
	    $arr["date"] = time();
	    $arr["by"] = $user_id;
	    $arr["type"] = 100;
	  }
	  $arr["comment"] = $comment;
	  if (!$this->checkCommentExist($arr,$art_id)) {
	    $parsed_comments[] = $arr;
	  }
	  continue;
	}
      }
      
      // here starts reel parsing
      $comment = substr($comment, strlen($Language->getText('plugin_tracker_import_utils','date').":"));
      $by_position = strpos($comment,$Language->getText('global','by').": ");
      if ($by_position === false) {
	$this->setError($Language->getText('plugin_tracker_import_utils','specify_originator',array($i-1,$comment)));
	return false;
      }
      $date_str = trim(substr($comment, 0, $by_position));
      //echo "$date_str<br>";
      if ($for_parse_report) $date = $date_str;
      else list($date,$ok) = util_importdatefmt_to_unixtime($date_str);
      //echo "$date<br>";
      //skip "By: "
      $comment = substr($comment, ($by_position + strlen($Language->getText('global','by').": ")));
      
      $by = strtok($comment," \n\t\r\0\x0B");
      $comment = trim(substr($comment,strlen($by)+1));
      
      if ($by == $Language->getText('global','none')) {
	$this->setError($Language->getText('plugin_tracker_import_utils','specify_valid_user',$i-1));
	return false;
      }
      if (!$for_parse_report) {
	$res = user_get_result_set_from_unix($by);
	if (db_numrows($res) > 0) {
	  $by = db_result($res,0,'user_id');
	} else if (validate_email($by)) {
	  //ok, $by remains what it is
	} else {
	  $this->setError($Language->getText('plugin_tracker_import_utils','not_a_user',array($by,$i-1)));
	  return false;
	}
      }
      
      //see if there is comment-type or none
      $comment_type_id = false;
      $type_end_pos = strpos($comment,"]");
      if (strpos($comment,"[") == 0 &&  $type_end_pos!= false) {
	$comment_type = substr($comment, 1, ($type_end_pos-1));
	$comment = trim(substr($comment,($type_end_pos+1)));
	
	$comment_type_id = $this->checkCommentType($comment_type);
      }
      
      if ($comment_type_id === false) {
	if ($for_parse_report) $comment_type_id = $Language->getText('global','none');
	else $comment_type_id = 100;
      } else if ($for_parse_report) {
	$comment_type_id = $comment_type;
      }
      
      $arr["date"] = $date;
      $arr["by"] = $by;
      $arr["type"] = $comment_type_id;
      $arr["comment"] = $comment;
      if (!$this->checkCommentExist($arr,$art_id)) {
	$parsed_comments[] = $arr;
      }
      unset($comment_type_id);
    }
    
    return true;
  }


  /** check whether this is really a valid comment_type
   * and if it is the case return its id else return false 
   */
  function checkCommentType($comment_type) {
    
    $comment_type_id = false;
    
    $c_type_field = $this->art_field_fact->getFieldFromName('comment_type_id');
    if ($c_type_field) {
      $predef_val = $c_type_field->getFieldPredefinedValues($this->ath->getID());
      $count = db_numrows($predef_val);
      for ($p=0;$p<$count;$p++) {
	if ($comment_type == db_result($predef_val,$p,1)) {
	  $comment_type_id = db_result($predef_val,$p,0);
	  break;
	}
      }
    }
    return $comment_type_id;
  }
  

  /** assume that the details input format is
   * ==================================================
   * [Type:<type>] By:<by> On:<date>
   *
   * <comment>
   *
   * @param details (IN): see above
   * @param parsed_details (OUT): an array (#detail => array2), where array2 is of the form
   *                              ("date" => date, "by" => user, "type" => comment-type, "comment" => comment-string)
   * @param for_parse_report (IN): if we parse the details to show them in the parse report then we keep the labels
   *                               for users and comment-types
   */
     function parseLegacyDetails($details,&$parsed_details,$for_parse_report=false) {
    global $sys_lf, $user_id,$Language;
    
    $comments = split("==================================================",$details);
    
    $i = 0;
    
    while (list(,$comment) = each($comments)) {
      
      $i++;
      if ($i==1) continue;
      
      $comment = trim($comment);
      //skip the "Type: "
      if (strpos($comment, $Language->getText('plugin_tracker_import_utils','type').": ") === false) {
	//if no type given, consider this whole string as the comment
	if ($for_parse_report) $comment_type = $Language->getText('global','none');
	else $comment_type = 100;
      } else {
	$comment = substr($comment, strlen($Language->getText('plugin_tracker_import_utils','type').": "));
	$by_position = strpos($comment,$Language->getText('global','by').": ");
	if ($by_position === false) {
	  $this->setError($Language->getText('plugin_tracker_import_utils','specify_originator',array($i-1,$comment)));
	  return false;
	}
	$type = trim(substr($comment,0,$by_position));
	$comment_type_id = $this->checkCommentType($type);
	if ($comment_type_id === false) {
	  if ($for_parse_report) $comment_type = $Language->getText('global','none');
	  else $comment_type = 100;
	} else {
	  if ($for_parse_report) $comment_type = $type;
	  else $comment_type = $comment_type_id;
	}
      }
      
      // By:
      $by_position = strpos($comment,$Language->getText('global','by').": ");
      if ($by_position === false) {
	$this->setError($Language->getText('plugin_tracker_import_utils','specify_originator',array($i-1,$comment)));
	return false;
      }
      
      $comment = substr($comment, ($by_position + strlen($Language->getText('global','by').": ")));
      $on_position = strpos($comment, $Language->getText('global','on').": ");
      $by = trim(substr($comment, 0, $on_position));
      
      
      if (!$for_parse_report) {
	$res = user_get_result_set_from_unix($by);
	if (db_numrows($res) > 0) {
	  $by = db_result($res,0,'user_id');
	} else if (validate_email($by)) {
	  //ok, $by remains what it is
	} else {
	  $this->setError($Language->getText('plugin_tracker_import_utils','not_a_user',array($by,$i-1)));
	  return false;
	}
      }
      // On:
      $comment = substr($comment, ($on_position+strlen($Language->getText('global','on').": ")));
      $on = strtok($comment,"\n\t\r\0\x0B");
      $comment = trim(substr($comment,strlen($on)));
      if (!$for_parse_report) list($on,$ok) = util_importdatefmt_to_unixtime($on);
      
      
      $arr["date"] = $on;
      $arr["by"] = $by;
      $arr["type"] = $comment_type;
      $arr["comment"] = trim($comment);
      $parsed_details[] = $arr;
    }
    
    return true;
  }
  


  /**
   * prepare our $data record so that we can use standard artifact methods to create, update, ...
   * the imported artifact
   */
  function prepareVfl($data,&$artifact_depend_id,&$add_cc,&$cc_comment,&$comments) {
  global $Language;
  for ($c=0; $c < count($data); $c++) {
    $label = $this->parsed_labels[$c];
    $field = $this->used_fields[$label];
    if ($field) $field_name = $field->getName();
    $imported_value = $data[$label];

    // FOLLOW-UP COMMENTS
    if ($label == $this->lbl_list['follow_ups']) {
      //$field_name = "details";
      if ($data[$label] != "" && trim($data[$label]) != $Language->getText('plugin_tracker_import_utils','no_followups')) {
	$comments = $data[$label];
      }
      continue;
      
    // DEPEND ON
    } else if ($label == $this->lbl_list['is_dependent_on']) {
      $depends = $data[$label];
      if ($depends != $Language->getText('global','none') && $depends != "") {
	$artifact_depend_id = $depends;
      } else {
	//we have to delete tracker_depend_ids if nothing has been specified
	$artifact_depend_id = $Language->getText('global','none');
      }
      continue;
    
    // CC LIST
    } else if ($label == $this->lbl_list['add_cc']) {
      if ($data[$label] != "" && $data[$label] != $Language->getText('global','none'))
      $add_cc = $data[$label];
      else $add_cc = "";
      continue;

    // CC COMMENT
    } else if ($label == $this->lbl_list['cc_comment']) {
      $cc_comment = $data[$label];
      continue;

    // ORIGINAL SUBMISSION
      //special treatment for "Original Submission" alias "details"
      //in the import. To avoid confusion, the details field is renamed
      //original_submission in the import
      //} else if (isset($field_name) && $field_name == "details") {
      //$vfl["original_submission"] = $data[$label];
      //continue;
    
    // SUBMITTED BY
    } else if ($field_name == "submitted_by") {
      $sub_user_name = $data[$label];
      if ($sub_user_name && $sub_user_name != "") {
	$res = user_get_result_set_from_unix($sub_user_name);
	$imported_value = db_result($res,0,'user_id');
      }
      $vfl[$field_name] = $imported_value;
      continue;
    } 

  
    
    
    // transform imported_value into format that can be inserted into db
    unset($value);
    unset($predef_vals);
    if (isset($this->predefined_values[$c])) {$predef_vals = $this->predefined_values[$c];}
    if (isset($predef_vals)) {
      if ($field && $field->getDisplayType() == "MB") {
	$val_arr = explode(",",$imported_value);
	while (list(,$name) = each($val_arr)) {
	  if ($name == $Language->getText('global','none')) $value[] = 100;
	  else $value[] = $predef_vals[$name];
	}
      } else {

	if ($imported_value == $Language->getText('global','none')) $value = 100;
	else $value = $predef_vals[$imported_value];

	//special case for severity where we allow to specify
	// 1 instead of "1 - Ordinary"
	// 5 instead of "5 - Major"
	// 9 intead of "9 - Critical"
	if ($field_name == "severity" &&
	    (strcasecmp($imported_value,'1') == 0 ||
	     strcasecmp($imported_value,'5') == 0 ||
	     strcasecmp($imported_value,'9') == 0)) {
	  $value = $imported_value;
	}
      }
      $vfl[$field_name] = $value; 


    // IT COULD BE SO SIMPLE !!!
    } else {
      $vfl[$field_name] = $imported_value;
    }
  }

  return $vfl;
  }



  /** check if all the values correspond to predefined values of the corresponding fields */
  function insertArtifact($row,$data,&$errors,$notify=false) {
    global $Language;
    
  //prepare everything to be able to call the artifacts create method
  $ah=new Tracker_Html($this->ath);
  if (!$ah || !is_object($ah)) {
    exit_error($Language->getText('global','error'),$Language->getText('plugin_tracker_index','not_create_art'));
  } else {
    // Check if a user can submit a new without loggin
    if ( !user_isloggedin() && !$this->ath->allowsAnon() ) {
      exit_not_logged_in();
      return;
    }
    
    //
    //  make sure this person has permission to add artifacts
    //
    if (!$this->ath->userIsAdmin()) {
      exit_permission_denied();
    }
    
    $vfl = $this->prepareVfl($data,$artifact_depend_id,$add_cc,$cc_comment,$comments);
   
    // Tracker_ creation        
    if (!$ah->create($vfl,true,$row)) {
      exit_error($Language->getText('global','error'),$ah->getErrorMessage());
    }
    //handle dependencies and such stuff ...
    if ($artifact_depend_id) {
      if (!$ah->addDependencies($artifact_depend_id,$changes,false)) {
	$errors .= $Language->getText('plugin_tracker_import_utils','problem_insert_dependent',$ah->getID())." ";
	//return false;
      }
    }
    if ($add_cc) {
      if (!$ah->addCC($add_cc,$cc_comment,$changes)) {
	$errors .= $Language->getText('plugin_tracker_import_utils','problem_add_cc',$ah->getID())." ";
      }
    }

    if ($comments) {
      if ($this->parseFollowUpComments($comments,$parsed_comments,'0') && $parsed_comments && !empty($parsed_comments)) {
	if (!$ah->addFollowUpComments($parsed_comments)) {
	  $errors .= $Language->getText('plugin_tracker_import_utils','problem_insert_followup',$ah->getID())." ";
	  return false;
	}
      } else {
	return false;
      }
    }    
    if($notify) {
        $agnf = new Tracker_NotificationsManager($this->ath);
        $ah->mailFollowupWithPermissions($agnf->getAllAddresses($this->ath->getID(), $update = false));
    }
  }
  return true;
  }




  function updateArtifact($row,$data,$aid,&$errors,$notify=false) {
  global $Language;

  $ah=new Tracker_Html($this->ath,$aid);
  if (!$ah || !is_object($ah)) {
    exit_error($Language->getText('global','error'),$Language->getText('plugin_tracker_index','not_create_art'));
  } else if ($ah->isError()) {
    exit_error($Language->getText('global','error'),$ah->getErrorMessage());
  } else {
    
    // Check if users can update anonymously
    if ( !user_isloggedin() && !$this->ath->allowsAnon()  ) {
      exit_not_logged_in();
    }
    
    if ( !$ah->Tracker->userIsAdmin() ) {
      exit_permission_denied();
      return;
    }
    

    $vfl = $this->prepareVfl($data,$artifact_depend_id,$add_cc,$cc_comment,$comments);

    //data control layer
    if (!$ah->handleUpdate($artifact_depend_id,100,$changes,false,$vfl,true)) {
      exit_error($Language->getText('global','error'),'');
    }
    if ($add_cc) {
      if (!$ah->updateCC($add_cc,$cc_comment)) {
	$errors .= $Language->getText('plugin_tracker_import_utils','problem_add_cc',$ah->getID())." ";
      }
    }
    $comments_ok = false;
    if ($comments) {
      if ($this->parseFollowUpComments($comments,$parsed_comments,$aid) && $parsed_comments && !empty($parsed_comments)) {
            $comments_ok = true;
            if (!$ah->addFollowUpComments($parsed_comments)) {
                $errors .= $Language->getText('plugin_tracker_import_utils','problem_insert_followup',$ah->getID())." ";
                $comments_ok = false;
                return false;
            }
        } else {
            return false;
        }
    }
    if($notify && (count($changes)>0 || $add_cc || $comments_ok)) {
        $agnf = new Tracker_NotificationsManager($this->ath);
        $ah->mailFollowupWithPermissions($agnf->getAllAddresses($this->ath->getID(), $update = true), $changes);
    }

    if(count($changes)>0 || $add_cc || $comments_ok) {
        // Update the 'last_update_date' artifact field
        $res_last_up = $ah->update_last_update_date();
    }      

  }  
  return true;
  }


  /**
   * Insert or update the imported artifacts into the db
   * @param artifacts_data: all artifacts in an array. artifacts are in the form array(field_label => value) 
   * @param $errors (OUT): string containing explanation what error occurred
   * @param $notify (IN): If true users notfication will be throw
   * @return true if parse ok, false if errors occurred
   */
  function updateDB($parsed_labels,$artifacts_data,$aid_column,&$errors, $notify=false) {

    $this->aid_column = $aid_column;
    $this->parsed_labels = $parsed_labels;
    $this->getPredefinedValues();

      
    for ($i=0; $i < count($artifacts_data); $i++) {
      $data = $artifacts_data[$i];
      if ($this->aid_column == -1) {
	$ok = $this->insertArtifact($i+2,$data,$errors,$notify);
	
	// if tracker_id given, verify if it exists already 
	//else send error
      } else {
	$aid_field = $this->art_field_fact->getFieldFromName('tracker_id');
	$aid_label = $aid_field->getLabel();
	$aid = $data[$aid_label];
	if ($aid != "") {
	  $ok = $this->updateArtifact($i+2,$data,$aid,$errors, $notify);
	  
	} else {
	  // have to create artifact from scratch
	  $ok = $this->insertArtifact($i+2,$data,$errors, $notify);
	}	  
      }
      if (!$ok) return false;
    }
    return true;
    
  }


}


?>
