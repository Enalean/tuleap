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

require_once(dirname(__FILE__).'/../Artifact/Tracker_Artifact.class.php');
require_once('Tracker_FormElement_Field.class.php');
require_once(dirname(__FILE__).'/../Artifact/Tracker_Artifact_ChangesetValue_Date.class.php');
require_once(dirname(__FILE__).'/../Report/dao/Tracker_Report_Criteria_Date_ValueDao.class.php');
require_once('dao/Tracker_FormElement_Field_Value_DateDao.class.php');
require_once('dao/Tracker_FormElement_Field_DateDao.class.php');
require_once(dirname(__FILE__).'/../Artifact/Tracker_Artifact_ChangesetValue_Date.class.php');
require_once('common/date/DateHelper.class.php');

class Tracker_FormElement_Field_Date extends Tracker_FormElement_Field {
    
    const DEFAULT_VALUE_TYPE_TODAY    = 0;
    const DEFAULT_VALUE_TYPE_REALDATE = 1;
    
    public $default_properties = array(
        'default_value_type' => array(
            'type'    => 'radio',
            'value'   => 0,      //default value is today
            'choices' => array(
                'default_value_today' => array(
                    'radio_value' => 0,
                    'type'        => 'label',
                    'value'       => 'today',
                ),
                'default_value' => array(
                    'radio_value' => 1,
                    'type'  => 'date',
                    'value' => '',
                ),
            )
        )
    );
    
    /**
     * Continue the initialisation from an xml (FormElementFactory is not smart enough to do all stuff.
     * Polymorphism rulez!!!
     *
     * @param SimpleXMLElement $xml         containing the structure of the imported Tracker_FormElement
     * @param array            &$xmlMapping where the newly created formElements indexed by their XML IDs are stored (and values)
     *
     * @return void
     */
    public function continueGetInstanceFromXML($xml, &$xmlMapping) {
        parent::continueGetInstanceFromXML($xml, $xmlMapping);
        // add children
        if (isset($this->default_properties['default_value'])) {
            if ($this->default_properties['default_value'] === 'today') {
                $this->default_properties['default_value_type']['value'] = self::DEFAULT_VALUE_TYPE_TODAY;
            } else {
                $this->default_properties['default_value_type']['value'] = self::DEFAULT_VALUE_TYPE_REALDATE;
                $this->default_properties['default_value_type']['choices']['default_value']['value'] = $this->default_properties['default_value'];
            }
            unset($this->default_properties['default_value']);
        } else {
            $this->default_properties['default_value_type']['value'] = self::DEFAULT_VALUE_TYPE_REALDATE;
            $this->default_properties['default_value_type']['choices']['default_value']['value'] = '';
        }
    }
    
    /**
     * Export form element properties into a SimpleXMLElement
     *
     * @param SimpleXMLElement &$root The root element of the form element
     *
     * @return void
     */
    public function exportPropertiesToXML(&$root) {
        $child = $root->addChild('properties');
        foreach ($this->getProperties() as $name => $property) {
            $value_type = $property['value'];
            if ($value_type == '1') {
                // a date
                $prop = $property['choices']['default_value'];
                if (!empty($prop['value'])) {
                    // a specific date
                    $child->addAttribute('default_value', $prop['value']);
                } // else no default value, nothing to do
            } else {
                // today
                $prop = $property['choices']['default_value_today'];
                // $prop['value'] is the string 'today'
                $child->addAttribute('default_value', $prop['value']);
            }
        }
    }
    
    /**
     * Returns the default value for this field, or nullif no default value defined
     *
     * @return mixed The default value for this field, or null if no default value defined
     */
    function getDefaultValue() {
        if ($this->getProperty('default_value_type')) {
            $value = $this->formatDate(parent::getDefaultValue());
        } else { //Get date of the current day
            $value = $this->formatDate($_SERVER['REQUEST_TIME']);
        }
        return $value;
    }
    
    /**
     * Return the Field_Date_Dao
     *
     * @return Tracker_FormElement_Field_DateDao The dao
     */
    protected function getDao() {
        return new Tracker_FormElement_Field_DateDao();
    }
    
