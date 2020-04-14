<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

// The artifact field object
class ArtifactField
{

    // The field id
    public $field_id;

    // The field name
    public $field_name;

    // The data_type: 1: text, 2: int, 3: float, 4: date, 5: user - See constants $DATATYPE_xx
    public $data_type;

    // Display type: SB: selectbox, TF: text field, DF: date field, TA: text area
    public $display_type;

    // The size associated with display_type
    public $display_size;

    // The label
    public $label;

    // The description
    public $description;

    // The scope of the field: S: system or P:project
    public $scope;

    // @deprecated
    // Is the field is required?
    // !!! Caution !!!
    // This field shouldn't be used (always 0)
    // Use empty_ok (required <=> ! empty_ok)
    // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
    public $required;

    // Is the field allowed to be empty?
    public $empty_ok;

    // Keep the history changes
    public $keep_history;

    // Is the field special?
    public $special;

    // Special value to specify the field values: artifact_submitters
    public $value_function;

    // Is the field used?
    public $use_it;

    // Place on the form
    public $place;

    // Default value
    public $default_value;

    // Id of the fieldset that this field belong to
    public $field_set_id;


    // Constants for data_type
    public $DATATYPE_TEXT = 1;
    public $DATATYPE_INT = 2;
    public $DATATYPE_FLOAT = 3;
    public $DATATYPE_DATE = 4;
    public $DATATYPE_USER = 5;
    /**
     * @var string
     */
    private $error_message = '';
    /**
     * @var bool
     */
    private $error_state = false;

    public function __construct()
    {
    }

    /**
     *  Retrieve and set the attributes values
     *
     * @param group_artifact_id: for this artifact type
     * @param field_name: and for this field name
     *
     * @return void
     */
    public function fetchData($group_artifact_id, $field_name)
    {
        global $display_debug;

        $sql = "SELECT af.field_id, field_name, data_type, display_type, " .
        "display_size,label, description,scope,required,empty_ok,keep_history,special, " .
        "value_function, " .
        "af.group_artifact_id, use_it, place, default_value, af.field_set_id " .
        "FROM artifact_field af, artifact_field_usage afu " .
        "WHERE af.field_name='" . db_es($field_name) . "' and af.field_id = afu.field_id" .
        " and afu.group_artifact_id=" . db_ei($group_artifact_id) .
        " and af.group_artifact_id=" . db_ei($group_artifact_id);

     //echo "<DBG:ArtifactField.fetchData>sql=".$sql."<br>";
        $res = db_query($sql);

        $field_array = db_fetch_array($res);

        if ($field_array) {
            $this->setFromArray($field_array);
        }
    }

    /**
     *  Set the attributes values
     *
     * @param field_array: the values array
     *
     * @return void
     */
    public function setFromArray($field_array)
    {
     //echo "setFromArray";
        $this->field_id = $field_array['field_id'];
        $this->field_name = $field_array['field_name'];
        $this->data_type = $field_array['data_type'];
        $this->display_type = $field_array['display_type'];
        $this->display_size = $field_array['display_size'];
        $this->label = $field_array['label'];
        $this->description = $field_array['description'];
        $this->scope = $field_array['scope'];
        $this->required = $field_array['required'];    // don't use it : value always equal to 0
        $this->empty_ok = $field_array['empty_ok'];
        $this->keep_history = $field_array['keep_history'];
        $this->special = $field_array['special'];
     // if value_function == "", the result of explode will be: array([0] => "") and that's not what we want.
        if ($field_array['value_function'] != "") {
            $this->value_function = explode(",", $field_array['value_function']);
        } else {
            $this->value_function = array();
        }
        $this->use_it = $field_array['use_it'];
        $this->place = $field_array['place'];
        $this->default_value = $field_array['default_value'];
        $this->field_set_id = $field_array['field_set_id'];

     //echo $this->field_name."-".$this->data_type."<br>";
    }

    /**
     *  Get the field_name attribute value
     *
     * @return string
     */
    public function getName()
    {
        return $this->field_name;
    }

    /**
     *  Get the field_id attribute value
     *
     * @return int
     */
    public function getID()
    {
        return $this->field_id;
    }

    /**
     *  Get the data_type attribute value
     *
     * @return int
     */
    public function getDataType()
    {
        return $this->data_type;
    }

    /**
     *  Get the display_type attribute value
     *
     * @return string
     */
    public function getDisplayType()
    {
        return $this->display_type;
    }

    /**
     *  Get the label of the field. The field type is a combination
     * of the field data type and the field display type
     *
     * @return string
     */
    public function getLabelFieldType()
    {
        global $Language;

        if (
            ($this->data_type == $this->DATATYPE_INT || $this->data_type == $this->DATATYPE_USER)
            && ($this->display_type == "SB")
        ) {
            return $Language->getText('tracker_include_type', 'sb');
        }

        if (
            ($this->data_type == $this->DATATYPE_INT || $this->data_type == $this->DATATYPE_USER)
            && ($this->display_type == "MB")
        ) {
            return $Language->getText('tracker_include_type', 'mb');
        }

        if (
            ($this->data_type == $this->DATATYPE_TEXT)
            && ($this->display_type == "TF")
        ) {
            return $Language->getText('tracker_include_type', 'tf');
        }

        if (
            ($this->data_type == $this->DATATYPE_TEXT)
            && ($this->display_type == "TA")
        ) {
            return $Language->getText('tracker_include_type', 'ta');
        }

        if (
            ($this->data_type == $this->DATATYPE_DATE)
            && ($this->display_type == "DF")
        ) {
            return $Language->getText('tracker_include_type', 'df');
        }

        if (
            ($this->data_type == $this->DATATYPE_FLOAT)
            && ($this->display_type == "TF")
        ) {
            return $Language->getText('tracker_include_type', 'ff');
        }

        if (
            ($this->data_type == $this->DATATYPE_INT)
            && ($this->display_type == "TF")
        ) {
            return $Language->getText('tracker_include_type', 'if');
        }

        return $Language->getText('tracker_common_field', 'unkown');
    }

    /**
     *  Get the display_size attribute value
     *
     * @return string
     */
    public function getDisplaySize()
    {
        return($this->display_size);
    }

    /**
     *  Get the label attribute value
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     *  Get the description attribute value
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     *  Get the scope attribute value
     *
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @deprecated Caution: don't use this function. Returned value always equal to 0. Use getEmptyOk (required <=> ! empty_ok)
     * Get the required attribute value
     *
     * @return bool
     */
    public function getRequired()
    {
        return $this->required;
    }

