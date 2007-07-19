<?php
/**
 *
 * Artifact.class.php - Main Artifact class
 *
 * SourceForge: Breaking Down the Barriers to Open Source Development
 * Copyright 1999-2001 (c) VA Linux Systems
 * http://sourceforge.net
 *
 *
 * Written for CodeX by Stephane Bouhet
 *
 */
require_once('common/tracker/ArtifactFile.class.php');
require_once('common/mail/Mail.class.php');

$Language->loadLanguageMsg('tracker/tracker');
$Language->loadLanguageMsg('include/include');

class Artifact extends Error {

    /**
     * Artifact Type object.
     *
     * @var             object  $ArtifactType.
     */
    var $ArtifactType; 

    /**
     * Array of artifact data.
     *
     * @var             array   $data_array.
     */
    var $data_array;
        

    /**
     *  Artifact - constructor.
     *
     *  @param  object  The ArtifactType object.
     *  @param  integer (primary key from database OR complete assoc array) 
     *          ONLY OPTIONAL WHEN YOU PLAN TO IMMEDIATELY CALL ->create()
     *  @return boolean success.
     */
    function Artifact(&$ArtifactType, $data=false, $checkPerms = true) {
      global $Language;
        $this->Error(); 

        $this->ArtifactType = $ArtifactType;

        //was ArtifactType legit?
        if (!$ArtifactType || !is_object($ArtifactType)) {
            $this->setError('Artifact: '.$Language->getText('tracker_common_canned','not_valid'));
            return false;
        }
        //did ArtifactType have an error?
        if ($ArtifactType->isError()) {
            $this->setError('Artifact: '.$ArtifactType->getErrorMessage());
            return false;
        }
                
        //
        //      make sure this person has permission to view artifacts belonging to this tracker
        //
        if ($checkPerms && !$this->ArtifactType->userCanView()) {
            $this->setError('Artifact: '.$Language->getText('tracker_common_artifact','view_private'));
            return false;
        }

        //
        //      set up data structures
        //
        if ($data) {
            if (is_array($data)) {
                $this->data_array = $data;
                //
                //      Should verify ArtifactType ID
                //
            } else {
                if (!$this->fetchData($data)) {
                    return false;
                }
            }
            //
            //      make sure this person has permission to view this artifact
            //
            if ($checkPerms) {
                if (!$this->userCanView()) {
                    $this->setError('Artifact: '.$Language->getText('tracker_common_artifact','view_private'));
                    return false;
                }
            }
        }
        return true;
    }


    /**
     *  fetchData - re-fetch the data for this Artifact from the database.
     *
     *  @param  int             The artifact ID.
     *  @return boolean success.
     */
    function fetchData($artifact_id) {

        global $art_field_fact,$Language;

        // first fetch values of standard fields
        $sql = "SELECT * FROM artifact WHERE artifact_id='$artifact_id' AND group_artifact_id='".$this->ArtifactType->getID()."'";
        $res=db_query($sql);
        if (!$res || db_numrows($res) < 1) {
            $this->setError('Artifact: '.$Language->getText('tracker_common_artifact','invalid_id'));
            return false;
        }
        $this->data_array = db_fetch_array($res);
        db_free_result($res);
            

        // now get the values for generic fields if any
        $sql = "SELECT * FROM artifact_field_value WHERE artifact_id='$artifact_id'";
        $res=db_query($sql);
        if (!$res || db_numrows($res) < 1) {
            // if no result then it is possible that there isn't any generic fields
            return true;
        }
        while ($row = db_fetch_array($res)) {
            $data_fields[$row['field_id']] = $row;
        }

        // Get the list of all fields used by this tracker and append
        // the values for these generic fields to data_array
        $fields = $art_field_fact->getAllUsedFields();

        while (list($key,$field) = each($fields) ) {
            //echo $field->getName()."-".$field->getID()."<br>";
            // Skip! Standard field values fectched in previous query
            // and comment_type_id is not stored in artifact_field_value table
            if ( $field->isStandardField() ||
                 $field->getName() == "comment_type_id") {
                continue;
            }
            $this->data_array[$field->getName()] = $data_fields[$field->getID()][$field->getValueFieldName()];

        }

        return true;
    }

    /**
     *  getArtifactType - get the ArtifactType Object this Artifact is associated with.
     *
     *  @return object  ArtifactType.
     */
    function getArtifactType() {
        return $this->ArtifactType;
    }
        
    /**
     *  getValue - get the value for this artifact field.
     *
     *           @param name: the field name
     *  @return value
     */
    function getValue($name) {
        if (array_key_exists($name, $this->data_array)) return $this->data_array[$name];
    }


    /**
     *  getMultiAssignedTo - get the value for the 'multi_assigned_to' field
     *  This function is needed because getValue() won't return an array.
     *
     *  @return array
     */
    function getMultiAssignedTo() {
        $aid=$this->getID();
        if (!$aid) return;
        $sql="SELECT afv.valueInt FROM artifact_field_value afv, artifact a, artifact_field af WHERE a.artifact_id=$aid AND afv.artifact_id=$aid AND a.group_artifact_id=af.group_artifact_id AND afv.field_id=af.field_id AND af.field_name='multi_assigned_to'";
        $res=db_query($sql);
        $i=0;
        $return_val = array();
        while($resrow = db_fetch_array($res)) {
            $return_val[$i++]=$resrow['valueInt'];
        }
        return $return_val;
    }

    /**
     *  getID - get this ArtifactID.
     *
     *  @return int     The artifact_id #.
     */
    function getID() {
        return $this->data_array['artifact_id'];
    }

    /**
     *  getStatusID - get open/closed/deleted flag.
     *
     *  @return int     Status: (1) Open, (2) Closed, (3) Deleted.
     */
    function getStatusID() {
        return $this->data_array['status_id'];
    }

    /**
     *  getSubmittedBy - get ID of submitter.
     *
     *  @return int user_id of submitter.
     */
    function getSubmittedBy() {
        return $this->data_array['submitted_by'];
    }

    /**
     *  getOpenDate - get unix time of creation.
     *
     *  @return int unix time.
     */
    function getOpenDate() {
        return $this->data_array['open_date'];
    }

    /**
     *  getCloseDate - get unix time of closure.
     *
     *  @return int unix time.
     */
    function getCloseDate() {
        return $this->data_array['close_date'];
    }

    /**
     *  getSummary - get text summary of artifact.
     *
     *  @return string The summary (subject).
     */
    function getSummary() {
        return $this->data_array['summary'];
    }

    /**
     *  getDetails - get text body (message) of artifact.
     *
     *  @return string  The body (message).
     */
    function getDetails() {
        return $this->data_array['details'];
    }

    /**
     *  getSeverity - get the severity of this artifact
     *
     *  @return int
     */
    function getSeverity() {
        return $this->data_array['severity'];
    }

    /**
     *  Insert an entry into the artifact_history
     *
     *  @param field: the field object
     *  @param old_value: the previous value of the field
     *  @param new_value: the current value of the field	
     *  @param type: extra information used to store the 'comment_type_id' field value (for the follow up comments)
     *  @param email: the email is the user is not logged in
     *
     *  @return int : the artifact_history_id
     */
    function addHistory ($field,$old_value,$new_value,$type=false,$email=false) {
	//MLS: add case where we add CC and file_attachment into history for task #240
	if ($field == 'cc' || $field == 'attachment' || $field == 'submitted_by' || $field == 'comment') {
	   $name = $field;
	} else {
           // If field is not to be kept in bug change history then do nothing
           if (!$field->getGlobalKeepHistory()) { return; }
	   $name = $field->getName();
	}
        
        /*
          handle the insertion of history for these parameters
        */
        if ($email) {
            // We use the email to identify the user
            $user=100;
        } else {
            if ( user_isloggedin() ) {
                $user=user_getid();
            } else {
                $user = 100;
            }
            $email = "";
        }
        
        // If type has a value add it into the sql statement (this is only for
        // the follow up comments (comment field))
        $fld_type = '';
        $val_type = '';
        if ($type) {
            $fld_type = ',type'; $val_type = ",'$type'";
        } else {
            // No comment type specified for a followup comment
            // so force it to None (100)
            if ($name == 'comment') {
                $fld_type = ',type'; $val_type = ",'100'";
            }
        }             
        
        $sql="insert into artifact_history(artifact_id,field_name,old_value,new_value,mod_by,email,date $fld_type) ".
            "VALUES (".$this->getID().",'".$name."','$old_value','$new_value','$user','".$email."','".time()."' $val_type)";
        //echo $sql;
        return db_query($sql);
    }
        
        
    /**
     *  Create a new artifact (and its values) in the db
     *
     * @param array $vfl the value-field-list. Array association pair of field_name => field_value. 
     *              If the function is called by the web-site submission form, the $vfl is set to false, and will be filled by the function extractFieldList function retrieving the HTTP parameters.
     *              If $vfl is not false, the fields expected in this array are *all* the fields of this tracker that are allowed to be submited by the user.
     *  @return boolean
     */
    function create($vfl=false,$import=false,$row=0) {
        global $ath,$art_field_fact,$Language;
        
        $group = $ath->getGroup();
        $group_artifact_id = $ath->getID();
	$error_message = ($import ? $Language->getText('tracker_common_artifact','row',$row) : "");

        // Retrieve HTTP GET variables and store them in $vfl array
        if (!$vfl) {
		$vfl = $art_field_fact->extractFieldList();
        }

        // We check the submitted fields to see if the user has the permissions to submit it
        if (!$import) {
            while ( list($key, $val) = each($vfl)) {
                $field = $art_field_fact->getFieldFromName($key);
                if ($field && (!$field->getName() == 'comment_type_id')) {   // SR #684 we don't check the perms for the field comment type
                    if (! $field->userCanSubmit($group->getID(),$group_artifact_id,user_getid())) {
                        // The user does not have the permissions to update the current field,
                        // we exit the function with an error message
                        $this->setError($Language->getText('tracker_common_artifact','bad_field_permission_submission', $field->getLabel()));
                        return false;
                    }
                    // we check if the given value is authorized for this field (for select box fields only)
                    // we don't check here the none value, we check after it with the function checkEmptyFields, to get a better error message if the field required (instead of value 100 is not a valid valid value for the field)
                    if ($field->isSelectBox() && $val != 100 && ! $field->checkValueInPredefinedValues($this->ArtifactType->getID(), $val)) {
                            $this->setError($Language->getText('tracker_common_artifact','bad_field_value', array($field->getLabel(), $val)));
                            return false;
                    }                    
                    if ($field->isMultiSelectBox()) {
                        foreach ($val as $a_value) {
                            if ($a_value != 100 && ! $field->checkValueInPredefinedValues($this->ArtifactType->getID(), $a_value)) {
                                $this->setError($Language->getText('tracker_common_artifact','bad_field_value', array($field->getLabel(), $val)));
                                return false;
                            }
                        }
                    }
                }
            }
        }
        
	if (!$import) {
	  // make sure  required fields are not empty
	  if ( $art_field_fact->checkEmptyFields($vfl) == false ) {
          $this->setError($art_field_fact->getErrorMessage());
          exit_missing_param();
	  }
	}


        // we don't force them to be logged in to submit a bug
        if (!user_isloggedin()) {
	  $user=100;
        } else {
	  $user=user_getid();
        }
	

	// add default values for fields that have not been shown
	$add_fields = $art_field_fact->getAllFieldsNotShownOnAdd();
	while (list($key,$def_val) = each($add_fields)) {
	  if (!array_key_exists($key,$vfl)) $vfl[$key] = $def_val;
	}

	
	if ($import &&
	    $vfl['submitted_by'] &&
	    $vfl['submitted_by'] != "")
	  $user = $vfl['submitted_by'];

        
	// first make sure this wasn't double-submitted
	$field = $art_field_fact->getFieldFromName('summary');
	if ( $field && $field->isUsed()) {
	  $res=db_query("SELECT * FROM artifact WHERE group_artifact_id = ".$ath->getID()." AND submitted_by=$user AND summary=\"".htmlspecialchars($vfl['summary'])."\"");
	  if ($res && db_numrows($res) > 0) {
	    $this->setError($Language->getText('tracker_common_artifact','double_subm',db_result($res,0,'artifact_id')));
	    return false;           
	  }
	}

                        
        //
        //  Create the insert statement for standard field
        //
        reset($vfl);
        $vfl_cols = '';
        $vfl_values = '';
        while (list($field_name,$value) = each($vfl)) {
                
	    //echo "<br>field_name=$field_name, value=$value";
 
            $field = $art_field_fact->getFieldFromName($field_name);
            if ( $field && $field->isStandardField() ) {
                // skip over special fields  
                if ($field->isSpecial()) {
                    continue; 
                }
                        
                $vfl_cols .= ','.$field->getName();
                $is_text = ($field->isTextField() || $field->isTextArea());
                if  ($is_text) {
                    $value = htmlspecialchars($value);
                } else if ($field->isDateField()) {
                    // if it's a date we must convert the format to unix time
                    list($value,$ok) = util_date_to_unixtime($value);
                }

                $vfl_values .= ',\''.$value.'\'';
                                                    
            }
                        
        } // while


        // Add all special fields that were not handled in the previous block
        $fixed_cols = 'open_date,group_artifact_id,submitted_by';
	if ($import) {
		if (!$vfl['open_date'] || $vfl['open_date'] == "") $open_date = time();
		else list($open_date,$ok) = util_date_to_unixtime($vfl['open_date']);
		$fixed_values = "'".$open_date."','$group_artifact_id','$user'";
	} else {
	        $fixed_values = "'".time()."','$group_artifact_id','$user'";
        }  


        //
        //  Finally, build the full SQL query and insert the artifact itself 
        //
        $sql="INSERT INTO artifact ($fixed_cols $vfl_cols) VALUES ($fixed_values $vfl_values)";
        //echo "<br>DBG - SQL insert artifact: $sql";
        $result=db_query($sql);
        $artifact_id=db_insertid($result);

        
	$was_error = false;
        if (!$artifact_id) {
            $this->setError($error_prefix.$Language->getText('tracker_common_artifact','insert_err',$sql));
            $was_error = true;
        } else {
                        
            //
            //  Insert the field values for no standard field
            //
            $fields = $art_field_fact->getAllUsedFields();
            while (list($field_name,$field) = each($fields)) {
                        
                // skip over special fields  
                if ( ($field->isSpecial())||($field->isStandardField()) ) {
                    continue; 
                }
                                
                if ( array_key_exists($field_name, $vfl) && $vfl[$field_name] ) {
                    // The field has a value from the user input

                    $value = $vfl[$field_name];
                                        
                    $is_text = ($field->isTextField() || $field->isTextArea());
                    if  ($is_text) {
                        $value = htmlspecialchars($value);
                    } else if ($field->isDateField()) {
                        // if it's a date we must convert the format to unix time
                        list($value,$ok) = util_date_to_unixtime($value);
                    }
        
                    // Insert the field value
                    if ( !$field->insertValue($artifact_id,$value) ) {
                        $error_message .= $Language->getText('tracker_common_artifact','field_err',array($field->getLabel(),$value));
                        $was_error = true;
                        $this->setError($error_message);
                    }
                                    
                } else {
                    // The field hasn't a value from the user input
                    // We need to insert default value for this field
                    // because all SQL queries (from Report or Artifact read/update) don't allow 
                    // empty record (we must use join and not left join for performance reasons).
                                        
                    if ( !$field->insertValue($artifact_id,$field->getDefaultValue()) ) {
                        $error_message .= $Language->getText('tracker_common_artifact','def_err',array($field->getLabel(),$field->getDefaultValue()));
                        $was_error = true;
                        $this->setError($error_message);
                    }
                                        
                }
                                
            } // while

        }

        // All ok then reload the artifact dat to make sure it is cached
        // correctly in memory
        $this->fetchData($artifact_id);

        return !$was_error;
    }