    /**
     * The field is permanently deleted from the db
     * This hooks is here to delete specific properties, 
     * or specific values of the field.
     * (The field itself will be deleted later)
     *
     * @return boolean true if success
     */
    public function delete() {
        return $this->getDao()->delete($this->id);
    }
    
    public function getCriteriaFrom($criteria) {
        //Only filter query if field is used
        if($this->isUsed()) {
            //Only filter query if criteria is valuated
            if ($criteria_value = $this->getCriteriaValue($criteria)) {
                $a = 'A_'. $this->id;
                $b = 'B_'. $this->id;
                $compare_date_stmt = $this->getSQLCompareDate(
                    $criteria->is_advanced, 
                    $criteria_value['op'], 
                    $criteria_value['from_date'], 
                    $criteria_value['to_date'],
                    $b. '.value'
                );
                return " INNER JOIN tracker_changeset_value AS $a 
                         ON ($a.changeset_id = c.id AND $a.field_id = $this->id )
                         INNER JOIN tracker_changeset_value_date AS $b
                         ON ($a.id = $b.changeset_value_id
                             AND $b.value 
                             AND $compare_date_stmt
                         ) ";
            }
        }
    }
    
     /**
     * Search in the db the criteria value used to search against this field.
     * @param Tracker_ReportCriteria $criteria
     * @return mixed
     */
   public function getCriteriaValue($criteria) {
        if ( ! isset($this->criteria_value) ) {
            $this->criteria_value = array();
            if ($row = $this->getCriteriaDao()->searchByCriteriaId($criteria->id)->getRow()) {
                $this->criteria_value['op'] = $row['op'];
                $this->criteria_value['from_date'] = $row['from_date'];
                $this->criteria_value['to_date'] = $row['to_date'];
            }
        }
        return $this->criteria_value;
    }
    
    /**
     * Format the criteria value submitted by the user for storage purpose (dao or session)
     *
     * @param mixed $value The criteria value submitted by the user
     *
     * @return mixed
     */
    public function getFormattedCriteriaValue($value) {
        if ( empty($value['to_date']) && empty($value['from_date']) ) {
            return '';
        } else {
            //from date
            if ( empty($value['from_date']) ) {
                $value['from_date'] = 0;
            } else {
                 $value['from_date'] = strtotime($value['from_date']);
            }
            
            //to date
            if ( empty($value['to_date']) ) {
                $value['to_date'] = 0;
            } else {
                 $value['to_date'] = strtotime($value['to_date']);
            }
            
            //Operator
            if ( empty($value['op']) || ($value['op'] !== '<' && $value['op'] !== '=' && $value['op'] !== '>')) {
                $value['op'] = '=';
            }
            
            return $value;
        }
    }
    
    /**
     * Build the sql statement for date comparison
     *
     * @param bool   $is_advanced Are we in advanced mode ?
     * @param string $op          The operator used for the comparison (not for advanced mode)
     * @param int    $from        The $from date used for comparison (only for advanced mode)
     * @param int    $to          The $to date used for comparison
     * @param string $column      The column to look into. ex: "A_234.value" | "c.submitted_on" ...
     *
     * @return string sql statement
     */
    protected function getSQLCompareDate($is_advanced, $op, $from, $to, $column) {
        if ($is_advanced) {
            if ( ! $to ) {
                $to = PHP_INT_MAX; //infinity
            }
            $and_compare_date = " $column BETWEEN ". $from ." 
                                                   AND ". $to ." + 24 * 60 * 60 ";
        } else {
            switch ($op) {
                case '<':
                    $and_compare_date = " $column < ". $to; 
                    break;
                case '=':
                    $and_compare_date = " $column BETWEEN ". $to ."
                                                           AND ". $to ." + 24 * 60 * 60 ";
                    break;
                default:
                    $and_compare_date = " $column > ". $to ." + 24 * 60 * 60 ";
                    break;
            }
        }
        return $and_compare_date;
    }
    
    public function getCriteriaWhere($criteria) {
        return '';
    }
    
    public function getQuerySelect() {
        $R1 = 'R1_'. $this->id;
        $R2 = 'R2_'. $this->id;
        return "$R2.value AS `". $this->name ."`";
    }
    
