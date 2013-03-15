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


class Tracker_FormElement_Field_OpenList extends Tracker_FormElement_Field_List {
    
    public $default_properties = array(
        'hint' => array(
            'value' => 'Type in a search term',
            'type'  => 'string',
            'size'  => 40,
        ),
    );
    
    /**
     * @return boolean
     */
    public function isMultiple() {
        return true;
    }
    
    /**
     * fetch the html widget 
     *
     * @param array $values The existing values. default is empty
     * @param mixed $name   A string for a given name or true for the default name (artifact[123]), false for no name.
     *
     * @return string html
     */
    public function fetchOpenList($values = array(), $name = true) {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '';
        if ($name === true) { //we want the default name
            $name = 'name="artifact['. $this->id .']"';
        } else if ($name === false) { //we don't want a name
            $name = '';
        } else { //we keep the given name
            $name = 'name="'. $name .'"';
        }
        $html .= '<div class="textboxlist">
                    <input id="tracker_field_'. $this->id .'" 
                           '. $name .'
                           style="width:98%"
                           type="text"></div>';
        
        $html .= '<div class="textboxlist-auto" id="tracker_artifact_textboxlist_'. $this->id .'">';
        $html .= '<div class="default">'.  $hp->purify($this->getProperty('hint'), CODENDI_PURIFIER_LIGHT) .'</div>';
        $html .= '<ul class="feed">';
        //Field values
        foreach($values as $v) {
            if ($v->getId() != 100) {
                $html .= '<li value="'. $v->getJsonId() .'">';
                $html .= $v->getLabel();
                $html .= '</li>';
            }
        }
        $html .= '</ul>';
        $html .= '</div>';
        return $html;
    }

     /**
     * fetch the html widget
     *
     * @param array $values The existing values. default is empty
     * @param mixed $name   A string for a given name or true for the default name (artifact[123]), false for no name.
     *
     * @return string html
     */
    public function fetchOpenListMasschange($values = array(), $name = true) {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '';
        if ($name === true) { //we want the default name
            $name = 'name="artifact['. $this->id .']"';
        } else if ($name === false) { //we don't want a name
            $name = '';
        } else { //we keep the given name
            $name = 'name="'. $name .'"';
        }
        $html .= '<div class="textboxlist">
                    <input id="tracker_field_'. $this->id .'"
                           '. $name .'
                           style="width:98%"
                           type="text"></div>';

        $html .= '<div class="textboxlist-auto" id="tracker_artifact_textboxlist_'. $this->id .'">';
        $html .= '<div class="default">'.  $hp->purify($this->getProperty('hint'), CODENDI_PURIFIER_LIGHT) .'</div>';
        $html .= '<ul class="feed">';
        $html .= '<li value="'.$GLOBALS['Language']->getText('global','unchanged').'">';
        $html .= $GLOBALS['Language']->getText('global','unchanged');
        $html .= '</li>';        
        $html .= '</ul>';
        $html .= '</div>';
        return $html;
    }

    /**
     * Fetch the html code to display the field value in new artifact submission form
     *
     * @return string html
     */
    protected function fetchSubmitValue($submitted_values = array()) {
        if (isset($submitted_values[$this->id])) {
            return $this->fetchOpenList($this->toObj($submitted_values[$this->id]));
        }
        return $this->fetchOpenList();
    }

     /**
     * Fetch the html code to display the field value in masschange submission form
     *
     * @return string html
     */
    protected function fetchSubmitValueMasschange() {
        return $this->fetchOpenListMasschange();
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
        $selected_values = $value ? $value->getListValues() : array();
        if (is_array($submitted_values[0])) {
            return $this->fetchOpenList($this->toObj($submitted_values[0][$this->id]));
        }
        return $this->fetchOpenList($selected_values);
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
        $labels = array();
        $selected_values = $value ? $value->getListValues() : array();
        foreach($selected_values as $id => $v) {
            if ($id != 100) {
                $labels[] = $v->getLabel();
            }
        }
        return implode(', ', $labels);
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
        if ( empty($value) || ! $value->getListValues()) {
            return '-';
        }
        $output = '';
        switch ($format) {
            case 'html':
                $output = $this->fetchArtifactValueReadOnly($artifact, $value);
                break;
            default:
                $selected_values = !empty($value) ? $value->getListValues() : array();
                foreach ($selected_values as $value) {
                    if ($value->getId() != 100) {
                        $output .= $value->getLabel();
                    }
                }
                break;
        }
        return $output;
    }


