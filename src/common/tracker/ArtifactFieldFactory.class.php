<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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

// Sort by place result
function art_field_factory_cmp_place($field1, $field2)
{
    if ($field1->getPlace() < $field2->getPlace()) {
        return -1;
    } elseif ($field1->getPlace() > $field2->getPlace()) {
        return 1;
    }
    return 0;
}

class ArtifactFieldFactory
{

    // The artifact type object
    public $ArtifactType;

    // The fields array indexed by name
    public $USAGE_BY_NAME;

    // The fields array indexed by id
    public $USAGE_BY_ID;
    /**
     * @var string
     */
    private $error_message = '';
    /**
     * @var bool
     */
    private $error_state = false;

    /**
     *
     *
     *  @param ArtifactType: the artifact type object
     *    @return bool success.
     */
    public function __construct(&$ArtifactType)
    {
        global $Language;

        if (!$ArtifactType || !is_object($ArtifactType)) {
            $this->setError($Language->getText('tracker_common_canned', 'not_valid'));
            return false;
        }
        if ($ArtifactType->isError()) {
            $this->setError('ArtifactFieldFactory: ' . $ArtifactType->getErrorMessage());
            return false;
        }

        $this->ArtifactType = $ArtifactType;

        $this->USAGE_BY_NAME = array();
        $this->USAGE_BY_ID = array();

        $this->fetchData($this->ArtifactType->getID());

        return true;
    }

    /**
     *  Retrieve the fields associated with an artifact type
     *
     *  @param group_artifact_id: the artifact type id
     *    @return bool success.
     */
    public function fetchData($group_artifact_id)
    {
        $sql = 'SELECT af.field_id, field_name, display_type, data_type, ' .
        'display_size,label, description,scope,required,empty_ok,keep_history,special, ' .
        'value_function,' .
        'af.group_artifact_id, use_it, place, default_value, field_set_id ' .
        'FROM artifact_field_usage afu, artifact_field af ' .
        'WHERE afu.group_artifact_id=' . db_ei($group_artifact_id) . ' ' .
        'AND afu.field_id=af.field_id AND af.group_artifact_id=' . db_ei($group_artifact_id);

     //echo $sql;

        $res = db_query($sql);

        // Now put all used fields in a global array for faster access
        // Index both by field_name and field_id
        while ($field_array = db_fetch_array($res)) {
         //echo $field_array['field_name']."<br>";
            $this->USAGE_BY_ID[$field_array['field_id']] = new ArtifactField();
            $obj = $this->USAGE_BY_ID[$field_array['field_id']];
            $obj->setFromArray($field_array);
            $this->USAGE_BY_ID[$field_array['field_id']] = $obj;

            $this->USAGE_BY_NAME[$field_array['field_name']] = new ArtifactField();
            $obj = $this->USAGE_BY_NAME[$field_array['field_name']];
            $obj->setFromArray($field_array);
            $this->USAGE_BY_NAME[$field_array['field_name']] = $obj;
        }

        // rewind internal pointer of global arrays
        reset($this->USAGE_BY_ID);
        reset($this->USAGE_BY_NAME);
    }

    /**
     *  Get the field object using his name
     *
     *  @param field_name: the field name
     *    @return    ArtifactField object
     */
    public function getFieldFromName($field_name)
    {
        $field = isset($this->USAGE_BY_NAME[$field_name]) ? $this->USAGE_BY_NAME[$field_name] : false;
        return $field;
    }

    /**
     *  Get the field object using his id
     *
     *  @param field_id: the field id
     *    @return    ArtifactField object
     */
    public function getFieldFromId($field_id)
    {
            return isset($this->USAGE_BY_ID[$field_id]) ? $this->USAGE_BY_ID[$field_id] : null;
    }

    /**
     *  Return all the fields used
     *
     *    @return    array
     */
    public function getAllUsedFields()
    {
        $result_fields = array();
        foreach ($this->USAGE_BY_NAME as $key => $field) {
            if ($field->getUseIt() == 1) {
                $result_fields[$key] = $field;
            }
        }

        uasort($result_fields, "art_field_factory_cmp_place");
        return $result_fields;
    }

    /**
     *  Return all the fields unused
     *
     *    @return    array
     */
    public function getAllUnusedFields()
    {
        $result_fields = array();
        foreach ($this->USAGE_BY_NAME as $key => $field) {
            if ($field->getUseIt() == 0) {
                $result_fields[$key] = $field;
            }
        }

        uasort($result_fields, "art_field_factory_cmp_place");
        return $result_fields;
    }