    /**
     *  Add a followup comment
     *
     * @param comment: the comment
     * @param email: user email if the user is not logged in
     * @param changes (OUT): array of changes (for notifications)
     *
     *  @return boolean
     */
    function addComment($comment,$email=false,&$changes) {
                        
        global $art_field_fact,$Language;

        // Add a new comment if there is one
        if ($comment != '') {
                
            // For none project members force the comment type to None (100)
            if ( !user_isloggedin() ) {
                if ( $email ) {
                    $this->addHistory('comment',htmlspecialchars($comment), "", 100, $email);
                } else {
                    $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_common_artifact','enter_email'));
                    return false;
                }
            } else {
                $this->addHistory('comment',htmlspecialchars($comment), "", 100);
            }
            $changes['comment']['add'] = stripslashes($comment);
            $changes['comment']['type'] = $Language->getText('global','none');
                        
            $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_common_artifact','add_comment'));               
            return true;
        } else {
            return false;
        }
    }


    /** 
     * handle a simple follow-up comment
     * Followup comments are added in the bug history along with the comment type.
     * 
     * If a canned response is given it overrides anything typed in the followup
     * comment text area
     *
     * @param comment (IN) : the comment that the user typed in
     * @param canned_response (IN) : the id of the canned response
     * @param feedback (OUT) : report if something went wrong or not
     */
    function addFollowUpComment($comment,$comment_type_id,$canned_response,&$changes,&$feedback) {
      global $art_field_fact,$Language;
      if ($canned_response != 100) {
	
	$sql="SELECT * FROM artifact_canned_responses WHERE artifact_canned_id='".$canned_response."'";
	$res3=db_query($sql);
	
	if ($res3 && db_numrows($res3) > 0) {
	  $comment = addslashes(util_unconvert_htmlspecialchars(db_result($res3,0,'body')));
	  $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_common_artifact','canned_used'));
	} else {
	  $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_common_artifact','unable_canned'));
	  $GLOBALS['Response']->addFeedback('error', db_error());
	}
      }
      
      if ($comment != '') {
          $this->addHistory('comment',htmlspecialchars($comment), '', $comment_type_id);
          $changes['comment']['add'] = stripslashes($comment);

	$field = $art_field_fact->getFieldFromName("comment_type_id");
	if ( $field && isset($comment_type_id) && $comment_type_id) {
	  $changes['comment']['type'] =
	    $field->getValue($this->ArtifactType->getID(), $comment_type_id);
	}
      }
    }
        
    /**
     *  Add a list of follow-up comments coming from the import facility
     *
     * @param parsed_comments (IN): an array (#detail => array2), where array2 is of the form
     *                              ("date" => date, "by" => user, "type" => comment-type, "comment" => comment-string)
     *  @return boolean
     */
    function addFollowUpComments($parsed_comments) {
      global $Language;

        while (list(,$arr) = each($parsed_comments)) {        
	    $by = $arr['by'];
	    if ($by == "100") {
	      //this case should not exist in new trackers but
	      //can appear if we parse legacy bugs or tasks
	      $email = $Language->getText('global','none');
	      $user_id = 100;
	    } else if (user_getname($by)) {
		$user_id = $by;
		$email = "";
	    } else {
		$email = $by;
		$user_id = 100;
	    }	

	    $sql="insert into artifact_history(artifact_id,field_name,old_value,new_value,mod_by,email,date,type) ".
            		"VALUES (".$this->getID().",'comment','".$arr['comment']."','','$user_id','$email','".$arr['date']."','".$arr['type']."')";
	    //echo $sql."<br>\n";

            db_query($sql);

	}

	return true;
    }
        
        
    /**
     *  Update an artifact. Rk: vfl is an variable list of fields, Vary from one project to another
     *  return true if artifact updated, false if nothing changed or DB update failed
     *
     * @param artifact_id_dependent: artifact dependencies
     * @param canned_response: canned responses
     * @param changes (OUT): array of changes (for notifications)
     *
     *  @return boolean
     */
    function handleUpdate ($artifact_id_dependent,$canned_response,&$changes,$masschange=false,$vfl=false,$import=false)
        {
            global $art_field_fact,$HTTP_POST_VARS,$Language;

	    if ($masschange && !$this->ArtifactType->userIsAdmin()) exit_permission_denied();
        
	    if (!$import) {
	    	// Retrieve HTTP GET variables and store them in $vfl array
        	$vfl = $art_field_fact->extractFieldList();

	        // make sure  required fields are not empty
        	if ( !$canned_response || 
                	($art_field_fact->checkEmptyFields($vfl) == false) ) {
                	exit_missing_param();
            	}
	    }
        
            //get this artifact from the db
            $result=$this->getFieldsValues();
        
            
                
            //
            //  See which fields changed during the modification
            //  and if we must keep history then do it. Also add them to the update
            //  statement
            //
            $changes = array();
            $upd_list = '';
            reset($vfl);
            while (list($field_name,$value) = each($vfl)) {
                
                $field = $art_field_fact->getFieldFromName($field_name);
                    
                    // skip over special fields  except for details which in this 
                    // particular case can be processed normally
                    if ($field->isSpecial()) {
                        continue; 
                    }
                    
                    // we check if the given value is authorized for this field (for select box fields only)
                    // we don't check here the none value, we have already check it before (we can't check here the none value because the function checkValueInPredefinedValues don't take the none value into account)
                    // if the value did not change, we don't do the check (because of stored values that can be deleted now)
                    if (! $masschange && $result[$field_name] != $value && $field->isSelectBox() && $value != 100 && ! $field->checkValueInPredefinedValues($this->ArtifactType->getID(), $value)) {
                        $this->setError($Language->getText('tracker_common_artifact','bad_field_value', array($field->getLabel(), $value)));
                        return false;
                    }                    
                    if (! $masschange && $field->isMultiSelectBox()) {
                        foreach ($value as $a_value) {
                            if ($a_value != 100 && ! $field->checkValueInPredefinedValues($this->ArtifactType->getID(), $a_value)) {
                                $this->setError($Language->getText('tracker_common_artifact','bad_field_value', array($field->getLabel(), $value)));
                                return false;
                            }
                        }
                    }

                    if ( ($field->isMultiSelectBox())&&(is_array($value)) ) {

		      if ($masschange && (in_array($Language->getText('global','unchanged'),$value))) {
				continue;
			}
                        // The field is a multi values field and it has multi assigned values
                        $values = $value;
                        
                        // check if the user can update the field or not
                        if (! $field->userCanUpdate($this->ArtifactType->getGroupID(), $this->ArtifactType->getID(), user_getid())) {
                            // The user does not have the permissions to update the current field,
                            // we exit the function with an error message
                            $this->setError($Language->getText('tracker_common_artifact','bad_field_permission_update', $field->getLabel()));
                            return false;
                        }

			//don't take into account the none value if there are several values selected
			if (count($values) > 1) {
				$temp = array();
				while (list($i,$v) = each($values)) {
					if ($v == 100) {
						unset($values[$i]);
						$unset = true;
					} else {
						$temp[] = $v;
					}
				}
				if (isset($unset) && $unset) $values = $temp;
			}

                        $old_values = $field->getValues($this->getID());
                        
                        list($deleted_values,$added_values) = util_double_diff_array($old_values,$values);

                        // Check if there are some differences
                        if ((count($deleted_values) > 0) || (count($added_values) > 0)) {

                            // Add values in the history
                            $a = $field->getLabelValues($this->ArtifactType->getID(),$old_values);
                            $val = join(",",$a);
			    $b = $field->getLabelValues($this->ArtifactType->getID(),$values);
			    $new_val = join(",",$b);
                            $this->addHistory($field,$val,$new_val);
                                
                            // Update the field value
                            if ( !$field->updateValues($this->getID(),$values) ) {
                                $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_common_artifact','field_upd_fail',$field->getLabel()));
                            }
                                    
                            // Keep track of the change
                            $field_html = new ArtifactFieldHtml($field);
                            if (count($deleted_values) > 0) {
                                $val = join(",",$field->getLabelValues($this->ArtifactType->getID(),$deleted_values));
                                $changes[$field_name]['del']=$val;
                            }
                            if (count($added_values) > 0) {
                                $val = join(",",$field->getLabelValues($this->ArtifactType->getID(),$added_values));
                                $changes[$field_name]['add']=$val;
                            }
                        }
                                        
                    } else {
	        	if ($masschange && ($value==$Language->getText('global','unchanged'))) {
				continue;
			}	
       
                        $old_value = $result[$field_name];
                        $is_text = ($field->isTextField() || $field->isTextArea());
                        if  ($is_text) {
                            $differ = ($old_value != stripslashes(htmlspecialchars($value))); 
                        } else if ($field->isDateField()) {
                            // if it's a date we must convert the format to unix time
			  if ($value != '') list($value,$ok) = util_date_to_unixtime($value);
			  else $value = '0';

			    //first have a look if both dates are uninitialized
			  if (($old_value == 0 || $old_value == '') && ($value == 0 || !$ok )) {
				$differ = false;
			    } else {
			    	// and make also sure that the old_value has been treated as the new value
			    	// i.e. old_value (unix timestamp) -> local date (with hours cut off, so change the date by x  hours) -> unixtime
				$old_date = format_date("Y-m-j",$old_value);
				list($old_val,$ok) = util_date_to_unixtime($old_date);
                            	$differ = ($old_val != $value);
			    }
                        } else {
                            $differ = ($old_value != $value);
                        }
                        if ($differ) {
                            // The userCanUpdate test is only done on modified fields
                            if ( $field->userCanUpdate($this->ArtifactType->getGroupID(), $this->ArtifactType->getID(), user_getid())) {
                                
                                if ($is_text) {
                                    if ( $field->isStandardField() ) {
                                        $upd_list .= "$field_name='".htmlspecialchars($value)."',";                                                 
                                    } else {
                                        $update_value = htmlspecialchars($value);
                                    }
                                                    
                                    $this->addHistory($field,addslashes($old_value),$value);
                                    $value = stripslashes($value);
                                } else {
                                    if ( $field->isStandardField() ) {
                                        $upd_list .= "$field_name='$value',";
                                    } else {
                                        $update_value = $value;
                                    }
                                    $this->addHistory($field,$old_value,$value);
                                }
                                    
                                // Update the field value
                                if ( !$field->isStandardField() ) {
                                    if ( !$field->updateValue($this->getID(),$update_value) ) {
                                        $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_common_artifact','field_upd_fail',$field->getLabel()));
                                    }
                                }
                                        
                                // Keep track of the change
                                $field_html = new ArtifactFieldHtml($field);
                                $changes[$field_name]['del']=$field_html->display($this->ArtifactType->getID(),$old_value,false,false,true,true);
                                $changes[$field_name]['add']=$field_html->display($this->ArtifactType->getID(),$value,false,false,true,true);
                            } else {
                                // The user does not have the permissions to update the current field,
                                // we exit the function with an error message
                                $this->setError($Language->getText('tracker_common_artifact','bad_field_permission_update', $field->getLabel()));
                                return false;
                            }
                        }
                    }
            } // while

	
	    //for masschange look at the special case of changing the submitted_by param
	    if ($masschange) {
		reset($HTTP_POST_VARS);
		while ( list($key, $val) = each($HTTP_POST_VARS)) {
			if ($key == 'submitted_by' && $val != $Language->getText('global','unchanged')) {
				$sql = "UPDATE artifact SET submitted_by=$val WHERE artifact_id = ".$this->getID();
				$res = db_query($sql);
				$field = $art_field_fact->getFieldFromName('submitted_by');
				if ($this->getSubmittedBy() != $val)
					$this->addHistory('submitted_by',$this->getSubmittedBy(),$val);
			}
		}
	    }

            // Comment field history is handled a little differently. Followup comments
            // are added in the bug history along with the comment type.
            // 
            // If a canned response is given it overrides anything typed in the followup
            // comment text area. 
            $comment = array_key_exists('comment', $HTTP_POST_VARS)?$HTTP_POST_VARS['comment']:'';
            $comment_type_id = array_key_exists('comment_type_id', $vfl)?$vfl['comment_type_id']:'';

	    $this->addFollowUpComment($comment,$comment_type_id,$canned_response,$changes,$feedback);
            
            
            //
            //  Enter the timestamp if we are changing to closed or declined
            //
            if (isset($changes['status_id']) && $this->isStatusClosed($vfl['status_id'])) {
                $now=time();
                $upd_list .= "close_date='$now',";
                $field = $art_field_fact->getFieldFromName('close_date');
                if ( $field ) {
                    $this->addHistory ($field,$result['close_date'],'');
                }
            }
        
            //
            //  Insert the list of dependencies 
            //
        
	    if ($import && $artifact_id_dependent) {
		if (!$this->deleteAllDependencies()) return false;
		if ($artifact_id_dependent == $Language->getText('global','none')) unset($artifact_id_dependent);
	    }
            if (!$this->addDependencies($artifact_id_dependent,$changes,$masschange)) {
                return false;
            }
                
            //
            //  Finally, build the full SQL query and update the artifact itself (if need be)
            //
        
            $res_upd = true;
            if ($upd_list) {
                // strip the excess comma at the end of the update field list
                $upd_list = substr($upd_list,0,-1);
                
                $sql="UPDATE artifact SET $upd_list ".
                    " WHERE artifact_id=".$this->getID();
                
                $res_upd=db_query($sql);
            }
        
            if (!$res_upd) {
                exit_error($Language->getText('tracker_common_artifact','upd_fail').': '.$sql,$Language->getText('tracker_common_artifact','upd_fail'));
                return false;
            } else {
                if (!$masschange) $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_common_artifact','upd_success'));
                return true;
            }

        }