    public function textboxlist($keyword, $limit = 10) {
        $json_values = array();
        $matching_values = $this->getBind()->getValuesByKeyword($keyword, $limit);
        $nb = count($matching_values);
        if ($nb < $limit) {
            foreach ($this->getOpenValueDao()->searchByKeyword($this->getId(), $keyword, $limit - $nb) as $row) {
                $matching_values[] = new Tracker_FormElement_Field_List_OpenValue(
                    $row['id'],
                    $row['label']
                );
            }
        }
        foreach($matching_values as $v) {
            $json_values[] = $v->fetchValuesForJson();
        }
        return json_encode($json_values);
    }
    
    public function process(Tracker_IDisplayTrackerLayout $layout, $request, $current_user) {
        parent::process($layout, $request, $current_user);
        switch($request->get('func')) {
            case 'textboxlist':
                if ($request->get('keyword')) {
                    echo $this->textboxlist($request->get('keyword'));
                } else {
                    echo '[]';
                }
                exit;
                break;
        }
    }
    
    /**
     * Display the html field in the admin ui
     * @return string html
     */
    protected function fetchAdminFormElement() {
        $no_values = array();
        $has_name  = false;
        return $this->fetchOpenList($no_values, $has_name);
    }
    
    /**
     * Return the dao
     *
     * @return Tracker_FormElement_Field_OpenListDao The dao
     */
    protected function getDao() {
        return new Tracker_FormElement_Field_OpenListDao();
    }
    
    /**
     * @return the label of the field (mainly used in admin part)
     */
    public static function getFactoryLabel() {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin','open_list');
    }
    
    /**
     * @return the description of the field (mainly used in admin part)
     */
    public static function getFactoryDescription() {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin','open_list_description');

        return 'Provide a textbox containing an list of values, with autocompletion';
    }
    
    /**
     * @return the path to the icon
     */
    public static function getFactoryIconUseIt() {
        return $GLOBALS['HTML']->getImagePath('ic/ui-scroll-pane-list.png');
    }
    
    /**
     * @return the path to the icon
     */
    public static function getFactoryIconCreate() {
        return $GLOBALS['HTML']->getImagePath('ic/ui-scroll-pane-list--plus.png');
    }
    
    protected function getValueDao() {
        return new Tracker_FormElement_Field_Value_OpenListDao();
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
        $value_ids = $this->getValueDao()->searchById($value_id, $this->id);
        $bindvalue_ids = array();
        foreach($value_ids as $v) {
            if ($v['bindvalue_id']) {
                $bindvalue_ids[] = $v['bindvalue_id'];
            }
        }
        $bind_values = array();
        if (count($bindvalue_ids)) {
            $bind_values = $this->getBind()->getBindValues($bindvalue_ids);
        }
        
        $list_values = array();
        foreach($value_ids as $v) {
            if ($v['bindvalue_id']) {
                if (isset($bind_values[$v['bindvalue_id']])) {
                    $list_values[] = $bind_values[$v['bindvalue_id']];
                }
            } else {
                if ($v = $this->getOpenValueById($v['openvalue_id'])) {
                    $list_values[] = $v;
                }
            }
        }
        $changeset_value = new Tracker_Artifact_ChangesetValue_OpenList($value_id, $this, $has_changed, $list_values);
        return $changeset_value;
    }

    protected function getOpenValueDao() {
        return new Tracker_FormElement_Field_List_OpenValueDao();
    }
    
    protected $cache_openvalues = array();
    