    /**
     *
     *  Returns the list of field names in the HTML Form corresponding to a
     *  field used by this project
     *
     *
     *    @return    array
     */
    public function extractFieldList($post_method = true, $prefix = null)
    {
        $request = HTTPRequest::instance();

        $vfl = array();
        if ($post_method) {
            foreach ($_POST as $key => $val) {
                //verify if the prefix param is given and cut the
                //prefix from the key
                if ($prefix != null) {
                    $pos = strpos($key, $prefix);
                    if (!is_bool($pos) && $pos == 0) {
                        $postfix =  substr($key, strlen($prefix));
                        if (isset($this->USAGE_BY_NAME[$postfix])) {
                            $vfl[$postfix] = $request->get($key);
                        }
                    }
                } else {
                    if (isset($this->USAGE_BY_NAME[$key])) {
                        $vfl[$key] = $request->get($key);
           //echo "Accepted key = ".$key." val = $val<BR>";
                    } else {
                      // we add operator for date filtering (used for masschange)
                      // the field present in HTTP_POST_VARS is named like [$field_name]_op
                        if (
                            (isset($this->USAGE_BY_NAME[substr($key, 0, strlen($key) - strlen('_op'))]) && substr($key, -3) == '_op') ||
                            (isset($this->USAGE_BY_NAME[substr($key, 0, strlen($key) - strlen('_end'))]) && substr($key, -4) == '_end')
                        ) {
                             $vfl[$key] = $request->get($key);
                        } else {
                            //echo "Rejected key = ".$key." val = $val<BR>";
                        }
                    }
                }
            }
        } else {
            foreach ($_GET as $key => $val) {
                if (isset($this->USAGE_BY_NAME[$key])) {
                    $vfl[$key] = $request->get($key);
                 //echo "Accepted key = ".$key." val = $val<BR>";
                } else {
              //echo "Rejected key = ".$key." val = $val<BR>";
                }
            }
        }

     /**
      * Hack to manage the case when the target is a mandatory multiple select list.
      *
      * Without this fix we are able to by pass the mandatory checking (ie user can submit artifact with
      * field value == none and codendi doesn't raise any error.
      * This bad behaviour comes from the way the submitted values are checked:
      * The code above relies on what is submitted by the user, it build the list of fields
      * from what the user submitted, later on it will check if the values are correct.
      *
      * For lists, it check against "none" value: if none is present but "allow empty value"
      * forbidden, it raises an error. It works because None is always present and is the default
      * value (except if configured by admin to another value).
      * But when there are dependencies, the none value disapear. so there is no longer any selected
      * value in the field (for multi select box). So when the code above populate the array of
      * submitted values, the multi select box is not present (because no value is submitted).
      * Later on, "checkEmptyValues" or field dependencies constraints are not checked because those
      * 2 methods rely on what this method (extractFieldList) returns.
      *
      * So, to make a long story short: in order to make everything work, the code below loop over
      * all used fields and if one field is missing in $vfl, it adds it with empty value.
      *
      * Note: mass change is not supported yet.
      *
      * @see src/www/tracker/include/ArtifactHtml.class.php#displayAdd
      */
        $user = UserManager::instance()->getCurrentUser();
        foreach ($this->getAllUsedFields() as $field) {
            // if ( (!$field->isSpecial() || $field->getName()=='summary' || $field->getName()=='details') ) {
            // The test here restrict to multiselect box but as I have no idea of the potential
            // impact of doing it on all possible fields, it's more safe
            if ($field->getDisplayType() == 'MB') {
                if (
                    ($request->get('func') == 'postadd' && $field->userCanSubmit($this->ArtifactType->getGroupId(), $this->ArtifactType->getID(), $user->getId()))
                    || ($request->get('func') == 'postmod' && $field->userCanUpdate($this->ArtifactType->getGroupId(), $this->ArtifactType->getID(), $user->getId()))
                ) {
                    if (!isset($vfl[$field->field_name])) {
                           $vfl[$field->field_name] = '';
                    }
                }
            }
        }

        return($vfl);
    }