    /**
     *  Get the empty_ok attribute value
     *
     * @return bool
     */
    public function getEmptyOk()
    {
        return $this->empty_ok;
    }

    /**
     *  Get the keep_history attribute value
     *  add CC and file_attachment into history for task #240
     *
     * @return bool
     */
    public function getKeepHistory()
    {
        if ($this->field_name == 'cc' || $this->field_name == 'attachment' || $this->field_name == 'submitted_by') {
            return true;
        } else {
            return $this->keep_history;
        }
    }

    /**
     *  Get the special attribute value
     *
     * @return bool
     */
    public function getSpecial()
    {
        return $this->special;
    }

    /**
     *  Get the value_function attribute value
     *
     * @return array
     */
    public function getValueFunction()
    {
        return $this->value_function;
    }


    /**
     *  Get the use_it attribute value
     *
     * @return bool
     */
    public function getUseIt()
    {
        return $this->use_it;
    }

    /**
     *  Get the place attribute value
     *
     * @return int
     */
    public function getPlace()
    {
        return $this->place;
    }

    /**
     *  Get the default_value attribute value
     *
     * @return string
     */
    public function getDefaultValue($for_insert = false)
    {
        if ($for_insert) {
            return $this->default_value;
        }

        $def_val = $this->default_value;
        if (
            ($this->data_type == $this->DATATYPE_INT || $this->data_type == $this->DATATYPE_USER)
            && ($this->display_type == "MB")
        ) {
            $res = explode(",", $def_val);
            return $res;
        }
        return $this->default_value;
    }


    public function getFieldSetID()
    {
        return $this->field_set_id;
    }


    /**
     *  Dump attribute values
     *
     * @return string
     */
    public function dumpStandard()
    {
        return "field_id=" . $this->field_id .
         " - field_name=" . $this->field_name .
         " - data_type=" . $this->data_type .
         " - display_type=" . $this->display_type .
         " - display_size=" . $this->display_size .
          " - label=" . $this->label .
          " - description=" . $this->description .
          " - scope=" . $this->scope .
          " - required=" . $this->required .
          " - empty_ok=" . $this->empty_ok .
          " - keep_history=" . $this->keep_history .
          " - special=" . $this->special .
          " - value_function=" . $this->value_function .
          " - use_it=" . $this->use_it .
          " - place=" . $this->place .
          " - default_value=" . $this->default_value .
               " - field_set_id=" . $this->field_set_id;
    }

    /**
     *  Return true if the field is a selectbox (display_type attribute value = SB)
     *
     * @return bool
     */
    public function isSelectBox()
    {
        return ( $this->getDisplayType() == "SB" );
    }

    /**
     *  Return true if the field is a multi selectbox (display_type attribute value = MB)
     *
     * @return bool
     */
    public function isMultiSelectBox()
    {
        return ( $this->getDisplayType() == "MB" );
    }

    /**
     *  Return true if the field is a date field (display_type attribute value = DF)
     *
     * @return bool
     */
    public function isDateField()
    {
        return ( $this->getDisplayType() == "DF" );
    }

    /**
     *  Return true if the field is a text field (display_type attribute value = TF)
     *
     * @return bool
     */
    public function isTextField()
    {
        return ( $this->getDisplayType() == "TF" );
    }

    /**
     *  Return true if the field is a text area (display_type attribute value = TA)
     *
     * @return bool
     */
    public function isTextArea()
    {
        return ( $this->getDisplayType() == "TA" );
    }

    /**
     *  Return true if the field is a float
     *
     * @return bool
     */
    public function isFloat()
    {
        return ( $this->getDataType() == $this->DATATYPE_FLOAT );
    }

    /**
     *  Return true if the field is a int
     *
     * @return bool
     */
    public function isInt()
    {
        return ( $this->getDataType() == $this->DATATYPE_INT );
    }

    /**
     *  Return true if the field is allowed to be empty
     *
     * @return bool
     */
    public function isEmptyOk()
    {
        $val = $this->getEmptyOk();
        return($val);
    }

    /**
     *  Return true if the field is special
     *
     * @return bool
     */
    public function isSpecial()
    {
        return( $this->special == 1 );
    }

    /**
     *  Return true if the field is used
     *
     * @return bool
     */
    public function isUsed()
    {
        return( $this->use_it );
    }

    /**
     *  Return true if the field is standard (using if name - if this field value is stored into artifact table)
     *
     * @return bool
     */
    public function isStandardField()
    {
        switch ($this->field_name) {
            case "artifact_id":
            case "status_id":
            case "submitted_by":
            case "open_date":
            case "close_date":
            case "summary":
            case "details":
            case "severity":
            case "last_update_date":
                return true;
            default:
                return false;
        }
    }

     /**
     * Returns true if the field is bound (to a function like group_members, etc ...)
     * @return bool true if the field values are bound to a function, false otherwise
     */
    public function isBound()
    {
        return count($this->getValueFunction()) > 0;
    }

    /**
     *  Return value function field attribute value
     *
     * @return array
     */
    public function getGlobalValueFunction($by_field_id = false)
    {
        global $art_field_fact;
        if ($by_field_id) {
            $field = $art_field_fact->getFieldFromId($by_field_id);
            $val = $field->getValueFunction();
        } else {
            $val = $this->getValueFunction();
        }
        return($val);
    }

    /**
     *  Return keep history field attribute value
     *
     * @return string
     */
    public function getGlobalKeepHistory($by_field_id = false)
    {
        global $art_field_fact;
        if ($by_field_id) {
            $field = $art_field_fact->getFieldFromId($by_field_id);
            $val = $field->getKeepHistory();
        } else {
            $val = $this->getKeepHistory();
        }
        return($val);
    }

    /**
     *  Return display size field attribute value
     *
     * @return string[]
     */
    public function getGlobalDisplaySize($by_field_id = false)
    {
        global $art_field_fact;

        if ($by_field_id) {
            $field = $art_field_fact->getFieldFromId($by_field_id);
            $val = $field->getDisplaySize();
        } else {
            $val = $this->getDisplaySize();
        }
        $t = explode('/', $val);
        if (!isset($t[1])) {
            $t[1] = '';
        }
        return $t;
    }