    /**
     * Check if an email address already exists
     *
     * @param cc: the email address
     *
     * @return boolean
     */
    function existCC($cc) {
        $sql = "SELECT artifact_cc_id FROM artifact_cc WHERE artifact_id=".$this->getID()." AND email='$cc'";
        $res = db_query($sql);
        return (db_numrows($res) >= 1);
    }

    /**
     * Insert an email address for the CC list
     *
     * @param cc: the email address
     * @param added_by: user who insert this cc list
     * @param comment: comment for this cc list
     * @param date: date of creation
     *
     * @return boolean
     */
    function insertCC($cc,$added_by,$comment,$date) {
        $sql = "INSERT INTO artifact_cc (artifact_id,email,added_by,comment,date) ".
            "VALUES (".$this->getID().",'$cc','$added_by','$comment','$date')";
        $res = db_query($sql);
        return ($res);
        
    }

    /**
     * Insert email addresses for CC list
     *
     * @param email: list of email addresses
     * @param comment: comment for these addresses
     * @param changes (OUT): list of changes
     * @param masschange: if in a masschange, we do not wan't to get feedback when everything ok
     *
     * @return boolean
     */
    function addCC($email,$comment,&$changes,$masschange=false) {
        global $Language;
        
        $user_id = (user_isloggedin() ? user_getid(): 100);
        
        $arr_email = util_split_emails($email);
        $date = time();
        $ok = true;
        $changed = false;
        
        if (! util_validateCCList($arr_email, $message)) {
            exit_error($Language->getText('tracker_index','cc_list_invalid'), $message);
        }
	
	//calculate old_values to put into artifact_history
	$old_value=$this->getCCEmails();
    reset($arr_email);
        while (list(,$cc) = each($arr_email)) {
            // Add this cc only if not there already
            if (!$this->existCC($cc)) {
                $changed = true;
                $res = $this->insertCC($cc,$user_id,$comment,$date);
                if (!$res) { $ok = false; } 
            }
        }
	
	if ($old_value == '') {
            $new_value = join(',', $arr_email);
	} else {
	    $new_value = $old_value .",".join(',', $arr_email);
	}

        if (!$ok) {
            $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_common_artifact','cc_add_fail'));
        } else {
            if (!$masschange) $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_common_artifact','cc_added'));
	    $this->addHistory('cc',$old_value,$new_value);
            $changes['CC']['add'] = join(',', $arr_email);
        }
        return $ok;
    }

    /**
     * Delete old cc list and add new email instead
     *
     * @param email: list of email addresses
     * @param comment: comment for these addresses
     *
     * @return boolean
     */
    function updateCC($email,$comment) {
        global $Language;
        
        $user_id = (user_isloggedin() ? user_getid(): 100);
        
        $arr_email = util_split_emails($email);
        $date = time();
        $ok = true;
        $changed = false;
        
        if (! util_validateCCList($arr_email, $message)) {
            exit_error($Language->getText('tracker_index','cc_list_invalid'), $message);
        }
	
	//calculate old_values to put into artifact_history
	$old_value=$this->getCCEmails();
        $new_value = join(',', $arr_email);

	//look if there is really something to do or not
	list($deleted_values,$added_values) = util_double_diff_array(explode(",",$old_value),$arr_email);
	if (count($deleted_values) == 0 && count($added_values) == 0) return true;

	if (!$this->deleteAllCC()) {
		$GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_common_artifact','prob_cc_list',$this->getID()));
		$ok = false;
	}

	reset($arr_email);
	while (list(,$cc) = each($arr_email)) {
                $changed = true;
                $res = $this->insertCC($cc,$user_id,$comment,$date);
                if (!$res) { $ok = false; } 
        }

        if (!$ok) {
            $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_common_artifact','cc_add_fail'));
        } else {
	    $this->addHistory('cc',$old_value,$new_value);
        }
        return $ok;
    }



    /**
     * Delete an email address in the CC list
     *
     * @param artifact_cc_id: cc list id
     * @param changes (OUT): list of changes
     *
     * @return boolean
     */
    function deleteCC($artifact_cc_id=false,&$changes,$masschange=false) {
        global $Language;
        
        // If both bug_id and bug_cc_id are given make sure the cc belongs 
        // to this bug (it's a bit paranoid but...)
        $sql = "SELECT artifact_id,email from artifact_cc WHERE artifact_cc_id='$artifact_cc_id'";
        $res1 = db_query($sql);
        if ((db_numrows($res1) <= 0) || (db_result($res1,0,'artifact_id') != $this->getID()) ) {
            $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_common_artifact','err_cc_id',$artifact_cc_id));
            return false;
        }
        	
	//calculate old_values to put into artifact_history
	$old_value=$this->getCCEmails();
        
	// Now delete the CC address
        $res2 = db_query("DELETE FROM artifact_cc WHERE artifact_cc_id='$artifact_cc_id'");
        if (!$res2) {
            $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_common_artifact','err_del_cc',array($artifact_cc_id,db_error($res2))));
            return false;
        } else {
            if (!$masschange) $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_common_artifact','cc_remove'));
	    $new_value=$this->getCCEmails();
	    $this->addHistory('cc',$old_value,$new_value);
            $changes['CC']['del'] = db_result($res1,0,'email');
            return true;
        }
    }

    /**
     * Check if an artifact depends already from the current one
     *
     * @param id: the artifact id
     *
     * @return boolean
     */
    function existDependency($id) {
        $sql = "SELECT is_dependent_on_artifact_id FROM artifact_dependencies WHERE artifact_id=".$this->getID()." AND is_dependent_on_artifact_id=$id";
        //echo $sql;
        $res = db_query($sql);
        return (db_numrows($res) >= 1);
    }
        
    /**
     * Check if an artifact exists
     *
     * @param id: the artifact id
     *
     * @return boolean
     */
    function validArtifact($id) {
        $sql = "SELECT * FROM artifact a, artifact_group_list agl WHERE ".
            "a.group_artifact_id = agl.group_artifact_id AND a.artifact_id=".$id." AND ".
            "agl.status = 'A'";
        $res = db_query($sql);
        if ( db_numrows($res) >= 1 )
            return true;
        else
            return false;
    }
        

    /**
     * Insert a artifact dependency with the current one
     *
     * @param id: the artifact id
     *
     * @return boolean
     */
    function insertDependency($id) {
        $sql = "INSERT INTO artifact_dependencies (artifact_id,is_dependent_on_artifact_id) ".
            "VALUES (".$this->getID().",$id)";
        //echo $sql;
        $res = db_query($sql);
        return ($res);
        
    }


