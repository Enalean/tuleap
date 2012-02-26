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
require_once(dirname(__FILE__).'/../Artifact/Tracker_Artifact_ChangesetValue_PermissionsOnArtifact.class.php');
require_once(dirname(__FILE__).'/../Report/dao/Tracker_Report_Criteria_PermissionsOnArtifact_ValueDao.class.php');
require_once('dao/Tracker_FormElement_Field_Value_PermissionsOnArtifactDao.class.php');
require_once('common/dao/UGroupDao.class.php');

class Tracker_FormElement_Field_PermissionsOnArtifact extends Tracker_FormElement_Field {

    public $default_properties = array();

    
    /**
     * Returns the default value for this field, or nullif no default value defined
     *
     * @return mixed The default value for this field, or null if no default value defined
     */
    function getDefaultValue() {        
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
        return true;
        //return $this->getDao()->delete($this->id);
    }
    
    /**
     * Display the field as a Changeset value.
     * Used in report table
     * @param int $artifact_id the corresponding artifact id
     * @param int $changeset_id the corresponding changeset
     * @param mixed $value the value of the field
     *
     * @return string
     */
    public function fetchChangesetValue($artifact_id, $changeset_id, $value, $from_aid = null) {
        
        $values = array();
        $artifact = Tracker_ArtifactFactory::instance()->getArtifactById($artifact_id);
        
        if ($artifact->useArtifactPermissions()) {
            $dao = new Tracker_Artifact_Changeset_ValueDao();
            $row = $dao->searchByFieldId($changeset_id, $this->id)->getRow();
            $changeset_value_id = $row['id'];
        
            foreach($this->getValueDao()->searchByChangesetValueId($changeset_value_id) as $value) {
                $name = $this->getUGroupDao()->searchByUGroupId($value['ugroup_id'])->getRow();
                $values[] = util_translate_name_ugroup($name['name']);
            }
        
            return implode(', ', $values);
        }
        return '';
    }
    
    /**
     * Display the field for CSV export
     * Used in CSV data export
     *
     * @param int $artifact_id the corresponding artifact id
     * @param int $changeset_id the corresponding changeset
     * @param mixed $value the value of the field
     *
     * @return string
     */
    public function fetchCSVChangesetValue($artifact_id, $changeset_id, $value) {
        $values = array();
        $artifact = Tracker_ArtifactFactory::instance()->getArtifactById($artifact_id);
        if ($artifact->useArtifactPermissions()) {
            $dao = new Tracker_Artifact_Changeset_ValueDao();
            $row = $dao->searchByFieldId($changeset_id, $this->id)->getRow();
            $changeset_value_id = $row['id'];
        
            foreach($this->getValueDao()->searchByChangesetValueId($changeset_value_id) as $value) {
                $name = $this->getUGroupDao()->searchByUGroupId($value['ugroup_id'])->getRow();
                $values[] = util_translate_name_ugroup($name['name']);
            }
        
            return implode(',', $values);
        }
        return '';
    }
    
    /**
     * Fetch the value
     * @param mixed $value the value of the field
     * @return string
     */
    public function fetchRawValue($value) {
        return $this->values[$value]->getLabel();
    }
    
    /**
     * Fetch the value in a specific changeset
     * @param Tracker_Artifact_Changeset $changeset
     * @return string
     */
    public function fetchRawValueFromChangeset($changeset) {
        $value = '';
        if ($v = $changeset->getValue($this->field)) {
            if (isset($v['value_id'])) {
                $v = array($v);
            }
            foreach($v as $val) {
                $value .= $this->values[$val['value_id']]['value'];
            }
        }
        return $value;        
        
    }
    
   /**
    * Returns the PermissionsOnArtifactDao
    *
    * @return Tracker_FormElement_Field_Value_PermissionsOnArtifactDao The dao
    */
    protected function getValueDao() {
        return new Tracker_FormElement_Field_Value_PermissionsOnArtifactDao();
    }
    
