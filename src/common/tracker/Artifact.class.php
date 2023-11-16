<?php
/*
 * SourceForge: Breaking Down the Barriers to Open Source Development
 * Copyright 1999-2001 (c) VA Linux Systems
 * http://sourceforge.net
 *
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

use Tuleap\Date\DateHelper;

/**
 *
 * Artifact.class.php - Main Artifact class
 */
class Artifact
{
    public const FORMAT_TEXT = 0;
    public const FORMAT_HTML = 1;

    //The diffetents mode of display
    public const OUTPUT_BROWSER   = 0;
    public const OUTPUT_EXPORT    = 1;
    public const OUTPUT_MAIL_TEXT = 2;

    /**
     * Artifact Type object.
     *
     * @var             object  $ArtifactType.
     */
    public $ArtifactType;

    /**
     * Array of artifact data.
     *
     * @var             array   $data_array.
     */
    public $data_array;
    /**
     * @var string
     */
    private $error_message = '';
    /**
     * @var bool
     */
    private $error_state = false;


    /**
     *  Artifact - constructor.
     *
     *  @param  object  The ArtifactType object.
     *  @param  integer (primary key from database OR complete assoc array)
     *          ONLY OPTIONAL WHEN YOU PLAN TO IMMEDIATELY CALL ->create()
     *  @return bool success.
     */
    public function __construct(&$ArtifactType, $data = false, $checkPerms = true)
    {
        global $Language;

        $this->ArtifactType = $ArtifactType;

        //was ArtifactType legit?
        if (! $ArtifactType || ! is_object($ArtifactType)) {
            $this->setError('Artifact: ' . $Language->getText('tracker_common_canned', 'not_valid'));
            return false;
        }
        //did ArtifactType have an error?
        if ($ArtifactType->isError()) {
            $this->setError('Artifact: ' . $ArtifactType->getErrorMessage());
            return false;
        }

        //      make sure this person has permission to view artifacts belonging to this tracker
        if ($checkPerms && ! $this->ArtifactType->userCanView()) {
            $this->setError('Artifact: ' . $Language->getText('tracker_common_artifact', 'view_private'));
            return false;
        }

        //      set up data structures
        if ($data) {
            if (is_array($data)) {
                $this->data_array = $data;
                //      Should verify ArtifactType ID
            } else {
                if (! $this->fetchData($data)) {
                    return false;
                }
            }
            //      make sure this person has permission to view this artifact
            if ($checkPerms) {
                if (! $this->userCanView()) {
                    $this->setError('Artifact: ' . $Language->getText('tracker_common_artifact', 'view_private_artifact'));
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
     *  @return bool success.
     */
    public function fetchData($artifact_id)
    {
        global $art_field_fact,$Language;
        if (! $art_field_fact) {
            $art_field_fact = new ArtifactFieldFactory($this->ArtifactType);
        }

        // first fetch values of standard fields
        $sql = "SELECT * FROM artifact WHERE artifact_id='" . db_ei($artifact_id) . "' AND group_artifact_id='" . db_ei($this->ArtifactType->getID()) . "'";
        $res = db_query($sql);
        if (! $res || db_numrows($res) < 1) {
            $this->setError('Artifact: ' . $Language->getText('tracker_common_artifact', 'invalid_id'));
            return false;
        }
        $this->data_array = db_fetch_array($res);
        db_free_result($res);

        // now get the values for generic fields if any
        $sql = "SELECT * FROM artifact_field_value WHERE artifact_id='" . db_ei($artifact_id) . "'";
        $res = db_query($sql);
        if (! $res || db_numrows($res) < 1) {
            // if no result then it is possible that there isn't any generic fields
            return true;
        }
        while ($row = db_fetch_array($res)) {
            $data_fields[$row['field_id']] = $row;
        }

        // Get the list of all fields used by this tracker and append
        // the values for these generic fields to data_array
        $fields = $art_field_fact->getAllUsedFields();

        foreach ($fields as $field) {
            //echo $field->getName()."-".$field->getID()."<br>";
            // Skip! Standard field values fectched in previous query
            // and comment_type_id is not stored in artifact_field_value table
            if (
                $field->isStandardField() ||
                 $field->getName() == "comment_type_id"
            ) {
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
    public function getArtifactType()
    {
        return $this->ArtifactType;
    }

    /**
     *  getValue - get the value for this artifact field.
     *
     *           @param name: the field name
     *  @return value
     */
    public function getValue($name)
    {
        if (array_key_exists($name, $this->data_array)) {
            return $this->data_array[$name];
        }
    }

    /**
     *  getMultiAssignedTo - get the value for the 'multi_assigned_to' field
     *  This function is needed because getValue() won't return an array.
     *
     *  @return array
     */
    public function getMultiAssignedTo()
    {
        $aid = $this->getID();
        if (! $aid) {
            return;
        }
        $sql        = "SELECT afv.valueInt
              FROM artifact_field_value afv, artifact a, artifact_field af
              WHERE a.artifact_id=" . db_ei($aid) . "
                AND afv.artifact_id=" . db_ei($aid) . "
                AND a.group_artifact_id=af.group_artifact_id
                AND afv.field_id=af.field_id
                AND af.field_name='multi_assigned_to'";
        $res        = db_query($sql);
        $i          = 0;
        $return_val = [];
        while ($resrow = db_fetch_array($res)) {
            $return_val[$i++] = $resrow['valueInt'];
        }
        return $return_val;
    }

    /**
     *  getID - get this ArtifactID.
     *
     *  @return int     The artifact_id #.
     */
    public function getID()
    {
        return $this->data_array['artifact_id'] ?? 0;
    }

    /**
     * useArtifactPermissions
     * @return bool true if the artifact has individual permissions set
     */
    public function useArtifactPermissions()
    {
        return $this->data_array['use_artifact_permissions'] ?? false;
    }

    /**
     *  getStatusID - get open/closed/deleted flag.
     *
     *  @return int     Status: (1) Open, (2) Closed, (3) Deleted.
     */
    public function getStatusID()
    {
        return $this->data_array['status_id'];
    }

    /**
     *  getSubmittedBy - get ID of submitter.
     *
     *  @return int user_id of submitter.
     */
    public function getSubmittedBy()
    {
        return $this->data_array['submitted_by'];
    }

    /**
     *  getOpenDate - get unix time of creation.
     *
     *  @return int unix time.
     */
    public function getOpenDate()
    {
        return $this->data_array['open_date'];
    }

     /**
     *  getLastUpdateDate - get unix time of last artifact update.
     *
     *  @return int unix time.
     */
    public function getLastUpdateDate()
    {
        return $this->data_array['last_update_date'];
    }

   /**
     *  getCloseDate - get unix time of closure.
     *
     *  @return int unix time.
     */
    public function getCloseDate()
    {
        return $this->data_array['close_date'];
    }

    /**
     *  getSummary - get text summary of artifact.
     *
     *  @return string The summary (subject).
     */
    public function getSummary()
    {
        return $this->data_array['summary'];
    }

    /**
     *  getDetails - get text body (message) of artifact.
     *
     *  @return string  The body (message).
     */
    public function getDetails()
    {
        return $this->data_array['details'];
    }

    /**
     *  getSeverity - get the severity of this artifact
     *
     *  @return int
     */
    public function getSeverity()
    {
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
    public function addHistory($field, $old_value, $new_value, $type = false, $email = false, $ahid = false, $comment_format = self::FORMAT_TEXT)
    {
        //MLS: add case where we add CC and file_attachment into history for task #240
        if (! is_object($field)) {
         // "cc", "attachment", "comment", etc
            $name = $field;
        } else {
         // If field is not to be kept in bug change history then do nothing
            if (! $field->getGlobalKeepHistory()) {
                return;
            }
            $name = $field->getName();
        }

        /*
          handle the insertion of history for these parameters
        */
        if ($email) {
            // We use the email to identify the user
            $user = 100;
        } else {
            if (user_isloggedin()) {
                $user = UserManager::instance()->getCurrentUser()->getId();
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
            $fld_type = ',type';
            $val_type = ",'" . db_ei($type) . "'";
        } else {
            // No comment type specified for a followup comment
            // so force it to None (100)
            if ($name == 'comment' || (preg_match("/^(lbl_)/", $name) && preg_match("/(_comment)$/", $name))) {
                $fld_type = ',type';
                $val_type = ",'100'";
            }
        }

        // Follow-up comments might have a different format
        if ($comment_format != self::FORMAT_TEXT && user_isloggedin()) {
            $fld_type .= ',format';
            $val_type .= ',' . db_ei($comment_format);
        }

        $sql = "insert into artifact_history(artifact_id,field_name,old_value,new_value,mod_by,email,date $fld_type) " .
            "VALUES (" . db_ei($this->getID()) . ",'" . db_es($name) . "','" . db_es($old_value) . "','" . db_es($new_value) . "','" . db_ei($user) . "','" . db_es($email) . "','" . time() . "' $val_type)";
        //echo $sql;
        return db_query($sql);
    }

    /**
     *  Create a new artifact (and its values) in the db
     *
     * @param array $vfl the value-field-list. Array association pair of field_name => field_value.
     *              If the function is called by the web-site submission form, the $vfl is set to false, and will be filled by the function extractFieldList function retrieving the HTTP parameters.
     *              If $vfl is not false, the fields expected in this array are *all* the fields of this tracker that are allowed to be submited by the user.
     *  @return bool
     */
    public function create($vfl = false, $import = false, $row = 0)
    {
        global $ath,$art_field_fact,$Language;

        $group             = $ath->getGroup();
        $group_artifact_id = $ath->getID();
        $error_message     = ($import ? $Language->getText('tracker_common_artifact', 'row', $row) : "");

        // Retrieve HTTP GET variables and store them in $vfl array
        if (! $vfl) {
            $vfl = $art_field_fact->extractFieldList();
        }

        // We check the submitted fields to see if the user has the permissions to submit it
        if (! $import) {
            foreach ($vfl as $key => $val) {
                $field = $art_field_fact->getFieldFromName($key);
                if ($field && (! $field->getName() == 'comment_type_id')) {   // SR #684 we don't check the perms for the field comment type
                    if (! $field->userCanSubmit($group->getID(), $group_artifact_id, UserManager::instance()->getCurrentUser()->getId())) {
                        // The user does not have the permissions to update the current field,
                        // we exit the function with an error message
                        $this->setError($Language->getText('tracker_common_artifact', 'bad_field_permission_submission', $field->getLabel()));
                        return false;
                    }
                    // we check if the given value is authorized for this field (for select box fields only)
                    // we don't check here the none value, we check after it with the function checkEmptyFields, to get a better error message if the field required (instead of value 100 is not a valid valid value for the field)
                    if ($field->isSelectBox() && $val != 100 && ! $field->checkValueInPredefinedValues($this->ArtifactType->getID(), $val)) {
                        $this->setError($Language->getText('tracker_common_artifact', 'bad_field_value', [$field->getLabel(), $val]));
                        return false;
                    }
                    if ($field->isMultiSelectBox()) {
                        foreach ($val as $a_value) {
                            if ($a_value != 100 && ! $field->checkValueInPredefinedValues($this->ArtifactType->getID(), $a_value)) {
                                $this->setError($Language->getText('tracker_common_artifact', 'bad_field_value', [$field->getLabel(), $val]));
                                return false;
                            }
                        }
                    }
                }
            }
            //When user is not autorised to submit some fields
            //we should block those artifact with mandatory fields and default value set to "None"
            $fieldsNotShown = $art_field_fact->getAllFieldsNotShownOnAdd();
            if ($art_field_fact->checkEmptyFields($fieldsNotShown, false) == false) {
                $this->setError($Language->getText('tracker_common_artifact', 'mandatory_not_set'));
                return false;
            }
        }

        if (! $import) {
            // make sure  required fields are not empty
            if ($art_field_fact->checkEmptyFields($vfl) == false) {
                $this->setError($art_field_fact->getErrorMessage());
                exit_missing_param();
            }
        }

        // we don't force them to be logged in to submit a bug
        if (! user_isloggedin()) {
            $user = 100;
        } else {
            $user = UserManager::instance()->getCurrentUser()->getId();
        }

        // add default values for fields that have not been shown
        $add_fields = $art_field_fact->getAllFieldsNotShownOnAdd();
        foreach ($add_fields as $key => $def_val) {
            if (! array_key_exists($key, $vfl)) {
                $vfl[$key] = $def_val;
            }
        }

        if (
            $import &&
            $vfl['submitted_by'] &&
            $vfl['submitted_by'] != ""
        ) {
            $user = $vfl['submitted_by'];
        }

        // first make sure this wasn't double-submitted
        $field = $art_field_fact->getFieldFromName('summary');
        if ($field && $field->isUsed()) {
            $res = db_query("SELECT *
                FROM artifact
                WHERE group_artifact_id = " . db_ei($ath->getID()) . "
                AND submitted_by=" .  db_ei($user) . "
                AND summary='" . db_es(htmlspecialchars($vfl['summary'])) . "'");
            if ($res && db_numrows($res) > 0) {
                $this->setError($Language->getText('tracker_common_artifact', 'double_subm', db_result($res, 0, 'artifact_id')));
                return false;
            }
        }

        //  Create the insert statement for standard field
        //
        //Reference manager for cross reference
        $reference_manager = ReferenceManager::instance();
        $vfl_cols          = '';
        $vfl_values        = '';
        $text_value_list   = [];
        foreach ($vfl as $field_name => $value) {
            //echo "<br>field_name=$field_name, value=$value";

            $field = $art_field_fact->getFieldFromName($field_name);
            if ($field && $field->isStandardField()) {
                // skip over special fields
                if ($field->isSpecial()) {
                    continue;
                }

                $vfl_cols .= ',' . $field->getName();
                $is_text   = ($field->isTextField() || $field->isTextArea());
                if ($is_text) {
                    $value = htmlspecialchars($value);
                    //Log for Cross references
                    $text_value_list[] = $value;
                } elseif ($field->isDateField()) {
                    // if it's a date we must convert the format to unix time
                    list($value,$ok) = util_date_to_unixtime($value);
                }

                $vfl_values .= ',\'' . db_es($value) . '\'';
            }
        } // while

        // Add all special fields that were not handled in the previous block
        $fixed_cols = 'open_date,last_update_date,group_artifact_id,submitted_by';
        if ($import) {
            if (! isset($vfl['open_date']) || ! $vfl['open_date'] || $vfl['open_date'] == "") {
                $open_date = time();
            } else {
                list($open_date,$ok) = util_date_to_unixtime($vfl['open_date']);
            }
            $fixed_values = "'" . db_ei($open_date) . "','" . time() . "','" . db_ei($group_artifact_id) . "','" . db_ei($user) . "'";
        } else {
            $fixed_values = "'" . time() . "','" . time() . "','" . db_ei($group_artifact_id) . "','" . db_ei($user) . "'";
        }

        //  Finally, build the full SQL query and insert the artifact itself
        $id_sharing = new TrackerIdSharingDao();
        if ($artifact_id = $id_sharing->generateArtifactId()) {
            $sql = "INSERT INTO artifact (artifact_id, $fixed_cols $vfl_cols) VALUES ($artifact_id, $fixed_values $vfl_values)";
            //echo "<br>DBG - SQL insert artifact: $sql";
            $result = db_query($sql);

            $was_error = false;
            if (! $result || db_affected_rows($result) == 0) {
                $this->setError($Language->getText('tracker_common_artifact', 'insert_err', $sql));
                $was_error = true;
            } else {
                //  Insert the field values for no standard field
                $fields = $art_field_fact->getAllUsedFields();
                foreach ($fields as $field_name => $field) {
                    // skip over special fields
                    if (($field->isSpecial()) || ($field->isStandardField())) {
                        continue;
                    }

                    if (array_key_exists($field_name, $vfl) && isset($vfl[$field_name]) && $vfl[$field_name]) {
                        // The field has a value from the user input

                        $value = $vfl[$field_name];

                        $is_text = ($field->isTextField() || $field->isTextArea());
                        if ($is_text) {
                            $value = htmlspecialchars($value);

                            //Log for Cross references
                            $text_value_list[] = $value;
                        } elseif ($field->isDateField()) {
                            // if it's a date we must convert the format to unix time
                            list($value,$ok) = util_date_to_unixtime($value);
                        }

                        // Insert the field value
                        if (! $field->insertValue($artifact_id, $value)) {
                            $error_message .= $Language->getText('tracker_common_artifact', 'field_err', [$field->getLabel(), $value]);
                            $was_error      = true;
                            $this->setError($error_message);
                        }
                    } else {
                        // The field hasn't a value from the user input
                        // We need to insert default value for this field
                        // because all SQL queries (from Report or Artifact read/update) don't allow
                        // empty record (we must use join and not left join for performance reasons).

                        if (! $field->insertValue($artifact_id, $field->getDefaultValue())) {
                            $error_message .= $Language->getText('tracker_common_artifact', 'def_err', [$field->getLabel(), $field->getDefaultValue()]);
                            $was_error      = true;
                            $this->setError($error_message);
                        }
                    }
                } // while
            }

            //Add Cross Reference
            for ($i = 0; $i < sizeof($text_value_list); $i++) {
                $reference_manager->extractCrossRef($text_value_list[$i], $artifact_id, ReferenceManager::REFERENCE_NATURE_ARTIFACT, $ath->getGroupID());
            }

            // artifact permissions
            $request                         = HTTPRequest::instance();
            $this->data_array['artifact_id'] = $artifact_id; // cheat
            $this->setPermissions($request->get('use_artifact_permissions_name'), $request->get('ugroups'));

            // All ok then reload the artifact data to make sure it is cached
            // correctly in memory
            $this->fetchData($artifact_id);
        } else {
            $this->setError($Language->getText('tracker_common_artifact', 'insert_err', $sql));
            $was_error = true;
        }
        return ! $was_error;
    }

    /**
     *  Add a followup comment
     *
     * @param comment: the comment
     * @param email: user email if the user is not logged in
     * @param changes (OUT): array of changes (for notifications)
     *
     *  @return bool
     */
    public function addComment($comment, $email, &$changes, $comment_format = self::FORMAT_TEXT)
    {
        global $art_field_fact,$Language;

        // Add a new comment if there is one
        if ($comment != '') {
            // For none project members force the comment type to None (100)
            if (! user_isloggedin()) {
                if ($email) {
                    $this->addHistory('comment', "", htmlspecialchars($comment), 100, $email, null, $comment_format);
                } else {
                    $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_common_artifact', 'enter_email'));
                    return false;
                }
            } else {
                $this->addHistory('comment', "", htmlspecialchars($comment), 100, null, null, $comment_format);
            }
            $changes['comment']['add']  = stripslashes($comment);
            $changes['comment']['type'] = $Language->getText('global', 'none');

            $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_common_artifact', 'add_comment'));
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
     */
    public function addFollowUpComment($comment, $comment_type_id, $canned_response, &$changes, $comment_format = self::FORMAT_TEXT)
    {
        global $art_field_fact,$Language;
        if ($canned_response && $canned_response != 100) {
            $sql  = "SELECT * FROM artifact_canned_responses WHERE artifact_canned_id='" . db_ei($canned_response) . "'";
            $res3 = db_query($sql);

            if ($res3 && db_numrows($res3) > 0) {
                     $comment = util_unconvert_htmlspecialchars(db_result($res3, 0, 'body'));
                     $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_common_artifact', 'canned_used'));
            } else {
                     $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_common_artifact', 'unable_canned'));
                     $GLOBALS['Response']->addFeedback('error', db_error());
            }
        }

        if ($comment != '') {
            $this->addHistory('comment', '', htmlspecialchars($comment), $comment_type_id, false, false, $comment_format);
            $changes['comment']['add']    = $comment;
            $changes['comment']['format'] = $comment_format;

            $field = $art_field_fact->getFieldFromName("comment_type_id");
            if ($field && isset($comment_type_id) && $comment_type_id) {
                     $changes['comment']['type'] =
                       $field->getValue($this->ArtifactType->getID(), $comment_type_id);
            }
            $reference_manager = $this->getReferenceManager();
            $reference_manager->extractCrossRef($comment, $this->getID(), ReferenceManager::REFERENCE_NATURE_ARTIFACT, $this->ArtifactType->getGroupID());

            return true;
        } else {
            return false;
        }
    }

    /**
     * Wrapper for tests
     *
     * @return ReferenceManager
     */
    public function getReferenceManager()
    {
        return ReferenceManager::instance();
    }

    /**
     *  Add a list of follow-up comments coming from the import facility
     *
     * @param parsed_comments (IN): an array (#detail => array2), where array2 is of the form
     *                              ("date" => date, "by" => user, "type" => comment-type, "comment" => comment-string)
     *  @return bool
     */
    public function addFollowUpComments($parsed_comments)
    {
        global $Language;

        $art_field_fact  = new ArtifactFieldFactory($this->ArtifactType);
        $artifact_import = new ArtifactImport($this->ArtifactType, $art_field_fact, $this->ArtifactType->Group);

        foreach ($parsed_comments as $arr) {
            $by = $arr['by'];
            if ($by == "100") {
             //this case should not exist in new trackers but
                //can appear if we parse legacy bugs or tasks
                $email   = $Language->getText('global', 'none');
                $user_id = 100;
            } elseif (user_getname($by)) {
                $user_id = $by;
                $email   = "";
            } else {
                $email   = $by;
                $user_id = 100;
            }

            if (! $artifact_import->checkCommentExist($arr, $this->getID())) {
                if (! $artifact_import->checkCommentExistInLegacyFormat($arr, $this->getID())) {
                    $comment = htmlspecialchars($arr['comment']);
                    $sql     = "insert into artifact_history(artifact_id,field_name,old_value,new_value,mod_by,email,date,type) " .
                    "VALUES (" . db_ei($this->getID()) . ",'comment','','" . db_es($comment) . "','" . db_ei($user_id) . "','" . db_es($email) . "','" . db_ei($arr['date']) . "','" . db_ei($arr['type']) . "')";

                    db_query($sql);
                }
            }
        }

        return true;
    }

    /**
    * Update a follow-up comment
    *
    * @param comment_id: follow-up comment id
    * @param comment_txt: text of the follow-up comment
    *
    * @return bool
    */
    public function updateFollowupComment($comment_id, $comment_txt, &$changes, $comment_format = self::FORMAT_TEXT)
    {
        if ($this->userCanEditFollowupComment($comment_id)) {
            $sql             = 'SELECT field_name, new_value, type FROM artifact_history'
                . ' WHERE artifact_id=' . db_ei($this->getID())
                . ' AND artifact_history_id=' . db_ei($comment_id)
                . ' AND (field_name="comment" OR field_name LIKE "lbl_%_comment")';
            $qry             = db_query($sql);
            $new_value       = db_result($qry, 0, 'new_value');
            $comment_type_id = db_result($qry, 0, 'type');
            if ($new_value == $comment_txt) {
                //comment doesn't change
                return false;
            }

            if ($qry) {
                $fname = db_result($qry, 0, 'field_name');
                if (preg_match("/^(lbl_)/", $fname) && preg_match("/(_comment)$/", $fname)) {
                    $comment_lbl = $fname;
                } else {
                    $comment_lbl = "lbl_" . $comment_id . "_comment";
                }
                //now add new comment entry
                $this->addHistory($comment_lbl, $new_value, htmlspecialchars($comment_txt), $comment_type_id, false, $comment_id, $comment_format);
                $changes['comment']['del']    = $new_value;
                $changes['comment']['add']    = $comment_txt;
                $changes['comment']['format'] = $comment_format;
                $reference_manager            = $this->getReferenceManager();
                $reference_manager->extractCrossRef($comment_txt, $this->getID(), ReferenceManager::REFERENCE_NATURE_ARTIFACT, $this->ArtifactType->getGroupID());

                return true;
            } else {
                return false;
            }
        } else {
            $this->setError($GLOBALS['Language']->getText('tracker_common_artifact', 'err_upd_comment', [$comment_id, $GLOBALS['Language']->getText('include_exit', 'perm_denied')]));
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('tracker_common_artifact', 'err_upd_comment', [$comment_id, $GLOBALS['Language']->getText('include_exit', 'perm_denied')]));
            return false;
        }
    }

    /**
     *  Update an artifact. Rk: vfl is an variable list of fields, Vary from one project to another
     *  return true if artifact updated, false if nothing changed or DB update failed
     *
     * @param artifact_id_dependent: artifact dependencies
     * @param canned_response: canned responses
     * @param changes (OUT): array of changes (for notifications)
     *
     *  @return bool
     */
    public function handleUpdate($artifact_id_dependent, $canned_response, &$changes, $masschange = false, $vfl = false, $import = false)
    {
        global $art_field_fact,$Language;
        if ($masschange && ! $this->ArtifactType->userIsAdmin()) {
            exit_permission_denied();
        }

        if (! $import) {
         // Retrieve HTTP GET variables and store them in $vfl array
            $vfl = $art_field_fact->extractFieldList();

            // make sure  required fields are not empty
            if (($art_field_fact->checkEmptyFields($vfl) == false)) {
                    exit_missing_param();
            }
        }

        //get this artifact from the db
        $result = $this->getFieldsValues();

        //  See which fields changed during the modification
        //  and if we must keep history then do it. Also add them to the update
        //  statement
        $reference_manager = ReferenceManager::instance();
        $text_value_list   = [];
        $changes           = [];
        $upd_list          = '';
        foreach ($vfl as $field_name => $value) {
            $field = $art_field_fact->getFieldFromName($field_name);

            // skip over special fields  except for details which in this
            // particular case can be processed normally
            if ($field->isSpecial()) {
                continue;
            }

            if ($field->isInt() && $value == '' && $field->getRequired() == 0) {
                $value = 0;
            }
            // we check if the given value is authorized for this field (for select box fields only)
            // we don't check here the none value, we have already check it before (we can't check here the none value because the function checkValueInPredefinedValues don't take the none value into account)
            // if the value did not change, we don't do the check (because of stored values that can be deleted now)
            if (! $masschange && $result[$field_name] != $value && $field->isSelectBox() && $value != 100 && ! $field->checkValueInPredefinedValues($this->ArtifactType->getID(), $value)) {
                $this->setError($Language->getText('tracker_common_artifact', 'bad_field_value', [$field->getLabel(), $value]));
                return false;
            }
            if (! $masschange && $field->isMultiSelectBox()) {
                if (is_array($value)) {
                    foreach ($value as $a_value) {
                        if ($a_value != 100 && ! $field->checkValueInPredefinedValues($this->ArtifactType->getID(), $a_value)) {
                            $this->setError($Language->getText('tracker_common_artifact', 'bad_field_value', [$field->getLabel(), $value]));
                            return false;
                        }
                    }
                }
            }

            $is_text = ($field->isTextField() || $field->isTextArea());
            if (($field->isMultiSelectBox()) && (is_array($value))) {
                if ($masschange && (in_array($Language->getText('global', 'unchanged'), $value))) {
                    continue;
                }
                // The field is a multi values field and it has multi assigned values
                $values = $value;

                // check if the user can update the field or not
                if (! $field->userCanUpdate($this->ArtifactType->getGroupID(), $this->ArtifactType->getID(), UserManager::instance()->getCurrentUser()->getId())) {
                    // we only throw an error if the values has changed
                    $old_values                         = $field->getValues($this->getID());
                    list($deleted_values,$added_values) = util_double_diff_array($old_values, $values);
                    if ((count($deleted_values) > 0) || (count($added_values) > 0)) {
                        // The user does not have the permissions to update the current field,
                        // we exit the function with an error message
                        $this->setError($Language->getText('tracker_common_artifact', 'bad_field_permission_update', $field->getLabel()));
                           return false;
                    }
                }

                //don't take into account the none value if there are several values selected
                if (count($values) > 1) {
                    $temp = [];
                    foreach ($values as $i => $v) {
                        if ($v == 100) {
                            unset($values[$i]);
                            $unset = true;
                        } else {
                            $temp[] = $v;
                        }
                    }
                    if (isset($unset) && $unset) {
                        $values = $temp;
                    }
                }

                $old_values = $field->getValues($this->getID());

                list($deleted_values,$added_values) = util_double_diff_array($old_values, $values);

                // Check if there are some differences
                if ((count($deleted_values) > 0) || (count($added_values) > 0)) {
                    // Add values in the history
                    $a       = $field->getLabelValues($this->ArtifactType->getID(), $old_values);
                    $val     = join(",", $a);
                    $b       = $field->getLabelValues($this->ArtifactType->getID(), $values);
                    $new_val = join(",", $b);
                    $this->addHistory($field, $val, $new_val);

                    // Update the field value
                    if (! $field->updateValues($this->getID(), $values)) {
                        $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_common_artifact', 'field_upd_fail', $field->getLabel()));
                    }
                    if ($is_text) {
                        //Log for Cross references
                        $text_value_list[] = $values;
                    }

                    // Keep track of the change
                    $field_html = new ArtifactFieldHtml($field);
                    if (count($deleted_values) > 0) {
                        $val                         = join(",", $field->getLabelValues($this->ArtifactType->getID(), $deleted_values));
                        $changes[$field_name]['del'] = $val;
                    }
                    if (count($added_values) > 0) {
                        $val                         = join(",", $field->getLabelValues($this->ArtifactType->getID(), $added_values));
                        $changes[$field_name]['add'] = $val;
                    }
                }
            } else {
                if ($masschange && ($value == $Language->getText('global', 'unchanged'))) {
                    continue;
                }

                $old_value = $result[$field_name];
                if ($is_text) {
                    $differ = ($old_value != htmlspecialchars((string) $value));
                    //Log for Cross references
                    $text_value_list[] = $value;
                } elseif ($field->isDateField()) {
                    // if it's a date we must convert the format to unix time
                    if ($value != '') {
                        list($value,$ok) = util_date_to_unixtime($value);
                    } else {
                        $value = '0';
                    }

                    //first have a look if both dates are uninitialized
                    if (($old_value == 0 || $old_value == '') && ($value == 0 || ! $ok )) {
                        $differ = false;
                    } else {
                        // and make also sure that the old_value has been treated as the new value
                        // i.e. old_value (unix timestamp) -> local date (with hours cut off, so change the date by x  hours) -> unixtime
                        $old_date           = format_date("Y-m-j", $old_value);
                        list($old_val,$ok)  = util_date_to_unixtime($old_date);
                                    $differ = ($old_val != $value);
                    }
                } else {
                    $differ = ($old_value != $value);
                }
                if ($differ) {
                    // The userCanUpdate test is only done on modified fields
                    if ($field->userCanUpdate($this->ArtifactType->getGroupID(), $this->ArtifactType->getID())) {
                        $update_value = '';
                        if ($is_text) {
                            if ($field->isStandardField()) {
                                $upd_list .= "$field_name='" . db_es(htmlspecialchars((string) $value)) . "',";
                            } else {
                                $update_value = htmlspecialchars((string) $value);
                            }

                            $this->addHistory($field, $old_value, $value);
                            $value = stripslashes((string) $value);
                        } else {
                            if ($field->isStandardField()) {
                                $upd_list .= "$field_name='" . db_es($value) . "',";
                            } else {
                                $update_value = $value;
                            }
                            $this->addHistory($field, $old_value, $value);
                        }

                        // Update the field value
                        if (! $field->isStandardField()) {
                            if (! $field->updateValue($this->getID(), $update_value)) {
                                $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_common_artifact', 'field_upd_fail', $field->getLabel()));
                            }
                        }

                        // Keep track of the change
                        $field_html                  = new ArtifactFieldHtml($field);
                        $changes[$field_name]['del'] = $field_html->display($this->ArtifactType->getID(), $old_value, false, false, true, true);
                        $changes[$field_name]['add'] = $field_html->display($this->ArtifactType->getID(), $value, false, false, true, true);
                    } else {
                        // The user does not have the permissions to update the current field,
                        // we exit the function with an error message
                        $this->setError($Language->getText('tracker_common_artifact', 'bad_field_permission_update', $field->getLabel()));
                        return false;
                    }
                }
            }
        } // while

        for ($i = 0; $i < sizeof($text_value_list); $i++) {
            $html = '';
            if (! is_array($text_value_list[$i])) {
                $html = (string) $text_value_list[$i];
            }
            $reference_manager->extractCrossRef($html, $this->getID(), ReferenceManager::REFERENCE_NATURE_ARTIFACT, $this->ArtifactType->getGroupID());
        }

        $request = HTTPRequest::instance();
        //for masschange look at the special case of changing the submitted_by param
        if ($masschange) {
            foreach ($_POST as $key => $val) {
                $val = $request->get($key); //Don't use _POST value
                if ($key == 'submitted_by' && $val != $Language->getText('global', 'unchanged')) {
                    $sql   = "UPDATE artifact SET submitted_by=" . db_ei($val) . " WHERE artifact_id = " . db_ei($this->getID());
                    $res   = db_query($sql);
                    $field = $art_field_fact->getFieldFromName('submitted_by');
                    if ($this->getSubmittedBy() != $val) {
                        $this->addHistory('submitted_by', $this->getSubmittedBy(), $val);
                    }
                }
            }
        }

        // Comment field history is handled a little differently. Followup comments
        // are added in the bug history along with the comment type.
        //
        // If a canned response is given it overrides anything typed in the followup
        // comment text area.
        $comment         = $request->get('comment');
        $comment_type_id = array_key_exists('comment_type_id', $vfl) ? $vfl['comment_type_id'] : '';
        $vFormat         = new Valid_WhiteList('comment_format', [self::FORMAT_HTML, self::FORMAT_TEXT]);
        $comment_format  = $request->getValidated('comment_format', $vFormat, self::FORMAT_TEXT);

        $this->addFollowUpComment($comment, $comment_type_id, $canned_response, $changes, $comment_format);

        //  Enter the timestamp if we are changing to closed or declined
        if (isset($changes['status_id']) && $this->isStatusClosed($vfl['status_id'])) {
            $now       = time();
            $upd_list .= "close_date='$now',";
            $field     = $art_field_fact->getFieldFromName('close_date');
            if ($field) {
                $this->addHistory($field, $result['close_date'], '');
            }
        }

        //  Reset the timestamp if we are changing from closed or declined
        if (isset($changes['status_id']) && ! $this->isStatusClosed($vfl['status_id'])) {
            $upd_list .= "close_date='',";
            $field     = $art_field_fact->getFieldFromName('close_date');
            if ($field) {
                $this->addHistory($field, $result['close_date'], '');
            }
        }

        //  Insert the list of dependencies
        if ($import && $artifact_id_dependent) {
            if (! $this->deleteAllDependencies()) {
                return false;
            }
            if ($artifact_id_dependent == $Language->getText('global', 'none')) {
                unset($artifact_id_dependent);
            }
        }
        if (isset($artifact_id_dependent)) {
            if (! $this->addDependencies($artifact_id_dependent, $changes, $masschange, $import)) {
                return false;
            }
        }

        //  Finally, build the full SQL query and update the artifact itself (if need be)
        $res_upd = true;
        if ($upd_list) {
            // strip the excess comma at the end of the update field list
            $upd_list = substr($upd_list, 0, -1);

            $sql = "UPDATE artifact SET $upd_list " .
                " WHERE artifact_id=" . db_ei($this->getID());

            $res_upd = db_query($sql);
        }

        if (! $res_upd) {
            exit_error($Language->getText('tracker_common_artifact', 'upd_fail') . ': ' . ($sql ?? ''), $Language->getText('tracker_common_artifact', 'upd_fail'));
        } else {
            if (! $request->exist('change_permissions') || $request->get('change_permissions')) {
                $this->setPermissions($request->get('use_artifact_permissions_name'), $request->get('ugroups'));
            }
            return true;
        }
    }

    /**
     * Set the permissions
     */
    public function setPermissions($use_artifact_permissions, $ugroups)
    {
        if ($this->ArtifactType->userIsAdmin()) {
            if ($use_artifact_permissions) {
                /** @psalm-suppress DeprecatedFunction */
                $result = permission_process_selection_form($this->ArtifactType->getGroupID(), 'TRACKER_ARTIFACT_ACCESS', $this->getId(), $ugroups);
                if (! $result[0]) {
                    return $GLOBALS['Response']->addFeedback('error', $result[1]);
                }
                //If the selected ugroup corresponds to the default one (all_user), there is no need to store it
                if ($ugroups[0] == $GLOBALS['UGROUP_ANONYMOUS']) {
                    $use_artifact_permissions = 0;
                }
            }
            $sql = "UPDATE artifact
                    SET use_artifact_permissions = " . ($use_artifact_permissions ? 1 : 0) . "
                    WHERE artifact_id=" . db_ei($this->getID());
            db_query($sql);
        }
    }

    /**
     * Check if an email address already exists
     *
     * @param cc: the email address
     *
     * @return bool
     */
    public function existCC($cc)
    {
        $sql = "SELECT artifact_cc_id FROM artifact_cc WHERE artifact_id=" . db_ei($this->getID()) . " AND email='" . db_es($cc) . "'";
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
     * @return bool
     */
    public function insertCC($cc, $added_by, $comment, $date)
    {
        $sql = "INSERT INTO artifact_cc (artifact_id,email,added_by,comment,date) " .
            "VALUES (" . db_ei($this->getID()) . ",'" . db_es($cc) . "','" . db_ei($added_by) . "','" . db_es($comment) . "','" . db_ei($date) . "')";
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
     * @return bool
     */
    public function addCC($email, $comment, &$changes, $masschange = false)
    {
        global $Language;

        $user_id = (user_isloggedin() ? UserManager::instance()->getCurrentUser()->getId() : 100);

        $arr_email = util_split_emails($email);
        $date      = time();
        $ok        = true;
        $changed   = false;

        if (! util_validateCCList($arr_email, $message)) {
            exit_error($Language->getText('tracker_index', 'cc_list_invalid'), $message);
        }

    //calculate old_values to put into artifact_history
        $old_value = $this->getCCEmails();
        foreach ($arr_email as $cc) {
            // Add this cc only if not there already
            if (! $this->existCC($cc)) {
                $changed = true;
                $res     = $this->insertCC($cc, $user_id, $comment, $date);
                if (! $res) {
                    $ok = false;
                }
            }
        }

        if ($old_value == '') {
            $new_value = join(',', $arr_email);
        } else {
            $new_value = $old_value . "," . join(',', $arr_email);
        }

        if (! $ok) {
            $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_common_artifact', 'cc_add_fail'));
        } else {
            $this->addHistory('cc', $old_value, $new_value);
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
     * @return bool
     */
    public function updateCC($email, $comment)
    {
        global $Language;

        $user_id = (user_isloggedin() ? UserManager::instance()->getCurrentUser()->getId() : 100);

        $arr_email = util_split_emails($email);
        $date      = time();
        $ok        = true;
        $changed   = false;

        if (! util_validateCCList($arr_email, $message)) {
            exit_error($Language->getText('tracker_index', 'cc_list_invalid'), $message);
        }

    //calculate old_values to put into artifact_history
        $old_value = $this->getCCEmails();
        $new_value = join(',', $arr_email);

    //look if there is really something to do or not
        list($deleted_values,$added_values) = util_double_diff_array(explode(",", $old_value), $arr_email);
        if (count($deleted_values) == 0 && count($added_values) == 0) {
            return true;
        }

        if (! $this->deleteAllCC()) {
            $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_common_artifact', 'prob_cc_list', $this->getID()));
            $ok = false;
        }

        foreach ($arr_email as $cc) {
                $changed = true;
                $res     = $this->insertCC($cc, $user_id, $comment, $date);
            if (! $res) {
                $ok = false;
            }
        }

        if (! $ok) {
            $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_common_artifact', 'cc_add_fail'));
        } else {
            $this->addHistory('cc', $old_value, $new_value);
        }
        return $ok;
    }

    /**
     * Delete an email address in the CC list
     *
     * @param artifact_cc_id: cc list id
     * @param changes (OUT): list of changes
     *
     * @return bool
     */
    public function deleteCC($artifact_cc_id, &$changes, $masschange = false)
    {
        global $Language;

        // If both bug_id and bug_cc_id are given make sure the cc belongs
        // to this bug (it's a bit paranoid but...)
        $sql  = "SELECT artifact_id,email from artifact_cc WHERE artifact_cc_id='" . db_ei($artifact_cc_id) . "'";
        $res1 = db_query($sql);
        if ((db_numrows($res1) <= 0) || (db_result($res1, 0, 'artifact_id') != $this->getID())) {
            $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_common_artifact', 'err_cc_id', $artifact_cc_id));
            return false;
        }

    //calculate old_values to put into artifact_history
        $old_value = $this->getCCEmails();

    // Now delete the CC address
        $res2 = db_query("DELETE FROM artifact_cc WHERE artifact_cc_id='" . db_ei($artifact_cc_id) . "'");
        if (! $res2) {
            $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_common_artifact', 'err_del_cc', [$artifact_cc_id, db_error($res2)]));
            return false;
        } else {
            if (! $masschange) {
                $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_common_artifact', 'cc_remove'));
            }
            $new_value = $this->getCCEmails();
            $this->addHistory('cc', $old_value, $new_value);
            $changes['CC']['del'] = db_result($res1, 0, 'email');
            return true;
        }
    }

    /**
     * Check if an artifact depends already from the current one
     *
     * @param id: the artifact id
     *
     * @return bool
     */
    public function existDependency($id)
    {
        $sql = "SELECT is_dependent_on_artifact_id FROM artifact_dependencies WHERE artifact_id=" . db_ei($this->getID()) . " AND is_dependent_on_artifact_id=" . db_ei($id);
        //echo $sql;
        $res = db_query($sql);
        return (db_numrows($res) >= 1);
    }

    /**
     * Check if an artifact exists
     *
     * @param id: the artifact id
     *
     * @return bool
     */
    public function validArtifact($id)
    {
        $sql = "SELECT * FROM artifact a, artifact_group_list agl WHERE " .
            "a.group_artifact_id = agl.group_artifact_id AND a.artifact_id=" . db_ei($id) . " AND " .
            "agl.status = 'A'";
        $res = db_query($sql);
        if (db_numrows($res) >= 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Insert a artifact dependency with the current one
     *
     * @param id: the artifact id
     *
     * @return bool
     */
    public function insertDependency($id)
    {
        $sql = "INSERT INTO artifact_dependencies (artifact_id,is_dependent_on_artifact_id) " .
            "VALUES (" . db_ei($this->getID()) . "," . db_ei($id) . ")";
        //echo $sql;
        $res = db_query($sql);
        return ($res);
    }

    /**
     * Delete all the CC Names of this Artifact
     */
    public function deleteAllCC()
    {
        $sql = "SELECT artifact_cc_id FROM artifact_cc WHERE artifact_id=" . db_ei($this->getID());
        $res = db_query($sql);
        if (db_numrows($res) > 0) {
            for ($i = 0; $i < db_numrows($res); $i++) {
                if ($i == 0) {
                    $ccNames = db_result($res, $i, 'artifact_cc_id');
                } else {
                    $ccNames .= "," . db_result($res, $i, 'artifact_cc_id');
                }
            }
            $sql     = "DELETE FROM artifact_cc WHERE artifact_cc_id IN (" . db_es($ccNames) . ") AND artifact_id=" . db_ei($this->getID());
            $res_del = db_query($sql);
            if (! $res_del) {
                return false;
            }
        }
        return true;
    }

     /**
      * Delete all the dependencies of this Artifact
      */
    public function deleteAllDependencies()
    {
        $sql = "SELECT is_dependent_on_artifact_id FROM artifact_dependencies WHERE artifact_id=" . db_ei($this->getID());
        $res = db_query($sql);
        if (db_numrows($res) > 0) {
            for ($i = 0; $i < db_numrows($res); $i++) {
                if ($i == 0) {
                    $dependencies = db_result($res, $i, 'is_dependent_on_artifact_id');
                } else {
                    $dependencies .= "," . db_result($res, $i, 'is_dependent_on_artifact_id');
                }
            }
            $sql     = "DELETE FROM artifact_dependencies WHERE is_dependent_on_artifact_id IN (" . db_es($dependencies) . ") AND artifact_id=" . db_ei($this->getID());
            $res_del = db_query($sql);
            if (! $res_del) {
                return false;
            }
        }
        return true;
    }

    /**
     * Insert artifact dependencies
     *
     * @param artifact_id_dependent: list of artifact which are depend on (comma sperator)
     * @param changes (OUT): list of changes
     *
     * @return bool
     */
    public function addDependencies($artifact_id_dependent, &$changes, $masschange, $import = false)
    {
        if (! $artifact_id_dependent) {
            return true;
        }

        $ok  = true;
        $ids = explode(",", $artifact_id_dependent);
        foreach ($ids as $id) {
            // Add this id only if not already exist
            //echo "add id=".$id."<br>";

            // Remove potential spaces (if the list of IDs are entered like that : 171, 765, 555)
            $id = trim($id);

            // Check existance
            if (! $this->validArtifact($id)) {
                // at import stage, $id can have value "None" or "Aucun"
                if (! $import || $id != $GLOBALS['Language']->getText('global', 'none')) {
                    $ok = false;
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('tracker_common_artifact', 'invalid_art', $id));
                }
            }
            if ($ok && ($id != $this->getID()) && ! $this->existDependency($id)) {
                $res = $this->insertDependency($id);
                if (! $res) {
                    $ok = false;
                }
            }
        }

        if (! $ok) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('tracker_common_artifact', 'depend_add_fail', $this->getID()));
        } else {
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
     * @return bool
     */
    public function deleteDependency($dependent_on_artifact_id, &$changes)
    {
        global $Language;

        // Delete the dependency
        $sql  = "DELETE FROM artifact_dependencies WHERE is_dependent_on_artifact_id=" . db_ei($dependent_on_artifact_id) . " AND artifact_id=" . db_ei($this->getID());
        $res2 = db_query($sql);
        if (! $res2) {
            $GLOBALS['Response']->addFeedback('error', " - Error deleting dependency $dependent_on_artifact_id: " . db_error());
            return false;
        } else {
            $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_common_artifact', 'depend_removed'));
            $changes['Dependencies']['del'] = $dependent_on_artifact_id;
            return true;
        }
    }

    /**
     * Delete a followup comment from the artifact
     *
     * @param aid: the artifact id
     * @param comment_id: the followup comment id
     *
     * @return bool
     */
    public function deleteFollowupComment($aid, $comment_id)
    {
        if ($this->userCanEditFollowupComment($comment_id)) {
            //Delete the followup comment
            $sel       = 'SELECT field_name, new_value FROM artifact_history'
                . ' WHERE (field_name = "comment" OR field_name LIKE "lbl_%_comment")'
                . ' AND artifact_history_id = ' . db_ei($comment_id)
                . ' AND artifact_id = ' . db_ei($aid);
            $res       = db_query($sel);
            $new_value = db_result($res, 0, 'new_value');
            if ($res) {
                $fname = db_result($res, 0, 'field_name');
                if (preg_match("/^(lbl_)/", $fname) && preg_match("/(_comment)$/", $fname)) {
                    $comment_lbl = $fname;
                } else {
                    $comment_lbl = "lbl_" . $comment_id . "_comment";
                }
                //now add a new history entry
                $this->addHistory($comment_lbl, $new_value, '', false, false, $comment_id);
                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('tracker_common_artifact', 'comment_removed'));
                return true;
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('tracker_common_artifact', 'err_del_comment', [$comment_id, db_error($res)]));
                return false;
            }
        } else {
            $this->setError($GLOBALS['Language']->getText('tracker_common_artifact', 'err_del_comment', [$comment_id, $GLOBALS['Language']->getText('include_exit', 'perm_denied')]));
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('tracker_common_artifact', 'err_del_comment', [$comment_id, $GLOBALS['Language']->getText('include_exit', 'perm_denied')]));
            return false;
        }
    }

    /**
     * Return if the status is closed status
     *
     * @param status: the status
     *
     * @return bool
     */
    public function isStatusClosed($status)
    {
        return (($status == '3') || ($status == '10') );
    }

    /**
     * get all the field values for this artifact
     *
     * @return array
     */
    public function getFieldsValues()
    {
        // get the artifact data
        $this->fetchData($this->getID());
        return $this->data_array;
    }

    /**
     * Return the user (user_id) that have posted follow up (comment_id)
     *
     * @return int
     */
    public function getCommenter($comment_id)
    {
        $sql = 'SELECT mod_by FROM artifact_history'
        . ' WHERE artifact_id=' . db_ei($this->getID())
        . ' AND field_name="comment"'
        . ' AND mod_by != 100'
        . ' AND artifact_history_id=' . db_ei($comment_id);
        $res = db_query($sql);
        return db_result($res, 0, 'mod_by');
    }

    /**
     * Return the users that have posted follow ups
     *
     * @return array
     */
    public function getCommenters()
    {
        $sql = "SELECT DISTINCT mod_by FROM artifact_history " .
        "WHERE artifact_id=" . db_ei($this->getID()) . " " .
            "AND field_name = 'comment' AND mod_by != 100";
        return db_query($sql);
    }

    /**
     * Return the mails of anonymous users that have posted follow ups
     *
     * @return array
     */
    public function getAnonymousCommenters()
    {
        $sql = "SELECT DISTINCT email FROM artifact_history " .
        "WHERE artifact_id=" . db_ei($this->getID()) . " " .
            "AND field_name = 'comment' " .
        "AND mod_by = 100";
        return db_query($sql);
    }

    /**
     * Get follow-up comment text
     *
     * @param int $comment_id
     *
     * @return String
     */
    public function getFollowup($comment_id)
    {
        $res = $this->getFollowUpDetails($comment_id);
        return $res['new_value'];
    }

    /**
     * Get all details of a follow-up comment
     *
     * @param int $comment_id
     *
     * @return Array
     */
    public function getFollowUpDetails($comment_id)
    {
        $sql = 'SELECT * FROM artifact_history'
        . ' WHERE (field_name="comment" OR field_name LIKE "lbl_%_comment")'
        . ' AND artifact_history_id=' . db_ei($comment_id)
        . ' AND new_value <> ""';
        $res = db_query($sql);
        if ($res && ! db_error($res) && db_numrows($res) == 1) {
            return db_fetch_array($res);
        }
        return false;
    }

    /**
     * Return the follow ups
     *
     * @return array
     */
    public function getFollowups()
    {
        global $art_field_fact;

        $flup_array = [];
        $qry        = 'SELECT artifact_history_id, date FROM artifact_history' .
        ' WHERE artifact_id = ' . db_ei($this->getID()) .
        ' AND field_name = "comment"';
        $res        = db_query($qry);
        while ($row = db_fetch_array($res)) {
            $ahid   = $row['artifact_history_id'];
            $fname  = "lbl_" . $ahid . "_comment";
            $sel    = 'SELECT NULL FROM artifact_history' .
             ' WHERE field_name = "' . db_es($fname) . '"' .
             ' AND artifact_id = ' . db_ei($this->getID());
            $result = db_query($sel);
            if (db_numrows($result) < 1) {
             //the followup comment was not edited/removed ==> add it to the list of comments to be displayed
                $flup_array[$ahid] = $row['date'];
            } else {
             //pick the latest
                $latest     = 'SELECT artifact_history_id , new_value FROM artifact_history' .
                  ' WHERE field_name = "' . db_es($fname) . '"' .
                  ' AND artifact_id = ' . db_ei($this->getID()) .
                  ' AND date = (SELECT MAX(date) FROM artifact_history' .
                  '             WHERE field_name = "' . db_es($fname) . '"' .
                  '             AND artifact_id = ' . db_ei($this->getID()) . ')';
                $res_latest = db_query($latest);
                $new_value  = db_result($res_latest, 0, 'new_value');
                if ($new_value <> '') {
                    //if new_value eq '' ==> the followup comment was removed, don't display it
                    $art_hist_id              = db_result($res_latest, 0, 'artifact_history_id');
                    $flup_array[$art_hist_id] = $row['date'];
                }
            }
        }
        arsort($flup_array);
        $comment_array = array_keys($flup_array);

        $field = $art_field_fact->getFieldFromName('comment_type_id');
        if ($field) {
         // Look for project specific values first
            $sql       = "SELECT DISTINCT artifact_history.artifact_history_id, artifact_history.format, artifact_history.artifact_id,artifact_history.field_name,artifact_history.old_value,artifact_history.new_value,artifact_history.date,user.user_name,artifact_history.mod_by,artifact_history.email,artifact_history.type AS comment_type_id,artifact_field_value_list.value AS comment_type " .
            "FROM artifact_history,artifact_field_value_list,artifact_field,user " .
            "WHERE artifact_history.artifact_id=" . db_ei($this->getID()) . " " .
            "AND (artifact_history.field_name = 'comment' OR artifact_history.field_name LIKE 'lbl_%_comment') " .
            "AND artifact_history.mod_by=user.user_id " .
            "AND artifact_history.type = artifact_field_value_list.value_id " .
            "AND artifact_history.artifact_history_id IN (" . db_es(implode(',', $comment_array)) . ") " .
            "AND artifact_field_value_list.field_id = artifact_field.field_id " .
            "AND artifact_field_value_list.group_artifact_id = artifact_field.group_artifact_id " .
            "AND artifact_field.group_artifact_id =" . db_ei($this->ArtifactType->getID()) . " " .
            "AND artifact_field.field_name = 'comment_type_id' " .
            "ORDER BY FIELD(artifact_history_id, " . db_es(implode(',', $comment_array)) . ")";
            $res_value = db_query($sql);
            $rows      = db_numrows($res_value);

         //echo "sql=".$sql." - rows=".$rows."<br>";
        } else {
         // Look for project specific values first
            $sql       = "SELECT DISTINCT artifact_history.artifact_history_id, artifact_history.format, artifact_history.artifact_id,artifact_history.field_name,artifact_history.old_value,artifact_history.new_value,artifact_history.date,user.user_name,artifact_history.mod_by,artifact_history.email,artifact_history.type AS comment_type_id,null AS comment_type " .
            "FROM artifact_history,user " .
            "WHERE artifact_history.artifact_id=" . $this->getID() . " " .
            "AND (artifact_history.field_name = 'comment' OR artifact_history.field_name LIKE 'lbl_%_comment') " .
            "AND artifact_history.mod_by=user.user_id " .
            "AND artifact_history.artifact_history_id IN (" . db_es(implode(',', $comment_array)) . ") " .
            "ORDER BY FIELD(artifact_history_id, " . db_es(implode(',', $comment_array)) . ")";
            $res_value = db_query($sql);
            $rows      = db_numrows($res_value);
        }
        return($res_value);
    }

    /**
     * Return the history events for this artifact (excluded comment events - See followups)
     *
     * @return array
     */
    public function getHistory()
    {
        //Addition of new followup comments is not recorded in history (update and removal of followups is recorded)
        $sql = "SELECT artifact_history.field_name,artifact_history.old_value,artifact_history.new_value,artifact_history.date,artifact_history.type,user.user_name " .
            "FROM artifact_history,user " .
            "WHERE artifact_history.mod_by=user.user_id " .
            "AND artifact_id=" . db_ei($this->getID()) .
            " AND artifact_history.field_name <> 'comment' " .
        "ORDER BY artifact_history.date DESC";
        return db_query($sql);
    }

    /**
     * Return the CC list values
     *
     * @return array
     */
    public function getCCList()
    {
        $sql = "SELECT artifact_cc_id,artifact_cc.email,artifact_cc.added_by,artifact_cc.comment,artifact_cc.date,user.user_name " .
            "FROM artifact_cc,user " .
            "WHERE added_by=user.user_id " .
            "AND artifact_id=" . db_ei($this->getID()) . " ORDER BY date DESC";
        return db_query($sql);
    }

    /**
     * Return the user ids of registered users in the CC list
     *
     * @return array
     */
    public function getCCIdList()
    {
        $sql = "SELECT u.user_id " .
        "FROM artifact_cc cc, user u " .
        "WHERE cc.email = u.user_name " .
        "AND cc.artifact_id=" . db_ei($this->getID());
        $res = db_query($sql);

        return util_result_column_to_array($res);
    }

    /**
     * Return the CC list emails only
     *
     * @return string
     */
    public function getCCEmails()
    {
        $sql    = "SELECT email " .
            "FROM artifact_cc " .
            "WHERE artifact_id=" . db_ei($this->getID()) . " ORDER BY date DESC";
        $result = db_query($sql);
        $rows   = db_numrows($result);
        if ($rows <= 0) {
            return '';
        } else {
            $email_arr = [];
            for ($i = 0; $i < $rows; $i++) {
                $email_arr[] = db_result($result, $i, 'email');
            }
            $old_value = join(",", $email_arr);
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
    public function getCC($artifact_cc_id)
    {
        $sql = "SELECT artifact_cc_id,artifact_cc.email,artifact_cc.added_by,artifact_cc.comment,artifact_cc.date,user.user_name " .
            "FROM artifact_cc,user " .
            "WHERE artifact_cc_id=" . db_ei($artifact_cc_id) . " " .
            "AND added_by=user.user_id";
        $res = db_query($sql);
        return db_fetch_array($res);
    }

    /**
     * Return the artifact dependencies values
     *
     * @return array
     */
    public function getDependencies()
    {
        $sql = "SELECT d.artifact_depend_id, d.is_dependent_on_artifact_id, d.artifact_id, a.summary, afvl.value as status, ag.group_artifact_id, ag.name, g.group_id, g.group_name " .
            "FROM artifact_dependencies d, artifact_group_list ag, `groups` g, artifact a, artifact_field_value_list afvl, artifact_field f " .
            "WHERE d.is_dependent_on_artifact_id = a.artifact_id AND " .
            "afvl.field_id = f.field_id AND " .
            "f.group_artifact_id = a.group_artifact_id AND " .
            "f.field_name = 'status_id' AND " .
            "afvl.value_id = a.status_id AND " .
            "afvl.group_artifact_id = a.group_artifact_id AND " .
            "a.group_artifact_id = ag.group_artifact_id AND " .
            "d.artifact_id = " . db_ei($this->getID()) . " AND " .
            "ag.group_id = g.group_id ORDER BY a.artifact_id";
        //echo "sql=$sql<br>";
        return db_query($sql);
    }

    /**
     * Return the artifact inverse dependencies values
     *
     * @return array
     */
    public function getInverseDependencies()
    {
        $sql = "SELECT d.artifact_depend_id, d.is_dependent_on_artifact_id, d.artifact_id, a.summary, afvl.value as status, ag.group_artifact_id, ag.name, g.group_id, g.group_name " .
            "FROM artifact_dependencies d, artifact_group_list ag, `groups` g, artifact a, artifact_field_value_list afvl, artifact_field f " .
            "WHERE d.artifact_id = a.artifact_id AND " .
            "afvl.field_id = f.field_id AND " .
            "f.group_artifact_id = a.group_artifact_id AND " .
            "f.field_name = 'status_id' AND " .
            "afvl.value_id = a.status_id AND " .
            "afvl.group_artifact_id = a.group_artifact_id AND " .
            "a.group_artifact_id = ag.group_artifact_id AND " .
            "d.is_dependent_on_artifact_id = " . db_ei($this->getID()) . " AND " .
            "ag.group_id = g.group_id ORDER BY a.artifact_id";
        //echo "sql=$sql<br>";
        return db_query($sql);
    }

    /**
     * Return the names of attached files
     *
     * @return string
     */
    public function getAttachedFileNames()
    {
        $sql    = "SELECT filename " .
            "FROM artifact_file " .
            "WHERE artifact_id=" . db_ei($this->getID()) . " ORDER BY adddate DESC";
        $result = db_query($sql);
        $rows   = db_numrows($result);
        if ($rows <= 0) {
            return '';
        } else {
            $name_arr = [];
            for ($i = 0; $i < $rows; $i++) {
                $name_arr[] = db_result($result, $i, 'filename');
            }
            $old_value = join(',', $name_arr);
            return $old_value;
        }
    }

    /**
     * Return the attached files
     *
     * @return array
     */
    public function getAttachedFiles()
    {
        $sql = "SELECT id,artifact_id,filename,filesize,filetype,description,bin_data,adddate,user.user_name " .
            "FROM artifact_file,user " .
            "WHERE submitted_by=user.user_id " .
            "AND artifact_id=" . db_ei($this->getID()) . " ORDER BY adddate DESC";
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
    public function getAttachedFile($id)
    {
        $sql = "SELECT id,filename,filesize,description,adddate,user.user_name " .
            "FROM artifact_file,user " .
            "WHERE submitted_by=user.user_id " .
            "AND id=" . db_ei($id);
        //echo "sql=$sql<br>";
        $res = db_query($sql);
        return db_fetch_array($res);
    }

    public function checkAssignees($field_name, $result, $art_field_fact, $changes, &$user_ids)
    {
        // check assignee  notification preferences
        // Never notify user 'none' (id #100)
        // Check for field 'assigned_to' (SelectBox)
    // assigned to can also be a multi_select_box
        $field = $art_field_fact->getFieldFromName($field_name);
        if ($field) {
            if ($field->getDisplayType() == "MB") {
                        $field_value = $field->getValues($this->getID());
                if ($field_value && (count($field_value) > 0)) {
                    $val_func = $field->getValueFunction();
                    if ($val_func[0] != "") {
                        foreach ($field_value as $user_id) {
                            if (($user_id) && ($user_id != 100)) {
                                $curr_assignee = UserManager::instance()->getUserById($user_id);
                                if (
                                    (! array_key_exists($user_id, $user_ids) || ! $user_ids[$user_id]) &&
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
       // we accept only names that correspond to codendi user names
                        foreach ($field_value as $value_id) {
                                        $user_name = $field->getValue($this->ArtifactType->getID(), $value_id);
                                        $res_u     = user_get_result_set_from_unix($user_name);
                                        $user_id   = db_result($res_u, 0, 'user_id');
                            if (($user_id) && ($user_id != 100)) {
                                $curr_assignee = UserManager::instance()->getUserById($user_id);
                                if (
                                    ! $user_ids[$user_id] &&
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
                   $user_id = isset($result[$field_name]) ? $result[$field_name] : null;
                $val_func   = $field->getValueFunction();
                if ($val_func[0] == "") {
          // we handle now also the case that the assigned_to field is NOT BOUND to a predefined value list
          // we accept only names that correspond to codendi user names
          // so: this user_id is not a user_id but a value_id
                    $user_name = $field->getValue($this->ArtifactType->getID(), $user_id);
                    $res       = user_get_result_set_from_unix($user_name);
                    $user_id   = db_result($res, 0, 'user_id');
                }
                if (($user_id) && ($user_id != 100)) {
                        $curr_assignee = UserManager::instance()->getUserById($user_id);
                    if (
                        (! array_key_exists($user_id, $user_ids) || ! $user_ids[$user_id]) &&
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

        // check old assignee  notification preferences if assignee was just changed
        // Never notify user 'none' (id #100)
        if (isset($changes[$field_name]) && isset($changes[$field_name]['del'])) {
            $user_name = $changes[$field_name]['del'];
        } else {
            unset($user_name);
        }
        if (isset($user_name) && $user_name) {
        //echo " verify deleted assigned_to - user_name=$user_name ";
            $del_arr = explode(",", $user_name);
            foreach ($del_arr as $uname) {
         //echo " uname=$uname ";
                    $res_oa       = user_get_result_set_from_unix($uname);
                    $user_id      = db_result($res_oa, 0, 'user_id');
                   $curr_assignee = UserManager::instance()->getUserById($user_id);
                if (
                    $user_id != 100 &&
                    ! isset($user_ids[$user_id]) &&
                    $this->ArtifactType->checkNotification($user_id, 'ASSIGNEE', $changes) &&
                    $this->userCanView($user_id) &&
                    $curr_assignee && (
                    $curr_assignee->isActive() || $curr_assignee->isRestricted())
                ) {
                    //echo "DBG - ASSIGNEE OLD - user=$user_id<br>";
                       $user_ids[$user_id] = true;
                }
            }
        }
    }

    /**
     *      userCanView - determine if the user can view this artifact.
     *
     *      @param $my_user_id    if not specified, use the current user id..
     *      @return bool user_can_view.
     */
    public function userCanView($my_user_id = 0)
    {
        if (! $my_user_id) {
            $u          = UserManager::instance()->getCurrentUser();
            $my_user_id = $u->getId();
        } else {
            $u = UserManager::instance()->getUserById($my_user_id);
        }
        // Super-user and Tracker admin have all rights to see even artfact that are restricted to all users
        if ($u !== null && ($u->isSuperUser() || $u->isTrackerAdmin($this->ArtifactType->getGroupID(), $this->ArtifactType->getID()))) {
            return true;
        }

        //Individual artifact permission
        $can_access = ! $this->useArtifactPermissions();
        if (! $can_access) {
            $res = permission_db_authorized_ugroups('TRACKER_ARTIFACT_ACCESS', $this->getID());
            if (db_numrows($res) > 0) {
                while ($row = db_fetch_array($res)) {
                    if (ugroup_user_is_member($my_user_id, $row['ugroup_id'], $this->ArtifactType->Group->getID(), $this->ArtifactType->getID())) {
                        $can_access = true;
                    }
                }
            }
        }
        if ($can_access) {
            // Full access
            $res = permission_db_authorized_ugroups('TRACKER_ACCESS_FULL', $this->ArtifactType->getID());
            if (db_numrows($res) > 0) {
                while ($row = db_fetch_array($res)) {
                    if (ugroup_user_is_member($my_user_id, $row['ugroup_id'], $this->ArtifactType->Group->getID(), $this->ArtifactType->getID())) {
                        return true;
                    }
                }
            }

            // 'submitter' access
            $res = permission_db_authorized_ugroups('TRACKER_ACCESS_SUBMITTER', $this->ArtifactType->getID());
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
            $res = permission_db_authorized_ugroups('TRACKER_ACCESS_ASSIGNEE', $this->ArtifactType->getID());
            if (db_numrows($res) > 0) {
                while ($row = db_fetch_array($res)) {
                    if (ugroup_user_is_member($my_user_id, $row['ugroup_id'], $this->ArtifactType->Group->getID(), $this->ArtifactType->getID())) {
                        // check that one of the assignees is also a member
                        if (ugroup_user_is_member($this->getValue('assigned_to'), $row['ugroup_id'], $this->ArtifactType->Group->getID(), $this->ArtifactType->getID())) {
                            return true;
                        }

                        // multi-assigned to
                        $multi_assigned = $this->getMultiAssignedTo();
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
        }
        return false;
    }

    /**
     *    getExtraFieldData - get an array of data for the extra fields associated with this artifact
     *
     *      the array returned looks like
     *          array(
     *             [field_id] => fieldvalues
     *             [field_id] => fieldvalues
     *          )
     *      for multi select boxes, the values are separated by a comma
     *
     *    @return    array    array of data
     */
    public function &getExtraFieldData()
    {
        global $art_field_fact;
        $extrafielddata = [];

        // now get the values for generic fields if any
        $sql = "SELECT * FROM artifact_field_value WHERE artifact_id='" . db_ei($this->getID()) . "'";
        $res = db_query($sql);
        if (! $res || db_numrows($res) < 1) {
            // if no result then it is possible that there isn't any generic fields
            return;
        }
        // Walk the database result (possible to get several values with a same field_id (multi select box))
        while ($row = db_fetch_array($res)) {
            $data_fields[$row['field_id']][] = $row;
        }

        // compute the extrafielddata array by walking the data_fields array
        $extrafielddata = [];
        foreach ($data_fields as $field_id => $data_field) {
            $current_field = $art_field_fact->getFieldFromId($field_id);
            if (isset($current_field) && $current_field && ! $current_field->isError()) {
                // $values contains the values of the field
                $values = [];
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
    public function buildNotificationArrays($changes, &$concerned_ids, &$concerned_addresses)
    {
        global $art_field_fact,$Language;

        // Rk: we store user ids in a hash to make sure they are only
        // stored once. Normally if an email is repeated several times sendmail
        // would take care of it but I prefer taking care of it now.
    // We also use the user_ids hash to check if a user has already been selected for
        // notification. If so it is not necessary to check it again in another role.
        $concerned_ids       = [];
        $concerned_addresses = [];
        $concerned_watchers  = [];

        // check submitter notification preferences
        $user_id   = $this->getSubmittedBy();
        $submitter = UserManager::instance()->getUserById($user_id);
        if ($user_id != 100 && ($submitter !== null && ($submitter->isActive() || $submitter->isRestricted()))) {
            if ($this->ArtifactType->checkNotification($user_id, 'SUBMITTER', $changes) && $this->userCanView($user_id)) {
                  //echo "DBG - SUBMITTER - user=$user_id<br>";
                $concerned_ids[$user_id] = true;
            }
        }

    // Retrieve field values for the assigned_to, multi_assigned_to value
        $result = $this->getFieldsValues();
        $this->checkAssignees("assigned_to", $result, $art_field_fact, $changes, $concerned_ids);
        $this->checkAssignees("multi_assigned_to", $result, $art_field_fact, $changes, $concerned_ids);

    // check all CC
        // (a) check all the people in the current CC list
        // (b) check the CC that has just been removed if any and see if she
        // wants to be notified as well
        // if the CC indentifier is an email address then notify in any case
        // because this user has no personal setting
        $res_cc = $this->getCCList();
        $arr_cc = [];
        if ($res_cc && (db_numrows($res_cc) > 0)) {
            while ($row = db_fetch_array($res_cc)) {
                $arr_cc[] = $row['email'];
            }
        }
        if (isset($changes['CC']) && isset($changes['CC']['del']) && $changes['CC']['del']) {
            // Only one CC can be deleted at once so just append it to the list....
            $arr_cc[] = $changes['CC']['del'];
        }

        foreach ($arr_cc as $cc) {
            //echo "DBG - CC=$cc<br>";
            if (validate_email($cc)) {
            //echo "DBG - CC email - email=".util_normalize_email($cc)."<br>";
                $concerned_addresses[util_normalize_email($cc)] = true;
            } else {
                $res     = user_get_result_set_from_unix($cc);
                $user_id = db_result($res, 0, 'user_id');
                if (! isset($concerned_ids[$user_id]) && $this->ArtifactType->checkNotification($user_id, 'CC', $changes)) {
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
                if (! isset($concerned_ids[$user_id]) && $this->ArtifactType->checkNotification($user_id, 'COMMENTER', $changes)) {
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
                $watcher                      = $row_watcher['user_id'];
                $concerned_watchers[$watcher] = true;
            }
        }

        foreach (array_keys($concerned_watchers) as $watcher) {
            if (! $concerned_ids[$watcher]) {
                $concerned_ids[$watcher] = true;
            }
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
    public function groupNotificationList($user_ids, &$user_sets, &$ugroup_sets)
    {
        $group_id          = $this->ArtifactType->getGroupID();
        $group_artifact_id = $this->ArtifactType->getID();

        $user_sets   = [];
        $ugroup_sets = [];

      //go through user_ids array:
      //for each user have a look at which ugroups he belongs

        foreach ($user_ids as $user_id) {
            $specific_ugroups = ugroup_db_list_tracker_ugroups_for_user($group_id, $group_artifact_id, $user_id);
   //echo "<br>specific_ugroups for $user_id = "; print_r($specific_ugroups);
            $dynamic_ugroups = ugroup_db_list_dynamic_ugroups_for_user($group_id, $group_artifact_id, $user_id);
   //echo "<br>dynamic_ugroups for $user_id = "; print_r($dynamic_ugroups);
            $all_ugroups = array_merge($dynamic_ugroups, $specific_ugroups);
   //echo "<br>all_ugroups for $user_id = "; print_r($all_ugroups);

            $found_gr = false;
            foreach ($ugroup_sets as $x => $ug) {
                     $diff1 = array_diff($ug, $all_ugroups);
                     $diff2 = array_diff($all_ugroups, $ug);
                if (empty($diff1) && empty($diff2)) {
         // we found the magic users that are part of exactly the same ugroups as this user
                    $gr   = $user_sets[$x];
                    $gr[] = $user_id;
                    unset($user_sets[$x]);
                    $user_sets[$x] = $gr;
                    $found_gr      = true;
                    break;
                }
            }
   // if we didn't find users who have exactly the same permissions we have to add this user separately
            if (! $found_gr) {
                     $user_sets[]   = [$user_id];
                     $ugroup_sets[] = $all_ugroups;
            }
        }
    }

    /**
      * Checks if a user is allowed to delete and update a follow-up comment
      *
      * @return bool
      */
    public function userCanEditFollowupComment($comment_id)
    {
        //if user is not logged in, he cannot update/delete comments
        if (! user_isloggedin()) {
            return false;
        }

        $user_id = UserManager::instance()->getCurrentUser()->getId();
        //tracker admin can delete and update followup comments
        if ($this->ArtifactType->userIsAdmin($user_id)) {
            return true;
        }

        $com_res   = $this->getOriginalCommentSubmitter($comment_id);
        $commenter = db_result($com_res, 0, 'mod_by');
        if ($commenter == $user_id) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks if the comment was removed
     *
     * @params int comment_id
     *
     * @return bool
     */
    public function isFollowupCommentDeleted($comment_id)
    {
        $sql = 'SELECT artifact_id, new_value
                FROM artifact_history
                WHERE artifact_history_id = ' . db_ei($comment_id);
        $res = db_query($sql);
        if (db_result($res, 0, 'new_value') == "") {
            return true;
        }
        $lbl    = "lbl_" . $comment_id . "_comment";
        $aid    = db_result($res, 0, 'artifact_id');
        $qry    = 'SELECT NULL FROM artifact_history'
        . ' WHERE artifact_id = ' . db_ei($aid)
        . ' AND field_name = "' . db_es($lbl) . '"'
        . ' AND new_value = ""';
        $result = db_query($qry);
        if (db_numrows($result) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Gets the original submitter of a follow-up comment
     *
     * @param int comment_id
     *
     * @return result set
     */
    public function getOriginalCommentSubmitter($comment_id)
    {
        $sql        = 'SELECT field_name, mod_by, email
                FROM artifact_history
                WHERE artifact_history_id = ' . db_ei($comment_id);
        $res        = db_query($sql);
        $field_name = db_result($res, 0, 'field_name');
        if ($field_name == "comment") {
            return $res;
        } elseif (preg_match("/^(lbl_)/", $field_name) && preg_match("/(_comment)$/", $field_name)) {
         // extract id of the original comment
            $id     = (int) substr($field_name, 4, -8);
            $qry    = 'SELECT mod_by, email
                    FROM artifact_history
                    WHERE artifact_history_id = ' . db_ei($id) . '
                    AND field_name = "comment"';
            $result = db_query($qry);
            return $result;
        }
    }

    /**
     * Gets the original submission date of a follow-up comment
     *
     * @param int comment_id
     *
     * @return result set
     */
    public function getOriginalCommentDate($comment_id)
    {
        $sql        = 'SELECT field_name, date
                FROM artifact_history
                WHERE artifact_history_id = ' . db_ei($comment_id);
        $res        = db_query($sql);
        $field_name = db_result($res, 0, 'field_name');
        if ($field_name == "comment") {
            return $res;
        } elseif (preg_match("/^(lbl_)/", $field_name) && preg_match("/(_comment)$/", $field_name)) {
         // extract id of the original comment
            $id     = (int) substr($field_name, 4, -8);
            $qry    = 'SELECT date
                    FROM artifact_history
                    WHERE artifact_history_id = ' . db_ei($id) . '
                    AND field_name = "comment"';
            $result = db_query($qry);
            return $result;
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
    public function mailFollowupWithPermissions($more_addresses = false, $changes = false)
    {
        global $art_field_fact,$Language;

      // check if notification is temporarily stopped in this tracker
        if (! $this->ArtifactType->getStopNotification()) {
            $group             = $this->ArtifactType->getGroup();
            $group_artifact_id = $this->ArtifactType->getID();
            $group_id          = $group->getGroupId();

          // See who is going to receive the notification. Plus append any other email
          // given at the end of the list.
            $withoutpermissions_concerned_addresses = [];
            $this->buildNotificationArrays($changes, $concerned_ids, $concerned_addresses);
            if ($more_addresses) {
                foreach ($more_addresses as $address) {
                    if ($address['address'] && $address['address'] != '') {
                        $res_username = user_get_result_set_from_email($address['address'], false);
                        if ($res_username && (db_numrows($res_username) == 1)) {
                            $u_id = db_result($res_username, 0, 'user_id');
                            if (! $address['check_permissions']) {
                                $curr_user = UserManager::instance()->getUserById($u_id);
                                if ($curr_user !== null && ($curr_user->isActive() || $curr_user->isRestricted())) {
                                    $withoutpermissions_concerned_addresses[user_getemail($u_id)] = true;
                                }
                                unset($concerned_ids[$u_id]);
                            } else {
                                $concerned_ids[$u_id] = true;
                            }
                        } else {
                            if (! $address['check_permissions']) {
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
            $host = \Tuleap\ServerHostname::rawHostname();

          //treat anonymous users
            $text_mail = $this->createMailForUsers([$GLOBALS['UGROUP_ANONYMOUS']], $changes, $group_id, $group_artifact_id, $ok, $subject);
            $html_mail = $this->createHTMLMailForUsers([$GLOBALS['UGROUP_ANONYMOUS']], $changes, $group_id, $group_artifact_id, $ok, $subject);

            if ($ok) {
                $this->sendNotification(array_keys($concerned_addresses), $subject, $text_mail, $html_mail);
            }

          //treat 'without permissions' emails
            if (count($withoutpermissions_concerned_addresses)) {
                $text_mail = $this->createMailForUsers(false, $changes, $group_id, $group_artifact_id, $ok, $subject);
                $html_mail = $this->createHTMLMailForUsers(false, $changes, $group_id, $group_artifact_id, $ok, $subject);

                if ($ok) {
                    $this->sendNotification(array_keys($withoutpermissions_concerned_addresses), $subject, $text_mail, $html_mail);
                }
            }

          //now group other registered users

       //echo "<br>concerned_ids = ".implode(',',array_keys($concerned_ids));

            $this->groupNotificationList(array_keys($concerned_ids), $user_sets, $ugroup_sets);

       //echo "<br>user_sets = "; print_r($user_sets); echo ", ugroup_sets = "; print_r($ugroup_sets);
            foreach ($ugroup_sets as $x => $ugroups) {
                 unset($arr_addresses);

                 $user_ids = $user_sets[$x];
                 //echo "<br>--->  preparing mail $x for ";print_r($user_ids);
                 $text_mail = $this->createMailForUsers($ugroups, $changes, $group_id, $group_artifact_id, $ok, $subject);
                 $html_mail = $this->createHTMLMailForUsers($ugroups, $changes, $group_id, $group_artifact_id, $ok, $subject);
                if (! $ok) {
                    continue; //don't send the mail if nothing permitted for this user group
                }

                foreach ($user_ids as $user_id) {
                    $arr_addresses[] = user_getemail($user_id);
                }

                if ($arr_addresses) {
                    $this->sendNotification($arr_addresses, $subject, $text_mail, $html_mail);
                }
            }
        }
    }

    /**
     * Build notification list based on user preferences
     *
     * @param Array                  $addresses
     * @param String                 $subject
     * @param Codendi_Mail_Interface $text_mail
     * @param Codendi_Mail_Interface $html_mail
     */
    public function sendNotification($addresses, $subject, $text_mail, $html_mail)
    {
        $html_addresses = [];
        $text_addresses = [];

        $mailMgr   = new MailManager();
        $mailPrefs = $mailMgr->getMailPreferencesByEmail($addresses);
        foreach ($mailPrefs['html'] as $user) {
            $html_addresses[] = $user->getEmail();
        }
        foreach ($mailPrefs['text'] as $user) {
            $text_addresses[] = $user->getEmail();
        }

        $mail = null;
        if ($text_mail && count($text_addresses)) {
            $this->sendMail($text_mail, $subject, $text_addresses);
        }
        if ($html_mail && count($html_addresses)) {
            if ($text_mail) {
                $html_mail->setBodyText($text_mail->getBody());
            }
            $this->sendMail($html_mail, $subject, $html_addresses);
        }
    }

    /**
     * Finalize & send mail to peple
     *
     * @param String                 $subject
     * @param Array                  $to
     */
    public function sendMail(Codendi_Mail_Interface $mail, $subject, array $to)
    {
        $mail->addAdditionalHeader("X-Codendi-Project", $this->ArtifactType->getGroup()->getUnixName());
        $mail->addAdditionalHeader("X-Codendi-Artifact", $this->ArtifactType->getItemName());
        $mail->addAdditionalHeader("X-Codendi-Artifact-ID", $this->getID());
        $mail->setFrom(ForgeConfig::get('sys_noreply'));
        $mail->setTo(join(',', $to));
        $mail->setSubject($subject);
        $mail->send();
    }

    /** for a certain set of users being part of the same ugroups
     * create the mail body containing only fields that they have the permission to read
     */
    public function createHTMLMailForUsers($ugroups, $changes, $group_id, $group_artifact_id, &$ok, &$subject)
    {
        global $art_field_fact,$art_fieldset_fact,$Language;

        $server_url = \Tuleap\ServerHostname::HTTPSUrl();

        $artifact_href = $server_url . "/tracker/?func=detail&aid=" . $this->getID() . "&atid=$group_artifact_id&group_id=$group_id";
        $used_fields   = $art_field_fact->getAllUsedFields();
        assert($this->ArtifactType instanceof ArtifactType);
        $art_fieldset_fact = new ArtifactFieldSetFactory($this->ArtifactType);
        $used_fieldsets    = $art_fieldset_fact->getAllFieldSetsContainingUsedFields();
        $ok                = false;

        $hp = $this->getHTMLPurifier();

        $body = '';

        //generate the field permissions (TRACKER_FIELD_READ, TRACKER_FIEDL_UPDATE or nothing)
        //for all fields of this tracker given the $ugroups the user is part of
        $field_perm = false;
        if ($ugroups) {
            $field_perm = $this->ArtifactType->getFieldPermissions($ugroups);
        }

        $summ = "";
        if ($field_perm === false || (isset($field_perm['summary']) && $field_perm['summary'] && permission_can_read_field($field_perm['summary']))) {
            $summ = util_unconvert_htmlspecialchars($this->getValue('summary'));
        }
        $subject = '[' . $this->ArtifactType->getCapsItemName() . ' #' . $this->getID() . '] ' . $summ;

        // artifact fields
        // Generate the message preamble with all required
        // artifact fields - Changes first if there are some.
        $body .= '<h1>' . $summ . '</h1>';
        if ($changes) {
            $body .= $this->formatChangesHTML($changes, $field_perm, $artifact_href, $visible_change);
            if (! $visible_change) {
                return;
            }
        }
        $ok = true;

        // Snapshot
        $fields_per_line = 2;
        // the column number is the number of field per line * 2 (label + value)
        // + the number of field per line -1 (a blank column between each pair "label-value" to give more space)
        $columns_number = ($fields_per_line * 2) + ($fields_per_line - 1);
        $max_size       = 40;
        $snapshot       = '';
        foreach ($used_fieldsets as $fieldset_id => $result_fieldset) {
            // this variable will tell us if we have to display the fieldset or not (if there is at least one field to display or not)
            $display_fieldset = false;

            $fieldset_html = '';

            $i                  = 0;
            $fields_in_fieldset = $result_fieldset->getAllUsedFields();
            foreach ($fields_in_fieldset as $key => $field) {
                if ($field->getName() != 'comment_type_id' && $field->getName() != 'artifact_id') {
                    $field_html = $this->_getFieldLabelAndValueForHTMLMail($group_id, $group_artifact_id, $field, $field_perm);
                    if ($field_html) {
                        // if the user can read at least one field, we can display the fieldset this field is within
                        $display_fieldset = true;

                        list($sz,) = explode("/", $field->getDisplaySize());

                        // Details field must be on one row
                        if ($sz > $max_size || $field->getName() == 'details') {
                            $fieldset_html .= "\n<TR>" .
                                  '<TD align="left" valign="top" width="10%" nowrap="nowrap">' . $field_html['label'] . '</td>' .
                                  '<TD valign="top" width="90%" colspan="' . ($columns_number - 1) . '">' . $field_html['value'] . '</TD>' .
                                  "\n</TR>";
                            $i              = 0;
                        } else {
                            $fieldset_html .= ($i % $fields_per_line ? '' : "\n<TR>");
                            $fieldset_html .= '<TD align="left" valign="top" width="10%" nowrap="nowrap">' . $field_html['label'] . '</td>' .
                                              '<TD width="38%" valign="top">' . $field_html['value'] . '</TD>';
                            $i++;
                            // if the line is not full, we add a additional column to give more space
                            $fieldset_html .= ($i % $fields_per_line) ? '<td class="artifact_spacer" width="4%">&nbsp;</td>' : "\n</TR>";
                        }
                    }
                }
            }

            // We display the fieldset only if there is at least one field inside that we can display
            if ($display_fieldset) {
                $snapshot .= '<TR style="color: #444444; background-color: #F6F6F6;"><TD COLSPAN="' . (int) $columns_number . '">&nbsp;<span title="' . $hp->purify(SimpleSanitizer::unsanitize($result_fieldset->getDescriptionText()), CODENDI_PURIFIER_CONVERT_HTML) . '">' . $hp->purify(SimpleSanitizer::unsanitize($result_fieldset->getLabel()), CODENDI_PURIFIER_CONVERT_HTML) . '</span></TD></TR>';
                $snapshot .= $fieldset_html;
            }
        }
        if ($snapshot) {
            $body .= '<h2>' . $GLOBALS['Language']->getText('tracker_include_artifact', 'mail_snapshot_title') . '</h2>';
            $body .= '<table>';
            $body .= $snapshot;
            $body .= '</table>';
            if (! $changes) {
                $body .= $this->fetchHtmlAnswerButton($artifact_href);
            }
        }

        $result = $this->getFollowups();
        if (db_numrows($result)) {
            $body .= '<h2>' . $GLOBALS['Language']->getText('tracker_include_artifact', 'mail_comment_title') . '</h2>';

            $body .= '<dl>';
            while ($row = db_fetch_array($result)) {
                $orig_subm       = $this->getOriginalCommentSubmitter($row['artifact_history_id']);
                $orig_sub_mod_by = db_result($orig_subm, 0, 'mod_by');
                if ($orig_sub_mod_by == 100) {
                    $submitter_real_name = db_result($orig_subm, 0, 'email');
                } else {
                    $submitter           = UserManager::instance()->getUserById($orig_sub_mod_by);
                    $submitter_real_name = $submitter->getRealName();
                }

                $orig_date = $this->getOriginalCommentDate($row['artifact_history_id']);
                $subm_date = format_date($GLOBALS['Language']->getText('system', 'datefmt'), db_result($orig_date, 0, 'date'));

                $body .= '<dt><strong>' . $submitter_real_name . '</strong> <span style="color:#bbb">' . $subm_date . '</span></dt>';
                $body .= '<dd>' . $this->formatFollowUp($group_id, $row['format'], $row['new_value'], self::OUTPUT_BROWSER) . '</dd>';
            }
            $body .= '</dl>';
        }
        // Finaly, transform relatives URLs to absolute
        // I'm Nicolas Terray and I approve this hack.
        $body = preg_replace('%<a href="/%', '<a href="' . $server_url . '/', $body);

        // Mail is ready, we can create it
        if ($ok) {
            $project       = ProjectManager::instance()->getProject($group_id);
            $breadcrumbs   = [];
            $breadcrumbs[] = '<a href="' . $server_url . '/projects/' . $project->getUnixName($tolower = true) . '" />' . $hp->purify($project->getPublicName()) . '</a>';
            $breadcrumbs[] = '<a href="' . $server_url . '/tracker/?group_id=' . (int) $group_id . '&amp;atid=' . (int) $group_artifact_id . '" />' . $hp->purify(SimpleSanitizer::unsanitize($this->ArtifactType->getName())) . '</a>';
            $breadcrumbs[] = '<a href="' . $artifact_href . '" />' . $hp->purify($this->ArtifactType->getItemName() . ' #' . $this->getID()) . '</a>';

            $mail = new Codendi_Mail();
            $mail->getLookAndFeelTemplate()->set('breadcrumbs', $breadcrumbs);
            $mail->getLookAndFeelTemplate()->set('title', $hp->purify($subject, CODENDI_PURIFIER_CONVERT_HTML));
            $mail->getLookAndFeelTemplate()->set('additional_footer_link', '<a href="' . $artifact_href . '">' . $GLOBALS['Language']->getText('tracker_include_artifact', 'mail_direct_link') . '</a>');
            $mail->setBodyHtml($body);
            return $mail;
        } else {
            return null;
        }
    }

    /**
     * @return string html call to action button to include in an html mail
     */
    public function fetchHtmlAnswerButton($artifact_href)
    {
        return '<p align="right" class="cta">
            <a href="' . $artifact_href . '" target="_blank" rel="noreferrer">' .
            $GLOBALS['Language']->getText('tracker_include_artifact', 'mail_answer_now') .
            '</a>
            </p>';
    }

    /**
     * return a field for the given user.
     *
     * @protected
     **/
    public function _getFieldLabelAndValueForHTMLMail($group_id, $group_artifact_id, $field, $field_perm)
    {
        $html       = false;
        $read_only  = true;
        $field_name = $field->getName();
        if ($field_perm === false || (isset($field_perm[$field_name]) && $field_perm[$field_name] && permission_can_read_field($field_perm[$field_name]))) {
            // For multi select box, we need to retrieve all the values
            if ($field->isMultiSelectBox()) {
                $field_value = $field->getValues($this->getID());
            } else {
                $field_value = $this->getValue($field->getName());
            }

            $field_html = new ArtifactFieldHtml($field);
            $field_html->disableJavascript();
            $label = $field_html->labelDisplay(false, false, false);

            if ($field->getName() == 'submitted_by') {
                $value = util_user_link(user_getname($field_value));
            } elseif ($field->getName() == 'open_date') {
                $value = format_date($GLOBALS['Language']->getText('system', 'datefmt'), $field_value);
            } elseif ($field->getName() == 'last_update_date') {
                $value = format_date($GLOBALS['Language']->getText('system', 'datefmt'), $field_value);
            } else {
                $value = $field_html->display($this->ArtifactType->getID(), $field_value, false, false, $read_only, false, false, 0, false, 0, false, 0, false, $this->ArtifactType->getGroupID());
            }
            $html = ['label' => $label, 'value' => $value];
        }
        return $html;
    }

    /** for a certain set of users being part of the same ugroups
     * create the mail body containing only fields that they have the permission to read
     */
    public function createMailForUsers($ugroups, $changes, $group_id, $group_artifact_id, &$ok, &$subject)
    {
        global $art_field_fact,$art_fieldset_fact,$Language;

        $fmt_len       = 40;
        $fmt_left      = sprintf("%%-%ds ", $fmt_len - 1);
        $fmt_right     = "%s";
        $artifact_href = \Tuleap\ServerHostname::HTTPSUrl() . "/tracker/?func=detail&aid=" . $this->getID() . "&atid=$group_artifact_id&group_id=$group_id";
        $used_fields   = $art_field_fact->getAllUsedFields();
        assert($this->ArtifactType instanceof ArtifactType);
         $art_fieldset_fact = new ArtifactFieldSetFactory($this->ArtifactType);
         $used_fieldsets    = $art_fieldset_fact->getAllFieldSetsContainingUsedFields();
        $ok                 = false;

        $body = '';

      //generate the field permissions (TRACKER_FIELD_READ, TRACKER_FIEDL_UPDATE or nothing)
      //for all fields of this tracker given the $ugroups the user is part of
         $field_perm = false;
        if ($ugroups) {
            $field_perm = $this->ArtifactType->getFieldPermissions($ugroups);
        }

        $summ = "";
        if ($field_perm === false || (isset($field_perm['summary']) && $field_perm['summary'] && permission_can_read_field($field_perm['summary']))) {
            $summ = util_unconvert_htmlspecialchars($this->getValue('summary'));
        }
        $subject = '[' . $this->ArtifactType->getCapsItemName() . ' #' . $this->getID() . '] ' . $summ;

      //echo "<br>......... field_perm for "; print_r($ugroups); echo " = "; print_r($field_perm);

        // artifact fields
        // Generate the message preamble with all required
        // artifact fields - Changes first if there are some.
        if ($changes) {
            $body = ForgeConfig::get('sys_lf') . "=============   " . strtoupper(SimpleSanitizer::unsanitize($this->ArtifactType->getName())) . " #" . $this->getID() .
            ": " . $Language->getText('tracker_include_artifact', 'latest_modif') . "   =============" . ForgeConfig::get('sys_lf') . $artifact_href . ForgeConfig::get('sys_lf') . ForgeConfig::get('sys_lf') .
            $this->formatChanges($changes, $field_perm, $visible_change) . ForgeConfig::get('sys_lf') . ForgeConfig::get('sys_lf') . ForgeConfig::get('sys_lf') . ForgeConfig::get('sys_lf') . "";

            if (! $visible_change) {
                return;
            }
        }
        $ok = true;

        $visible_snapshot = false;
        $full_snapshot    = "";

        // We write the name of the project
        $pm             = ProjectManager::instance();
        $full_snapshot .= sprintf($fmt_left . ForgeConfig::get('sys_lf') . "", $Language->getText('tracker_include_artifact', 'project') . ' ' . $pm->getProject($group_id)->getPublicName());

        // Write all the fields, grouped by fieldsetset and ordered by rank.
        $left = 1;

        $visible_fieldset = false;
        // fetch list of used fieldsets for this artifact
        foreach ($used_fieldsets as $fieldset_id => $fieldset) {
            $fieldset_snapshot = '';
            $used_fields       = $fieldset->getAllUsedFields();
            // fetch list of used fields and the current field values
            // for this artifact
            foreach ($used_fields as $field) {
                $field_name = $field->getName();

                if ($field_perm === false || (isset($field_perm[$field_name]) && $field_perm[$field_name] && permission_can_read_field($field_perm[$field_name]))) {
                    $field_html = new ArtifactFieldHtml($field);

                    $visible_fieldset = true;
                    $visible_snapshot = true;

                    // For multi select box, we need to retrieve all the values
                    if ($field->isMultiSelectBox()) {
                        $field_value = $field->getValues($this->getID());
                    } else {
                        $field_value = $this->getValue($field->getName());
                    }
                    $display = $field_html->display(
                        $group_artifact_id,
                        $field_value,
                        false,
                        true,
                        true,
                        true
                    );
                    $item    = sprintf(($left ? $fmt_left : $fmt_right), $display);
                    if (strlen($item) > $fmt_len) {
                        if (! $left) {
                            $fieldset_snapshot .= "" . ForgeConfig::get('sys_lf') . "";
                        }
                        $fieldset_snapshot .= sprintf($fmt_right, $display);
                        $fieldset_snapshot .= "" . ForgeConfig::get('sys_lf') . "";
                        $left               = 1;
                    } else {
                        $fieldset_snapshot .= $item;
                        $left               = ! $left;
                        if ($left) {
                            $fieldset_snapshot .= "" . ForgeConfig::get('sys_lf') . "";
                        }
                    }
                }
            } // while

            if ($visible_fieldset) {
                $full_snapshot .= "" . ForgeConfig::get('sys_lf') . "";
                $full_snapshot .= ($left ? "" : "" . ForgeConfig::get('sys_lf') . "");
                $full_snapshot .= '--- ' . SimpleSanitizer::unsanitize($fieldset->getLabel()) . ' ---';
                $full_snapshot .= "" . ForgeConfig::get('sys_lf') . "";
                $full_snapshot .= $fieldset_snapshot;
            }
        }

        if ($visible_snapshot) {
            $full_snapshot .= "" . ForgeConfig::get('sys_lf') . "";
        }

        $body .= "=============   " . strtoupper(SimpleSanitizer::unsanitize($this->ArtifactType->getName())) . " #" . $this->getID() .
        ": " . $Language->getText('tracker_include_artifact', 'full_snapshot') . "   =============" . ForgeConfig::get('sys_lf') .
        ($changes ? '' : $artifact_href) . ForgeConfig::get('sys_lf') . ForgeConfig::get('sys_lf') . $full_snapshot;

        if (! $left) {
            $body .= "" . ForgeConfig::get('sys_lf') . "";
        }

        // Now display other special fields

        // Then output the history of bug comments from newest to oldest
        $body .= $this->showFollowUpComments($group_id, 0, self::OUTPUT_MAIL_TEXT);

        // Then output the CC list
        $body .= "" . ForgeConfig::get('sys_lf') . ForgeConfig::get('sys_lf') . $this->showCCList($group_id, $group_artifact_id, true);

        // Then output the dependencies
        $body .= "" . ForgeConfig::get('sys_lf') . ForgeConfig::get('sys_lf') . $this->showDependencies($group_id, $group_artifact_id, true);

        // Then output the history of attached files from newest to oldest
        $body .= "" . ForgeConfig::get('sys_lf') . ForgeConfig::get('sys_lf') . $this->showAttachedFiles($group_id, $group_artifact_id, true);

        // Extract references from the message
        $referenceManager = ReferenceManager::instance();
        $ref_array        = $referenceManager->extractReferencesGrouped($body, $group_id);
        if (count($ref_array) > 0) {
            $body .= ForgeConfig::get('sys_lf') . ForgeConfig::get('sys_lf') . $Language->getText('tracker_include_artifact', 'references') . ForgeConfig::get('sys_lf');
        }
        foreach ($ref_array as $description => $match_array) {
            $body .= ForgeConfig::get('sys_lf') . $description . ":" . ForgeConfig::get('sys_lf');
            foreach ($match_array as $match => $ref_instance) {
                $body .= ' ' . $ref_instance->getMatch() . ': ' . $ref_instance->getFullGotoLink() . ForgeConfig::get('sys_lf');
            }
        }

        // Finally output the message trailer
        $body .= "" . ForgeConfig::get('sys_lf') . ForgeConfig::get('sys_lf') . $Language->getText('tracker_include_artifact', 'follow_link');
        $body .= "" . ForgeConfig::get('sys_lf') . $artifact_href;

        if ($ok) {
            $mail = new Codendi_Mail();
            $mail->setBodyText($body);
            return $mail;
        } else {
            return null;
        }
    }

    /**
     * Check whether $field_name is readable according to $field_perm
     *
     * All permissions are granted when $field_perm is equal to false.
     * $field_perm is equal to false when addresses are added in tracker admin to
     * "Global Email Notification with "check permission" unchecked.
     *
     * @param Array $field_perm ...
     *
     * @return bool
     */
    public function hasFieldPermission($field_perm, $field_name)
    {
        $hasPerm = false;
        if ($field_perm === false) {
            $hasPerm = true;
        } else {
            if (isset($field_perm[$field_name]) && $field_perm[$field_name] && permission_can_read_field($field_perm[$field_name])) {
                $hasPerm = true;
            }
        }
        return $hasPerm;
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
    public function formatChanges($changes, $field_perm, &$visible_change)
    {
        global $art_field_fact,$Language;
        $visible_change = false;
        $out_hdr        = '';
        $out            = '';
        $out_com        = '';
        $out_att        = '';
        reset($changes);
        $fmt = "%20s | %-25s | %s" . ForgeConfig::get('sys_lf');

        if (
            $this->hasFieldPermission($field_perm, 'assigned_to') ||
            $this->hasFieldPermission($field_perm, 'multi_assigned_to') ||
            (! isset($field_perm['assigned_to']) && ! isset($field_perm['multi_assigned_to']))
        ) {
            $current_user = UserManager::instance()->getCurrentUserWithLoggedInInformation();
            $user         = $current_user->user;
            if ($current_user->is_logged_in) {
                   $out_hdr  = $Language->getText('tracker_include_artifact', 'changes_by') . ' ' . $user->getRealName() . ' <' . $user->getEmail() . ">" . ForgeConfig::get('sys_lf') . "";
                   $out_hdr .= $Language->getText('tracker_import_utils', 'date') . ': ' . format_date($GLOBALS['Language']->getText('system', 'datefmt'), time()) . ' (' . user_get_timezone() . ')';
            } else {
                   $out_hdr = $Language->getText('tracker_include_artifact', 'changes_by') . ' ' . $Language->getText('tracker_include_artifact', 'anon_user') . '        ' . $Language->getText('tracker_import_utils', 'date') . ': ' . format_date($GLOBALS['Language']->getText('system', 'datefmt'), time());
            }
        }
        //Process special cases first: follow-up comment
        if (array_key_exists('comment', $changes) && $changes['comment']) {
            $visible_change = true;
            $out_com        = ForgeConfig::get('sys_lf') . ForgeConfig::get('sys_lf') . "---------------   " . $Language->getText('tracker_include_artifact', 'add_flup_comment') . "   ----------------" . ForgeConfig::get('sys_lf') . "";

            if (isset($changes['comment']['type']) && $changes['comment']['type'] != $Language->getText('global', 'none') && $changes['comment']['type'] != '') {
                 $out_com .= "[" . $changes['comment']['type'] . "]" . ForgeConfig::get('sys_lf');
            }
            $out_com .= $this->formatFollowUp(null, $changes['comment']['format'], $changes['comment']['add'], self::OUTPUT_MAIL_TEXT);
            unset($changes['comment']);
        }

           //Process special cases first: file attachment
        if (array_key_exists('attach', $changes) && $changes['attach']) {
            $visible_change = true;
            $out_att        = "" . ForgeConfig::get('sys_lf') . ForgeConfig::get('sys_lf') . "---------------    " . $Language->getText('tracker_include_artifact', 'add_attachment') . "     -----------------" . ForgeConfig::get('sys_lf') . "";
            $out_att       .= sprintf(
                $Language->getText('tracker_include_artifact', 'file_name') . " %-30s " . $Language->getText('tracker_include_artifact', 'size') . ":%d KB" . ForgeConfig::get('sys_lf') . "",
                $changes['attach']['name'],
                intval($changes['attach']['size'] / 1024)
            );
            $out_att       .= $changes['attach']['description'] . ForgeConfig::get('sys_lf') . $changes['attach']['href'];
            unset($changes['attach']);
        }

        // All the rest of the fields now
        foreach ($changes as $field_name => $h) {
            // If both removed and added items are empty skip - Sanity check
            if (
                ((isset($h['del']) && $h['del']) || (isset($h['add']) && $h['add']))
                && $this->hasFieldPermission($field_perm, $field_name)
            ) {
                $visible_change = true;
                $label          = $field_name;
                $field          = $art_field_fact->getFieldFromName($field_name);
                if ($field) {
                    $label = $field->getLabel();
                    if (isset($h['del'])) {
                        $h['del'] = SimpleSanitizer::unsanitize(util_unconvert_htmlspecialchars($h['del']));
                    }
                    if (isset($h['add'])) {
                        $h['add'] = SimpleSanitizer::unsanitize(util_unconvert_htmlspecialchars($h['add']));
                    }
                }
                $out .= sprintf($fmt, SimpleSanitizer::unsanitize($label), isset($h['del']) ? $h['del'] : "", isset($h['add']) ? $h['add'] : "");
            }
        } // while

        if ($out) {
            $out = ForgeConfig::get('sys_lf') . ForgeConfig::get('sys_lf') . sprintf($fmt, $Language->getText('tracker_include_artifact', 'what') . '    ', $Language->getText('tracker_include_artifact', 'removed'), $Language->getText('tracker_include_artifact', 'added')) .
                "------------------------------------------------------------------" . ForgeConfig::get('sys_lf') . $out;
        }

        return($out_hdr . $out . $out_com . $out_att);
    }

    /**
     * Format the changes
     *
     * @param changes: array of changes
     * @param $field_perm an array with the permission associated to each field. false to no check perms
     * @param string $artifact_href The direct link to the artifact
     * @param $visible_change only needed when using permissions. Returns true if there is any change
     * that the user has permission to see
     *
     * @return string
     */
    public function formatChangesHTML($changes, $field_perm, $artifact_href, &$visible_change)
    {
        global $art_field_fact,$Language;
        $group_id       = $this->ArtifactType->getGroupID();
        $visible_change = false;
        $out            = '';
        $out_com        = '';
        $out_ch         = '';
        reset($changes);
        $fmt = "%20s | %-25s | %s" . ForgeConfig::get('sys_lf');

        $hp = $this->getHTMLPurifier();

        $out .= '<h2>' . $Language->getText('tracker_include_artifact', 'mail_latest_modifications') . '</h2>';
        $out .= '
            <div class="tracker_artifact_followup_header">
                <div class="tracker_artifact_followup_title">
                    <span class="tracker_artifact_followup_title_user">';

        $current_user = UserManager::instance()->getCurrentUserWithLoggedInInformation();
        $user         = $current_user->user;

        if (
            $this->hasFieldPermission($field_perm, 'assigned_to') ||
            $this->hasFieldPermission($field_perm, 'multi_assigned_to') ||
            (! isset($field_perm['assigned_to']) && ! isset($field_perm['multi_assigned_to']))
        ) {
            if ($current_user->is_logged_in) {
                $out .= '<a href="mailto:' . $hp->purify($user->getEmail()) . '">' . $hp->purify($user->getRealName()) . ' (' . $hp->purify($user->getUserName()) . ')</a>';
            } else {
                $out = $Language->getText('tracker_include_artifact', 'anon_user');
            }
        }

        $timezone = '';
        if ($user->getId() != 0) {
            $timezone = ' (' . $user->getTimezone() . ')';
        }

        $out .= '
                    </span>
                </div>
                <div class="tracker_artifact_followup_date">' . format_date($GLOBALS['Language']->getText('system', 'datefmt'), $_SERVER['REQUEST_TIME']) . $timezone . '</div>
            </div>
            <div class="tracker_artifact_followup_avatar">
                ' . $user->fetchHtmlAvatar() . '
            </div>
            <div class="tracker_artifact_followup_content">
                <div class="tracker_artifact_followup_comment">';

        //Process special cases first: follow-up comment
        if (! empty($changes['comment'])) {
            $visible_change = true;
            if (! empty($changes['comment']['type']) && $changes['comment']['type'] != $Language->getText('global', 'none')) {
                $out_com .= "<strong>[" . $changes['comment']['type'] . "]</strong><br />";
            }
            $out_com .= '<div class="tracker_artifact_followup_comment_body">';
            $out_com .= $this->formatFollowUp($group_id, $changes['comment']['format'], $changes['comment']['add'], self::OUTPUT_BROWSER);
            $out_com .= '</div>';
            unset($changes['comment']);
        }
        //Process special cases first: file attachment
        if (! empty($changes['attach'])) {
            $visible_change = true;
            $out_ch        .= '<tr>';
            $out_ch        .= '<td valign="top"><strong>' . $Language->getText('tracker_include_artifact', 'add_attachment') . '</strong></td>';
            $out_ch        .= '<td valign="top"><a href="' . $changes['attach']['href'] . '">' . $hp->purify($changes['attach']['name']) . '</a> (' . size_readable($changes['attach']['size']) . ')</td>';
            $out_ch        .= '</tr>';
            unset($changes['attach']);
        }

        // All the rest of the fields now
        reset($changes);
        foreach ($changes as $field_name => $h) {
            // If both removed and added items are empty skip - Sanity check
            if ((! empty($h['del']) || ! empty($h['add'])) && $this->hasFieldPermission($field_perm, $field_name)) {
                $visible_change = true;
                $label          = $field_name;
                $field          = $art_field_fact->getFieldFromName($field_name);
                if ($field) {
                    $label = $field->getLabel();
                    if (isset($h['del'])) {
                        $h['del'] = SimpleSanitizer::unsanitize(util_unconvert_htmlspecialchars($h['del']));
                    }
                    if (isset($h['add'])) {
                        $h['add'] = SimpleSanitizer::unsanitize(util_unconvert_htmlspecialchars($h['add']));
                    }
                }
                $out_ch .= '<tr>';
                $out_ch .= '  <td valign="top" nowrap="nowrap"><ul style="margin:0; padding:0; margin-left:1.5em; "><li><strong>' . $hp->purify(SimpleSanitizer::unsanitize($label)) . ':&nbsp;</strong></li></ul></td>';
                $out_ch .= '  <td valign="top">';
                if ($field && ($field->getDisplayType() == 'TA' || $field->getDisplayType() == 'TF')) {
                    $before   = explode("\n", $h['del']);
                    $after    = explode("\n", $h['add']);
                    $callback = [Codendi_HTMLPurifier::instance(), 'purify'];
                    $d        = new Codendi_Diff(
                        array_map($callback, $before, array_fill(0, count($before), CODENDI_PURIFIER_CONVERT_HTML)),
                        array_map($callback, $after, array_fill(0, count($after), CODENDI_PURIFIER_CONVERT_HTML))
                    );
                    $f        = new Codendi_HtmlUnifiedDiffFormatter(2);
                    $diff     = $f->format($d);
                    if ($diff) {
                        $out_ch .= '<div class="diff">' . $diff . '</div>';
                    }
                } else {
                    $before = '<del>' . $hp->purify($h['del']) . '</del>';
                    $after  = '<ins>' . $hp->purify($h['add']) . '</ins>';
                    if ($field && $field->getDisplayType() == 'MB') {
                        if (strlen($before) != 11) { //'<del></del>' => empty
                            $out_ch .= $before;
                        }
                        if (strlen($before) != 11 && strlen($after) != 11) { //'<ins></ins>' => empty
                            $out_ch .= ' &plusmn; ';
                        }
                        if (strlen($after) != 11) { //'<ins></ins>' => empty
                            $out_ch .= $after;
                        }
                    } else {
                        $out_ch .= $before;
                        $out_ch .= ' &rarr; ';
                        $out_ch .= $after;
                    }
                }
                $out_ch .= '</td>';
                $out_ch .= '</tr>';
            }
        }
        if ($out_ch) {
            $out_ch = '<div class="tracker_artifact_followup_comment_changes">' .
                $Language->getText('tracker_include_artifact', 'mail_changes') . '<table cellpadding="0" border="0" cellspacing="0" class="artifact_changes">' .
                $out_ch .
                '</table>
                </div>';
        }

        $out .= $out_com . $out_ch;

        $out .= '
                </div>
            </div>
            <div style="clear:both;"></div>';
        $out .= $this->fetchHtmlAnswerButton($artifact_href);
        return $out;
    }

        /**
         * Return the string to display the follow ups comments
         *
         * @param Integer   group_id: the group id
         * @param Integer   output By default set to OUTPUT_BROWSER, the output is displayed on browser
         *                         set to OUTPUT_MAIL_TEXT, the followups will be sent in mail
         *                         else is an export csv/DB
         * @return string the follow-up comments to display in HTML or in ascii mode
         */
    public function showFollowUpComments($group_id, $pv, $output = self::OUTPUT_BROWSER)
    {
        $hp = $this->getHTMLPurifier();
        $uh = UserHelper::instance();

        //  Format the comment rows from artifact_history
        global $Language;

            //$group = $this->ArtifactType->getGroup();
            $group_artifact_id = $this->ArtifactType->getID();
            //$group_id = $group->getGroupId();

        $result = $this->getFollowups();
        $rows   = db_numrows($result);

        // No followup comment -> return now
        if ($rows <= 0) {
            if ($output == self::OUTPUT_EXPORT || $output == self::OUTPUT_MAIL_TEXT) {
                $out = ForgeConfig::get('sys_lf') . ForgeConfig::get('sys_lf') . " " . $Language->getText('tracker_import_utils', 'no_followups') . ForgeConfig::get('sys_lf');
            } else {
                $out = '<H4>' . $Language->getText('tracker_import_utils', 'no_followups') . '</H4>';
            }
                    return $out;
        }

        $out = '';

        // Header first
        if ($output == self::OUTPUT_EXPORT || $output == self::OUTPUT_MAIL_TEXT) {
            $out .= $Language->getText('tracker_include_artifact', 'follow_ups') . ForgeConfig::get('sys_lf') . str_repeat("*", strlen($Language->getText('tracker_include_artifact', 'follow_ups')));
        } else {
            if ($rows > 0) {
                $out .= '<div style="text-align:right">';
                $out .= '<script type="text/javascript">
                    function tracker_expand_all_comments() {
                        $H(tracker_comment_togglers).values().each(function (value) {
                                (value)(null, true, true);
                        });
                    }

                    function tracker_collapse_all_comments() {
                        $H(tracker_comment_togglers).values().each(function (value) {
                                (value)(null, true, false);
                        });
                    }
                    var matches = location.hash.match(/#comment_(\d*)/);
                    var linked_comment_id = matches ? matches[1] : null;
                    </script>';
                $out .= '<a href="#expand_all" onclick="tracker_expand_all_comments(); return false;">' .
                 $Language->getText('tracker_include_artifact', 'expand_all') .
                 '</a> | <a href="#expand_all" onclick="tracker_collapse_all_comments(); return false;">' .
                 $Language->getText('tracker_include_artifact', 'collapse_all') . '</a></div>';
            }
        }

        // Loop throuh the follow-up comments and format them
        $last_visit_date = user_get_preference('tracker_' . $this->ArtifactType->getId() . '_artifact_' . $this->getId() . '_last_visit');
        for ($i = 0; $i < $rows; $i++) {
            $comment_type    = db_result($result, $i, 'comment_type');
            $comment_type_id = db_result($result, $i, 'comment_type_id');
            $comment_id      = db_result($result, $i, 'artifact_history_id');
            $field_name      = db_result($result, $i, 'field_name');
            $orig_subm       = $this->getOriginalCommentSubmitter($comment_id);
            $orig_date       = $this->getOriginalCommentDate($comment_id);
            $value           = db_result($result, $i, 'new_value');
            $isHtml          = db_result($result, $i, 'format');

            if (($comment_type_id == 100) || ($comment_type == "")) {
                $comment_type = '';
            } else {
                $comment_type = '[' . SimpleSanitizer::unsanitize($comment_type) . ']';
            }

            if ($output == self::OUTPUT_EXPORT || $output == self::OUTPUT_MAIL_TEXT) {
                $fmt = ForgeConfig::get('sys_lf') . ForgeConfig::get('sys_lf') . "------------------------------------------------------------------" . ForgeConfig::get('sys_lf') .
                    $Language->getText('tracker_import_utils', 'date') . ": %-30s" . $Language->getText('global', 'by') . ": %s" . ForgeConfig::get('sys_lf') . "%s";
                //The mail body
                $comment_txt = $this->formatFollowUp($group_id, $isHtml, $value, $output);
                $out        .= sprintf(
                    $fmt,
                    format_date(util_get_user_preferences_export_datefmt(), db_result($orig_date, 0, 'date')),
                    (db_result($orig_subm, 0, 'mod_by') == 100 ? db_result($orig_subm, 0, 'email') : user_getname(db_result($orig_subm, 0, 'mod_by'))),
                    ($comment_type != '' ? $comment_type . ForgeConfig::get('sys_lf') : '') . $comment_txt
                );
            } else {
                $style  = '';
                $toggle = 'ic/toggle_minus.png';
                if ($last_visit_date > db_result($orig_date, 0, 'date') && $i > 0) {
                    $style  = 'style="display:none;"';
                    $toggle = 'ic/toggle_plus.png';
                }
                $out .= "\n" . '
                    <div class="followup_comment" id="comment_' . $comment_id . '">
                        <div class="' . util_get_alt_row_color($i) . ' followup_comment_header">
                            <div class="followup_comment_title">';
                $out .= '<script type="text/javascript">document.write(\'<span>';
                $out .= $GLOBALS['HTML']->getImage(
                    $toggle,
                    [
                        'id' => 'comment_' . (int) $comment_id . '_toggle',
                        'style' => 'vertical-align:middle; cursor:hand; cursor:pointer;',
                        'title' => addslashes($GLOBALS['Language']->getText('tracker_include_artifact', 'toggle')),
                    ]
                );
                $out .= '</span>\');</script>';
                $out .= '<script type="text/javascript">';
                $out .= "tracker_comment_togglers[" . (int) $comment_id . "] = function (evt, force, expand) {
                        var toggle = $('comment_" . (int) $comment_id . "_toggle');
                        var element = $('comment_" . (int) $comment_id . "_content');
                        if (element) {
                            if (!force || (expand && !element.visible()) || (!expand && element.visible())) {
                                Element.toggle(element);

                                //replace image
                                var src_search = 'toggle_minus';
                                var src_replace = 'toggle_plus';
                                if (toggle.src.match('toggle_plus')) {
                                    src_search = 'toggle_plus';
                                    src_replace = 'toggle_minus';
                                }
                                toggle.src = toggle.src.replace(src_search, src_replace);
                            }
                        }
                        if (evt) {
                            Event.stop(evt);
                        }
                        return false;
                    };
                    Event.observe($('comment_" . (int) $comment_id . "_toggle'), 'click', tracker_comment_togglers[" . (int) $comment_id . "]);";
                $out .= '</script>';
                $out .= '<span><a href="#comment_' . (int) $comment_id . '" title="Link to this comment - #' . (int) $comment_id . '" onclick="tracker_comment_togglers[' . (int) $comment_id . '](null, true, true);">';
                $out .= $GLOBALS['HTML']->getImage('ic/comment.png', ['border' => 0, 'style' => 'vertical-align:middle', 'title' => 'Link to this comment - #' . (int) $comment_id]);
                $out .= '</a> </span>';
                $out .= '<span class="followup_comment_title_user">';
                if (db_result($orig_subm, 0, 'mod_by') == 100) {
                    $out .= db_result($orig_subm, 0, 'email');
                } else {
                    $out .= '<a href="/users/' . urlencode(user_getname(db_result($orig_subm, 0, 'mod_by'))) . '">' . $hp->purify($uh->getDisplayNameFromUserId(db_result($orig_subm, 0, 'mod_by')), CODENDI_PURIFIER_CONVERT_HTML) . '</a>';
                }

                $out .= ' </span>';
                $out .= '<span class="followup_comment_title_date">';
                $out .= DateHelper::timeAgoInWords(db_result($orig_date, 0, 'date'), false, true);
                $out .= '</span>';
                if ($field_name != "comment") {
                    $out .= "  (" . $GLOBALS['Language']->getText('tracker_include_artifact', 'last_edited') . " ";
                    $out .= '<span class="followup_comment_title_edited_user">';
                    if (db_result($result, $i, 'mod_by') == 100) {
                        $out .= db_result($result, $i, 'email');
                    } else {
                        $out .= '<a href="/users/' . urlencode(user_getname(db_result($result, $i, 'mod_by'))) . '">' . $hp->purify(user_getname(db_result($result, $i, 'mod_by')), CODENDI_PURIFIER_CONVERT_HTML) . '</a>';
                    }
                    $out .= ' </span>';
                    $out .= '<span class="followup_comment_title_date">';
                    $out .= DateHelper::timeAgoInWords(db_result($result, $i, 'date'), false, true);
                    $out .= '</span>' . ")";
                }
                $out .= "\n</div><!-- followup_comment_title -->\n";
                $out .= '<div class="followup_comment_title_toolbar">';
                if (db_result($orig_subm, 0, 'mod_by') == 100) {
                    $user_quoted = db_result($orig_subm, 0, 'email');
                } else {
                    $user_quoted = $uh->getDisplayNameFromUserId(db_result($orig_subm, 0, 'mod_by')) ?? '';
                }
                $user_quoted = addslashes(addslashes($user_quoted));
                if ($pv == 0) {
                    $out .= '<script type="text/javascript">document.write(\'<a href="#quote" onclick="tracker_quote_comment(\\\'' . $user_quoted . '\\\', \\\'' . (int) $comment_id . '\\\'); return false;" title="quote">';
                    $out .= $GLOBALS['HTML']->getImage('ic/quote.png', ['border' => 0, 'alt' => 'quote']);
                    $out .= '</a>\');</script>';
                }
                if ($this->userCanEditFollowupComment($comment_id) && ! $pv) {
                    $out .= '<a href="/tracker/?func=editcomment&group_id=' . (int) $group_id . '&aid=' . (int) $this->getID() . '&atid=' . (int) $group_artifact_id . '&artifact_history_id=' . (int) $comment_id . '" title="' . $GLOBALS['Language']->getText('tracker_fieldeditor', 'edit') . '">';
                    $out .= $GLOBALS['HTML']->getImage('ic/edit.png', ['border' => 0, 'alt' => $GLOBALS['Language']->getText('tracker_fieldeditor', 'edit')]);
                    $out .= '</a>';
                    $out .= '<a href="/tracker/?func=delete_comment&group_id=' . (int) $group_id . '&aid=' . (int) $this->getID() . '&atid=' . (int) $group_artifact_id . '&artifact_history_id=' . (int) $comment_id . '" ';
                    $out .= ' onClick="return confirm(\'' . $GLOBALS['Language']->getText('tracker_include_artifact', 'delete_comment') . '\')" title="' . $GLOBALS['Language']->getText('tracker_include_artifact', 'del') . '">';
                    $out .= $GLOBALS['HTML']->getImage('ic/close.png', ['border' => 0, 'alt' => $GLOBALS['Language']->getText('tracker_include_artifact', 'del')]);
                    $out .= '</a>';
                }
                $out .= "\n</div><!-- followup_comment_title_toolbar -->\n";
                $out .= '<div style="clear:both;"></div>';
                $out .= "\n</div><!-- followup_comment_header -->\n";
                $out .= '<div class="followup_comment_content" ' . $style . ' id="comment_' . (int) $comment_id . '_content">';
                if ($comment_type != "") {
                    $out .= '<div class="followup_comment_content_type"><b>' .  $hp->purify($comment_type, CODENDI_PURIFIER_CONVERT_HTML)  . '</b></div>';
                }
                $out .= $this->formatFollowUp($group_id, $isHtml, $value, $output);
                $out .= '</div>';
                $out .= '</div>';
                $out .= '<script type="text/javascript">
                    if (linked_comment_id == ' . (int) $comment_id . ') {
                        tracker_comment_togglers[' . (int) $comment_id . '](null, true, true);
                    }
                    </script>';
            }
        }
        if ($output == self::OUTPUT_BROWSER) {
            if ($rows > 0) {
                $out .= '<div style="text-align:right">';
                $out .= '<a href="#expand_all" onclick="tracker_expand_all_comments(); return false;">' .
                $Language->getText('tracker_include_artifact', 'expand_all') .
                '</a> | <a href="#expand_all" onclick="tracker_collapse_all_comments(); return false;">' .
                $Language->getText('tracker_include_artifact', 'collapse_all') . '</a></div>';
            }
        }

        // final touch...
        $out .= (($output != self::OUTPUT_BROWSER) ? ForgeConfig::get('sys_lf') : "");

        return($out);
    }

                /**
         * Display the list of CC addresses
         *
         * @param group_id: the group id
         * @param group_artifact_id: the artifact type ID
         * @param ascii: ascii mode
         *
         * @return string
         */
    public function showCCList($group_id, $group_artifact_id, $ascii = false, $pv = 0)
    {
        $hp = Codendi_HTMLPurifier::instance();
        global $Language;

        //      format the CC list for this artifact
        $result = $this->getCCList();
        $rows   = db_numrows($result);
        $out    = '';

        // Nobody in the CC list -> return now
        if ($rows <= 0) {
            if ($ascii) {
                $out = $Language->getText('tracker_include_artifact', 'cc_empty') . ForgeConfig::get('sys_lf');
            } else {
                $out = '<H4>' . $Language->getText('tracker_include_artifact', 'cc_empty') . '</H4>';
            }
                    return $out;
        }

        // Header first an determine what the print out format is
        // based on output type (Ascii, HTML)
        if ($ascii) {
            $out         .= $Language->getText('tracker_include_artifact', 'cc_list') . ForgeConfig::get('sys_lf') . str_repeat("*", strlen($Language->getText('tracker_include_artifact', 'cc_list'))) . ForgeConfig::get('sys_lf') . ForgeConfig::get('sys_lf');
                    $fmt  = "%-35s | %s" . ForgeConfig::get('sys_lf');
                    $out .= sprintf($fmt, $Language->getText('tracker_include_artifact', 'cc_address'), $Language->getText('tracker_include_artifact', 'fill_cc_list_cmt'));
                    $out .= "------------------------------------------------------------------" . ForgeConfig::get('sys_lf');
        } else {
                    $title_arr   = [];
                    $title_arr[] = $Language->getText('tracker_include_artifact', 'cc_address');
                    $title_arr[] = $Language->getText('tracker_include_artifact', 'fill_cc_list_cmt');
                    $title_arr[] = $Language->getText('tracker_include_artifact', 'added_by');
                    $title_arr[] = $Language->getText('tracker_include_artifact', 'posted_on');
            if ($pv == 0) {
                $title_arr[] = $Language->getText('tracker_include_canned', 'delete');
            }
                    $out .= html_build_list_table_top($title_arr);

                    $fmt = "\n" . '<TR class="%s"><td>%s</td><td>%s</td><td align="center">%s</td><td align="center">%s</td>';
            if ($pv == 0) {
                $fmt .= '<td align="center">%s</td>';
            }
                    $fmt .= '</tr>';
        }

        // Loop through the cc and format them
        for ($i = 0; $i < $rows; $i++) {
                    $email          = db_result($result, $i, 'email');
                    $artifact_cc_id = db_result($result, $i, 'artifact_cc_id');

                    // if the CC is a user point to its user page else build a mailto: URL
                    $res_username = user_get_result_set_from_unix($email);
            if ($res_username && (db_numrows($res_username) == 1)) {
                $href_cc = util_user_link($email);
            } else {
                $href_cc = '<a href="mailto:' . util_normalize_email($email) . '">' . $email . '</a>';
            }

            if ($ascii) {
                $out .= sprintf($fmt, $email, SimpleSanitizer::unsanitize(db_result($result, $i, 'comment')));
            } else {
                $user_id = UserManager::instance()->getCurrentUser()->getId();
                // show CC delete icon if one of the condition is met:
                // (a) current user is a group member
                // (b) the CC name is the current user
                // (c) the CC email address matches the one of the current user
                // (d) the current user is the person who added a gieven name in CC list
                if (
                    user_ismember($this->ArtifactType->getGroupID()) ||
                    (user_getname($user_id) == $email) ||
                    (user_getemail($user_id) == $email) ||
                    (user_getname($user_id) == db_result($result, $i, 'user_name') )
                ) {
                            $html_delete = '<a href="?func=delete_cc&group_id=' . (int) $group_id . '&aid=' . (int) $this->getID() . '&atid=' . (int) $group_artifact_id . '&artifact_cc_id=' . (int) $artifact_cc_id . '" ' .
                            ' onClick="return confirm(\'' . $Language->getText('tracker_include_artifact', 'delete_cc') . '\')">' .
                            '<IMG SRC="' . util_get_image_theme("ic/trash.png") . '" HEIGHT="16" WIDTH="16" BORDER="0" ALT="' . $Language->getText('global', 'btn_delete') . '"></A>';
                } else {
                            $html_delete = '-';
                }

                $out .= sprintf(
                    $fmt,
                    util_get_alt_row_color($i),
                    $href_cc,
                    $hp->purify(SimpleSanitizer::unsanitize(db_result($result, $i, 'comment')), CODENDI_PURIFIER_BASIC, $this->ArtifactType->getGroupId()),
                    util_user_link(db_result($result, $i, 'user_name')),
                    format_date($GLOBALS['Language']->getText('system', 'datefmt'), db_result($result, $i, 'date')),
                    $html_delete
                );
            } // for
        }

        // final touch...
        $out .= ($ascii ? ForgeConfig::get('sys_lf') : "</TABLE>");

        return($out);
    }

                /**
         * Display the artifact dependencies list
         *
         * @param group_id: the group id
         * @param group_artifact_id: the artifact type ID
         * @param ascii: ascii mode
         *
         * @return string
         */
    public function showDependencies($group_id, $group_artifact_id, $ascii = false, $pv = 0)
    {
        $hp = Codendi_HTMLPurifier::instance();
        global $Language;

        //      format the dependencies list for this artifact
        $result = $this->getDependencies();
        $rows   = db_numrows($result);
        $out    = '';
        // Nobody in the dependencies list -> return now
        if ($rows <= 0) {
            if ($ascii) {
                $out = $Language->getText('tracker_include_artifact', 'dep_list_empty') . ForgeConfig::get('sys_lf');
            } else {
                $out = '<H4>' . $Language->getText('tracker_include_artifact', 'dep_list_empty') . '</H4>';
            }
                    return $out;
        }

        // Header first an determine what the print out format is
        // based on output type (Ascii, HTML)
        if ($ascii) {
            $out         .= $Language->getText('tracker_include_artifact', 'dep_list') . ForgeConfig::get('sys_lf') . str_repeat("*", strlen($Language->getText('tracker_include_artifact', 'dep_list'))) . ForgeConfig::get('sys_lf') . ForgeConfig::get('sys_lf');
                    $fmt  = "%-15s | %s (%s)" . ForgeConfig::get('sys_lf');
                    $out .= sprintf(
                        $fmt,
                        $Language->getText('tracker_include_artifact', 'artifact'),
                        $Language->getText('tracker_include_artifact', 'summary'),
                        $Language->getText('global', 'status')
                    );
                    $out .= "------------------------------------------------------------------" . ForgeConfig::get('sys_lf');
        } else {
                    $title_arr   = [];
                    $title_arr[] = $Language->getText('tracker_include_artifact', 'artifact');
                    $title_arr[] = $Language->getText('tracker_include_artifact', 'summary');
                    $title_arr[] = $Language->getText('global', 'status');
                    $title_arr[] = $Language->getText('tracker_import_admin', 'tracker');
                    $title_arr[] = $Language->getText('tracker_include_artifact', 'group');
            if ($pv == 0) {
                $title_arr[] = $Language->getText('tracker_include_canned', 'delete');
            }
                    $out .= html_build_list_table_top($title_arr);

                    $fmt = "\n" . '<TR class="%s"><td>%s</td><td>%s</td><td align="center">%s</td><td align="center">%s</td><td align="center">%s</td>';
            if ($pv == 0) {
                $fmt .= '<td align="center">%s</td>';
            }
                    $fmt .= '</tr>';
        }

        // Loop through the denpendencies and format them
        for ($i = 0; $i < $rows; $i++) {
                    $dependent_on_artifact_id = db_result($result, $i, 'is_dependent_on_artifact_id');
                    $summary                  = db_result($result, $i, 'summary');
                    $status                   = db_result($result, $i, 'status');
                    $tracker_label            = db_result($result, $i, 'name');
                    $group_label              = db_result($result, $i, 'group_name');

            if ($ascii) {
                $out .= sprintf($fmt, $dependent_on_artifact_id, util_unconvert_htmlspecialchars($summary), $status);
            } else {
                if (user_ismember($this->ArtifactType->getGroupID())) {
                            $html_delete = '<a href="?func=delete_dependent&group_id=' . (int) $group_id . '&aid=' . (int) $this->getID() . '&atid=' . (int) $group_artifact_id . '&dependent_on_artifact_id=' . (int) $dependent_on_artifact_id . '" ' .
                            ' onClick="return confirm(\'' . $Language->getText('tracker_include_artifact', 'del_dep') . '\')">' .
                            '<IMG SRC="' . util_get_image_theme("ic/trash.png") . '" HEIGHT="16" WIDTH="16" BORDER="0" ALT="' . $Language->getText('global', 'btn_delete') . '"></A>';
                } else {
                            $html_delete = '-';
                }

                $out .= sprintf(
                    $fmt,
                    util_get_alt_row_color($i),
                    '<a href="/tracker/?func=gotoid&group_id=' . (int) $group_id . '&aid=' . (int) $dependent_on_artifact_id . '">' . (int) $dependent_on_artifact_id . '</a>',
                    $hp->purify(util_unconvert_htmlspecialchars($summary), CODENDI_PURIFIER_CONVERT_HTML),
                    $hp->purify($status, CODENDI_PURIFIER_CONVERT_HTML),
                    $hp->purify(SimpleSanitizer::unsanitize($tracker_label), CODENDI_PURIFIER_CONVERT_HTML),
                    $hp->purify($group_label, CODENDI_PURIFIER_CONVERT_HTML),
                    $html_delete
                );
            } // for
        }

        // final touch...
        $out .= ($ascii ? ForgeConfig::get('sys_lf') : "</TABLE>");

        return($out);
    }

                /**
         * Display the list of attached files
         *
         * @param group_id: the group id
         * @param group_artifact_id: the artifact type ID
         * @param ascii: ascii mode
         *
         * @return string
         */
    public function showAttachedFiles($group_id, $group_artifact_id, $ascii = false, $pv = 0)
    {
        global $Language;
        $hp = $this->getHtmlPurifier();
        //  show the files attached to this artifact
        $result = $this->getAttachedFiles();
        $rows   = db_numrows($result);

        // No file attached -> return now
        if ($rows <= 0) {
            if ($ascii) {
                $out = $Language->getText('tracker_include_artifact', 'no_file_attached') . ForgeConfig::get('sys_lf');
            } else {
                $out = '<H4>' . $Language->getText('tracker_include_artifact', 'no_file_attached') . '</H4>';
            }
                    return $out;
        }

        // Header first
        if ($ascii) {
            $out = $Language->getText('tracker_include_artifact', 'file_attachment') . ForgeConfig::get('sys_lf') . str_repeat("*", strlen($Language->getText('tracker_include_artifact', 'file_attachment')));
        } else {
            $title_arr   = [];
            $title_arr[] = $Language->getText('tracker_include_artifact', 'name');
            $title_arr[] = $Language->getText('tracker_include_artifact', 'desc');
            $title_arr[] = $Language->getText('tracker_include_artifact', 'size_kb');
            $title_arr[] = $Language->getText('global', 'by');
            $title_arr[] = $Language->getText('tracker_include_artifact', 'posted_on');
            if ($pv == 0) {
                $title_arr[] = $Language->getText('tracker_include_canned', 'delete');
            }

            $out = html_build_list_table_top($title_arr);
        }

        // Determine what the print out format is based on output type (Ascii, HTML)
        if ($ascii) {
                    $fmt = ForgeConfig::get('sys_lf') . ForgeConfig::get('sys_lf') . "------------------------------------------------------------------" . ForgeConfig::get('sys_lf') .
                        $Language->getText('tracker_import_utils', 'date') . ": %s  " . $Language->getText('tracker_include_artifact', 'name') . ": %s  " . $Language->getText('tracker_include_artifact', 'size') . ": %dKB   " . $Language->getText('global', 'by') . ": %s" . ForgeConfig::get('sys_lf') . "%s" . ForgeConfig::get('sys_lf') . "%s";
        } else {
                    $fmt = "" . ForgeConfig::get('sys_lf') . '<TR class="%s"><td>%s</td><td>%s</td><td align="center">%s</td><td align="center">%s</td><td align="center">%s</td>';
            if ($pv == 0) {
                $fmt .= '<td align="center">%s</td>';
            }
                    $fmt .= '</tr>';
        }

        // Determine which protocl to use for embedded URL in ASCII format
        $server = \Tuleap\ServerHostname::HTTPSUrl();

        // Loop throuh the attached files and format them
        for ($i = 0; $i < $rows; $i++) {
                    $artifact_file_id = db_result($result, $i, 'id');
                    $href             = "/tracker/download.php?artifact_id=" . (int) $this->getID() . "&id=" . (int) $artifact_file_id;

            if ($ascii) {
                $out .= sprintf(
                    $fmt,
                    format_date($GLOBALS['Language']->getText('system', 'datefmt'), db_result($result, $i, 'adddate')),
                    db_result($result, $i, 'filename'),
                    intval(db_result($result, $i, 'filesize') / 1024),
                    db_result($result, $i, 'user_name'),
                    SimpleSanitizer::unsanitize(db_result($result, $i, 'description')),
                    $server . $href
                );
            } else {
                // show CC delete icon if one of the condition is met:
                // (a) current user is group member
                // (b) the current user is the person who added a gieven name in CC list
                if (
                    user_ismember($this->ArtifactType->getGroupID()) ||
                    (user_getname(UserManager::instance()->getCurrentUser()->getId()) == db_result($result, $i, 'user_name') )
                ) {
                                        $html_delete = '<a href="?func=delete_file&group_id=' . (int) $group_id . "&atid=" . (int) $group_artifact_id . "&aid=" . (int) $this->getID() . "&id=" . (int) db_result($result, $i, 'id') . '" ' .
                                            ' onClick="return confirm(\'' . $Language->getText('tracker_include_artifact', 'delete_attachment') . '\')">' .
                                            '<IMG SRC="' . util_get_image_theme("ic/trash.png") . '" HEIGHT="16" WIDTH="16" BORDER="0" ALT="' . $Language->getText('global', 'btn_delete') . '"></A>';
                } else {
                                    $html_delete = '-';
                }
                                $out .= sprintf(
                                    $fmt,
                                    util_get_alt_row_color($i),
                                    '<a href="' . $href . '">' .  $hp->purify(db_result($result, $i, 'filename'), CODENDI_PURIFIER_CONVERT_HTML) . '</a>',
                                    $hp->purify(SimpleSanitizer::unsanitize(db_result($result, $i, 'description')), CODENDI_PURIFIER_BASIC, $group_id),
                                    intval(db_result($result, $i, 'filesize') / 1024),
                                    util_user_link(db_result($result, $i, 'user_name')),
                                    format_date($GLOBALS['Language']->getText('system', 'datefmt'), db_result($result, $i, 'adddate')),
                                    $html_delete
                                );
            }
        } // for

        // final touch...
        $out .= ($ascii ? "" . ForgeConfig::get('sys_lf') . "" : "</TABLE>");

        return($out);
    }

    /** Update the last_update_date field in the Artifact table to 'now'
     */
    public function update_last_update_date()
    {
        $sql = "UPDATE artifact SET last_update_date=" . time() .
            " WHERE artifact_id=" . db_ei($this->getID());

        return db_query($sql);
    }

    /**
     * Returns an instance of Codendi_HTMLPurifier
     *
     * @return Codendi_HTMLPurifier
     */
    public function getHTMLPurifier()
    {
        return Codendi_HTMLPurifier::instance();
    }

    /**
     * Format the comment text to a given format according to parameters
     *
     * @param int $groupId Project id
     * @param bool $commentFormat $value's format
     * @param String  $value         Comment content
     * @param int $output Output format
     *
     * @return String
     */
    public function formatFollowUp($groupId, $commentFormat, $value, $output)
    {
        $commentText = '';
        if ($output == self::OUTPUT_EXPORT) {
            return util_unconvert_htmlspecialchars($value);
        } else {
            $hp = $this->getHTMLPurifier();
            if ($output == self::OUTPUT_MAIL_TEXT) {
                if ($commentFormat == self::FORMAT_HTML) {
                    $commentText = $hp->purify(util_unconvert_htmlspecialchars($value), CODENDI_PURIFIER_STRIP_HTML);
                } else {
                    $commentText = $value;
                }
                $commentText = util_unconvert_htmlspecialchars($commentText);
            } else {
                if ($commentFormat == self::FORMAT_HTML) {
                    $level = CODENDI_PURIFIER_LIGHT;
                } else {
                    $level = CODENDI_PURIFIER_BASIC;
                }
                $commentText =  $hp->purify(util_unconvert_htmlspecialchars($value), $level, $groupId);
            }
            return $commentText;
        }
    }

    /**
     * @param string $string
     */
    public function setError($string): void
    {
        $this->error_state   = true;
        $this->error_message = $string;
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        if ($this->error_state) {
            return $this->error_message;
        } else {
            return $GLOBALS['Language']->getText('include_common_error', 'no_err');
        }
    }

    /**
     * @return bool
     */
    public function isError()
    {
        return $this->error_state;
    }
}