    protected function getOpenValueById($oid) {
        if ( ! isset($this->cache_openvalues[$oid]) ) {
            $this->cache_openvalues[$oid] = null;
            if ($row = $this->getOpenValueDao()->searchById($this->getId(), $oid)->getRow()) {
                $this->cache_openvalues[$oid] = new Tracker_FormElement_Field_List_OpenValue(
                    $row['id'],
                    $row['label']
                );
            }
        }
        return $this->cache_openvalues[$oid];
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
        $openvalue_dao = $this->getOpenValueDao();
        // the separator is a comma
        $values = $this->sanitize($value);
        $value_ids = array();
        foreach ($values as $v) {
            $bindvalue_id = null;
            $openvalue_id = null;
            switch ($v{0}) {
            case 'b': // bind value
                $bindvalue_id = (int)substr($v, 1);
                break;
            case 'o': // open value
                $openvalue_id = (int)substr($v, 1);
                break;
            case '!': // new open value
                $openvalue_id = $openvalue_dao->create($this->getId(), substr($v, 1));
                break;
            default:
                break;
            }
            if ($bindvalue_id || $openvalue_id) {
                $value_ids[] = array(
                    'bindvalue_id' => $bindvalue_id,
                    'openvalue_id' => $openvalue_id,
                );
            }
        }
        return $this->getValueDao()->create($changeset_value_id, $value_ids);
    }
    
    /**
     * Remove bad stuff
     *
     * @param string $submitted_value
     *
     * @return array
     */
    protected function sanitize($submitted_value) {
        $values = explode(',', (string)$submitted_value);
        $sanitized = array();
        foreach ($values as $v) {
            $v = trim($v);
            if ($v) {
                switch ($v{0}) {
                case 'b': // bind value
                    if ($bindvalue_id = (int)substr($v, 1)) {
                        $sanitized[] = $v;
                    }
                    break;
                case 'o': // open value
                    if ($openvalue_id = (int)substr($v, 1)) {
                        $sanitized[] = $v;
                    }
                    break;
                case '!': // new open value
                    
                    $sanitized[] = $v;
                    break;
                default:
                    break;
                }
            }
        }
        return $sanitized;
    }
    
    protected function toObj($submitted_value) {
        $openvalue_dao = $this->getOpenValueDao();
        $values = explode(',', (string)$submitted_value);
        $sanitized = array();
        foreach ($values as $v) {
            $v = trim($v);
            if ($v) {
                switch ($v{0}) {
                case 'b': // bind value
                    if ($bindvalue_id = (int)substr($v, 1)) {
                        $sanitized[] = new Tracker_FormElement_Field_List_Bind_UsersValue($bindvalue_id);
                    }
                    break;
                case 'o': // open value
                    if ($openvalue_id = (int)substr($v, 1)) {
                        $v = $this->getOpenValueById($openvalue_id);
                        $sanitized[] = $v;
                    }
                    break;
                case '!': // new open value 
                    $sanitized[] = new Tracker_FormElement_Field_List_UnsavedValue(substr($v, 1));
                    break;
                default:
                    break;
                }
            }
        }
        return $sanitized;
    }
    /**
     * Check if there are changes between old and new value for this field
     *
     * @param Tracker_Artifact_ChangesetValue $previous_changesetvalue The data stored in the db
     * @param string                           $new_value              string
     *
     * @return bool true if there are differences
     */
    public function hasChanges($previous_changesetvalue, $new_value) {
        return $previous_changesetvalue->getValue() != $this->sanitize($new_value);
    }
    
    /**
     * Display the field as a Changeset value.
     * Used in report table
     *
     * @param int $artifact_id the corresponding artifact id
     * @param int $changeset_id the corresponding changeset
     * @param mixed $value the value of the field
     *
     * @return string
     */
    public function fetchChangesetValue($artifact_id, $changeset_id, $value, $from_aid = null) {
        $arr = array();
        $bindtable = $this->getBind()->getBindtableSqlFragment();
        $values = $this->getDao()->searchChangesetValues(
            $changeset_id, 
            $this->id, 
            $bindtable['select'], 
            $bindtable['select_nb'], 
            $bindtable['from'], 
            $bindtable['join_on_id']
        );
        foreach ($values as $row) {
            if ($row['openvalue_label']) {
                $v = new Tracker_FormElement_Field_List_OpenValue(
                    $row['id'],
                    $row['openvalue_label']
                );
            } else {
                $v = $this->getBind()->getValueFromRow($row);
            }
            if ($v) {
                $arr[] = $v->fetchFormatted();
            }
        }
        $html = implode(', ', $arr);
        return $html;
    }
    