    /**
     * Simply return the value associated with a given value_id
     * for a given field of a given group. If associated value not
     * found then return value_id itself.
     * By doing so if this function is called by mistake on a field with type
     * text area or text field then it returns the text itself.
     *
     * WARNING: the value is not escaped for any output. Please purify it before usage
     *
     * @return bool|string
     */
    public function getValue($group_artifact_id, $value_id, $by_field_id = false)
    {
        global $Language;
         $uh = UserHelper::instance();

        // close_date and assigned_to fields are special select box fields
        $value_func = $this->getGlobalValueFunction();
        if (count($value_func) > 0) {
      // For now all of our value functions returns users so there is no need
      // to make a test for the type of value function it is
      // if ($value_func == '...')
            if (is_numeric($value_id)) {
                return $uh->getDisplayNameFromUserId($value_id) ?? '';
            } else {
                return $Language->getText('tracker_common_field', 'not_found');
            }
        } elseif ($this->isDateField()) {
            return format_date($sys_datefmt, $value_id);
        }

        // Look for project specific values first...
        $sql = "SELECT * FROM artifact_field_value_list " .
        "WHERE  field_id='" . db_ei($this->field_id) . "' AND group_artifact_id='" . db_ei($group_artifact_id) . "' " .
        "AND value_id='" . db_ei($value_id) . "'";
        $result = db_query($sql);
        if ($result && db_numrows($result) > 0) {
            return db_result($result, 0, 'value');
        }

        // check here if value is 100
        if ($value_id == 100) {
            return $Language->getText('global', 'none');
        }
        // No value found for this value id !!!
        return $value_id . $Language->getText('tracker_common_field', 'not_found');
    }

    /** return the id of the ugroup in the value function
     * false if the value function does not concern a special
     * ugroup (id > 100)
     */
    public function isUgroupValueFunction($value)
    {
        if (preg_match('/ugroup_([0-9]+)/', $value, $matches)) {
            if (strlen($matches[1]) > 2) {
                  return $matches[1];
            }
        }
        return false;
    }



    /**
     * Return all possible values for a select box field
     * Rk: if the checked value is given then it means that we want this value
     *     in the list in any case (even if it is hidden and active_only is requested)
     *
     * @return array
     */
    public function getFieldPredefinedValues($group_artifact_id, $checked = false, $by_field_id = false, $active_only = true, $use_cache = false, $with_display_preferences = false)
    {
            // ArtifactTypeHtml object created in index.php
            global $ath, $RES_CACHE;
        // The "Assigned_to" box requires some special processing
        // because possible values  are project members) and they are
        // not stored in the artifact_field_value table but in the user_group table
        $value_func = $this->getGlobalValueFunction();
        $qry_value  = [];
        if (count($value_func) > 0) {
            $show_suspended = true;
            for ($i = 0; $i < count($value_func); $i++) {
                if ($value_func[$i] == 'group_members') {
                    $qry_value[$i] = ugroup_db_get_dynamic_members($GLOBALS['UGROUP_PROJECT_MEMBERS'], $group_artifact_id, $ath->getGroupID(), $with_display_preferences, null, $show_suspended);
                } elseif ($value_func[$i] == 'group_admins') {
                    $qry_value[$i] = ugroup_db_get_dynamic_members($GLOBALS['UGROUP_PROJECT_ADMIN'], $group_artifact_id, $ath->getGroupID(), $with_display_preferences, null, $show_suspended);
                } elseif ($value_func[$i] == 'tracker_admins') {
                    $qry_value[$i] = ugroup_db_get_dynamic_members($GLOBALS['UGROUP_TRACKER_ADMIN'], $group_artifact_id, $ath->getGroupID(), $with_display_preferences, null, $show_suspended);
                } elseif ($value_func[$i] == 'artifact_submitters') {
                    $qry_value[$i] = $ath->getSubmitters($with_display_preferences);
                } elseif (preg_match('/ugroup_([0-9]+)/', $value_func[$i], $matches)) {
                    if (strlen($matches[1]) > 2) {
                        $qry_value[$i] = ugroup_db_get_members($matches[1], $with_display_preferences);
                    } else {
                        $qry_value[$i] = ugroup_db_get_dynamic_members($matches[1], $group_artifact_id, $ath->getGroupID(), $with_display_preferences, null, $show_suspended);
                    }
                }
            }
            $qry_value      = array_filter($qry_value);
            $qry_value_size = count($qry_value);
            if ($qry_value_size === 0) {
                return [];
            }
            $qry = $qry_value[0];
            for ($i = 1; $i < $qry_value_size; $i++) {
                 $qry = $qry . " UNION " . $qry_value[$i];
            }
            $res_value = db_query($qry);
        } else {
      // If only active field
            $status_cond = "";
            if ($active_only) {
                if ($checked && !is_array($checked)) { //TODO: investigate why $checked may be an array
                    $status_cond = "AND  (status IN ('A','P') OR value_id='" . db_ei($checked) . "') ";
                } else {
                    $status_cond = "AND  status IN ('A','P') ";
                }
            }

      // CAUTION !! the fields value_id and value must be first in the
      // select statement because the output is used in the html_build_select_box
      // function

      // Look for project specific values first
            $sql = "SELECT value_id,value,field_id,group_artifact_id,description,order_id,status " .
            "FROM artifact_field_value_list " .
            "WHERE group_artifact_id=" . db_ei($group_artifact_id) . " AND field_id= " . db_ei($this->field_id) . " " .
            $status_cond . " ORDER BY order_id ASC";

      // Use cache ?
            if ($use_cache) {
                if (isset($RES_CACHE[$sql])) {
                    $res_value = $RES_CACHE[$sql];
                } else {
                    $res_value = db_query($sql);
                    $RES_CACHE[$sql] = $res_value;
                }
            } else {
                $res_value = db_query($sql);
            }

            $rows = db_numrows($res_value);
        }

        return($res_value);
    }

    /**
     * Check if a value exists in the predefined values list
     *
     * @param group_artifact_id: the group artifact id
     * @param value: the value
     *
     * @return array
     */
    public function checkValueInPredefinedValues($group_artifact_id, $value)
    {
        $res = $this->getFieldPredefinedValues($group_artifact_id);
        while ($res_array = db_fetch_array($res)) {
            if ($res_array[0] == $value) {
                return true;
            }
        } // while

        return false;
    }