    /**
     * Delete all the CC Names of this Artifact
     */
    function deleteAllCC() {
	$sql = "SELECT artifact_cc_id FROM artifact_cc WHERE artifact_id=".$this->getID();
	$res = db_query($sql);
	if (db_numrows($res) > 0) {
		for ($i=0;$i<db_numrows($res);$i++) {
			if ($i==0) $ccNames = db_result($res,$i,'artifact_cc_id');
			else $ccNames .= ",".db_result($res,$i,'artifact_cc_id');
		}
		$sql = "DELETE FROM artifact_cc WHERE artifact_cc_id IN ($ccNames) AND artifact_id=".$this->getID();
		$res_del = db_query($sql);
		if (!$res_del) return false; 
	}
	return true;
    }

     /**
      * Delete all the dependencies of this Artifact
      */
     function deleteAllDependencies() {
	$sql = "SELECT is_dependent_on_artifact_id FROM artifact_dependencies WHERE artifact_id=".$this->getID();
	$res = db_query($sql);
	if (db_numrows($res) > 0) {
		for ($i=0;$i<db_numrows($res);$i++) {
			if ($i==0) $dependencies = db_result($res,$i,'is_dependent_on_artifact_id');
			else $dependencies .= ",".db_result($res,$i,'is_dependent_on_artifact_id');
		}
		$sql = "DELETE FROM artifact_dependencies WHERE is_dependent_on_artifact_id IN ($dependencies) AND artifact_id=".$this->getID();
		$res_del = db_query($sql);
		if (!$res_del) return false; 
	}
	return true;
     }


    /**
     * Insert artifact dependencies
     *
     * @param artifact_id_dependent: list of artifact which are depend on (comma sperator)
     * @param changes (OUT): list of changes
     *
     * @return boolean
     */
    function addDependencies($artifact_id_dependent,&$changes,$masschange) {
        global $Language;
        
        if ( !$artifact_id_dependent ) 
            return true;
                        
        $ok = true;
        $ids = explode(",",$artifact_id_dependent);
                
        while (list(,$id) = each($ids)) {
            // Add this id only if not already exist
            //echo "add id=".$id."<br>";
            // Check existance
            if (!$this->validArtifact($id)) {
                $ok = false;
                $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_common_artifact','invalid_art',$id));
            }
            if ($ok && ($id != $this->getID()) && !$this->existDependency($id)) {
                $res = $this->insertDependency($id);
                if (!$res) { $ok = false; }
            }
        }
        
        if (!$ok) {
            $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_common_artifact','depend_add_fail',$this->getID()));
        } else {
            if (!$masschange) $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_common_artifact','depend_add'));
            $changes['Dependencies']['add'] = $artifact_id_dependent;
        }
        return $ok;
    }

    /**
     * Delete an artifact id from the dependencies list
     *
     * @param dependent_on_artifact_id: artifact id which is depend on
     * @param changes (OUT): list of changes
     *
     * @return boolean
     */
    function deleteDependency($dependent_on_artifact_id,&$changes) {
        global $Language;
        
        // Delete the dependency
        $sql = "DELETE FROM artifact_dependencies WHERE is_dependent_on_artifact_id=$dependent_on_artifact_id AND artifact_id=".$this->getID();
        $res2 = db_query($sql);
        if (!$res2) {
            $GLOBALS['Response']->addFeedback('error', " - Error deleting dependency $dependent_on_artifact_id: ".db_error($res2));
            return false;
        } else {
            $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_common_artifact','depend_removed'));
            $changes['Dependencies']['del'] = $dependent_on_artifact_id;
            return true;
        }
    }

    /**
     * Return if the status is closed status
     *
     * @param status: the status
     *
     * @return boolean
     */
    function isStatusClosed($status) {
        return (($status == '3') || ($status == '10') );
    }


    /**
     * get all the field values for this artifact
     *
     * @return array
     */
    function getFieldsValues() {

        // get the artifact data
        $this->fetchData($this->getID());
        return $this->data_array;
    }

        
    /**
     * Return the users that have posted follow ups 
     *
     * @return array
     */
    function getCommenters() {
        $sql="SELECT DISTINCT mod_by FROM artifact_history ".
	  "WHERE artifact_id=".$this->getID()." ".
            "AND field_name = 'comment' AND mod_by != 100";
        return db_query($sql);
    }

    /**
     * Return the mails of anonymous users that have posted follow ups 
     *
     * @return array
     */
    function getAnonymousCommenters() {
        $sql="SELECT DISTINCT email FROM artifact_history ".
	  "WHERE artifact_id=".$this->getID()." ".
            "AND field_name = 'comment' ".
	  "AND mod_by = 100";
        return db_query($sql);
    }

    /**
     * Return the follow ups 
     *
     * @return array
     */
    function getFollowups () {
        global $art_field_fact;

        $field = $art_field_fact->getFieldFromName('comment_type_id');
        if ( $field ) {
            // Look for project specific values first
	  $sql="SELECT DISTINCT artifact_history.artifact_history_id,artifact_history.artifact_id,artifact_history.field_name,artifact_history.old_value,artifact_history.date,user.user_name,artifact_history.mod_by,artifact_history.email,artifact_history.type AS comment_type_id,artifact_field_value_list.value AS comment_type ".
	      "FROM artifact_history,artifact_field_value_list,artifact_field,user ".
	      "WHERE artifact_history.artifact_id=".$this->getID()." ".
	      "AND artifact_history.field_name = 'comment' ".
	      "AND artifact_history.mod_by=user.user_id ".
	      "AND artifact_history.type = artifact_field_value_list.value_id ".
	      "AND artifact_field_value_list.field_id = artifact_field.field_id ".
	      "AND artifact_field_value_list.group_artifact_id = artifact_field.group_artifact_id ".
	      "AND artifact_field.group_artifact_id =".$this->ArtifactType->getID()." ".
	      "AND artifact_field.field_name = 'comment_type_id' ".
	      "ORDER BY artifact_history.date DESC";
            //echo $sql;
            $res_value = db_query($sql);
            $rows=db_numrows($res_value);
                        
            //echo "sql=".$sql." - rows=".$rows."<br>";
        } else {
            // Look for project specific values first
            $sql="SELECT DISTINCT artifact_history.artifact_history_id,artifact_history.artifact_id,artifact_history.field_name,artifact_history.old_value,artifact_history.date,user.user_name,artifact_history.mod_by,artifact_history.email,artifact_history.type AS comment_type_id,null AS comment_type ".
                "FROM artifact_history,user ".
                "WHERE artifact_history.artifact_id=".$this->getID()." ".
                "AND artifact_history.field_name = 'comment' ".
                "AND artifact_history.mod_by=user.user_id ".
                "ORDER BY artifact_history.date DESC";
            //echo $sql;
            $res_value = db_query($sql);
            $rows=db_numrows($res_value);
                        
        }
        return($res_value);

    }
        
    /**
     * Return the history events for this artifact (excluded comment events - See followups)
     *
     * @return array
     */
    function getHistory () {
        $sql="select artifact_history.field_name,artifact_history.old_value,artifact_history.new_value,artifact_history.date,artifact_history.type,user.user_name ".
            "FROM artifact_history,user ".
            "WHERE artifact_history.mod_by=user.user_id ".
            "AND artifact_history.field_name <> 'comment' ".
            "AND artifact_id=".$this->getID()." ORDER BY artifact_history.date DESC";
        return db_query($sql);
    }

    /**
     * Return the CC list values
     *
     * @return array
     */
    function getCCList() {
                
        $sql="SELECT artifact_cc_id,artifact_cc.email,artifact_cc.added_by,artifact_cc.comment,artifact_cc.date,user.user_name ".
            "FROM artifact_cc,user ".
            "WHERE added_by=user.user_id ".
            "AND artifact_id=".$this->getID()." ORDER BY date DESC";
        return db_query($sql);
    }

    /**
     * Return the user ids of registered users in the CC list
     *
     * @return array
     */
    function getCCIdList() {
                
        $sql="SELECT u.user_id ".
	  "FROM artifact_cc cc, user u ".
	  "WHERE cc.email = u.user_name ".
	  "AND cc.artifact_id=".$this->getID();
	$res = db_query($sql);
	
        return util_result_column_to_array($res);
    }

    /**
     * Return the CC list emails only
     *
     * @return string
     */
    function getCCEmails() {
                
        $sql="SELECT email ".
            "FROM artifact_cc ".
            "WHERE artifact_id=".$this->getID()." ORDER BY date DESC";
        $result = db_query($sql);
	$rows=db_numrows($result);
        if ($rows <= 0) {
	    return '';
	} else {
	    $email_arr=array();
	    for ($i=0; $i < $rows; $i++) {
	        $email_arr[] = db_result($result, $i, 'email');
	    }
	    $old_value = join(",",$email_arr);
	    return $old_value;
	}
    }

    /**
     * Return a CC list values
     *
     * @param artifact_cc_id: the artifact cc id
     *
     * @return array
     */
    function getCC($artifact_cc_id) {
                
        $sql="SELECT artifact_cc_id,artifact_cc.email,artifact_cc.added_by,artifact_cc.comment,artifact_cc.date,user.user_name ".
            "FROM artifact_cc,user ".
            "WHERE artifact_cc_id=".$artifact_cc_id." ".
            "AND added_by=user.user_id";
        $res = db_query($sql);
        return db_fetch_array($res);
    }

    /**
     * Return the artifact dependencies values
     *
     * @return array
     */
    function getDependencies() {
                
    	$sql="SELECT d.artifact_depend_id, d.is_dependent_on_artifact_id, d.artifact_id, a.summary, ag.group_artifact_id, ag.name, g.group_id, g.group_name ".
            "FROM artifact_dependencies d, artifact_group_list ag, groups g, artifact a ".
            "WHERE d.is_dependent_on_artifact_id = a.artifact_id AND ".
            "a.group_artifact_id = ag.group_artifact_id AND ".
            "d.artifact_id = ".$this->getID()." AND ".
            "ag.group_id = g.group_id ORDER BY a.artifact_id";
        //echo "sql=$sql<br>";
        return db_query($sql);
    }

    /**
     * Return the artifact inverse dependencies values
     *
     * @return array
     */
    function getInverseDependencies() {
                
        $sql="SELECT d.artifact_id, a.summary, ag.group_artifact_id, ag.name, g.group_id, g.group_name ".
            "FROM artifact_dependencies d, artifact_group_list ag, groups g, artifact a ".
            "WHERE d.artifact_id = a.artifact_id AND ".
            "a.group_artifact_id = ag.group_artifact_id AND ".
            "d.is_dependent_on_artifact_id = ".$this->getID()." AND ".
            "ag.group_id = g.group_id ORDER BY a.artifact_id";
        //echo "sql=$sql<br>";
        return db_query($sql);
    }

    /**
     * Return the names of attached files
     *
     * @return string
     */
    function getAttachedFileNames () {
        $sql="SELECT filename ".
            "FROM artifact_file ".
            "WHERE artifact_id=".$this->getID()." ORDER BY adddate DESC";
        $result = db_query($sql);
	$rows=db_numrows($result);
        if ($rows <= 0) {
	    return '';
	} else {
	    $name_arr=array();
	    for ($i=0; $i < $rows; $i++) {
	        $name_arr[] = db_result($result, $i, 'filename');
	    }
	    $old_value = join(',',$name_arr);
	    return $old_value;
	}
    }

    /**
     * Return the attached files
     *
     * @return array
     */
    function getAttachedFiles () {
        $sql="SELECT id,artifact_id,filename,filesize,filetype,description,bin_data,adddate,user.user_name ".
            "FROM artifact_file,user ".
            "WHERE submitted_by=user.user_id ".
            "AND artifact_id=".$this->getID()." ORDER BY adddate DESC";
        //echo "sql=$sql<br>";
        return db_query($sql);
    }