    /**
     * Display the field for CSV data export
     * Used in CSV data export
     *
     * @param int $artifact_id the corresponding artifact id
     * @param int $changeset_id the corresponding changeset
     * @param mixed $value the value of the field
     *
     * @return string
     */
    public function fetchCSVChangesetValue($artifact_id, $changeset_id, $value) {
        $arr = array();
        $bindtable = $this->getBind()->getBindtableSqlFragment();
        $values = $this->getDao()->searchChangesetValues(
            $changeset_id, 
            $this->id, 
            $bindtable['select'], 
            $bindtable['select_nb'], 
            $bindtable['from'], 
            $bindtable['join_on_id']
        );
        foreach ($values as $row) {
            if ($row['openvalue_label']) {
                $v = new Tracker_FormElement_Field_List_OpenValue(
                    $row['id'],
                    $row['openvalue_label']
                );
            } else {
                $v = $this->getBind()->getValueFromRow($row);
            }
            if ($v) {
                $arr[] = $v->fetchFormattedForCSV();
            }
        }
        $txt = implode(',', $arr);
        return $txt;
    }
    
    /**
     * Return the dao of the criteria value used with this field.
     * @return DataAccessObject
     */
    protected function getCriteriaDao() {
        return new Tracker_Report_Criteria_OpenList_ValueDao();
    }
    
    /**
     * Get the "from" statement to allow search with this field
     * You can join on 'c' which is a pseudo table used to retrieve 
     * the last changeset of all artifacts.
     *
     * @param Tracker_ReportCriteria $criteria
     *
     * @return string
     */
    public function getCriteriaFrom($criteria) {
        //Only filter query if field is used
        if($this->isUsed()) {
            $criteria_value = $this->extractCriteriaValue($this->getCriteriaValue($criteria));
            $openvalues     = array();
            $bindvalues     = array();
            foreach ($criteria_value as $v) {
                if (is_a($v, 'Tracker_FormElement_Field_List_UnsavedValue')) {
                    //ignore it
                } else if (is_a($v, 'Tracker_FormElement_Field_List_OpenValue')) {
                    $openvalues[] = $v->getId();
                } else { //bindvalue
                    $bindvalues[] = $v->getId();
                }
            }
            $openvalues = implode(',', $openvalues);
            $bindvalues = implode(',', $bindvalues);
            //Only filter query if criteria is valuated
            if ($openvalues || $bindvalues) {
                $a = 'A_'. $this->id;
                $b = 'B_'. $this->id;
                $statement = '';
                if ($openvalues) {
                    $statement .= "$b.openvalue_id IN ($openvalues)";
                }
                if ($bindvalues) {
                    if ($statement) {
                        $statement .= ' OR ';
                    }
                    $statement .= "$b.bindvalue_id IN ($bindvalues)";
                }
                return " INNER JOIN tracker_changeset_value AS $a 
                         ON ($a.changeset_id = c.id 
                             AND $a.field_id = ". $this->id ."
                         ) 
                         INNER JOIN tracker_changeset_value_openlist AS $b ON (
                            $b.changeset_value_id = $a.id
                            AND ($statement)
                         ) 
                         ";
            }
        }
        return '';
    }
    
    /**
     * Get the "from" statement to retrieve field values
     * You can join on artifact AS a, tracker_changeset AS c 
     * which tables used to retrieve the last changeset of matching artifacts.
     * @return string
     */
    public function getQueryFrom() {
        return $this->getBind()->getQueryFrom('tracker_changeset_value_openlist');
    }
    
    /**
     * Get the "where" statement to allow search with this field
     *
     * @see getCriteriaFrom
     *
     * @param Tracker_ReportCriteria $criteria
     *
     * @return string
     */
    public function getCriteriaWhere($criteria) {
        return ''; //$this->getBind()->getCriteriaWhere($this->getCriteriaValue($criteria));
    }
    