    /**
     * Return all the values for a select box field for a specified status (exclude binding values)
     *
     * @param group_artifact_id: the group artifact id
     * @param status: the status
     *
     * @return array|false
     */
    public function getFieldValues($group_artifact_id, $status)
    {
        $res_value = false;
        $gvf = $this->getGlobalValueFunction();
        if (!isset($gvf[0]) || !$gvf[0]) {
            $status_in = array();
            foreach ($status as $s) {
                $status_in[] = "'" . db_es($s) . "'";
            }
            $sql = "SELECT value_id,value,field_id,group_artifact_id,description,order_id,status " .
            "FROM artifact_field_value_list " .
            "WHERE group_artifact_id=" . db_ei($group_artifact_id) . " AND field_id= " . db_ei($this->field_id) . " " .
            "AND status IN (" . implode(',', $status_in) . ") " .
            "ORDER BY order_id ASC";
            $res_value = db_query($sql);
        }

        return($res_value);
    }

    /**
     * Return the value informations
     *
     * @param group_artifact_id: the group artifact id
     * @param value_id: the value id
     *
     * @return array|null
     */
    public function getFieldValue($group_artifact_id, $value_id)
    {
        $res = null;
        $gvf = $this->getGlobalValueFunction();
        if (!isset($gvf[0]) || !$gvf[0]) {
            $sql = "SELECT value_id,value,field_id,group_artifact_id,description,order_id,status " .
            "FROM artifact_field_value_list " .
            "WHERE group_artifact_id=" . db_ei($group_artifact_id) . " AND field_id= " . db_ei($this->field_id) . " " .
            "AND value_id ='" . db_ei($value_id) . "'";
            $res_value = db_query($sql);
            $res = db_fetch_array($res_value);
        }

        return $res;
    }

    /**
     *  Return true if the field is a user name
     *
     * @return bool
     */
    public function isUsername()
    {
        return ($this->data_type == $this->DATATYPE_USER);
    }

    /**
     *  Return the field name according to the data_type attribute
     *
     * @return bool
     */
    public function getValueFieldName()
    {
     //echo "DT=".$this->getDataType()."<br>";
        switch ($this->getDataType()) {
            case $this->DATATYPE_TEXT:
                return "valueText";
            break;

            case $this->DATATYPE_INT:
            case $this->DATATYPE_USER:
                return "valueInt";
            break;

            case $this->DATATYPE_FLOAT:
                return "valueFloat";
            break;

            case $this->DATATYPE_DATE:
                return "valueDate";
            break;
        } // switch
    }

