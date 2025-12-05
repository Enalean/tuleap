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


/**
 *
 * Artifact.php - Main Artifact class
 */
class Artifact // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    public const int FORMAT_TEXT = 0;
    public const int FORMAT_HTML = 1;

    //The diffetents mode of display
    public const int OUTPUT_EXPORT    = 1;
    public const int OUTPUT_MAIL_TEXT = 2;

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
                 $field->getName() == 'comment_type_id'
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
        $sql        = 'SELECT afv.valueInt
              FROM artifact_field_value afv, artifact a, artifact_field af
              WHERE a.artifact_id=' . db_ei($aid) . '
                AND afv.artifact_id=' . db_ei($aid) . "
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
     *  getSubmittedBy - get ID of submitter.
     *
     *  @return int user_id of submitter.
     */
    public function getSubmittedBy()
    {
        return $this->data_array['submitted_by'];
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
            $email = '';
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
            if ($name == 'comment' || (preg_match('/^(lbl_)/', $name) && preg_match('/(_comment)$/', $name))) {
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
            'VALUES (' . db_ei($this->getID()) . ",'" . db_es($name) . "','" . db_es($old_value) . "','" . db_es($new_value) . "','" . db_ei($user) . "','" . db_es($email) . "','" . time() . "' $val_type)";
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
        $error_message     = ($import ? $Language->getText('tracker_common_artifact', 'row', $row) : '');

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
            $vfl['submitted_by'] != ''
        ) {
            $user = $vfl['submitted_by'];
        }

        // first make sure this wasn't double-submitted
        $field = $art_field_fact->getFieldFromName('summary');
        if ($field && $field->isUsed()) {
            $res = db_query('SELECT *
                FROM artifact
                WHERE group_artifact_id = ' . db_ei($ath->getID()) . '
                AND submitted_by=' .  db_ei($user) . "
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
            if (! isset($vfl['open_date']) || ! $vfl['open_date'] || $vfl['open_date'] == '') {
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
            $request                         = \Tuleap\HTTPRequest::instance();
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
                    $this->addHistory('comment', '', htmlspecialchars($comment), 100, $email, null, $comment_format);
                } else {
                    $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_common_artifact', 'enter_email'));
                    return false;
                }
            } else {
                $this->addHistory('comment', '', htmlspecialchars($comment), 100, null, null, $comment_format);
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
     * Wrapper for tests
     *
     * @return ReferenceManager
     */
    public function getReferenceManager()
    {
        return ReferenceManager::instance();
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
            $sql = 'UPDATE artifact
                    SET use_artifact_permissions = ' . ($use_artifact_permissions ? 1 : 0) . '
                    WHERE artifact_id=' . db_ei($this->getID());
            db_query($sql);
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
        $sql = 'SELECT is_dependent_on_artifact_id FROM artifact_dependencies WHERE artifact_id=' . db_ei($this->getID()) . ' AND is_dependent_on_artifact_id=' . db_ei($id);
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
        $sql = 'SELECT * FROM artifact a, artifact_group_list agl WHERE ' .
            'a.group_artifact_id = agl.group_artifact_id AND a.artifact_id=' . db_ei($id) . ' AND ' .
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
        $sql = 'INSERT INTO artifact_dependencies (artifact_id,is_dependent_on_artifact_id) ' .
            'VALUES (' . db_ei($this->getID()) . ',' . db_ei($id) . ')';
        //echo $sql;
        $res = db_query($sql);
        return ($res);
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
        $ids = explode(',', $artifact_id_dependent);
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
     * Return the history events for this artifact (excluded comment events - See followups)
     *
     * @return array
     */
    public function getHistory()
    {
        //Addition of new followup comments is not recorded in history (update and removal of followups is recorded)
        $sql = 'SELECT artifact_history.field_name,artifact_history.old_value,artifact_history.new_value,artifact_history.date,artifact_history.type,user.user_name ' .
            'FROM artifact_history,user ' .
            'WHERE artifact_history.mod_by=user.user_id ' .
            'AND artifact_id=' . db_ei($this->getID()) .
            " AND artifact_history.field_name <> 'comment' " .
        'ORDER BY artifact_history.date DESC';
        return db_query($sql);
    }

    /**
     * Return the artifact dependencies values
     *
     * @return array
     */
    public function getDependencies()
    {
        $sql = 'SELECT d.artifact_depend_id, d.is_dependent_on_artifact_id, d.artifact_id, a.summary, afvl.value as status, ag.group_artifact_id, ag.name, g.group_id, g.group_name ' .
            'FROM artifact_dependencies d, artifact_group_list ag, `groups` g, artifact a, artifact_field_value_list afvl, artifact_field f ' .
            'WHERE d.is_dependent_on_artifact_id = a.artifact_id AND ' .
            'afvl.field_id = f.field_id AND ' .
            'f.group_artifact_id = a.group_artifact_id AND ' .
            "f.field_name = 'status_id' AND " .
            'afvl.value_id = a.status_id AND ' .
            'afvl.group_artifact_id = a.group_artifact_id AND ' .
            'a.group_artifact_id = ag.group_artifact_id AND ' .
            'd.artifact_id = ' . db_ei($this->getID()) . ' AND ' .
            'ag.group_id = g.group_id ORDER BY a.artifact_id';
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
        $sql    = 'SELECT filename ' .
            'FROM artifact_file ' .
            'WHERE artifact_id=' . db_ei($this->getID()) . ' ORDER BY adddate DESC';
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
     *      userCanView - determine if the user can view this artifact.
     *
     *      @param $my_user_id    if not specified, use the current user id..
     *      @return bool user_can_view.
     */
    public function userCanView($my_user_id = PFUser::ANONYMOUS_USER_ID)
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