    /**
     * Search in the db the criteria value used to search against this field.
     * @param Tracker_ReportCriteria $criteria
     * @return mixed
     */
    public function getCriteriaValue($criteria) {
        if ( ! isset($this->criteria_value) ) {
            $this->criteria_value = '';
            if ($row = $this->getCriteriaDao()->searchByCriteriaId($criteria->id)->getRow()) {
                $this->criteria_value = $row['value'];
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
        return $value;
    }

    /**
     * Display the field value as a criteria
     * @param Tracker_ReportCriteria $criteria
     * @return string
     * @see fetchCriteria
     */
    public function fetchCriteriaValue($criteria) {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '';
        $criteria_value = $this->extractCriteriaValue($this->getCriteriaValue($criteria));
        
        $name = "criteria[$this->id]";
        return $this->fetchOpenList($criteria_value, $name);
    }
    
    
    protected function extractCriteriaValue($criteria_value) {
        //switch to array
        if (strpos($criteria_value, ',') !== false) {
            $criteria_value = explode(',', $criteria_value);
        } else {
            $criteria_value = array($criteria_value);
        }
        
        //first extract open and unsaved values
        $bindvalue_ids = array();
        foreach ($criteria_value as $key => $val) {
            $val = trim($val);
            if (!$val) {
                unset($criteria_value[$key]);
            } else if ($val{0} === 'o') {
                if ($v = $this->getOpenValueById(substr($val, 1))) {
                    $criteria_value[$key] = $v;
                } else {
                    unset($criteria_value[$key]);
                }
            } else if ($val{0} === 'b') {
                $bindvalue_ids[] = substr($val, 1);
                $criteria_value[$key] = $val; //store the trimmed val
            } else if ($val{0} === '!') {
                $criteria_value[$key] = new Tracker_FormElement_Field_List_UnsavedValue(substr($val, 1));
            } else {
                unset($criteria_value[$key]);
            }
        }
        
        //load bind values
        $bind_values = array();
        if (count($bindvalue_ids)) {
            $bind_values = $this->getBind()->getBindValues($bindvalue_ids);
        }
        //then extract bindvalues from the criteria list
        foreach ($criteria_value as $key => $val) {
            if (is_string($val)) {
                $val = substr($val, 1);
                if ( ! empty($bind_values[$val])) {
                    $criteria_value[$key] = $bind_values[$val];
                } else {
                    unset($criteria_value[$key]);
                }
            }
        }
        return $criteria_value;
    }
    
    /**
     * @return bool
     */
    protected function criteriaCanBeAdvanced() {
        return false;
    }

    public function getFieldDataFromSoapValue(stdClass $soap_value, Tracker_Artifact $artifact = null) {
        if (isset($soap_value->field_value->bind_value)) {
            return $this->joinFieldDataFromArray(
                array_map(
                    array($this, 'getSOAPBindValueLabel'),
                    $soap_value->field_value->bind_value
                )
            );
        } else {
            return $this->getFieldData($soap_value->field_value->value);
        }
    }

    private function getSOAPBindValueLabel(stdClass $field_bind_value) {
        return $this->getFieldDataFromStringValue($field_bind_value->bind_value_label);
    }

    private function joinFieldDataFromArray(array $field_data) {
        return implode(',', array_filter($field_data));
    }

    /**
     * Get the field data for artifact submission
     *
     * @param string the soap field value
     *
     * @return mixed the field data corresponding to the soap_value for artifact submision
     */
    public function getFieldData($soap_value) {
        if (trim($soap_value) != '') {
            return $this->joinFieldDataFromArray(
                array_map(
                    array($this, 'getFieldDataFromStringValue'),
                    explode(',', $soap_value)
                )
            );
        } else {
            return '';
        }
    }

    protected function getFieldDataFromStringValue($value) {
        if ($value == '') {
            return;
        }
        $sv = $this->getBind()->getFieldData($value, false);   // false because we are walking all values one by one
        if ($sv) {
            // existing bind value
            return 'b'.$sv;
        } else {
            $row = $this->getOpenValueDao()->searchByExactLabel($this->getId(), $value)->getRow();
            if ($row) {
                // existing open value
                return 'o'.$row['id'];
            } else {
                // new open value
                return '!'.$value;
            }
        }
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
        if (!$is_valid = $this->getBind()->isvalid($value)) {
                return $GLOBALS['Response']->addFeedback('error', $this->getValidatorErrorMessage());
        }
        return $is_valid;
    }
    
    /**
     * @return string the i18n error message to display if the value submitted by the user is not valid
     */
    protected function getValidatorErrorMessage() {
        return $this->getLabel() . ' ' . $GLOBALS['Language']->getText('plugin_tracker_common_artifact', 'error_openlist_value');
    }
    
    /**
     * @return boolean true if the value corresponds to none
     */
    public function isNone($value) {
        return ($value === null || $value === '');
    }

}
?>