    /**
     * Return a attached file
     *
     * @param id: the file id
     *
     * @return array
     */
    function getAttachedFile ($id) {
        $sql="SELECT id,filename,filesize,description,adddate,user.user_name ".
            "FROM artifact_file,user ".
            "WHERE submitted_by=user.user_id ".
            "AND id=".$id;
        //echo "sql=$sql<br>";
        $res = db_query($sql);
        return db_fetch_array($res);
    }

    function checkAssignees ($field_name,$result,$art_field_fact,$changes,&$user_ids) {

        // check assignee  notification preferences
        // Never notify user 'none' (id #100)
        // Check for field 'assigned_to' (SelectBox)
	// assigned to can also be a multi_select_box
	$field = $art_field_fact->getFieldFromName($field_name);
        if ( $field ) {
		if ($field->getDisplayType() == "MB") {
            		$field_value = $field->getValues($this->getID());
			if ($field_value && (count($field_value) > 0) ) {
				$val_func = $field->getValueFunction();
				if ( $val_func[0] != "") {
                			while (list (,$user_id)=each ($field_value)) {
                    				if ( ($user_id) && ($user_id != 100) ) {
                    				    $curr_assignee = new User($user_id);	
						            if ((!array_key_exists($user_id, $user_ids) || !$user_ids[$user_id]) && 
						                 $this->ArtifactType->checkNotification($user_id, 'ASSIGNEE', $changes) && 
						                 $this->userCanView($user_id) && 
                                         $curr_assignee->isActive() || $curr_assignee->isRestricted()
                                         ) {
						      //echo "DBG - ASSIGNEE - user=$user_id<br>";
						      $user_ids[$user_id] = true;
                        				}
						}
                    			}
                		} else {
					// we handle now also the case that the assigned_to field is NOT BOUND to a predefined value list
					// we accept only names that correspond to codex user names
					while (list (,$value_id)=each ($field_value)) {
						$user_name = $field->getValue($this->ArtifactType->getID(),$value_id);
						$res_u = user_get_result_set_from_unix($user_name);
						$user_id = db_result($res_u,0,'user_id');
                    				if ( ($user_id) && ($user_id != 100) ) {
                    				    $curr_assignee = new User($user_id);	
                        				if (!$user_ids[$user_id] && 
							    $this->ArtifactType->checkNotification($user_id, 'ASSIGNEE', $changes) &&
							    $this->userCanView($user_id) &&
                                $curr_assignee->isActive() || $curr_assignee->isRestricted()
                                ) {
							    //echo "DBG - ASSIGNEE - user=$user_id<br>";
							    $user_ids[$user_id] = true;
                        				}
                    				}
					}	
				}
            		}
		} else {
			// display type is SB
        		$user_id = $result[$field_name];
			$val_func = $field->getValueFunction();
			if ($val_func[0] == "") {
				// we handle now also the case that the assigned_to field is NOT BOUND to a predefined value list
				// we accept only names that correspond to codex user names
				// so: this user_id is not a user_id but a value_id
				$user_name = $field->getValue($this->ArtifactType->getID(),$user_id);
				$res = user_get_result_set_from_unix($user_name);
				$user_id = db_result($res,0,'user_id');
			}
        		if ( ($user_id) && ($user_id != 100) ) {
        		        $curr_assignee = new User($user_id);
            			if ((!array_key_exists($user_id, $user_ids) || !$user_ids[$user_id]) && 
				    $this->ArtifactType->checkNotification($user_id, 'ASSIGNEE', $changes) &&
				    $this->userCanView($user_id) && 
                    $curr_assignee->isActive() || $curr_assignee->isRestricted()) {
				    //echo "DBG - ASSIGNEE - user=$user_id<br>";
				    $user_ids[$user_id] = true;
            			}
        		}

		}
	}
        

        // check old assignee  notification preferences if assignee was just changed
        // Never notify user 'none' (id #100)
        if (isset($changes[$field_name]) && isset($changes[$field_name]['del'])) {
            $user_name = $changes[$field_name]['del'];
        } else {
            unset($user_name);
        }
        if (isset($user_name) && $user_name) {
		//echo " verify deleted assigned_to - user_name=$user_name ";
	    $del_arr = explode(",",$user_name);
	    while (list (,$uname)=each ($del_arr)) {
		//echo " uname=$uname ";
            	$res_oa = user_get_result_set_from_unix($uname);
            	$user_id = db_result($res_oa,0,'user_id');
            $curr_assignee = new User($user_id);
            	if ($user_id != 100 && 
		    !isset($user_ids[$user_id]) && 
		    $this->ArtifactType->checkNotification($user_id, 'ASSIGNEE', $changes) &&
		    $this->userCanView($user_id) &&
            $curr_assignee->isActive() || $curr_assignee->isRestricted()) {
                	//echo "DBG - ASSIGNEE OLD - user=$user_id<br>";
                	$user_ids[$user_id] = true;
            	}
            }
	}
    }


    
    /**
     *	  userCanView - determine if the user can view this artifact.
     *
     *	  @param $my_user_id	if not specified, use the current user id..
     *	  @return boolean	user_can_view.
     */
    function userCanView($my_user_id=0) {

        if (!$my_user_id) {
            // Super-user has all rights...
            if (user_is_super_user()) return true;
            $my_user_id=user_getid();
        } else {
            $u = new User($my_user_id);
            if ($u->isSuperUser()) return true;
        }

        // Full access
        $res=permission_db_authorized_ugroups('TRACKER_ACCESS_FULL',$this->ArtifactType->getID());
        if (db_numrows($res) > 0) {
            while ($row = db_fetch_array($res)) {
                if (ugroup_user_is_member($my_user_id, $row['ugroup_id'], $this->ArtifactType->Group->getID(), $this->ArtifactType->getID())) {
                    return true;
                }
            }
        }

        // 'submitter' access
        $res=permission_db_authorized_ugroups('TRACKER_ACCESS_SUBMITTER',$this->ArtifactType->getID());
        if (db_numrows($res) > 0) {
            while ($row = db_fetch_array($res)) {
                if (ugroup_user_is_member($my_user_id, $row['ugroup_id'], $this->ArtifactType->Group->getID(), $this->ArtifactType->getID())) {
                    // check that submitter is also a member
                    if (ugroup_user_is_member($this->getSubmittedBy(), $row['ugroup_id'], $this->ArtifactType->Group->getID(), $this->ArtifactType->getID())) {
                        return true;
                    }
                }
            }
        }
        // 'assignee' access
        $res=permission_db_authorized_ugroups('TRACKER_ACCESS_ASSIGNEE',$this->ArtifactType->getID());
        if (db_numrows($res) > 0) {
            while ($row = db_fetch_array($res)) {
                if (ugroup_user_is_member($my_user_id, $row['ugroup_id'], $this->ArtifactType->Group->getID(), $this->ArtifactType->getID())) {
                    // check that one of the assignees is also a member
                    if (ugroup_user_is_member($this->getValue('assigned_to'), $row['ugroup_id'], $this->ArtifactType->Group->getID(), $this->ArtifactType->getID())) {
                        return true;
                    }

                    // multi-assigned to
                    $multi_assigned=$this->getMultiAssignedTo();
                    if (is_array($multi_assigned)) {
                        foreach ($multi_assigned as $assigned) {
                            if (ugroup_user_is_member($assigned, $row['ugroup_id'], $this->ArtifactType->Group->getID(), $this->ArtifactType->getID())) {
                                return true;
                            }
                        }
                    }
                }
            }
        }
        return false;
    } 
    
    /**
     *	getExtraFieldData - get an array of data for the extra fields associated with this artifact
     *
     *      the array returned looks like 
     *          array(
     *             [field_id] => fieldvalues
     *             [field_id] => fieldvalues
     *          )
     *      for multi select boxes, the values are separated by a comma
     *
     *	@return	array	array of data
     */
    function &getExtraFieldData() {
    	global $art_field_fact;
    	$extrafielddata = array();

    	// now get the values for generic fields if any
        $sql = "SELECT * FROM artifact_field_value WHERE artifact_id='".$this->getID()."'";
        $res=db_query($sql);
        if (!$res || db_numrows($res) < 1) {
            // if no result then it is possible that there isn't any generic fields
            return;
        }
        // Walk the database result (possible to get several values with a same field_id (multi select box))
        while ($row = db_fetch_array($res)) {
            $data_fields[$row['field_id']][] = $row;
        }
        
        // compute the extrafielddata array by walking the data_fields array
        $extrafielddata = array();
        foreach ($data_fields as $field_id => $data_field) {
            $current_field = $art_field_fact->getFieldFromId($field_id);
            if (isset($current_field) && $current_field && !$current_field->isError()) {
                // $values contains the values of the field
                $values = array();
                foreach ($data_field as $data_value) {
                    $values[] = $data_value[$current_field->getValueFieldName()];
                }
                // if there is more than one value, we separate them with a comma.
                // if not, implode will return the single value.
                $extrafielddata[$field_id] = implode(",", $values);
            }
        }
        return $extrafielddata;
    }


    /**
     * Build an array of user_ids using the changes array
     *
     * @param changes (IN): array of changes
     * 
     * @param concerned_ids (OUT): user_ids of concerned users (attention user_ids are stored as keys)
     * @param concerned_addresses (OUT): email addresses of anonymous users (for instance in CC addresses)
     *
     */
    function buildNotificationArrays($changes,&$concerned_ids,&$concerned_addresses) {
                
        global $art_field_fact,$Language;
        
        // Rk: we store user ids in a hash to make sure they are only
        // stored once. Normally if an email is repeated several times sendmail
        // would take care of it but I prefer taking care of it now.
	// We also use the user_ids hash to check if a user has already been selected for 
        // notification. If so it is not necessary to check it again in another role.
        $concerned_ids = array();
	$concerned_addresses = array();
	$concerned_watchers = array();

        
        
        // check submitter notification preferences
        $user_id = $this->getSubmittedBy();
        $submitter = new User($user_id);
        if ($user_id != 100 && ($submitter->isActive() || $submitter->isRestricted())) {
	  if ($this->ArtifactType->checkNotification($user_id, 'SUBMITTER', $changes) && $this->userCanView($user_id)) {
	        //echo "DBG - SUBMITTER - user=$user_id<br>";
                $concerned_ids[$user_id] = true;
            }
        }
        
	// Retrieve field values for the assigned_to, multi_assigned_to value
        $result = $this->getFieldsValues();
	$this->checkAssignees("assigned_to",$result,$art_field_fact,$changes,$concerned_ids);
	$this->checkAssignees("multi_assigned_to",$result,$art_field_fact,$changes,$concerned_ids);
	

	// check all CC 
        // (a) check all the people in the current CC list
        // (b) check the CC that has just been removed if any and see if she
        // wants to be notified as well
        // if the CC indentifier is an email address then notify in any case
        // because this user has no personal setting
        $res_cc = $this->getCCList();
        $arr_cc = array();
        if ($res_cc && (db_numrows($res_cc) > 0)) {
            while ($row = db_fetch_array($res_cc)) {
                $arr_cc[] = $row['email'];
            }
        }
        if (isset($changes['CC']) && isset($changes['CC']['del']) && $changes['CC']['del'] ) {
            // Only one CC can be deleted at once so just append it to the list....
            $arr_cc[] = $changes['CC']['del'];
        }
                        
        while (list(,$cc) = each($arr_cc)) {
            //echo "DBG - CC=$cc<br>";
            if (validate_email($cc)) {
	        //echo "DBG - CC email - email=".util_normalize_email($cc)."<br>";
                $concerned_addresses[util_normalize_email($cc)] = true;
            } else {
                $res = user_get_result_set_from_unix($cc);
                $user_id = db_result($res,0,'user_id');
                if (!isset($concerned_ids[$user_id]) && $this->ArtifactType->checkNotification($user_id, 'CC', $changes)) {
		    //echo "DBG - CC - user=$user_id<br>";
                    $concerned_ids[$user_id] = true;
                }
            }
        } // while
        
        
        // check all commenters
        $res_com = $this->getCommenters();
        if (db_numrows($res_com) > 0) {
            while ($row = db_fetch_array($res_com)) {
                $user_id = $row['mod_by'];
                if (!isset($concerned_ids[$user_id]) && $this->ArtifactType->checkNotification($user_id, 'COMMENTER', $changes)) {
		    //echo "DBG - COMMENTERS - user=$user_id<br>";
                    $concerned_ids[$user_id] = true;
                }
            }
        }
        // check all anonymous commenters
        $res_com = $this->getAnonymousCommenters();
        if (db_numrows($res_com) > 0) {
            while ($row = db_fetch_array($res_com)) {
                $user_mail = $row['email'];
		//echo "DBG - anon COMMENTERS - user=$user_mail<br>";
		$concerned_addresses[$user_mail] = true;
            }
        }

	//check all watchers
	foreach (array_keys($concerned_ids) as $watchee) {
	  $db_res = $this->ArtifactType->getWatchers($watchee);
	  while ($row_watcher = db_fetch_array($db_res)) {
	    $watcher = $row_watcher['user_id'];
        $concerned_watchers[$watcher] = true;
	  }
	}

	foreach (array_keys($concerned_watchers) as $watcher) {
	  if (!$concerned_ids[$watcher]) $concerned_ids[$watcher] = true;
	}
    }