    /**
     *
     *  Check whether empty values are allowed for the bug fields
     *
     *  @param Array   $field_array   associative array of field_name -> value
     *  @param bool $showFeedback default value set to true, manage the display or not of the error message with reference to the field label
     *
     *    @return bool
     */
    public function checkEmptyFields($field_array, $showFeedback = true)
    {
        global $Language;

        $bad_fields = array();
        foreach ($field_array as $key => $val) {
            //Those fields are automatically filled out
            if ($key != 'artifact_id' && $key != 'open_date' && $key != 'last_update_date') {
                $field = $this->getFieldFromName($key);
                if ($field) {
                    if ($field->isMultiSelectBox()) {
                        $is_empty = (implode(",", $val) == "100");
                    } else {
                        $is_empty = ( ($field->isSelectBox()) ? ($val == 100) : ($val == ''));
                    }
                    if ($is_empty && !$field->isEmptyOk()) {
                        $bad_fields[] = $field->getLabel();
                    }
                }
            }
        }

        if (count($bad_fields) > 0) {
            $hp = Codendi_HTMLPurifier::instance();
            $bad_fields_escaped = array();
            foreach ($bad_fields as $f) {
                $bad_fields_escaped[] =  $hp->purify(SimpleSanitizer::unsanitize($f), CODENDI_PURIFIER_CONVERT_HTML);
            }
            if ($showFeedback) {
                $GLOBALS['Response']->addFeedback('error', $Language->getText('tracker_common_field_factory', 'missing', join(', ', $bad_fields_escaped)), CODENDI_PURIFIER_DISABLED);
                $this->setError($Language->getText('tracker_common_field_factory', 'missing', join(', ', $bad_fields)));
            }
            return false;
        } else {
            return true;
        }
    }


    /**
     * return all the fields (+ their default value)
     * of this tracker that have not been showed to the user
     * during the artifact creation
     */
    public function getAllFieldsNotShownOnAdd()
    {
        $result_fields = array();
        foreach ($this->USAGE_BY_NAME as $key => $field) {
            if (
                $field->getUseIt() == 1 &&
                !$field->userCanSubmit($this->ArtifactType->Group->getID(), $this->ArtifactType->getID())
            ) {
                  $result_fields[$key] = $field->getDefaultValue();
            }
        }

        return $result_fields;
    }

    /**
     * Returns all the fields of this tracker that are contained in the field set of id $fieldset_id
     *
     * @param int $fieldset_id the id of the field set
     * @return array{ArtifactField} the array of the ArtifactField objects contained in the fieldset
     */
    public function getFieldsContainedInFieldSet($fieldset_id)
    {
        $fields_contained_in_fieldset = array();
        $sql = "SELECT af.field_id
                FROM artifact_field af, artifact_field_usage afu
                WHERE af.field_set_id=" . db_ei($fieldset_id) . " AND
                      af.group_artifact_id=" . db_ei($this->ArtifactType->getID()) . " AND
                      afu.group_artifact_id=" . db_ei($this->ArtifactType->getID()) . " AND
                      afu.field_id=af.field_id
                ORDER BY afu.place ASC";
        $res = db_query($sql);
        while ($field_array = db_fetch_array($res)) {
            $current_field = $this->getFieldFromId($field_array['field_id']);
            $fields_contained_in_fieldset[$current_field->getID()] = $current_field;
        }
        return $fields_contained_in_fieldset;
    }


    /**
     * param $ug: the ugroup that we are searching for
     * param $atid_dest: all groups that do not have this tracker are foreign groups
     * return name of $ug if it is a foreign group else return false
     */
    public function _getForeignUgroupName($ug, $atid_dest)
    {
        $db_res = db_query("SELECT ugroup.name FROM ugroup,artifact_group_list agl " .
        "WHERE ugroup.ugroup_id='" . db_ei($ug) . "' " .
        "AND agl.group_artifact_id='" .  db_ei($atid_dest)  . "' " .
        "AND ugroup.group_id!=agl.group_id");
        if ($name_array = db_fetch_array($db_res)) {
            return $name_array['name'];
        } else {
            return false;
        }
    }