    /**
     * Fetch the html code to display the field value in new artifact submission form
     * @param array $submitted_values the values already submitted
     *
     * @return string html
     */
    protected function fetchSubmitValue($submitted_values = array()) {
        $value = '';        
        if (!empty($submitted_values[$this->getId()])) {
            $value=$submitted_values[$this->getId()];
        }else if ($this->hasDefaultValue()) {
            $value = $this->getDefaultValue();
        }
        $checked = '';
        if (is_array($value)) {
            if (isset($value['use_artifact_permissions']) && $value['use_artifact_permissions']) {
                $checked = 'checked="checked"';
            }
        }
        
        $html = '';
        $html .= '<p class="tracker_field_permissionsonartifact">';
        $html .= '<input type="hidden" name="use_artifact_permissions" value="0" />';
        $html .= '<input type="checkbox" name="artifact['.$this->getId().'][use_artifact_permissions]" id="artifact_'.$this->getId().'_use_artifact_permissions" value="1" '.$checked.'/>';
        $html .= '<label for="artifact_'.$this->getId().'_use_artifact_permissions">'. $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'permissions_label') .'</label>';
        $html .= '</p>';
        
        if (is_array($value)) {
            $html .= plugin_tracker_permission_fetch_selection_field('PLUGIN_TRACKER_ARTIFACT_ACCESS', 0, $this->getTracker()->getGroupId(), 'artifact['.$this->getId().'][u_groups][]', false, $value['u_groups']);

        } else {
            $html .= plugin_tracker_permission_fetch_selection_field('PLUGIN_TRACKER_ARTIFACT_ACCESS', 0, $this->getTracker()->getGroupId(), 'artifact['.$this->getId().'][u_groups][]');
        }
        return $html;
    }

    /**
     * Fetch the html code to display the field value in masschange submission form
     *
     * @return string html
     */
    protected function fetchSubmitValueMasschange() {
        $value = '';
        if ($this->hasDefaultValue()) {
            $value = $this->getDefaultValue();
        }
        $html = '';
        $html .= '<p class="tracker_field_permissionsonartifact">';
        $html .= '<input type="hidden" name="use_artifact_permissions" value="0" />';
        $html .= '<input type="checkbox" name="artifact['.$this->getId().'][use_artifact_permissions]" id="artifact_'.$this->getId().'_use_artifact_permissions" value="1"/>';
        $html .= '<label for="artifact_'.$this->getId().'_use_artifact_permissions">'. $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'permissions_label') .'</label>';
        $html .= '</p>';
        $html .= plugin_tracker_permission_fetch_selection_field('PLUGIN_TRACKER_ARTIFACT_ACCESS', 0, $this->getTracker()->getGroupId(), 'artifact['.$this->getId().'][u_groups][]');
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
        $is_read_only = false;
        return $this->fetchArtifactValueCommon($is_read_only, $artifact, $value, $submitted_values);
    }
    
    /**
     * Fetch the field value in artifact to be displayed in mail
     *
     * @param Tracker_Artifact                $artifact         The artifact
     * @param Tracker_Artifact_ChangesetValue $value            The actual value of the field
     * @param string                          $format           mail format
     *
     * @return string
     */
    public function fetchMailArtifactValue(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null, $format='text') {
        $is_read_only = true;
        $output = '';
        $separator = '&nbsp;';
        if ($format == 'text') {
            $separator = PHP_EOL;
            $output .= $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'permissions_label');
        }

        $ugroups  = permission_fetch_selected_ugroups('PLUGIN_TRACKER_ARTIFACT_ACCESS', $artifact->getId(), $this->getTracker()->getGroupId());
        $output .= $separator.implode(', ',$ugroups);
        return $output;
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
        $is_read_only = true;
        return $this->fetchArtifactValueCommon($is_read_only, $artifact, $value);
    }
    
    /**
     * Fetch the html code to display the field value in artifact 
     * @see fetchArtifactValueReadOnly
     * @see fetchArtifactValue
     *
     * @param bool                            $is_read_only     Is the field read only
     * @param Tracker_Artifact                $artifact         The artifact
     * @param Tracker_Artifact_ChangesetValue $value            The actual value of the field
     * @param array                           $submitted_values The value already submitted by the user
     * 
     * @return string
     */
    protected function fetchArtifactValueCommon($is_read_only, Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null, $submitted_values = array()) {
        $html = '';
        $value = array();
        if (is_array($submitted_values[0])) {
            if (isset($submitted_values[0][$this->getId()])) {
                    $value = $submitted_values[0][$this->getId()];
            }
        }

       $checked = '';
       if ( !empty($value) ) {
            if (isset($value['use_artifact_permissions']) && $value['use_artifact_permissions']) {
                $checked = 'checked="checked"';
            }
       } else if ($artifact->useArtifactPermissions()) {
            $checked = 'checked="checked"';
        }
        
        $readonly = '';
        if ($is_read_only) {
            $readonly = 'disabled="disabled"';
        }
        
        $html = '';
        $html .= '<p class="tracker_field_permissionsonartifact">';
        $html .= '<input type="hidden" name="use_artifact_permissions" value="0" />';
        $html .= '<input type="checkbox" 
                         name="artifact['.$this->getId().'][use_artifact_permissions]"
                         id="artifact_'.$this->getId().'_use_artifact_permissions" 
                         value="1" '.
                         $checked.' '.
                         $readonly .'/>';
        $html .= '<label for="artifact_'.$this->getId().'_use_artifact_permissions">'. $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'permissions_label') .'</label>';
        $html .= '</p>';

        if ($value == null) {
            $html .= plugin_tracker_permission_fetch_selection_field('PLUGIN_TRACKER_ARTIFACT_ACCESS', $artifact->getId(), $this->getTracker()->getGroupId(), 'artifact['.$this->getId().'][u_groups][]', $is_read_only, $value);
        } else if (is_array($value)) {
            $html .= plugin_tracker_permission_fetch_selection_field('PLUGIN_TRACKER_ARTIFACT_ACCESS', $artifact->getId(), $this->getTracker()->getGroupId(), 'artifact['.$this->getId().'][u_groups][]', $is_read_only, $value['u_groups']);

        } else {
            $html .= plugin_tracker_permission_fetch_selection_field('PLUGIN_TRACKER_ARTIFACT_ACCESS', $artifact->getId(), $this->getTracker()->getGroupId(), 'artifact['.$this->getId().'][u_groups][]', $is_read_only);
        }
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
            $html .= ' '.$GLOBALS['Language']->getText('plugin_tracker_artifact','changed_from').' '. $from_value .'  '.$GLOBALS['Language']->getText('plugin_tracker_artifact','to').' ';
        }
        $to_value = $this->getValue($to['value_id']);
        $html .= $to_value['value'];
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
        $html .= '<p class="tracker_field_permissionsonartifact">';
        $html .= '<input type="hidden" name="use_artifact_permissions" value="0" />';
        $html .= '<input type="checkbox" name="artifact['.$this->getId().'][use_artifact_permissions]" id="artifact_'.$this->getId().'_use_artifact_permissions" value="1"/>';
        $html .= '<label for="artifact_'.$this->getId().'_use_artifact_permissions">'. $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'permissions_label') .'</label>';
        $html .= '</p>';
        $html .= plugin_tracker_permission_fetch_selection_field('PLUGIN_TRACKER_ARTIFACT_ACCESS', 0, 0, 'artifact['.$this->getId().'][u_groups][]');
        return $html;
    }

    /**
     * @return the label of the field (mainly used in admin part)
     */
    public static function getFactoryLabel() {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin','permissions');
    }
    
    /**
     * @return the description of the field (mainly used in admin part)
     */
    public static function getFactoryDescription() {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin','permissions_description');
    }
    
    /**
     * @return the path to the icon
     */
    public static function getFactoryIconUseIt() {
        return $GLOBALS['HTML']->getImagePath('ic/lock.png');
    }
    
    /**
     * @return the path to the icon
     */
    public static function getFactoryIconCreate() {
        return $GLOBALS['HTML']->getImagePath('ic/lock--plus.png');
    }
    
    /**
     * @return bool say if the field is a unique one
     */
    public static function getFactoryUniqueField() {
        return true;
    }
    
    /**
     * Fetch the html code to display the field value in tooltip
     * 
     * @param Tracker_Artifact $artifact
     * @param Tracker_Artifact_ChangesetValue_PermissionsOnArtifact $value The changeset value for this field
     * @return string
     */
    protected function fetchTooltipValue(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null) {
        $html = '';
        if ($value && $artifact->useArtifactPermissions()) {
            $ugroup_dao = $this->getUGroupDao();
        
            $perms = $value->getPerms();
            $perms_name = array();
            foreach ($perms as $perm) {
                $row = $ugroup_dao->searchByUGroupId($perm)->getRow();
                $perms_name[] = util_translate_name_ugroup($row['name']);
            }
            $html .= implode(",", $perms_name);
        }
        return $html;
    }
    
   /**
    * Returns the UGroupDao
    *
    * @return UGroupDao The dao
    */
    protected function getUGroupDao() {
        return new UGroupDao(CodendiDataAccess::instance());
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
            $criteria_value = $this->getCriteriaValue($criteria);
            if ($criteria_value && count($criteria_value) == 1 && in_array("100", array_keys($criteria_value))){
                $a = 'A_'. $this->id;
                $b = 'B_'. $this->id;
                 $sql = " INNER JOIN tracker_changeset_value AS $a ON ($a.changeset_id = c.id AND $a.field_id = ". $this->id .")
                          INNER JOIN tracker_artifact AS $b ON ($b.last_changeset_id = $a.changeset_id AND
                            $b.use_artifact_permissions = 0) ";
                return $sql;
            } else if ($criteria_value) {
                $a = 'A_'. $this->id;
                $b = 'B_'. $this->id;
                $c = 'C_'. $this->id;
                $sql = " INNER JOIN tracker_changeset_value AS $a ON ($a.changeset_id = c.id AND $a.field_id = ". $this->id .")
                         INNER JOIN tracker_changeset_value_permissionsonartifact AS $b ON ($b.changeset_value_id = $a.id 
                            AND $b.ugroup_id IN(". implode(',', array_keys($criteria_value)) .")
                      )";
                return $sql;
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
        $R1 = 'R1_'. $this->id;
        $R2 = 'R2_'. $this->id;
        
        return "LEFT JOIN ( tracker_changeset_value AS $R1 
                    INNER JOIN tracker_changeset_value_permissionsonartifact AS $R2 ON ($R2.changeset_value_id = $R1.id)
                ) ON ($R1.changeset_id = c.id AND $R1.field_id = ". $this->id ." )";
       
    }
    
     /**
     * Get the "select" statement to retrieve field values
     * @return string
     * @see getQueryFrom
     */
    public function getQuerySelect() {
        $R1 = 'R1_'. $this->id;
        $R2 = 'R2_'. $this->id;
        
        
        return "$R2.ugroup_id AS `". $this->name ."`";
    }
    
    /**
     * Search in the db the criteria value used to search against this field.
     * @param Tracker_ReportCriteria $criteria
     * @return mixed
     */
    public function getCriteriaValue($criteria) {
        
        if ($this->criteria_value) {
            $values = $this->criteria_value; 
            $this->criteria_value = array();
            foreach($values as $value) {
                foreach ($value as $v) {
                    if ($v !='') {
                        $this->criteria_value[$v] = $value;
                    } else {
                        return '';
                    }
                }
            }            
        } else if (!isset($this->criteria_value)) {
            $this->criteria_value = array();
            foreach($this->getCriteriaDao()
                         ->searchByCriteriaId($criteria->id) as $row) {
                $this->criteria_value[$row['value']] = $row;
            }
        }
        
        return $this->criteria_value;
    }
    
    public function getCriteriaWhere($criteria) {
        return '';
    }
    
    public function fetchCriteriaValue($criteria) {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '';
        $criteria_value = $this->getCriteriaValue($criteria);
        $multiple = ' ';
        $size     = ' ';
        $name     = "criteria[$this->id][values][]";
        
        //Field values
        $permission_type ='PLUGIN_TRACKER_ARTIFACT_ACCESS';
        $object_id = 0;
        $group_id = $this->getTracker()->getGroupId();
        
        //TODO :From permissions.php
        // Get ugroups already defined for this permission_type
        $res_ugroups=permission_db_authorized_ugroups($permission_type, $object_id);
        $nb_set=db_numrows($res_ugroups);

        // Now retrieve all possible ugroups for this project, as well as the default values
        $sql="SELECT ugroup_id,is_default FROM permissions_values WHERE permission_type='$permission_type'";
        $res=db_query($sql);
        $predefined_ugroups='';
        $default_values=array();
        if (db_numrows($res)<1) {
            $html .= "<p><b>".$GLOBALS['Language']->getText('global','error')."</b>: ".$GLOBALS['Language']->getText('project_admin_permissions','perm_type_not_def',$permission_type);
            return $html;
        } else { 
            while ($row = db_fetch_array($res)) {
                if ($predefined_ugroups) { $predefined_ugroups.= ' ,';}
                    $predefined_ugroups .= $row['ugroup_id'] ;
                if ($row['is_default']) $default_values[]=$row['ugroup_id'];
            }
        }
        $sql="SELECT * FROM ugroup WHERE group_id=".$group_id." OR ugroup_id IN (".$predefined_ugroups.") ORDER BY ugroup_id";
        $res=db_query($sql);
    
        $array = array();
        while($row = db_fetch_array($res)) {
            $name_ugroup = util_translate_name_ugroup($row[1]);
            $array[] = array(
                'value' => $row[0], 
                'text' => $name_ugroup
              );
        }            
        //end permissions.php
        
       if ($criteria->is_advanced) {
            $multiple = ' multiple="multiple" ';
            $size     = ' size="'. min(7, count($array) + 2) .'" ';
        }
        
        $html .= '<select id="tracker_report_criteria_'. ($criteria->is_advanced ? 'adv_' : '') . $this->id .'" 
                          name="'. $name .'" '. 
                          $size . 
                          $multiple .'>';
        //Any value
        $selected = count($criteria_value) ? '' : 'selected="selected"';
        $html .= '<option value="" '. $selected .'>'. $GLOBALS['Language']->getText('global','any') .'</option>';
        //None value
        $selected = isset($criteria_value[100]) ? 'selected="selected"' : '';
        $html .= '<option value="100" '. $selected .'>'. $GLOBALS['Language']->getText('global','none') .'</option>';
       
        foreach($array as $value) {
            $id = $value ['value'];
            $selected = isset($criteria_value[$id]) ? 'selected="selected"' : '';
            $html .= '<option value="'. $value['value'] .'">';
            $html .= $value['text'];
            $html .= '</option>';
        }
        $html .= '</select>';
        return $html;
    }

    protected function getCriteriaDao() {
        return new Tracker_Report_Criteria_PermissionsOnArtifact_ValueDao();
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
        return true;
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
        if (empty($value)) {
            $value['use_artifact_permissions'] = 0;
            $value['u_groups'] = array();
        }
        $artifact->setUseArtifactPermissions($value['use_artifact_permissions']);
        permission_clear_all($this->getTracker()->getGroupId(), 'PLUGIN_TRACKER_ARTIFACT_ACCESS', $artifact->getId(), false);
        
        if (!empty($value['u_groups'])) {
                $ok = $this->addPermissions($value['u_groups'], $artifact->getId());
        }
        //save in changeset
        return $this->getValueDao()->create($changeset_value_id, $value['use_artifact_permissions'], $value['u_groups']);
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
        return $old_value !== $new_value;
    }
    
    /**
     * Get the value of this field
     *
     * @param Tracker_Artifact_Changeset $changeset   The changeset (needed in only few cases like 'lud' field)
     * @param int                        $value_id    The id of the value
     * @param boolean                    $has_changed If the changeset value has changed from the previous one
     *
     * @return Tracker_Artifact_ChangesetValue or null if not found
     */
    public function getChangesetValue($changeset, $value_id, $has_changed) {
        
        $changeset_value = null;
        $value_ids = $this->getValueDao()->searchById($value_id, $this->id);
        $ugroups = array();
        
        foreach($value_ids as $v) {
           $ugroups[] = $v['ugroup_id'];
        }

        $changeset_value = new Tracker_Artifact_ChangesetValue_PermissionsOnArtifact($value_id, $this, $has_changed, $changeset->getArtifact()->useArtifactPermissions(), $ugroups);
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
     * Get the field data for artifact submission
     *
     * @param string the soap field value
     *
     * @return mixed the field data corresponding to the soap_value for artifact submision
     */
    public function getFieldData($soap_value) {
        if (trim($soap_value) != '') {
            $soap_values = explode(',', $soap_value);
            //$available_ugroups = $this->getAvailableUGroups();
            $ugroups_id = array();
            foreach ($soap_values as $v) {
                //$ugroup_id = $this->getUGroupIdByName($v);
                if ($ugroup_id) {
                    $ugroups_id[] = $ugroup_id;
                } else {
                    return null;
                }
            }
            $return = array(
                'uses_artifact_permissions' => 1,
                'u_groups' => $ugroups_id
            );
            return $return;
        } else {
            $return = array(
                'uses_artifact_permissions' => 0,
                'u_groups' => array()
            );
            return $return;
        }
    }
     
    /**
     * @return bool
     */
    protected function criteriaCanBeAdvanced() {
        return true;
    }
     
    /**
     * Adds permissions in the database
     * 
     * @param Array $ugroups the list of ugroups
     * @param int          $artifact  The id of the artifact
     * 
     * @return boolean
     */
    public function addPermissions ($ugroups, $artifact_id) {
        $pm = PermissionsManager::instance();
        $permission_type = 'PLUGIN_TRACKER_ARTIFACT_ACCESS';
        foreach ($ugroups as $ugroup) {
            if(!$pm->addPermission($permission_type, $artifact_id, $ugroup)) {
                return false;
            }
        }
        return true;
    }
}
?>