    /** group users to be notified of artifact changes
     * groups are done with respect to ugroups and 
     * their permissions on the artifact
     * @param user_id an array of user ids
     * return $user_sets array of arrays of user ids: 
     * return $ugroup_sets array of arrays of ugroup_ids.
     * the $user_sets keys correspond to the $ugroup_sets keys i.e.
     * $ugroup_sets[x] are the ugroups that the users in $user_sets[x]
     * belong to
     */
    function groupNotificationList($user_ids,&$user_sets,&$ugroup_sets) {
      
      $group_id = $this->ArtifactType->getGroupID();
      $group_artifact_id = $this->ArtifactType->getID();

      $user_sets = array();
      $ugroup_sets = array();
      
      //go through user_ids array:
      //for each user have a look at which ugroups he belongs
      

      foreach ($user_ids as $user_id) {
	$specific_ugroups = ugroup_db_list_tracker_ugroups_for_user($group_id,$group_artifact_id,$user_id);
	//echo "<br>specific_ugroups for $user_id = "; print_r($specific_ugroups);
	$dynamic_ugroups = ugroup_db_list_dynamic_ugroups_for_user($group_id,$group_artifact_id,$user_id);
	//echo "<br>dynamic_ugroups for $user_id = "; print_r($dynamic_ugroups);
	$all_ugroups = array_merge($dynamic_ugroups, $specific_ugroups);
	//echo "<br>all_ugroups for $user_id = "; print_r($all_ugroups);

	$found_gr = false;
	while (list($x,$ug) = each($ugroup_sets)) {
	  $diff1 = array_diff($ug,$all_ugroups);
	  $diff2 = array_diff($all_ugroups,$ug);
	  if ( empty($diff1) && empty($diff2) ) {
	    // we found the magic users that are part of exactly the same ugroups as this user
	    $gr = $user_sets[$x];
	    $gr[] = $user_id;
	    unset($user_sets[$x]);
	    $user_sets[$x] = $gr;
	    $found_gr = true;
	    break;
	  }
	}
	// if we didn't find users who have exactly the same permissions we have to add this user separately 
	if (!$found_gr) {
	  $user_sets[] = array($user_id);
	  $ugroup_sets[] = $all_ugroups;
	}
      }
      
    }
    
    
        /**
    * Send different messages to persons affected by this artifact with respect 
	* to their different permissions 
    *
    * @param more_addresses: additional addresses
    * @param changes: array of changes
    *
    * @return void
    */
    function mailFollowupWithPermissions($more_addresses=false,$changes=false) {
        global $sys_datefmt,$art_field_fact,$sys_lf,$Language;
        
        $group = $this->ArtifactType->getGroup();
        $group_artifact_id = $this->ArtifactType->getID();
        $group_id = $group->getGroupId();
        
        // See who is going to receive the notification. Plus append any other email 
        // given at the end of the list.
        $withoutpermissions_concerned_addresses = array();
        $this->buildNotificationArrays($changes, $concerned_ids, $concerned_addresses);
        if ($more_addresses) {
            foreach ($more_addresses as $address) {
                if ($address['address'] && $address['address'] != '') {
                    $res_username = user_get_result_set_from_email($address['address'], false);
                    if ($res_username && (db_numrows($res_username) == 1)) {
                        $u_id = db_result($res_username,0,'user_id');
                        if (!$address['check_permissions']) {
                            $curr_user = new User($u_id);	
                            if ($curr_user->isActive() || $curr_user->isRestricted()) {
                                $withoutpermissions_concerned_addresses[user_getemail($u_id)] = true;
                            }
                            unset($concerned_ids[$u_id]);
                        } else {
                            $concerned_ids[$u_id] = true;
                        }
                    } else {
                        if (!$address['check_permissions']) {
                            $withoutpermissions_concerned_addresses[$address['address']] = true;
                            unset($concerned_addresses[$address['address']]);
                        } else {
                            $concerned_addresses[$address['address']] = true;
                        }
                    }
                }
            }
        }
        //concerned_ids contains users for wich we have to check permissions
        //concerned_addresses contains emails for which there is no existing user. Permissions will be checked (Anonymous users)
        //withoutpermissions_concerned_addresses contains emails for which there is no permissions check
        
        //Prepare e-mail
        list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);		
        $mail =& new Mail();
        $mail->setFrom($GLOBALS['sys_noreply']);
        $mail->addAdditionalHeader("X-CodeX-Project",     group_getunixname($group_id));
        $mail->addAdditionalHeader("X-CodeX-Artifact",    $this->ArtifactType->getItemName());
        $mail->addAdditionalHeader("X-CodeX-Artifact-ID", $this->getID());
        
        
	    //treat anonymous users
	    $body = $this->createMailForUsers(array($GLOBALS['UGROUP_ANONYMOUS']),$changes,$group_id,$group_artifact_id,$ok,$subject);
	    
	    if ($ok) { //don't send the mail if nothing permitted for this user group
            $to = join(',',array_keys($concerned_addresses));
            if ($to) { 
                $mail->setTo($to);
                $mail->setSubject($subject);
                $mail->setBody($body);
                $mail->send();
            }
	    }
        
        //treat 'without permissions' emails
        if (count($withoutpermissions_concerned_addresses)) {
            $body = $this->createMailForUsers(false,$changes,$group_id,$group_artifact_id,$ok,$subject);
            if ($ok) {
                $mail->setTo(join(',', array_keys($withoutpermissions_concerned_addresses)));
                $mail->setSubject($subject);
                $mail->setBody($body);
                $mail->send();
            }
        }
        
        //now group other registered users

	    //echo "<br>concerned_ids = ".implode(',',array_keys($concerned_ids));

	    $this->groupNotificationList(array_keys($concerned_ids),$user_sets,$ugroup_sets);

	    //echo "<br>user_sets = "; print_r($user_sets); echo ", ugroup_sets = "; print_r($ugroup_sets);