    /**
     *
     *  Copy all the fields informations from this artifacttype to another.
     *
     *  @param atid_source: source tracker
     *  @param atid_dest: destination tracker
     *  @param mapping_fieldset_array: mapping array between source fieldsets and dest ones $mapping_fieldset_array[$source_fieldset_id] = $dest_fieldset_id
     *
     *    @return bool
     */
    public function copyFields($atid_dest, $mapping_fieldset_array, $ugroup_mapping = false)
    {
        global $Language;

        foreach ($this->USAGE_BY_NAME as $field) {
         //$field = new ArtifactField();
         //$field->setFromArray($field_array);

         //test if we got as value_function a ugroup that does not exist in the dest group
            $val_function = $field->getValueFunction();
            $dest_val_func = array();

         //go through all group binds
            if (!empty($val_function)) {
                foreach ($val_function as $val_func) {
                       $ug = $field->isUgroupValueFunction($val_func);
                    if ($ug !== false) {
                        if ($ugroup_mapping == false || empty($ugroup_mapping)) {
                                  //avoid that when copying a tracker only (not copying a template with all trackers)
                                  //that we use ugroups from foreign groups in the value_function
                                  $name = $this->_getForeignUgroupName($ug, $atid_dest);
                            if ($name !== false) {
          //don't copy this ugroup
                                $GLOBALS['Response']->addFeedback('warning', $Language->getText('tracker_common_field_factory', 'ugroup_not_exist', array($field->getLabel(),$name)));
                            } else {
                                $dest_val_func[] = "ugroup_$ug";
                            }
                        } else {
                            if (isset($ugroup_mapping[$ug])) {
                                $dest_ug = $ugroup_mapping[$ug];
                                $dest_val_func[] = "ugroup_" . $dest_ug;
                            } else {
                                $name = $this->_getForeignUgroupName($ug, $atid_dest);
                                if ($name !== false) {
                                  //don't copy this ugroup
                                    $GLOBALS['Response']->addFeedback('warning', $Language->getText('tracker_common_field_factory', 'ugroup_not_exist', array($field->getLabel(),$name)));
                                }
                            }
                        }
                    } else {
            //this is the case where we have
            //artifact_submitters,group_members,group_admins,tracker_admins
                        $dest_val_func[] = $val_func;
                    }
                }
            }

            $sql_insert = 'INSERT INTO artifact_field VALUES
                 (' . db_ei($field->getID()) . ',' . db_ei($atid_dest) . ', ' . db_ei($mapping_fieldset_array[$field->getFieldSetID()]) .
            ',"' . db_es($field->getName()) . '",' . db_ei($field->getDataType()) .
            ',"' . db_es($field->getDisplayType()) . '","' . db_es($field->getDisplaySize()) . '","' . db_es($field->getLabel()) .
            '","' . db_es($field->getDescription()) . '","' . db_es($field->getScope()) . '",' . db_ei($field->getRequired()) .
            ',' . db_ei($field->getEmptyOk()) . ',' . db_ei($field->getKeepHistory()) . ',' . db_ei($field->getSpecial()) .
            ',"' . db_es(implode(",", $dest_val_func)) . '","' . db_es($field->getDefaultValue(true)) . '")';

            $res_insert = db_query($sql_insert);
         //echo $sql_insert;
            if (!$res_insert || db_affected_rows($res_insert) <= 0) {
                $this->setError($Language->getText('tracker_common_field_factory', 'ins_err', array($field_array["field_id"],$atid_dest,db_error())));
                return false;
            }

         // Copy artifact_field_usage records
            $place = ($field->getPlace() == "" ? "null" : $field->getPlace());
            $sql_insert = 'INSERT INTO artifact_field_usage VALUES (' . $field->getID() . ',' . $atid_dest . ',' . $field->getUseIt() .
            ',' . $place . ')';

         //echo $sql_insert;
            $res_insert = db_query($sql_insert);
            if (!$res_insert || db_affected_rows($res_insert) <= 0) {
                $this->setError($Language->getText('tracker_common_field_factory', 'use_ins_err', array($field->getID(),$atid_dest,db_error())));
                return false;
            }
        } // while

        // Copy artifact_field_value_list records
        $sql = 'SELECT field_id,value_id,value,description,order_id,status ' .
        'FROM artifact_field_value_list ' .
        'WHERE group_artifact_id=' . db_ei($this->ArtifactType->getID());

        //echo $sql;

        $res = db_query($sql);

        while ($field_array = db_fetch_array($res)) {
            $sql_insert = 'INSERT INTO artifact_field_value_list VALUES (' . db_ei($field_array["field_id"]) . ',' . db_ei($atid_dest) . ',' . db_ei($field_array["value_id"]) .
              ',"' . db_es($field_array["value"]) . '","' . db_es($field_array["description"]) . '",' . db_ei($field_array["order_id"]) .
              ',"' . db_es($field_array["status"]) . '")';

      //echo $sql_insert;
            $res_insert = db_query($sql_insert);
            if (!$res_insert || db_affected_rows($res_insert) <= 0) {
                $this->setError($Language->getText('tracker_common_field_factory', 'vl_ins_err', array($field_array["field_id"],$atid_dest,db_error())));
                return false;
            }
        } // while

        return true;
    }


    /**
     *
     *  Delete all the fields informations for a tracker
     *
     *  @param atid: the tracker id
     *
     *    @return bool
     */
    public function deleteFields($atid)
    {
        // Remove fields permissions
        foreach ($this->USAGE_BY_ID as $field_id => $field) {
            permission_clear_all_fields_tracker($this->ArtifactType->getGroupID(), $atid, $field_id);
        }

     // Delete artifact_field records
        $sql = 'DELETE ' .
        'FROM artifact_field ' .
        'WHERE group_artifact_id=' . db_ei($atid);

     //echo $sql;

        $res = db_query($sql);

     // Delete artifact_field_usage records
        $sql = 'DELETE ' .
        'FROM artifact_field_usage ' .
        'WHERE group_artifact_id=' . db_ei($atid);

     //echo $sql;

        $res = db_query($sql);

     // Delete artifact_field_value_list records
        $sql = 'DELETE ' .
        'FROM artifact_field_value_list ' .
        'WHERE group_artifact_id=' . db_ei($atid);

     //echo $sql;

        $res = db_query($sql);

        return true;
    }

    /**
     *  Check if a field id already exist for an artifact type
     *
     *  @param field_id: the field id
     *
     *  @return bool - exist or not
     */
    public function existFieldId($field_id)
    {
        $sql = "SELECT * FROM artifact_field WHERE group_artifact_id=" . db_ei($this->ArtifactType->getID()) .
         " AND field_id=" . db_ei($field_id);

        $result = db_query($sql);
        if ($result && db_numrows($result) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *  Compute the default value given a data type and a display type
     *
     *  @param data_type: the field data type (string, int, flat or date)
     *  @param display_size: the field display size
     *
     *  @return string
     */
    public function getDefaultValue($data_type, $display_type)
    {
        $af = new ArtifactField();

        if (
            ($data_type == $af->DATATYPE_INT || $data_type == $af->DATATYPE_USER)
            && ($display_type == "SB")
        ) {
            return "100";
        }

        if (
            ($data_type == $af->DATATYPE_INT || $data_type == $af->DATATYPE_USER)
            && ($display_type == "MB")
        ) {
            return "100";
        }

        if (
            ($data_type == $af->DATATYPE_TEXT)
            && ($display_type == "TF")
        ) {
            return "";
        }

        if (
            ($data_type == $af->DATATYPE_TEXT)
            && ($display_type == "TA")
        ) {
            return "";
        }

        if (
            ($data_type == $af->DATATYPE_DATE)
            && ($display_type == "DF")
        ) {
            return "";
        }

        if (
            ($data_type == $af->DATATYPE_FLOAT)
            && ($display_type == "TF")
        ) {
            return "0.0";
        }

        if (
            ($data_type == $af->DATATYPE_INT)
            && ($display_type == "TF")
        ) {
            return "0";
        }

        return "";
    }

    /**
     *  Create a new field
     *
     *  @param description: the field description
     *  @param label: the field label
     *  @param data_type: the field data type (string, int, flat or date)
     *  @param display_type: the field display type (select box, text field, ...)
     *  @param display_size: the field display size
     *  @param rank_on_screen: rank on screen
     *  @param empty_ok: allow empty fill
     *  @param keep_history: keep in the history
     *  @param special: is the field has special process
     *  @param use_it: this field is used or not
     *  @param field_set_id: the field set id that this field belong to
     *
     *  @return bool - succeed or failed
     */
    public function createField(
        $description,
        $label,
        $data_type,
        $display_type,
        $display_size,
        $rank_on_screen,
        $empty_ok,
        $keep_history,
        $special,
        $use_it,
        $field_set_id
    ) {
        global $Language;

     // Check arguments
        if ($data_type == "" || $display_type == "" || $label == "") {
            $this->setError($Language->getText('tracker_common_field_factory', 'label_requ'));
            return false;
        }

        $field_id = $this->ArtifactType->getNextFieldID();
        $field_name = $this->ArtifactType->buildFieldName($this->ArtifactType->getNextFieldID());

        $af = new ArtifactField($this->ArtifactType->getID(), "");

     // Default values
        $empty_ok = ($empty_ok ? $empty_ok : 0);
        $keep_history = ($keep_history ? $keep_history : 0);
        $use_it = ($use_it ? $use_it : 0);
        $special = ($special ? $special : 0);
        $display_size = (($display_size != "N/A") ? $display_size : "" );

        $default_value = $this->getDefaultValue($data_type, $display_type);
     // First create the artifact_field
        $sql = "INSERT INTO artifact_field VALUES (" .
        db_ei($field_id) . "," . db_ei($this->ArtifactType->getID()) . "," . db_ei($field_set_id) . ",'" . db_es($field_name) . "'," . db_ei($data_type) . ",'" . db_es($display_type) . "','" . db_es($display_size) . "','" .
        db_es($label) . "','" . db_es($description) . "','',0," . db_ei($empty_ok) . "," . db_ei($keep_history) . "," . db_ei($special) . ",'','" . db_es($default_value) . "')";

        $res_insert = db_query($sql);
        if (!$res_insert || db_affected_rows($res_insert) <= 0) {
            $this->setError($Language->getText('tracker_common_field_factory', 'ins_err', array($field_id,$this->ArtifactType->getID(),db_error())));
            return false;
        }

     // Then, insert the artifact_field_usage
        $sql = "INSERT INTO artifact_field_usage VALUES (" .
        db_ei($field_id) . "," . db_ei($this->ArtifactType->getID()) . "," . db_ei($use_it) . ",'" .
        db_ei($rank_on_screen) . "')";

        $res_insert = db_query($sql);
        if (!$res_insert || db_affected_rows($res_insert) <= 0) {
            $this->setError($Language->getText('tracker_common_field_factory', 'use_ins_err', array($field_id,$this->ArtifactType->getID(),db_error())));
            return false;
        }

     // We need to insert with the default value, records in artifact_field_value table
     // for the new field
        $sql_artifacts = 'SELECT artifact_id ' .
        'FROM artifact ' .
        'WHERE group_artifact_id=' .  db_ei($this->ArtifactType->getID());

     //echo $sql_artifacts;

        $res = db_query($sql_artifacts);

     // Insert artifact_field_value record
        $name = '';
        switch ($data_type) {
            case $af->DATATYPE_TEXT:
                   $name = "valueText";
                break;

            case $af->DATATYPE_INT:
            case $af->DATATYPE_USER:
                   $name = "valueInt";
                break;

            case $af->DATATYPE_FLOAT:
                   $name = "valueFloat";
                break;

            case $af->DATATYPE_DATE:
                   $name = "valueDate";
                break;
        } // switch

        $sql = "INSERT INTO artifact_field_value (field_id,artifact_id,$name) VALUES ";

        $count = db_numrows($res);
        for ($i = 0; $i < $count; $i++) {
            $id = db_result($res, $i, "artifact_id");
            if ($i > 0) {
                $sql .= ",";
            }
            $sql .= "(" .  db_ei($field_id)  . "," .  db_ei($id)  . ",'" . db_es($default_value) . "')";
        }

        $result = db_query($sql);

     // If select box or multi select box, we need to create the None value
        if ($display_type == "SB" || $display_type == "MB") {
            $sql = "INSERT INTO artifact_field_value_list VALUES ( " . db_ei($field_id) . "," . db_ei($this->ArtifactType->getID()) .
             ",100,'" . db_es($Language->getText('global', 'none')) . "','',10,'P')";
            $result = db_query($sql);
        }

     // Reload the fields
        $this->fetchData($this->ArtifactType->getID());

        //Set permissions
        $permissions = array($field_id =>
                             array(
                                   $GLOBALS['UGROUP_ANONYMOUS']     => permission_get_input_value_from_permission('TRACKER_FIELD_READ'),
                                   $GLOBALS['UGROUP_REGISTERED']    => permission_get_input_value_from_permission('TRACKER_FIELD_SUBMIT'),
                                   $GLOBALS['UGROUP_PROJECT_MEMBERS']  => permission_get_input_value_from_permission('TRACKER_FIELD_UPDATE')
                             )
           );

           permission_process_update_fields_permissions(
               $this->ArtifactType->getGroupID(),
               $this->ArtifactType->getID(),
               $this->getAllUsedFields(),
               $permissions
           );
        return true;
    }

    /**
     * @param $string
     */
    public function setError($string)
    {
        $this->error_state = true;
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