    public function getQueryFrom() {
        $R1 = 'R1_'. $this->id;
        $R2 = 'R2_'. $this->id;
        
        return "LEFT JOIN ( tracker_changeset_value AS $R1 
                    INNER JOIN tracker_changeset_value_date AS $R2 ON ($R2.changeset_value_id = $R1.id)
                ) ON ($R1.changeset_id = c.id AND $R1.field_id = ". $this->id ." )";
    }
    /**
     * Get the "group by" statement to retrieve field values
     */
    public function getQueryGroupby() {
        $R1 = 'R1_'. $this->id;
        $R2 = 'R2_'. $this->id;
        return "$R2.value";
    }
    
    protected function getCriteriaDao() {
        return new Tracker_Report_Criteria_Date_ValueDao();
    }
    
    public function fetchChangesetValue($artifact_id, $changeset_id, $value, $from_aid = null) {
        return $this->formatDate($value);
    }
    
    public function fetchCSVChangesetValue($artifact_id, $changeset_id, $value) {
        return $this->formatDateForCSV($value);
    }
    
    public function fetchAdvancedCriteriaValue($criteria) {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '';
        $criteria_value = $this->getCriteriaValue($criteria);
        $html .= '<div style="text-align:right">';
        $value = isset($criteria_value['from_date']) ? $this->formatDate($criteria_value['from_date']) : '';
        $html .= $GLOBALS['Language']->getText('plugin_tracker_include_field','start');
        $html .= $GLOBALS['HTML']->getDatePicker("criteria_".$this->id ."_from", "criteria[". $this->id ."][from_date]", $value);
        $html .= "<br />";
        $value = isset($criteria_value['to_date']) ? $this->formatDate($criteria_value['to_date']) : '';
        $html .= $GLOBALS['Language']->getText('plugin_tracker_include_field','end');
        $html .= $GLOBALS['HTML']->getDatePicker("criteria_".$this->id ."_to", "criteria[". $this->id ."][to_date]", $value);
        $html .= '</div>';
        return $html;
    }

    public function fetchCriteriaValue($criteria) {
        $html = '';
        if ($criteria->is_advanced) {
            $html = $this->fetchAdvancedCriteriaValue($criteria);
        } else {
            $hp = Codendi_HTMLPurifier::instance();
            $criteria_value = $this->getCriteriaValue($criteria);
            $lt_selected = '';
            $eq_selected = '';
            $gt_selected = '';
            if ($criteria_value) {
                if ($criteria_value['op'] == '<') {
                    $lt_selected = 'selected="selected"';
                } else if ($criteria_value['op'] == '>') {
                    $gt_selected = 'selected="selected"';
                } else {
                    $eq_selected = 'selected="selected"';
                }
            } else {
                $eq_selected = 'selected="selected"';
            }
            $html .= '<div style="white-space:nowrap;">';
            $html .= '<select name="criteria['. $this->id .'][op]">'.
            '<option value=">"'. $lt_selected .'>&gt;</option>'.
            '<option value="="'. $eq_selected .'>=</option>'.
            '<option value="<"'. $lt_selected .'>&lt;</option>'.
            '</select>';
            $value = $criteria_value ? $this->formatDate($criteria_value['to_date']) : '';
            $html .= $GLOBALS['HTML']->getDatePicker("tracker_report_criteria_".$this->id, "criteria[". $this->id ."][to_date]", $value);
            $html .= '</div>';
        }
        return $html;
    }

    public function fetchMasschange() {

    }
    
    /**
     * Format a timestamp into Y-m-d H:i:s format
     */
    protected function formatDateTime($date) {
        return format_date("Y-m-d H:i:s", (float)$date, '');
    }
    
    /**
     * Returns the CSV date format of the user regarding its preferences
     * Returns either 'month_day_year' or 'day_month_year'
     *
     * @return string the CSV date format of the user regarding its preferences
     */
    public function _getUserCSVDateFormat() {
        $user = UserManager::instance()->getCurrentUser();
        $date_csv_export_pref = $user->getPreference('user_csv_dateformat');
        return $date_csv_export_pref;
    }
    
    protected function formatDateForCSV($date) {
        $date_csv_export_pref = $this->_getUserCSVDateFormat();
        switch ($date_csv_export_pref) {
            case "month_day_year";
                $fmt = 'm/d/Y H:i:s';
                break;
            case "day_month_year";
                $fmt = 'd/m/Y H:i:s';
                break;
            default:
                $fmt = 'm/d/Y H:i:s';
                break;
        }
        return format_date($fmt, (float)$date, '');
    }
    
    /**
     * @return bool
     */
    protected function criteriaCanBeAdvanced() {
        return true;
    }
    
    /**
     * Fetch the value
     * @param mixed $value the value of the field
     * @return string
     */
    public function fetchRawValue($value) {
        return $this->formatDate($value);
    }
    
    /**
     * Fetch the value in a specific changeset
     * @param Tracker_Artifact_Changeset $changeset
     * @return string
     */
    public function fetchRawValueFromChangeset($changeset) {
        $value = 0;
        if ($v = $changeset->getValue($this)) {
            if ($row = $this->getValueDao()->searchById($v->getId(), $this->id)->getRow()) {
                $value = $row['value'];
            }
        }
        return $this->formatDate($value);
    }
    
    protected function getValueDao() {
        return new Tracker_FormElement_Field_Value_DateDao();
    }
    
    /**
     * Fetch the html code to display the field value in new artifact submission form
     * @param array $submitted_values the values already submitted
     *
     * @return string html
     */
    protected function fetchSubmitValue($submitted_values = array()) {
        $html = '';
        $value = '';        
        if (!empty($submitted_values)) {            
            $value=$submitted_values[$this->getId()];
        }else if ($this->hasDefaultValue()) {
            $value = $this->getDefaultValue();
        }
        $html .= $GLOBALS['HTML']->getDatePicker("tracker_admin_field_".$this->id, "artifact[". $this->id ."]", $value);
        return $html;
    }

     /**
     * Fetch the html code to display the field value in masschange submission form
     * @param array $submitted_values the values already submitted
     *
     * @return string html
     */
    protected function fetchSubmitValueMasschange() {
        $html = '';
        $value = $GLOBALS['Language']->getText('global','unchanged');
        $html .= $GLOBALS['HTML']->getDatePicker("tracker_admin_field_".$this->id, "artifact[". $this->id ."]", $value);
        return $html;
    }   

    /**
     * Fetch the html code to display the field value in artifact
     *
     * @param Tracker_Artifact                $artifact         The artifact
     * @param Tracker_Artifact_ChangesetValue $value            The actual value of the field
     * @param array                           $submitted_values The value already submitted by the user
     *
     * @return string
     */
    protected function fetchArtifactValue(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null, $submitted_values = array()) {
        $html = '';
        if (is_array($submitted_values[0])) {
            $value=$submitted_values[0][$this->getId()];
        } else {
            if ($value != null) {
                $value = $value->getTimestamp();
                $value = $value ? $this->formatDate($value) : '';
            }
        }
        $hp = Codendi_HTMLPurifier::instance();
        $html .= $GLOBALS['HTML']->getDatePicker("tracker_field_".$this->id, "artifact[$this->id]", $value);
        return $html;
    }

    /**
     * Fetch data to display the field value in mail
     *
     * @param Tracker_Artifact                $artifact         The artifact
     * @param Tracker_Artifact_ChangesetValue $value            The actual value of the field
     * @param string                          $format           output format
     *
     * @return string
     */
    public function fetchMailArtifactValue(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null, $format='text') {
        return $this->fetchArtifactValueReadOnly($artifact, $value);
    }

    /**
     * Fetch the html code to display the field value in artifact in read only mode
     *
     * @param Tracker_Artifact                $artifact The artifact
     * @param Tracker_Artifact_ChangesetValue $value    The actual value of the field
     *
     * @return string
     */
    public function fetchArtifactValueReadOnly(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null) {
        $html = '';
        if ( empty($value) ) {
            return '';
        }
        $value = $value->getTimestamp();
        $value = $value ? $this->formatDate($value) : '';
        $html .= $value;
        return $html;
    }
    
    /**
     * Fetch the changes that has been made to this field in a followup
     * @param Tracker_ $artifact
     * @param array $from the value(s) *before*
     * @param array $to   the value(s) *after*
     */
    public function fetchFollowUp($artifact, $from, $to) {
        $html = '';
        if (!$from || !($from_value = $this->getValue($from['value_id']))) {
            $html .= $GLOBALS['Language']->getText('plugin_tracker_artifact','set_to').' ';
        } else {
            $html .= $GLOBALS['Language']->getText('plugin_tracker_artifact','changed_from').' '. $this->formatDate($from_value['value']) .' '.$GLOBALS['Language']->getText('plugin_tracker_artifact','to').' ';
        }
        $to_value = $this->getValue($to['value_id']);
        $html .= $this->formatDate($to_value['value']);
        return $html;
    }
    
    /**
     * Display the html field in the admin ui
     * @return string html
     */
    protected function fetchAdminFormElement() {
        $html = '';
        $value = '';
        if ($this->hasDefaultValue()) {
            $value = $this->getDefaultValue();
        }
        $html .= $GLOBALS['HTML']->getDatePicker("tracker_admin_field_".$this->id, "", $value);
        return $html;
    }

    /**
     * @return the label of the field (mainly used in admin part)
     */
    public static function getFactoryLabel() {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin','date');
    }
    
    /**
     * @return the description of the field (mainly used in admin part)
     */
    public static function getFactoryDescription() {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin','date_description');
    }
    
    /**
     * @return the path to the icon
     */
    public static function getFactoryIconUseIt() {
        return $GLOBALS['HTML']->getImagePath('calendar/cal.png');
    }
    
    /**
     * @return the path to the icon
     */
    public static function getFactoryIconCreate() {
        return $GLOBALS['HTML']->getImagePath('calendar/cal--plus.png');
    }
    
    /**
     * Fetch the html code to display the field value in tooltip
     * 
     * @param Tracker_Artifact $artifact
     * @param Tracker_Artifact_ChangesetValue_Date $value The changeset value for this field
     * @return string
     */
    protected function fetchTooltipValue(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null) {
        $html = '';
        if ($value) {
            $html .= DateHelper::timeAgoInWords($value->getTimestamp());
        }
        return $html;
    }
    
    /**
     * Validate a value
     *
     * @param Tracker_Artifact $artifact The artifact 
     * @param mixed            $value    data coming from the request. May be string or array. 
     *
     * @return bool true if the value is considered ok
     */
    protected function validate(Tracker_Artifact $artifact, $value) {
        $is_valid = true;
        if ($value) {
            $r = new Rule_Date();
            if (!($is_valid = $r->isValid($value))) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_common_artifact', 'error_date_value', array($this->getLabel())));
            }
        }
        return $is_valid;
    }
    
    /**
     * Save the value and return the id
     * 
     * @param Tracker_Artifact                $artifact                The artifact
     * @param int                             $changeset_value_id      The id of the changeset_value 
     * @param mixed                           $value                   The value submitted by the user
     * @param Tracker_Artifact_ChangesetValue $previous_changesetvalue The data previously stored in the db
     *
     * @return int or array of int
     */
    protected function saveValue($artifact, $changeset_value_id, $value, Tracker_Artifact_ChangesetValue $previous_changesetvalue = null) {
        return $this->getValueDao()->create($changeset_value_id, strtotime($value));
    }
    
    /**
     * Check if there are changes between old and new value for this field
     *
     * @param Tracker_Artifact_ChangesetValue $old_value The data stored in the db
     * @param mixed                           $new_value May be string or array
     *
     * @return bool true if there are differences
     */
    public function hasChanges(Tracker_Artifact_ChangesetValue $old_value, $new_value) {
        return strtotime($this->formatDate($old_value->getTimestamp())) != strtotime($new_value);
    }
    
    /**
     * Get the value of this field
     *
     * @param Tracker_Artifact_Changeset $changeset   The changeset (needed in only few cases like 'lud' field)
     * @param int                        $value_id    The id of the value
     * @param boolean                    $has_changed If the changeset value has changed from the rpevious one
     *
     * @return Tracker_Artifact_ChangesetValue or null if not found
     */
    public function getChangesetValue($changeset, $value_id, $has_changed) {
        $changeset_value = null;
        if ($row = $this->getValueDao()->searchById($value_id, $this->id)->getRow()) {
            $changeset_value = new Tracker_Artifact_ChangesetValue_Date($value_id, $this, $has_changed, $row['value']);
        }
        return $changeset_value;
    }
    
    /**
     * Get available values of this field for SOAP usage
     * Fields like int, float, date, string don't have available values
     *
     * @return mixed The values or null if there are no specific available values
     */
    public function getSoapAvailableValues() {
        return null;
    }
    
    /**
     * Compute the number of digits of an int (could be private but I want to unit test it)
     * 1 => 1
     * 12 => 2
     * 123 => 3
     * 1999 => 4
     * etc.
     *
     */
    public function _nbDigits($int_value) {
        return 1 + (int) (log($int_value) / log(10));
    }
    
    /**
     * Explode a date in the form of (m/d/Y H:i or d/m/Y H:i) regarding the csv peference 
     * into its a list of 5 parts (YYYY,MM,DD,H,i)
     * if DD and MM are not defined then default them to 1
     *
     *
     * Please use function date_parse_from_format instead
     * when codendi will run PHP >= 5.3
     *
     *
     * @param string $date the date in the form of m/d/Y H:i or d/m/Y H:i
     *
     * @return array the five parts of the date array(YYYY,MM,DD,H,i)
     */
    public function explodeXlsDateFmt($date) {
        $u_pref = $this->_getUserCSVDateFormat();
        
        $res = preg_match("/\s*(\d+)\/(\d+)\/(\d+) (\d+):(\d+)/",$date,$match);
        if ($res == 0) {
            //if it doesn't work try (n/j/Y) only
            $res = preg_match("/\s*(\d+)\/(\d+)\/(\d+)/",$date,$match);
            if ($res == 0) {
              // nothing is valid return Epoch time
              $year = '1970'; $month='1'; $day='1'; $hour='0'; $minute='0';
            } else {
                if ($u_pref == "day_month_year") {
                    list(,$day,$month,$year) = $match; $hour='0'; $minute='0';
                } else {
                    list(,$month,$day,$year) = $match; $hour='0'; $minute='0';
                }
            }
        } else {
            if ($u_pref == "day_month_year") {
                list(,$day,$month,$year,$hour,$minute) = $match;
            } else {
                list(,$month,$day,$year,$hour,$minute) = $match;
            }
        }
        if (checkdate($month,$day,$year)) {
            if ($this->_nbDigits($year) == 4) {
                return array($year,$month,$day,$hour,$minute);
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    /**
     * Get the field data for CSV import
     *
     * @param string $data_cell the CSV field value (a date with the form dd/mm/YYYY or mm/dd/YYYY)
     *
     * @return string the date with the form YYYY-mm-dd corresponding to the date $data_cell, or null if date format is wrong or empty
     */
    public function getFieldDataForCSVPreview($data_cell) {
        if ($data_cell !== '') {
            $date_explode = $this->explodeXlsDateFmt($data_cell);
            if ($date_explode != null) {
                if ($this->_nbDigits($date_explode[0]) == 4) {
                    return $date_explode[0] . '-' . $date_explode[1] . '-' . $date_explode[2];
                } else {
                    return null;
                }
            } else {
                return null;
            }
        } else {
            return null;
        }
    }
    
    /**
     * Get the field data for artifact submission
     *
     * @param string the soap field value
     *
     * @return String the field data corresponding to the soap_value for artifact submision, or null if date format is wrong
     */
    public function getFieldData($soap_value) {
        if (strpos($soap_value, '/') !== false) {
            // Assume the format is either dd/mm/YYYY or mm/dd/YYYY depending on the user preferences.
            return $this->getFieldDataForCSVPreview($soap_value);
        } elseif (strpos($soap_value, '-') !== false) {
            // Assume the format is YYYY-mm-dd
            $date_array = explode('-', $soap_value);
            if (count($date_array) == 3 && checkdate($date_array[1], $date_array[2], $date_array[0]) && $this->_nbDigits($date_array[0])) {
                return $soap_value;
            }else {
                return null;
            }
        } else {
            return null;
        }
    }

}
?>