    /**
     * Update the field value for a specific artifact_id
     *
     * @param artifact_id: the artifact
     * @param value: the new value
     *
     * @return bool
     */
    public function updateValue($artifact_id, $value)
    {
        $sql = "update artifact_field_value set ";
        switch ($this->getDataType()) {
            case $this->DATATYPE_TEXT:
                  $sql .= "valueText='" . db_es($value) . "'";
                break;

            case $this->DATATYPE_INT:
            case $this->DATATYPE_USER:
                  $sql .= "valueInt=$value";
                break;

            case $this->DATATYPE_FLOAT:
                  $sql .= "valueFloat=$value";
                break;

            case $this->DATATYPE_DATE:
                  $sql .= "valueDate=$value";
                break;
        } // switch

        $sql .= " where artifact_id = " . db_ei($artifact_id) . " and field_id = " . db_ei($this->getID());

        $result = db_query($sql);

        if (!$result) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Update the field values for a specific artifact_id
     *
     * @param artifact_id: the artifact
     * @param values: a single value or an array of values
     *
     * @return bool
     */
    public function updateValues($artifact_id, $values)
    {
     // First delete the items
        $sql = "DELETE FROM artifact_field_value WHERE artifact_id=" . db_ei($artifact_id) . " AND field_id=" . db_ei($this->getID());

        $result = db_query($sql);

        if (!$result) {
            return false;
        }

        return $this->insertValue($artifact_id, $values);
    }

    /**
     * Insert the field value for a specific artifact_id
     *
     * @param artifact_id: the artifact
     * @param value: the value (array or single value)
     *
     * @return bool
     */
    public function insertValue($artifact_id, $value)
    {
        if (is_array($value)) {
            $rc_update = true;
            for ($i = 0; $i < count($value); $i++) {
                if ((count($value) > 1) && ($value[$i] == 100)) {
                 //don't insert the row if there's more
                 //than 1 item selected and this item is None
                } else {
                    if (!$this->insertSingleValue($artifact_id, $value[$i])) {
                        $rc_update = false;
                    }
                }
            }
            return $rc_update;
        } else {
            return $this->insertSingleValue($artifact_id, $value);
        }
    }

    /**
     * Insert the field value for a specific artifact_id for a value
     *
     * @param Integer    artifact_id the artifact
     * @param FieldValue value       the value
     *
     * @return bool
     */
    private function insertFieldValue($artifact_id, $value)
    {
        $sql = "INSERT INTO artifact_field_value (field_id,artifact_id,";
        $values =  db_ei($this->getID()) . "," . db_ei($artifact_id) . ",";
        switch ($this->getDataType()) {
            case $this->DATATYPE_TEXT:
                $name = "valueText";
                $values .= "'" . db_es($value) . "'";
                break;

            case $this->DATATYPE_INT:
            case $this->DATATYPE_USER:
                $name = "valueInt";
                if ($this->isSelectBox() || $this->isMultiSelectBox()) {
                    if ($value != 0) {
                        $values .= db_ei($value);
                    } else {
                        $values .= "100";
                    }
                } else {
                    $values .= ($value ? db_ei($value) : "0");
                }
                break;

            case $this->DATATYPE_FLOAT:
                $name = "valueFloat";
                $values .= ($value ? db_es($value) : "0.0");
                break;

            case $this->DATATYPE_DATE:
                $name = "valueDate";
                $values .= ($value ? db_ei($value) : "0");
                break;
        }
        $sql .= $name . ") VALUES (" . $values . ")";
        return db_query($sql);
    }

    /**
     * Insert the field value for a specific artifact_id for a single value
     *
     * @param artifact_id: the artifact
     * @param value: the value (single value)
     *
     * @return bool
     */
    public function insertSingleValue($artifact_id, $value)
    {
        if (!$this->insertFieldValue($artifact_id, $value)) {
            // This might happen if the submitted type is incorrect.
            // In this case, insert the default value, and return false.
            $this->insertDefaultValue($artifact_id);
            return false;
        } else {
            return true;
        }
    }

    /**
     * Insert the default field value for a specific artifact_id for a single value
     *
     * @param artifact_id: the artifact
     *
     * @return bool
     */
    public function insertDefaultValue($artifact_id)
    {
        // We could simply call insertSingleValue($artifact_id,$this->getDefaultValue()) but
        // it might end up in an infinite loop since insertSingleValue() might call this
        // function.
        $value = $this->getDefaultValue();
        return $this->insertFieldValue($artifact_id, $value);
    }


    /**
     *  Return the value used for the where clause
     *
     * @return string
     */
    public function buildMatchExpression($field_name, &$to_match)
    {
        switch ($this->getDataType()) {
            case $this->DATATYPE_TEXT:
            case $this->DATATYPE_DATE:
           // If it is sourrounded by /.../ the assume a regexp
           // else transform into a series of LIKE %word%
                if (preg_match('/\/(.*)\//', $to_match, $matches)) {
                    $expr = $field_name . " RLIKE '" . $matches[1] . "' ";
                } else {
                     $words = preg_split('/\s+/', $to_match);
                    foreach ($words as $i => $w) {
                    //echo "<br>DBG $i, $w, $words[$i]";
                        $words[$i] = $field_name . " LIKE '%" . db_es($w) . "%'";
                    }
                     $expr = join(' AND ', $words);
                }
                break;

            case $this->DATATYPE_INT:
            case $this->DATATYPE_USER:
           // If it is sourrounded by /.../ the assume a regexp
           // else assume an equality
                if (preg_match('/\/(.*)\//', $to_match, $matches)) {
                     $expr = $field_name . " RLIKE '" . db_es($matches[1]) . "' ";
                } else {
                    $int_reg = '[+\-]*[0-9]+';
                    if (preg_match("/\s*(<|>|>=|<=)\s*($int_reg)/", $to_match, $matches)) {
                // It's < or >,  = and a number then use as is
                        $matches[2] = (string) ((int) $matches[2]);
                        $expr = $field_name . " " . $matches[1] . " '" . $matches[2] . "' ";
                        $to_match = $matches[1] . ' ' . $matches[2];
                    } elseif (preg_match("/\s*($int_reg)\s*-\s*($int_reg)/", $to_match, $matches)) {
               // it's a range number1-number2
                        $matches[1] = (string) ((int) $matches[1]);
                        $matches[2] = (string) ((int) $matches[2]);
                        $expr = $field_name . " >= '" . $matches[1] . "' AND " . $field_name . " <= '" . $matches[2] . "' ";
                        $to_match = $matches[1] . '-' . $matches[2];
                    } elseif (preg_match("/\s*($int_reg)/", $to_match, $matches)) {
               // It's a number so use  equality
                        $matches[1] = (string) ((int) $matches[1]);
                        $expr = $field_name . " = '" . $matches[1] . "'";
                        $to_match = $matches[1];
                    } else {
     // Invalid syntax - no condition
                        $expr = '1';
                        $to_match = '';
                    }
                }
                break;

            case $this->DATATYPE_FLOAT:
           // If it is sourrounded by /.../ the assume a regexp
           // else assume an equality
                if (preg_match('/\/(.*)\//', $to_match, $matches)) {
                     $expr = $field_name . " RLIKE '" . $matches[1] . "' ";
                } else {
                    $flt_reg = '[+\-0-9.eE]+';

                    if (preg_match("/\s*(<|>|>=|<=)\s*($flt_reg)/", $to_match, $matches)) {
                // It's < or >,  = and a number then use as is
                        $matches[2] = (string) ((float) $matches[2]);
                        $expr = $field_name . " " . $matches[1] . " '" . $matches[2] . "' ";
                        $to_match = $matches[1] . ' ' . $matches[2];
                    } elseif (preg_match("/\s*($flt_reg)\s*-\s*($flt_reg)/", $to_match, $matches)) {
               // it's a range number1-number2
                        $matches[1] = (string) ((float) $matches[1]);
                        $matches[2] = (string) ((float) $matches[2]);
                        $expr = $field_name . " >= '" . $matches[1] . "' AND " . $field_name . " <= '" . $matches[2] . "' ";
                        $to_match = $matches[1] . '-' . $matches[2];
                    } elseif (preg_match("/\s*($flt_reg)/", $to_match, $matches)) {
               // It's a number so use  equality
                        $matches[1] = (string) ((float) $matches[1]);
                        $expr = $field_name . " = '" . $matches[1] . "'";
                        $to_match = $matches[1];
                    } else {
     // Invalid syntax - no condition
                        $expr = '1';
                        $to_match = '';
                    }
                }
                break;

            default:
                 // All the rest (???) use =
                 $expr = $field_name . " = '" . db_es($to_match) . "'";
                break;
        }

        //echo "<br>DBG expr to match for '".$field_name."' = $expr";
        return ' (' . $expr . ') ';
    }

    /**
     * Return the list of values for the field and a given artifact (for MB field type)
     *
     * @param artifact_id: the artifact
     *
     * @return array
     */
    public function getValues($artifact_id)
    {
        switch ($this->getDataType()) {
            case $this->DATATYPE_TEXT:
                  $name = "valueText";
                break;

            case $this->DATATYPE_INT:
            case $this->DATATYPE_USER:
                  $name = "valueInt";
                break;

            case $this->DATATYPE_FLOAT:
                  $name = "valueFloat";
                break;

            case $this->DATATYPE_DATE:
                  $name = "valueDate";
                break;
        } // switch

        $sql = "SELECT " . $name . " FROM artifact_field_value WHERE artifact_id=" . db_ei($artifact_id) . " AND field_id=" . db_ei($this->field_id);
        $result = db_query($sql);

        return util_result_column_to_array($result);
    }

    /**
     * Return a list of label for a given id values
     *
     * @param group_artifact_id: the artifact type id
     * @param values: array of values (id)
     *
     * @return array
     */
    public function getLabelValues($group_artifact_id, $values)
    {
        global $Language;

        $label_values = array();

        $hash_values = array();
     // Retrieve the full list (id+label)
        if ($this->isUserName()) {
            $all_values = $this->getUsersList($values);
        } else {
            $all_values = $this->getFieldPredefinedValues($group_artifact_id, false, false, false, true);
        }

        // Create an hash table with the id as key
        for ($i = 0; $i < db_numrows($all_values); $i++) {
            $hash_values[db_result($all_values, $i, 0)] = db_result($all_values, $i, 1);
        }

        // Build the label values using the id values
        foreach ($values as $v) {
            if ($v == 100) {
                $label_values[] = $Language->getText('global', 'none');
            } elseif ($v == 0) {
                $label_values[] = $Language->getText('global', 'any');
            } else {
                $label_values[] = $hash_values[$v];
            }
        }

            return $label_values;
    }

    /**
     *  Retrieve the users list
     *
     *  @return array
     */
    public function getUsersList($values)
    {
        $sql = "SELECT user_id,user_name " .
         "FROM user " .
         "WHERE user_id IN (" . db_es(join(",", $values)) . ")";
        $res_value = db_query($sql);

        return $res_value;
    }

    /**
     *  Update the field
     *
     *  @param group_artifact_id: the group artifact id
     *  @param field_name: the field name
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
     *
     *  @return bool - succeed or failed
     */
    public function update(
        $group_artifact_id,
        $field_name,
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
        $fieldset_id
    ) {
        global $Language;

     // Check arguments
        if ($field_name == "" || $data_type == "" || $display_type == "") {
            $this->setError($Language->getText('tracker_common_field', 'name_requ'));
            return false;
        }

     // Default values
        $empty_ok = ($empty_ok ? $empty_ok : 0);
        $keep_history = ($keep_history ? $keep_history : 0);
        $use_it = ($use_it ? $use_it : 0);
        $special = ($special ? $special : 0);
        $rank_on_screen = ($rank_on_screen ? $rank_on_screen : "''");

     // First update the artifact_field
        $sql = "UPDATE artifact_field SET " .
         "field_name='" . db_es($field_name) . "'," .
         "data_type=" . db_ei($data_type) . "," .
         "display_type='" . db_es($display_type) . "'," .
         "display_size='" . db_es($display_size) . "'," .
         "label='" . db_es($label) . "'," .
         "description='" . db_es($description) . "'," .
         "empty_ok=" . db_ei($empty_ok) . "," .
         "keep_history=" . db_ei($keep_history) . "," .
         "special=" . db_ei($special) . "," .
               "field_set_id=" . db_ei($fieldset_id) . " " .
         "WHERE group_artifact_id=" . db_ei($group_artifact_id) . " AND field_id=" . db_ei($this->field_id);

        $res = db_query($sql);
        if (!$res) {
            $this->setError($Language->getText('tracker_common_field', 'upd_err', array($this->field_id,$group_artifact_id,db_error())));
            return false;
        }

     // Then, update the artifact_field_usage
        $sql = "UPDATE artifact_field_usage SET " .
         "use_it=" . db_ei($use_it) . "," .
         "place=" . db_ei($rank_on_screen) . " " .
         "WHERE group_artifact_id=" . db_ei($group_artifact_id) . " AND field_id=" . db_ei($this->field_id);

        $res = db_query($sql);
        if (!$res) {
            $this->setError($Language->getText('tracker_common_field', 'use_err', array($field_id,$group_artifact_id,db_error())));
            return false;
        }

        if (($use_it == "1") && ($this->getUseIt() == "0")) {
         // We need to insert with the default value, records in artifact_field_value table
         // for the unused field
            $sql_artifacts = 'SELECT artifact_id ' .
            'FROM artifact ' .
            'WHERE group_artifact_id=' .  db_ei($group_artifact_id);

            $res = db_query($sql_artifacts);

            while ($artifacts_array = db_fetch_array($res)) {
                $id = $artifacts_array["artifact_id"];

          // Check if there is an existing record
                $sql = "SELECT * FROM artifact_field_value WHERE " .
                "artifact_id = " . db_ei($id) . " AND field_id = " . db_ei($this->getID());
                $res_artifact = db_query($sql);

                if (db_numrows($res_artifact) <= 0) {
                    // Insert artifact_field_value record
                    $sql = "INSERT INTO artifact_field_value (field_id,artifact_id,";
                    $values =  db_ei($this->getID()) . "," . db_ei($id) . ",'" . db_es($this->getDefaultValue()) . "'";

                    switch ($this->getDataType()) {
                        case $this->DATATYPE_TEXT:
                                 $name = "valueText";
                            break;

                        case $this->DATATYPE_INT:
                        case $this->DATATYPE_USER:
                            $name = "valueInt";
                            break;

                        case $this->DATATYPE_FLOAT:
                            $name = "valueFloat";
                            break;

                        case $this->DATATYPE_DATE:
                            $name = "valueDate";
                            break;
                    } // switch

                    $sql .= ($name ?? '') . ") VALUES (" . $values . ")";

                    $result = db_query($sql);
                }
            } // while
        }

     // Reload the field
        $this->fetchData($group_artifact_id, $field_name);

        return true;
    }

    /**
     *  Update the value_function attribute and set the data_type to user
     *
     *  @param group_artifact_id: the group artifact id
     *  @param value_function: the value function
     *
     *  @return bool - succeed or failed
     */
    public function updateValueFunction($group_artifact_id, $value_function)
    {
        global $Language;

        if ($value_function == "") {
            $dtype = $this->DATATYPE_INT;
        } else {
            $dtype = $this->DATATYPE_USER;
        }
     // Update the artifact_field
        $sql = "UPDATE artifact_field SET " .
         "value_function='" . db_es(implode(",", $value_function)) . "', " .
         "data_type=" .  db_ei($dtype) . " " .
         "WHERE group_artifact_id=" . db_ei($group_artifact_id) . " AND field_id=" . db_ei($this->field_id);

        $res = db_query($sql);
        if (!$res) {
            $this->setError($Language->getText('tracker_common_field', 'upd_err', array($field_id,$group_artifact_id,db_error())));
            return false;
        }

     // Set the data type to user
        $this->data_type = $dtype;

     // Reload the field
        $this->fetchData($group_artifact_id, $this->field_name);

        return true;
    }

    /**
     *  Update the default_value attribute
     *
     *  @param group_artifact_id: the group artifact id
     *  @param default_value: the default value
     *  @param mixed computed_value: indication for computed field values (used for instance for current date field value)
     *
     *  @return bool - succeed or failed
     */
    public function updateDefaultValue($group_artifact_id, $default_value, $computed_value = false)
    {
        global $Language;

        if ($this->isDateField()) {
            if ($computed_value != false) {
                // value '' match with current date
                if ($computed_value == 'current_date') {
                    $value = '';
                }
            } else {
                // value 0 match with empty date
                list($value,$ok) = util_date_to_unixtime($default_value);
            }
        } elseif (
            ($this->data_type == $this->DATATYPE_INT || $this->data_type == $this->DATATYPE_USER) &&
            is_array($default_value)
        ) {
            $value = implode(",", $default_value);
        } else {
            $value = $default_value;
        }

     // Update the artifact_field
        $sql = "UPDATE artifact_field SET " .
         "default_value='" . db_es($value) . "' " .
         "WHERE group_artifact_id=" . db_ei($group_artifact_id) . " AND field_id=" . db_ei($this->field_id);

        $res = db_query($sql);
        if (!$res) {
            $this->setError($Language->getText('tracker_common_field', 'upd_err', array($field_id,$group_artifact_id,db_error())));
            return false;
        }

     // Reload the field
        $this->fetchData($group_artifact_id, $this->field_name);

        return true;
    }

    /**
     *  Delete the field and all its dependencies
     *
     *  @param group_artifact_id: the group artifact id
     *
     *  @return bool - succeed or failed
     */
    public function delete($group_artifact_id)
    {
        global $Language;

     // First delete the artifact_field
        $sql = "DELETE FROM artifact_field " .
         "WHERE group_artifact_id=" . db_ei($group_artifact_id) . " AND field_id=" . db_ei($this->field_id);

        $res = db_query($sql);
        if (!$res) {
            $this->setError($Language->getText('tracker_common_field', 'del_err', array($this->field_id,$group_artifact_id,db_error())));
            return false;
        }

     // Delete the artifact_field_usage
        $sql = "DELETE FROM artifact_field_usage " .
         "WHERE group_artifact_id=" . db_ei($group_artifact_id) . " AND field_id=" . db_ei($this->field_id);

        $res = db_query($sql);
        if (!$res) {
            $this->setError($Language->getText('tracker_common_field', 'use_del_err', array($this->field_id,$group_artifact_id,db_error())));
            return false;
        }

     // Then, delete the artifact_field_value_list
        $sql = "DELETE FROM artifact_field_value_list " .
         "WHERE group_artifact_id=" . db_ei($group_artifact_id) . " AND field_id=" . db_ei($this->field_id);

        $res = db_query($sql);
        if (!$res) {
            $this->setError($Language->getText('tracker_common_field', 'del_err', array($this->field_id,$group_artifact_id,db_error())));
            return false;
        }

     // Delete all records linked to artifact_id for this field
        $sql_artifacts = 'SELECT artifact_id ' .
        'FROM artifact ' .
        'WHERE group_artifact_id=' .  db_ei($group_artifact_id);

        $res = db_query($sql_artifacts);

        while ($artifacts_array = db_fetch_array($res)) {
            $id = $artifacts_array["artifact_id"];

      // Delete artifact_field_value records
            $sql = "DELETE FROM artifact_field_value WHERE artifact_id = " . db_ei($id) . " AND field_id = " . db_ei($this->getID());

            db_query($sql);
        } // while

        return true;
    }

    /**
     *  Check if a field value id or a field value already exists
     *
     *  @param group_artifact_id: the group artifact id
     *  @param field_id: the field id
     *  @param value_id: the value id
     *  @param value: the value
     *
     *  @return bool - exist or not
     */
    public function existValue($group_artifact_id, $value_id, $value)
    {
     // Check id first
        $sql = "SELECT * FROM artifact_field_value_list WHERE group_artifact_id=" . db_ei($group_artifact_id) .
         " AND field_id=" . db_ei($this->getID()) . " AND value_id=" . db_ei($value_id);

        $result = db_query($sql);
        if ($result && db_numrows($result) > 0) {
            return true;
        } else {
         // Check value
            $sql = "SELECT * FROM artifact_field_value_list WHERE group_artifact_id=" . db_ei($group_artifact_id) .
            " AND field_id=" . db_ei($this->getID()) . " AND value='" . db_es($value) . "'";

            $result = db_query($sql);
            if ($result && db_numrows($result) > 0) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     *  Create a new field value for select box field
     *
     *  @param group_artifact_id: the group artifact id
     *  @param value: the value
     *  @param description: the value description
     *  @param order_id: the rank
     *
     *  @return bool - exist or not
     */
    public function createValueList($group_artifact_id, $value, $description, $order_id)
    {
        global $Language;

     // Check arguments
        if ($value == "") {
            $this->setError($Language->getText('tracker_common_field', 'val_requ'));
            return false;
        }

        $value_id = $this->getNextValueID($group_artifact_id, $this->getID());

     // Check if a field value id already exists
        if ($this->existValue($group_artifact_id, $value_id, $value)) {
            $this->setError($Language->getText('tracker_common_field', 'val_exist'));
            return false;
        }

     // Default values
        $order_id = ($order_id ? $order_id : 0);

     // Create the artifact_field_value_list
        $sql = "INSERT INTO artifact_field_value_list VALUES (" .
        db_ei($this->getID()) . "," . db_ei($group_artifact_id) . "," . db_ei($value_id) . ",'" . db_es($value) . "','" . db_es($description) . "'," . db_ei($order_id) . ",'A')";

        $res_insert = db_query($sql);
        if (!$res_insert || db_affected_rows($res_insert) <= 0) {
            $this->setError($Language->getText('tracker_common_field', 'vl_ins_err', array($this->getID(),$group_artifact_id,$value_id,db_error())));
            return false;
        }

        return true;
    }

    /**
     *  Update a field value for select box field
     *
     *  @param group_artifact_id: the group artifact id
     *  @param value_id: the value id
     *  @param value: the value
     *  @param description: the value description
     *  @param order_id: the rank
     *  @param status: the value status (V,H)
     *
     *  @return bool - exist or not
     */
    public function updateValueList($group_artifact_id, $value_id, $value, $description, $order_id, $status)
    {
        global $Language;

     // Check arguments
        if ($value_id == "" || $value == "" || $value_id == 100) {
                        // $value_id == 100 -> Can't edit "None" value
            $this->setError($Language->getText('tracker_common_field', 'val_id_requ'));
            return false;
        }

     // Default values
        $order_id = ($order_id ? $order_id : 0);

     // Update the artifact_field_value_list
        $sql = "UPDATE artifact_field_value_list SET " .
               "value='" . db_es($value) . "'," .
               "description='" . db_es($description) . "'," .
               "order_id=" . db_ei($order_id) . "," .
               "status='" . db_es($status) . "' " .
         "WHERE field_id=" . db_ei($this->getID()) . " AND group_artifact_id=" . db_ei($group_artifact_id) . " AND value_id=" . db_ei($value_id);

        $res = db_query($sql);
        if (!$res) {
            $this->setError($Language->getText('tracker_common_field', 'vl_upd_err', array($this->getID(),$group_artifact_id,$value_id,db_error())));
            return false;
        }

        return true;
    }

    /**
     *  Delete a field value for select box field
     *
     *  @param group_artifact_id: the group artifact id
     *  @param value_id: the value id
     *
     *  @return bool - exist or not
     */
    public function deleteValueList($group_artifact_id, $value_id)
    {
        global $Language;

     // Delete the artifact_field_value_list
        $sql = "DELETE FROM artifact_field_value_list " .
        "WHERE field_id=" . db_ei($this->getID()) . " AND group_artifact_id=" . db_ei($group_artifact_id) . " AND value_id=" . db_ei($value_id);

        $res = db_query($sql);
        if (!$res) {
            $this->setError($Language->getText('tracker_common_field', 'vl_del_err', array($this->getID(),$group_artifact_id,$value_id,db_error())));
            return false;
        }

        return true;
    }

    /**
     * Retrieve the next free value id (computed by max(id)+1)
     *
     * @param group_artifact_id: the group artifact id
     * @param field_id: the field id
     *
     * @return int
     */
    public function getNextValueID($group_artifact_id, $field_id)
    {
        $sql = "SELECT max(value_id)+1 FROM artifact_field_value_list WHERE " .
        "field_id=" . db_ei($field_id) . " AND group_artifact_id=" . $group_artifact_id . " " .
         "GROUP BY field_id,group_artifact_id";

        $result = db_query($sql);
        if ($result && db_numrows($result) > 0) {
            $id = db_result($result, 0, 0);
     // do not get into conflict with None
            if ($id == 100) {
                return ($id + 1);
            } else {
                return $id;
            }
        } else {
     // do not get into conflict with Any
            return 1;
        }
    }



    /** return true if user has Read or Update permission on this field
     * @param group_id: the project this field is in
     * @param group_artifact_id: the trackers id this field is in
     * @param user_id: if not given or false take the current user
    **/
    public function userCanRead($group_id, $group_artifact_id, $user_id = false)
    {
        $pm = PermissionsManager::instance();
        $um = UserManager::instance();
        if (! $user_id) {
            $user = $um->getCurrentUser();
        } else {
            $user = $um->getUserById($user_id);
        }
        if ($user === null) {
            return false;
        }
        $ok = $user->isSuperUser()
              || $pm->userHasPermission($group_artifact_id . "#" . $this->field_id, 'TRACKER_FIELD_READ', $user->getUgroups($group_id, array('artifact_type' => $group_artifact_id)))
              || $pm->userHasPermission($group_artifact_id . "#" . $this->field_id, 'TRACKER_FIELD_UPDATE', $user->getUgroups($group_id, array('artifact_type' => $group_artifact_id)));
        return $ok;
    }

    /** return true if user has Update permission on this field
     * @param group_id: the project this field is in
     * @param group_artifact_id: the trackers id this field is in
     * @param user_id: if not given or false take the current user
    **/
    public function userCanUpdate($group_id, $group_artifact_id, $user_id = false)
    {
        $pm = PermissionsManager::instance();
        $um = UserManager::instance();
        if (! $user_id) {
            $user = $um->getCurrentUser();
        } else {
            $user = $um->getUserById($user_id);
        }
        $ok = $user->isSuperUser() || $pm->userHasPermission($group_artifact_id . "#" . $this->field_id, 'TRACKER_FIELD_UPDATE', $user->getUgroups($group_id, array('artifact_type' => $group_artifact_id)));
        return $ok;
    }


    /** return true if user has Submit permission on this field
     * @param group_id: the project this field is in
     * @param group_artifact_id: the trackers id this field is in
     * @param user_id: if not given or false take the current user
    **/
    public function userCanSubmit($group_id, $group_artifact_id, $user_id = false)
    {
        $pm = PermissionsManager::instance();
        $um = UserManager::instance();
        if (! $user_id) {
            $user = $um->getCurrentUser();
        } else {
            $user = $um->getUserById($user_id);
        }
        if ($user === null) {
            return false;
        }
        $ok = $user->isSuperUser() || $pm->userHasPermission($group_artifact_id . "#" . $this->field_id, 'TRACKER_FIELD_SUBMIT', $user->getUgroups($group_id, array('artifact_type' => $group_artifact_id)));
        return $ok;
    }


    /** return true if users in ugroups have Submit permission on this field
     * @param ugroups: the ugroups users are part of
     * @param group_artifact_id: the trackers id this field is in
    **/
    public function ugroupsCanRead($ugroups, $group_artifact_id)
    {
        $pm = PermissionsManager::instance();
        $ok = $pm->userHasPermission($group_artifact_id . "#" . $this->field_id, 'TRACKER_FIELD_READ', $ugroups);
        return $ok;
    }


    /** return true if users in ugroups have Submit permission on this field
     * @param ugroups: the ugroups users are part of
     * @param group_artifact_id: the trackers id this field is in
    **/
    public function ugroupsCanUpdate($ugroups, $group_artifact_id)
    {
        $pm = PermissionsManager::instance();
        $ok = $pm->userHasPermission($group_artifact_id . "#" . $this->field_id, 'TRACKER_FIELD_UPDATE', $ugroups);
        return $ok;
    }


    /** return true if users in ugroups have Submit permission on this field
     * @param ugroups: the ugroups users are part of
     * @param group_artifact_id: the trackers id this field is in
    **/
    public function ugroupsCanSubmit($ugroups, $group_artifact_id)
    {
        $pm = PermissionsManager::instance();
        $ok = $pm->userHasPermission($group_artifact_id . "#" . $this->field_id, 'TRACKER_FIELD_SUBMIT', $ugroups);
        return $ok;
    }



    /**
     * Retrieve users permissions (TRACKER_FIELD_SUBMIT, -UPDATE, -READ)
     * on this field.
     * @param ugroups: the ugroups users are part of (called from ArtifactHtml createMailForUsers)
     * @param group_artifact_id: the trackers id this field is in
     * return array of all associated permissions
     */
    public function getPermissionForUgroups($ugroups, $group_artifact_id)
    {
        $perms = array();

        if ($this->ugroupsCanRead($ugroups, $group_artifact_id)) {
            $perms[] = 'TRACKER_FIELD_READ';
        }
        if ($this->ugroupsCanUpdate($ugroups, $group_artifact_id)) {
            $perms[] = 'TRACKER_FIELD_UPDATE';
        }
        if ($this->ugroupsCanSubmit($ugroups, $group_artifact_id)) {
            $perms[] = 'TRACKER_FIELD_SUBMIT';
        }
        return $perms;
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