	    reset($ugroup_sets);
	    while (list($x,$ugroups) = each($ugroup_sets)) {
            unset($arr_addresses);
            
            $user_ids = $user_sets[$x];
            //echo "<br>--->  preparing mail $x for ";print_r($user_ids);
            $body = $this->createMailForUsers($ugroups,$changes,$group_id,$group_artifact_id,$ok,$subject);
            
            if (!$ok) continue; //don't send the mail if nothing permitted for this user group

            foreach ($user_ids as $user_id) {
                $arr_addresses[] = user_getemail($user_id);
            }

            $to = join(',',$arr_addresses);
            if ($to) { 
                $mail->setTo($to);
                $mail->setSubject($subject);
                $mail->setBody($body);
                $mail->send();
            }
	    }
	    $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_include_artifact','update_sent')); //to '.$to;
    }


	/** for a certain set of users being part of the same ugroups
	 * create the mail body containing only fields that they have the permission to read
	 */
	function createMailForUsers($ugroups,$changes,$group_id,$group_artifact_id,&$ok,&$subject) {
	  global $art_field_fact,$art_fieldset_fact,$Language,$sys_lf,$sys_datefmt;

	  $fmt_len = 40;
	  $fmt_left = sprintf("%%-%ds ", $fmt_len-1);
	  $fmt_right = "%s";
	  $artifact_href = get_server_url()."/tracker/?func=detail&aid=".$this->getID()."&atid=$group_artifact_id&group_id=$group_id";
	  $used_fields = $art_field_fact->getAllUsedFields();
      $art_fieldset_fact = new ArtifactFieldsetFactory($this->ArtifactType);
      $used_fieldsets = $art_fieldset_fact->getAllFieldSetsContainingUsedFields();
	  $ok = false;

	  $body = '';
	    
	  //generate the field permissions (TRACKER_FIELD_READ, TRACKER_FIEDL_UPDATE or nothing)
	  //for all fields of this tracker given the $ugroups the user is part of
      $field_perm = false;
	  if ($ugroups) {
          $field_perm = $this->ArtifactType->getFieldPermissions($ugroups);
      }

	  $summ = "";
	  if (!$field_perm || ($field_perm['summary'] && permission_can_read_field($field_perm['summary']))) {
	    $summ = util_unconvert_htmlspecialchars($this->getValue('summary'));
	  }
	  $subject='['.$this->ArtifactType->getCapsItemName().' #'.$this->getID().'] '.$summ;
	  

	  //echo "<br>......... field_perm for "; print_r($ugroups); echo " = "; print_r($field_perm);

	    // artifact fields
	    // Generate the message preamble with all required
	    // artifact fields - Changes first if there are some.
	    if ($changes) {
		$body = "$sys_lf=============   ".strtoupper($this->ArtifactType->getName())." #".$this->getID().
		    ": ".$Language->getText('tracker_include_artifact','latest_modif')."   =============$sys_lf".$artifact_href."$sys_lf$sys_lf".
		  $this->formatChanges($changes,$field_perm,$visible_change)."$sys_lf$sys_lf$sys_lf$sys_lf";

		if (!$visible_change) return;
	    }
        
	    $ok = true;
	    
            
	    $visible_snapshot = false;
	    $full_snapshot = "";

        // We write the name of the project
        $full_snapshot .= sprintf($fmt_left."$sys_lf",$Language->getText('tracker_include_artifact','project').' '.group_getname($group_id) );
        
	    // Write all the fields, grouped by fieldsetset and ordered by rank.
	    $left = 1;
	    
	    $visible_fieldset = false;
        // fetch list of used fieldsets for this artifact
	    foreach ($used_fieldsets as $fieldset_id => $fieldset) {
            $fieldset_snapshot = '';
            $used_fields = $fieldset->getAllUsedFields();
            // fetch list of used fields and the current field values
            // for this artifact
            while ( list($key, $field) = each($used_fields) ) {

                $field_name = $field->getName();

                if (!$field_perm || ($field_perm[$field_name] && permission_can_read_field($field_perm[$field_name]))) {
            
                    $field_html = new ArtifactFieldHtml($field);
                    
                    $visible_fieldset = true;
                    $visible_snapshot = true;

                    // For multi select box, we need to retrieve all the values
                    if ( $field->isMultiSelectBox() ) {
                      $field_value = $field->getValues($this->getID());
                    } else {
                      $field_value = $this->getValue($field->getName());
                    }
                    $display = $field_html->display($group_artifact_id,
                                  $field_value,false,true,true,true);
                    $item = sprintf(($left? $fmt_left : $fmt_right), $display);
                    if (strlen($item) > $fmt_len) {
                        if (! $left) {
                          $fieldset_snapshot .= "$sys_lf";
                        }
                        $fieldset_snapshot .= sprintf($fmt_right, $display);
                        $fieldset_snapshot .= "$sys_lf";
                        $left = 1;
                    } else {
                        $fieldset_snapshot .= $item;
                        $left = ! $left;
                        if ($left) {
                          $fieldset_snapshot .= "$sys_lf";
                        }
                    }
              }
            
            } // while
            
            if ($visible_fieldset) {
                $full_snapshot .= "$sys_lf";
                $full_snapshot .= ($left?"":"$sys_lf");
                $full_snapshot .= '--- '.$fieldset->getLabel().' ---';
                $full_snapshot .= "$sys_lf";
                $full_snapshot .= $fieldset_snapshot;
            }
        }

	    if ($visible_snapshot) $full_snapshot .= "$sys_lf";

	    $body .= "=============   ".strtoupper($this->ArtifactType->getName())." #".$this->getID().
		": ".$Language->getText('tracker_include_artifact','full_snapshot')."   =============$sys_lf".
		($changes ? '':$artifact_href)."$sys_lf$sys_lf".$full_snapshot;


	    if (! $left) {
	      $body .= "$sys_lf";
	    }
	    
	    // Now display other special fields
        
        // Then output the history of bug comments from newest to oldest
	    $body .= $this->showFollowUpComments($group_id, true);
	    
	    // Then output the CC list
	    $body .= "$sys_lf$sys_lf".$this->showCCList($group_id, $group_artifact_id, true);
	    
	    // Then output the dependencies
	    $body .= "$sys_lf$sys_lf".$this->showDependencies($group_id,$group_artifact_id,true);
	    
	    // Then output the history of attached files from newest to oldest
	    $body .= "$sys_lf$sys_lf".$this->showAttachedFiles($group_id,$group_artifact_id,true);
	    
        // Extract references from the message
        $referenceManager =& ReferenceManager::instance();
        $ref_array = $referenceManager->extractReferencesGrouped($body, $group_id);
        if (count($ref_array) > 0) {
            $body .= $sys_lf.$sys_lf.$Language->getText('tracker_include_artifact','references').$sys_lf;
        }
        foreach ($ref_array as $description => $match_array) {
            $body .= $sys_lf.$description.":".$sys_lf;
            foreach ($match_array as $match => $ref_instance) {
                $reference =& $ref_instance->getReference();
                $body .= ' '.$ref_instance->getMatch().': '.$ref_instance->getFullGotoLink().$sys_lf;
            }
        }
        
	    // Finally output the message trailer
	    $body .= "$sys_lf$sys_lf".$Language->getText('tracker_include_artifact','follow_link');
	    $body .= "$sys_lf".$artifact_href;

	    return $body;
	}

	
        /**
         * Format the changes
         *
         * @param changes: array of changes
	 * @param $field_perm an array with the permission associated to each field. false to no check perms
	 * @param $visible_change only needed when using permissions. Returns true if there is any change 
	 * that the user has permission to see
         *
         * @return string
         */
        function formatChanges($changes,$field_perm,&$visible_change) {
        
            global $sys_datefmt,$art_field_fact,$sys_lf,$Language;
	    $visible_change = false;
            $out_hdr = '';
            $out = '';
            $out_com = '';
            $out_att = '';
            reset($changes);
            $fmt = "%20s | %-25s | %s$sys_lf";
        

	    if (!$field_perm || ( 
		(($field_perm['assigned_to'] && permission_can_read_field($field_perm['assigned_to'])) || 
		 ($field_perm['multi_assigned_to'] && permission_can_read_field($field_perm['multi_assigned_to'])) ||
         (!$field_perm['assigned_to'] && !$field_perm['multi_assigned_to'])))) {
	      if (user_isloggedin()) {
		$user_id = user_getid();
		$out_hdr = $Language->getText('tracker_include_artifact','changes_by').' '.user_getrealname($user_id).' <'.user_getemail($user_id).">$sys_lf";
		$out_hdr .= $Language->getText('tracker_import_utils','date').': '.format_date($sys_datefmt,time()).' ('.user_get_timezone().')';
	      } else {
		$out_hdr = $Language->getText('tracker_include_artifact','changes_by').' '.$Language->getText('tracker_include_artifact','anon_user').'        '.$Language->getText('tracker_import_utils','date').': '.format_date($sys_datefmt,time());
	      }
	    }
            //Process special cases first: follow-up comment
	    if (array_key_exists('comment', $changes) && $changes['comment']) {
	      $visible_change = true;
	      $out_com = "$sys_lf$sys_lf---------------   ".$Language->getText('tracker_include_artifact','add_flup_comment')."   ----------------$sys_lf";
	    
	      if (isset($changes['comment']['type']) && $changes['comment']['type'] != $Language->getText('global','none') && $changes['comment']['type'] != '') {
		$out_com .= "[".$changes['comment']['type']."]$sys_lf";
	      }
	      $out_com .= util_unconvert_htmlspecialchars($changes['comment']['add']);
	      unset($changes['comment']);
	    }
        
            //Process special cases first: file attachment
	    if (array_key_exists('attach', $changes) && $changes['attach']) {
	      $visible_change = true;
	      $out_att = "$sys_lf$sys_lf---------------    ".$Language->getText('tracker_include_artifact','add_attachment')."     -----------------$sys_lf";
	      $out_att .= sprintf($Language->getText('tracker_include_artifact','file_name')." %-30s ".$Language->getText('tracker_include_artifact','size').":%d KB$sys_lf",$changes['attach']['name'],
				  intval($changes['attach']['size']/1024) );
	      $out_att .= $changes['attach']['description']."$sys_lf".$changes['attach']['href'];
	      unset($changes['attach']);
	    }
        
            // All the rest of the fields now
            reset($changes);
            while ( list($field_name,$h) = each($changes)) {
	      
	      // If both removed and added items are empty skip - Sanity check
	      if (!$h['del'] && !$h['add'] ||
		  $field_perm && (
		  !$field_perm[$field_name] || 
		  !permission_can_read_field($field_perm[$field_name]))) { continue; }
	      
	      $visible_change = true;
	      $label = $field_name;
	      $field = $art_field_fact->getFieldFromName($field_name);
	      if ( $field ) {
		$label = $field->getLabel();
	      }
	      $out .= sprintf($fmt, $label, $h['del'],$h['add']);
	    } // while
	    
	    if ($out) {
	      $out = "$sys_lf$sys_lf".sprintf($fmt,$Language->getText('tracker_include_artifact','what').'    ',$Language->getText('tracker_include_artifact','removed'),$Language->getText('tracker_include_artifact','added')).
		"------------------------------------------------------------------$sys_lf".$out;
            }
	    
            return($out_hdr.$out.$out_com.$out_att);
	    
        }

        
                /**
         * Display the follow ups comments
         *
         * @param group_id: the group id
         * @param ascii: ascii mode
         *
         * @return void
         */
        function showFollowUpComments($group_id, $ascii=false) {

            //
            //  Format the comment rows from artifact_history
            //  
            global $sys_datefmt,$sys_lf,$Language;
            
                $group = $this->ArtifactType->getGroup();
                $group_artifact_id = $this->ArtifactType->getID();
                $group_id = $group->getGroupId();

            $result=$this->getFollowups ();
            $rows=db_numrows($result);
        
            // No followup comment -> return now
            if ($rows <= 0) {
                        if ($ascii)
                            $out = "$sys_lf$sys_lf ".$Language->getText('tracker_import_utils','no_followups')."$sys_lf";
                        else
                            $out = '<H4>'.$Language->getText('tracker_import_utils','no_followups').'</H4>';
                        return $out;
            }
        
            $out = '';
            
            // Header first
            if ($ascii) {
		$out .= $Language->getText('tracker_include_artifact','follow_ups').$sys_lf.str_repeat("*",strlen($Language->getText('tracker_include_artifact','follow_ups')));
            } else {
                        $title_arr=array();
                        $title_arr[]=$Language->getText('tracker_include_artifact','fill_cc_list_cmt');
                        //$title_arr[]=$Language->getText('tracker_import_utils','date');
                        //$title_arr[]=$Language->getText('global','by');
                        
                        $out .= html_build_list_table_top ($title_arr,false,false,true,null,null,0,0);
                        $out .= '<tr><td>';
            }
            
            // Loop throuh the follow-up comments and format them
            for ($i=0; $i < $rows; $i++) {
                        $comment_type = db_result($result, $i, 'comment_type');
			$comment_type_id = db_result($result, $i, 'comment_type_id');
		        if ( ($comment_type_id == 100) ||($comment_type == "") )
                            $comment_type = '';
                        else
                            $comment_type = '['.$comment_type.']';
                        if ($ascii) {
                            $fmt = "$sys_lf$sys_lf------------------------------------------------------------------$sys_lf".
                                $Language->getText('tracker_import_utils','date').": %-30s".$Language->getText('global','by').": %s$sys_lf".
                                ($comment_type != ""? "%s$sys_lf%s" : '%s');
                        } else {
                            $fmt = "\n".'
                            <div class="'. util_get_alt_row_color($i) .' followup_comment">
                                <div class="followup_comment_title">
                                    <span class="followup_comment_title_user">%s </span>
                                    <span class="followup_comment_title_date">on %s</span>
                                </div>
                                <div class="followup_comment_content">'.($comment_type != ""? '<div class="followup_comment_content_type"><b>%s</b></div>' : "").'%s</div>
                            </div>';
                        }
                
                        // I wish we had sprintf argument swapping in PHP3 but
                        // we don't so do it the ugly way...
                        if ($ascii) {
                                if ( $comment_type != "" ) {
                                    $out .= sprintf($fmt,
                                                    format_date($sys_datefmt,db_result($result, $i, 'date')),
                                                    (db_result($result, $i, 'mod_by')==100?db_result($result, $i, 'email'):db_result($result, $i, 'user_name')),
                                                    $comment_type,
                                                    util_unconvert_htmlspecialchars(db_result($result, $i, 'old_value'))
                                                    );
                                } else {
                                    $out .= sprintf($fmt,
                                                    format_date($sys_datefmt,db_result($result, $i, 'date')),
                                                    (db_result($result, $i, 'mod_by')==100?db_result($result, $i, 'email'):db_result($result, $i, 'user_name')),
                                                    util_unconvert_htmlspecialchars(db_result($result, $i, 'old_value'))
                                                    );
                                }
                        } else {
                                if ( $comment_type != "" ) {
                                    $out .= sprintf($fmt,
                                                    (db_result($result, $i, 'mod_by')==100?db_result($result, $i, 'email'):'<a href="/users/'.db_result($result, $i, 'user_name').'">'.db_result($result, $i, 'user_name').'</a>'),
                                                    format_date($sys_datefmt,db_result($result, $i, 'date')),
                                                    $comment_type,
                                                    util_make_links(nl2br(db_result($result, $i, 'old_value')),$group_id,$group_artifact_id));
                                } else {
                                    $out .= sprintf($fmt,
                                                    (db_result($result, $i, 'mod_by')==100?db_result($result, $i, 'email'):'<a href="/users/'.db_result($result, $i, 'user_name').'">'.db_result($result, $i, 'user_name').'</a>'),
                                                    format_date($sys_datefmt,db_result($result, $i, 'date')),
                                                    util_make_links(nl2br(db_result($result, $i, 'old_value')),$group_id,$group_artifact_id));
                                }
                        }
            }
        
            // final touch...
            $out .= ($ascii ? "$sys_lf" : "</td></tr></TABLE>");
        
            return($out);
                
        }

                /**
         * Display the list of CC addresses
         *
         * @param group_id: the group id
         * @param group_artifact_id: the artifact type ID
         * @param ascii: ascii mode
         *
         * @return void
         */
        function showCCList ($group_id, $group_artifact_id, $ascii=false) {
        
            global $sys_datefmt,$sys_lf,$Language;
        
            //
            //      format the CC list for this artifact
            //
        
            $result = $this->getCCList();
            $rows   = db_numrows($result);
            $out    = '';
            
            // Nobody in the CC list -> return now
            if ($rows <= 0) {
                        if ($ascii)
                            $out = $Language->getText('tracker_include_artifact','cc_empty')."$sys_lf";
                        else
                            $out = '<H4>'.$Language->getText('tracker_include_artifact','cc_empty').'</H4>';
                        return $out;
            }
        
            // Header first an determine what the print out format is
            // based on output type (Ascii, HTML)
            if ($ascii) {
		$out .= $Language->getText('tracker_include_artifact','cc_list').$sys_lf.str_repeat("*",strlen($Language->getText('tracker_include_artifact','cc_list'))).$sys_lf.$sys_lf;
                        $fmt = "%-35s | %s$sys_lf";
                        $out .= sprintf($fmt, $Language->getText('tracker_include_artifact','cc_address'), $Language->getText('tracker_include_artifact','fill_cc_list_cmt'));
                        $out .= "------------------------------------------------------------------$sys_lf";
            } else {    
        
                        $title_arr=array();
                        $title_arr[]=$Language->getText('tracker_include_artifact','cc_address');
                        $title_arr[]=$Language->getText('tracker_include_artifact','fill_cc_list_cmt');
                        $title_arr[]=$Language->getText('tracker_include_artifact','added_by');
                        $title_arr[]=$Language->getText('tracker_include_artifact','posted_on');
                        $title_arr[]=$Language->getText('tracker_include_canned','delete');
                        $out .= html_build_list_table_top ($title_arr);
                
                        $fmt = "\n".'<TR class="%s"><td>%s</td><td>%s</td><td align="center">%s</td>'.
                            '<td align="center">%s</td><td align="center">%s</td></tr>';
                }
                
            // Loop through the cc and format them
            for ($i=0; $i < $rows; $i++) {
        
                        $email = db_result($result, $i, 'email');
                        $artifact_cc_id = db_result($result, $i, 'artifact_cc_id');
                
                        // if the CC is a user point to its user page else build a mailto: URL
                        $res_username = user_get_result_set_from_unix($email);
                        if ($res_username && (db_numrows($res_username) == 1))
                            $href_cc = util_user_link($email);
                        else
                            $href_cc = "<a href=\"mailto:".util_normalize_email($email)."\">".$email.'</a>';
                
                        if ($ascii) {
                            $out .= sprintf($fmt, $email, db_result($result, $i, 'comment'));
                        } else {
                
                            // show CC delete icon if one of the condition is met:
                            // (a) current user is a group member
                            // (b) the CC name is the current user 
                            // (c) the CC email address matches the one of the current user
                            // (d) the current user is the person who added a gieven name in CC list
                            if ( user_ismember($this->ArtifactType->getGroupID()) ||
                                (user_getname(user_getid()) == $email) ||  
                                (user_getemail(user_getid()) == $email) ||
                                (user_getname(user_getid()) == db_result($result, $i, 'user_name') )) {
                                        $html_delete = "<a href=\"".$_SERVER['PHP_SELF']."?func=delete_cc&group_id=$group_id&aid=".$this->getID()."&atid=".$group_artifact_id."&artifact_cc_id=$artifact_cc_id\" ".
                                        " onClick=\"return confirm('".$Language->getText('tracker_include_artifact','delete_cc')."')\">".
                                        '<IMG SRC="'.util_get_image_theme("ic/trash.png").'" HEIGHT="16" WIDTH="16" BORDER="0" ALT="'.$Language->getText('global','btn_delete').'"></A>';
                            } else {
                                        $html_delete = '-';
                            }
                
                            $out .= sprintf($fmt,
                                            util_get_alt_row_color($i),
                                            $href_cc,
                                            db_result($result, $i, 'comment'),
                                            util_user_link(db_result($result, $i, 'user_name')),
                                            format_date($sys_datefmt,db_result($result, $i, 'date')),
                                            $html_delete);
                        
                        } // for
            }
        
            // final touch...
            $out .= ($ascii ? "$sys_lf" : "</TABLE>");
        
            return($out);
        
        }

                /**
         * Display the artifact dependencies list
         *
         * @param group_id: the group id
         * @param group_artifact_id: the artifact type ID
         * @param ascii: ascii mode
         *
         * @return void
         */
        function showDependencies ($group_id, $group_artifact_id, $ascii=false) {
        
            global $sys_datefmt,$sys_lf,$Language;
        
            //
            //      format the dependencies list for this artifact
            //
        
            $result=$this->getDependencies();
            $rows=db_numrows($result);
            $out = '';
            // Nobody in the dependencies list -> return now
            if ($rows <= 0) {
                        if ($ascii)
                            $out = $Language->getText('tracker_include_artifact','dep_list_empty')."$sys_lf";
                        else
                            $out = '<H4>'.$Language->getText('tracker_include_artifact','dep_list_empty').'</H4>';
                        return $out;
            }
        
            // Header first an determine what the print out format is
            // based on output type (Ascii, HTML)
            if ($ascii) {
		$out .= $Language->getText('tracker_include_artifact','dep_list').$sys_lf.str_repeat("*",strlen($Language->getText('tracker_include_artifact','dep_list')))."$sys_lf$sys_lf";
                        $fmt = "%-15s | %s$sys_lf";
                        $out .= sprintf($fmt, $Language->getText('tracker_include_artifact','artifact'), $Language->getText('tracker_include_artifact','summary'));
                        $out .= "------------------------------------------------------------------$sys_lf";
            } else {    
        
                        $title_arr=array();
                        $title_arr[]=$Language->getText('tracker_include_artifact','artifact');
                        $title_arr[]=$Language->getText('tracker_include_artifact','summary');
                        $title_arr[]=$Language->getText('tracker_import_admin','tracker');
                        $title_arr[]=$Language->getText('tracker_include_artifact','group');
                        $title_arr[]=$Language->getText('tracker_include_canned','delete');
                        $out .= html_build_list_table_top ($title_arr);
                
                        $fmt = "\n".'<TR class="%s"><td>%s</td><td>%s</td><td align="center">%s</td>'.
                            '<td align="center">%s</td><td align="center">%s</td></tr>';
                }
                
            // Loop through the denpendencies and format them
            for ($i=0; $i < $rows; $i++) {
        
                        $dependent_on_artifact_id = db_result($result, $i, 'is_dependent_on_artifact_id');
                        $summary = db_result($result, $i, 'summary');
                        $tracker_label = db_result($result, $i, 'name');
                        $group_label = db_result($result, $i, 'group_name');
                
                        if ($ascii) {
                            $out .= sprintf($fmt, $dependent_on_artifact_id, $summary);
                        } else {
                
                            if ( user_ismember($this->ArtifactType->getGroupID()) ) {
                                        $html_delete = "<a href=\"".$_SERVER['PHP_SELF']."?func=delete_dependent&group_id=$group_id&aid=".$this->getID()."&atid=".$group_artifact_id."&dependent_on_artifact_id=$dependent_on_artifact_id\" ".
                                        " onClick=\"return confirm('".$Language->getText('tracker_include_artifact','del_dep')."')\">".
                                        '<IMG SRC="'.util_get_image_theme("ic/trash.png").'" HEIGHT="16" WIDTH="16" BORDER="0" ALT="'.$Language->getText('global','btn_delete').'"></A>';
                            } else {
                                        $html_delete = '-';
                            }
                
                            $out .= sprintf($fmt,
                                            util_get_alt_row_color($i),
                                            "<a href=\"/tracker/?func=gotoid&group_id=$group_id&aid=$dependent_on_artifact_id\">$dependent_on_artifact_id</a>",
                                            $summary,
                                            $tracker_label,
                                            $group_label,
                                            $html_delete);
                        
                        } // for
            }
        
            // final touch...
            $out .= ($ascii ? "$sys_lf" : "</TABLE>");
        
            return($out);
        
        }

                /**
         * Display the list of attached files
         *
         * @param group_id: the group id
         * @param group_artifact_id: the artifact type ID
         * @param ascii: ascii mode
         *
         * @return void
         */
        function showAttachedFiles ($group_id,$group_artifact_id,$ascii=false) {
        
            global $sys_datefmt,$sys_lf,$Language;
        
            //
            //  show the files attached to this artifact
            //   
        
            $result=$this->getAttachedFiles();
            $rows=db_numrows($result);
        
            // No file attached -> return now
            if ($rows <= 0) {
                        if ($ascii)
                            $out = $Language->getText('tracker_include_artifact','no_file_attached')."$sys_lf";
                        else
                            $out = '<H4>'.$Language->getText('tracker_include_artifact','no_file_attached').'</H4>';
                        return $out;
                }
                
            // Header first
            if ($ascii) {
		$out = $Language->getText('tracker_include_artifact','file_attachment').$sys_lf.str_repeat("*",strlen($Language->getText('tracker_include_artifact','file_attachment')));
            } else {    
                
                $title_arr=array();
                $title_arr[]=$Language->getText('tracker_include_artifact','name');
                $title_arr[]=$Language->getText('tracker_include_artifact','desc');
                $title_arr[]=$Language->getText('tracker_include_artifact','size_kb');
                $title_arr[]=$Language->getText('global','by');
                $title_arr[]=$Language->getText('tracker_include_artifact','posted_on');
		$title_arr[]=$Language->getText('tracker_include_canned','delete');
        
                $out = html_build_list_table_top ($title_arr);
            }
        
            // Determine what the print out format is based on output type (Ascii, HTML)
            if ($ascii) {
                        $fmt = "$sys_lf$sys_lf------------------------------------------------------------------$sys_lf".
                            $Language->getText('tracker_import_utils','date').": %s  ".$Language->getText('tracker_include_artifact','name').": %s  ".$Language->getText('tracker_include_artifact','size').": %dKB   ".$Language->getText('global','by').": %s$sys_lf%s$sys_lf%s";
            } else {
                        $fmt = "$sys_lf".'<TR class="%s"><td>%s</td><td>%s</td><td align="center">%s</td><td align="center">%s</td><td align="center">%s</td>'.
                    '<td align="center">%s</td></tr>';
            }
        
            // Determine which protocl to use for embedded URL in ASCII format
            $server=get_server_url();
        
            // Loop throuh the attached files and format them
            for ($i=0; $i < $rows; $i++) {
        
                        $artifact_file_id = db_result($result, $i, 'id');
                        $href = "/tracker/download.php?artifact_id=".$this->getID()."&id=".$artifact_file_id;
                
                        if ($ascii) {
                            $out .= sprintf($fmt,
                                            format_date($sys_datefmt,db_result($result, $i, 'adddate')),
                                            db_result($result, $i, 'filename'),
                                            intval(db_result($result, $i, 'filesize')/1024),
                                            db_result($result, $i, 'user_name'),
                                            db_result($result, $i, 'description'),
                                            $server.$href);
                        } else {
                            // show CC delete icon if one of the condition is met:
                            // (a) current user is group member
                            // (b) the current user is the person who added a gieven name in CC list
			  if ( user_ismember($this->ArtifactType->getGroupID()) ||
                                (user_getname(user_getid()) == db_result($result, $i, 'user_name') )) {
                                        $html_delete = "<a href=\"".$_SERVER['PHP_SELF']."?func=delete_file&group_id=".$group_id."&atid=".$group_artifact_id."&aid=".$this->getID()."&id=".db_result($result, $i, 'id')."\" ".
                                            " onClick=\"return confirm('".$Language->getText('tracker_include_artifact','delete_attachment')."')\">".
                                            '<IMG SRC="'.util_get_image_theme("ic/trash.png").'" HEIGHT="16" WIDTH="16" BORDER="0" ALT="'.$Language->getText('global','btn_delete').'"></A>';
                            } else {
                                        $html_delete = '-';
                            }
                            $out .= sprintf($fmt,
                                            util_get_alt_row_color($i),
                                            "<a href=\"$href\">". db_result($result, $i, 'filename').'</a>',
                                            db_result($result, $i, 'description'),
                                            intval(db_result($result, $i, 'filesize')/1024),
                                            util_user_link(db_result($result, $i, 'user_name')),
                                            format_date($sys_datefmt,db_result($result, $i, 'adddate')),
                                            $html_delete);
                        }
        } // for
        
            // final touch...
            $out .= ($ascii ? "$sys_lf" : "</TABLE>");
        
            return($out);
        
        }


}

?>
