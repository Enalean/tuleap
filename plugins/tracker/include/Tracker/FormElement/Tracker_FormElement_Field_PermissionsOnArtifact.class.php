<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

use Tuleap\User\UserGroup\NameTranslator;

require_once('common/dao/UGroupDao.class.php');

class Tracker_FormElement_Field_PermissionsOnArtifact extends Tracker_FormElement_Field {

    const GRANTED_GROUPS     = 'granted_groups';
    const USE_IT             = 'use_artifact_permissions';
    const IS_USED_BY_DEFAULT = false;
    const PERMISSION_TYPE    = 'PLUGIN_TRACKER_ARTIFACT_ACCESS';

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
    public function fetchChangesetValue($artifact_id, $changeset_id, $value, $report=null, $from_aid = null) {

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
    public function fetchCSVChangesetValue($artifact_id, $changeset_id, $value, $report) {
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
        $value   = $this->getValueFromSubmitOrDefault($submitted_values);
        $checked = '';
        if (is_array($value)) {
            if (isset($value[self::USE_IT]) && $value[self::USE_IT]) {
                $checked = 'checked="checked"';
            }
        }

        $html = '';
        $html .= '<p class="tracker_field_permissionsonartifact">';
        $html .= '<input type="hidden" name="artifact['.$this->getId().'][use_artifact_permissions]" value="0" />';
        $html .= '<label class="checkbox" for="artifact_'.$this->getId().'_use_artifact_permissions">';
        $html .= '<input type="checkbox" name="artifact['.$this->getId().'][use_artifact_permissions]" id="artifact_'.$this->getId().'_use_artifact_permissions" value="1" '.$checked.'/>';
        $html .= $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'permissions_label') . '</label>';
        $html .= '</p>';

        if (is_array($value)) {
            $html .= plugin_tracker_permission_fetch_selection_field(self::PERMISSION_TYPE, 0, $this->getTracker()->getGroupId(), 'artifact['.$this->getId().'][u_groups][]', false, $value['u_groups']);

        } else {
            $html .= plugin_tracker_permission_fetch_selection_field(self::PERMISSION_TYPE, 0, $this->getTracker()->getGroupId(), 'artifact['.$this->getId().'][u_groups][]');
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
        $html .= '<input type="hidden" name="artifact['.$this->getId().'][use_artifact_permissions]" value="0" />';
        $html .= '<label class="checkbox" for="artifact_'.$this->getId().'_use_artifact_permissions">';
        $html .= '<input type="checkbox" name="artifact['.$this->getId().'][use_artifact_permissions]" id="artifact_'.$this->getId().'_use_artifact_permissions" value="1"/>';
        $html .= $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'permissions_label') . '</label>';
        $html .= '</p>';
        $html .= plugin_tracker_permission_fetch_selection_field(self::PERMISSION_TYPE, 0, $this->getTracker()->getGroupId(), 'artifact['.$this->getId().'][u_groups][]');
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
     * @param PFUser                          $user             The user who will receive the email
     * @param Tracker_Artifact_ChangesetValue $value            The actual value of the field
     * @param string                          $format           mail format
     *
     * @return string
     */
    public function fetchMailArtifactValue(Tracker_Artifact $artifact, PFUser $user, Tracker_Artifact_ChangesetValue $value = null, $format='text') {
        $is_read_only = true;
        $output = '';
        $separator = '&nbsp;';
        if ($format == 'text') {
            $separator = PHP_EOL;
            $output .= $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'permissions_label');
        }

        $ugroups  = permission_fetch_selected_ugroups(self::PERMISSION_TYPE, $artifact->getId(), $this->getTracker()->getGroupId());
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

    public function fetchArtifactValueWithEditionFormIfEditable(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null, $submitted_values = array()) {
        return $this->fetchArtifactValueReadOnly($artifact, $value) . $this->getHiddenArtifactValueForEdition($artifact, $value, $submitted_values);
    }

    private function isValidSubmittedValues($submitted_values) {
        return (
            isset($submitted_values[0])
            && is_array($submitted_values[0])
            && isset($submitted_values[0][$this->getId()])
        );
    }

    private function isCheckedArtifactValue($artifact_value) {
        return isset($artifact_value[self::USE_IT]) && $artifact_value[self::USE_IT];
    }

    private function getArtifactValueHTML($selected_values, $artifact_id, $is_checked, $is_read_only) {
        $field_id          = $this->getId();
        $checked           = $is_checked   ? ' checked="checked"'   : '';
        $readonly          = $is_read_only ? ' disabled="disabled"' : '';
        $permissions_label = $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'permissions_label');
        $element_name      = 'artifact['.$field_id.'][u_groups][]';

        $html  = '';
        $html .= '<p class="tracker_field_permissionsonartifact">';
        $html .= '<input type="hidden" name="artifact[' . $field_id . '][use_artifact_permissions]" value="0" />';
        $html .= '<label class="checkbox" for="artifact_' . $field_id . '_use_artifact_permissions">';
        $html .= '<input type="checkbox"
                                 name="artifact[' . $field_id . '][use_artifact_permissions]"
                                 id="artifact_' . $field_id . '_use_artifact_permissions"
                                 value="1"' . $checked . $readonly . '/>';
        $html .= $permissions_label . '</label>';
        $html .= '</p>';

        $hp    = Codendi_HTMLPurifier::instance();
	$html .= '<select '
            . 'name="'.$hp->purify($element_name).'" '
            . 'id="'.$hp->purify(str_replace('[]', '', $element_name)).'" '
            . 'multiple '
            . 'size="8" '
            . (($is_read_only) ? 'disabled="disabled"' : '' )
        .'>';

        $html .= $this->getOptions($this->getAllUserGroups(), $this->getLastChangesetValues($artifact_id));
        $html .= '</select>';

        return $html;
    }

    private function getLastChangesetValues($artifact_id) {
        $user_group_ids = array();

        $db_res = permission_db_authorized_ugroups(self::PERMISSION_TYPE, $artifact_id);
        while ($row = db_fetch_array($db_res)) {
            $user_group_ids[] = $row['ugroup_id'];
        }

        return $user_group_ids;
    }

    /**
     * Fetch the html code to display the field value in artifact
     *
     * @see fetchArtifactValueReadOnly
     * @see fetchArtifactValue
     *
     * @param bool                            $is_read_only     Is the field read only
     * @param Tracker_Artifact                $artifact         The artifact
     * @param Tracker_Artifact_ChangesetValue $value            The actual value of the field
     * @param array                           $submitted_values The value already submitted by the user
     *
     * @return string html
     */
    protected function fetchArtifactValueCommon($is_read_only, Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null, $submitted_values = array()) {
        $selected_values = array();
        if ($this->isValidSubmittedValues($submitted_values)) {
            $value = $submitted_values[0][$this->getId()];
            if (isset($value['u_groups'])) {
                $selected_values = $value['u_groups'];
            }
            $is_checked = $this->isCheckedArtifactValue($value) || $artifact->useArtifactPermissions();
        } else {
            $is_checked = $artifact->useArtifactPermissions();
        }
        return $this->getArtifactValueHTML($selected_values, $artifact->getId(), $is_checked, $is_read_only);
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
        $html .= '<input type="hidden" name="artifact['.$this->getId().'][use_artifact_permissions]" value="0" />';
        $html .= '<label class="checkbox" for="artifact_'.$this->getId().'_use_artifact_permissions">';
        $html .= '<input type="checkbox" name="artifact['.$this->getId().'][use_artifact_permissions]" id="artifact_'.$this->getId().'_use_artifact_permissions" value="1"/>';
        $html .= $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'permissions_label') .'</label>';
        $html .= '</p>';
        $html .= plugin_tracker_permission_fetch_selection_field(self::PERMISSION_TYPE, 0, 0, 'artifact['.$this->getId().'][u_groups][]');
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
        if (! isset($this->criteria_value)) {
            $this->criteria_value = array();
        }

        if (isset($this->criteria_value[$criteria->report->id]) && $this->criteria_value[$criteria->report->id]) {
            $values = $this->criteria_value[$criteria->report->id];
            $this->criteria_value[$criteria->report->id] = array();

            foreach($values as $value) {
                foreach ($value as $v) {
                    if ($v !='') {
                        $this->criteria_value[$criteria->report->id][$v] = $value;
                    } else {
                        return '';
                    }
                }
            }

        } else if (! isset($this->criteria_value[$criteria->report->id])) {
            $this->criteria_value[$criteria->report->id] = array();
            foreach($this->getCriteriaDao()
                         ->searchByCriteriaId($criteria->id) as $row) {
                $this->criteria_value[$criteria->report->id][$row['value']] = $row;
            }
        }

        return $this->criteria_value[$criteria->report->id];
    }

    public function getCriteriaWhere($criteria) {
        return '';
    }

    public function fetchCriteriaValue($criteria) {

        $purifier       = Codendi_HTMLPurifier::instance();
        $html           = '';
        $criteria_value = $this->getCriteriaValue($criteria);
        $multiple       = ' ';
        $size           = ' ';
        $name           = "criteria[$this->id][values][]";

        $user_groups = $this->getAllUserGroups();

        if (! $user_groups) {
            $html .= "<p><b>".$GLOBALS['Language']->getText('global','error')."</b>: ".$GLOBALS['Language']->getText('project_admin_permissions','perm_type_not_def',$permission_type);
            return $html;
        }

       if ($criteria->is_advanced) {
            $multiple = ' multiple="multiple" ';
            $size     = ' size="'. min(7, count($user_groups) + 2) .'" ';
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

        if (! is_array($criteria_value)) {
            $criteria_value = array();
        }

        $html .= $this->getOptions($user_groups, array_keys($criteria_value));
        $html .= '</select>';
        return $html;
    }

    private function getOptions($user_groups, $selected_ids = array()) {
        $options = '';
        foreach($user_groups as $user_group) {
            $id = $user_group->getId();
            $selected = (in_array($id, $selected_ids)) ? 'selected="selected"' : '';
            $options .= '<option value="'. $id .'" '.$selected.'>';
            $options .= NameTranslator::getUserGroupDisplayName($user_group->getName());
            $options .= '</option>';
        }

        return $options;
    }

    /**
     * @return ProjectUGroup []
     */
    private function getAllUserGroups() {
        $user_groups     = array();
        $permission_type = self::PERMISSION_TYPE;

        $sql = "SELECT ugroup_id FROM permissions_values WHERE permission_type='$permission_type'";
        $res = db_query($sql);

        $predefined_ugroups = '';
        if (db_numrows($res) < 1) {
            return $user_groups;
        } else {
            while ($row = db_fetch_array($res)) {
                if ($predefined_ugroups) {
                    $predefined_ugroups.= ' ,';
                }
                $predefined_ugroups .= $row['ugroup_id'] ;
            }
        }

        $sql = "SELECT * FROM ugroup WHERE group_id=".$this->getTracker()->getGroupId()." OR ugroup_id IN (".$predefined_ugroups.") ORDER BY ugroup_id";
        $res = db_query($sql);

        while($row = db_fetch_array($res)) {
            $user_groups[] = new ProjectUGroup($row);
        }

        return $user_groups;
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
        if (isset($value[self::USE_IT]) && $value[self::USE_IT] === 1) {
            if (in_array(ProjectUGroup::NONE, $value['u_groups'])) {
                return false;
            }
        }
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
     * @return boolean
     */
    protected function saveValue($artifact, $changeset_value_id, $value, Tracker_Artifact_ChangesetValue $previous_changesetvalue = null) {
        if (empty($value) || ! isset($value[self::USE_IT]) || $value[self::USE_IT] == 0) {
            $value[self::USE_IT] = 0;
            $value['u_groups']   = array();
        }
        $artifact->setUseArtifactPermissions($value[self::USE_IT]);
        permission_clear_all($this->getTracker()->getGroupId(), self::PERMISSION_TYPE, $artifact->getId(), false);

        if (!empty($value['u_groups'])) {
                $ok = $this->addPermissions($value['u_groups'], $artifact->getId());
        }
        //save in changeset
        return $this->getValueDao()->create($changeset_value_id, $value[self::USE_IT], $value['u_groups']);
    }

    /**
     * @see Tracker_FormElement_Field::hasChanges()
     */
    public function hasChanges(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $old_value, $new_value) {
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

        $changeset_value = new Tracker_Artifact_ChangesetValue_PermissionsOnArtifact($value_id, $changeset, $this, $has_changed, $changeset->getArtifact()->useArtifactPermissions(), $ugroups);
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

     public function getFieldDataFromRESTValue(array $value, Tracker_Artifact $artifact = null) {
        if (isset($value['value'][self::GRANTED_GROUPS])) {
            $user_groups = $this->getUserGroupsFromREST($value['value'][self::GRANTED_GROUPS]);
            return $this->getFieldDataFromArray($user_groups);
        }
        throw new Tracker_FormElement_InvalidFieldException('Permission field values must be passed as an array of ugroup ids e.g. "value" : {"granted_groups" : [158, "142_3"]}');
    }

    /**
     * @return int[]
     * @throws Tracker_FormElement_InvalidFieldException
     */
    private function getUserGroupsFromREST($user_groups) {
        if (! is_array($user_groups)) {
            throw new Tracker_FormElement_InvalidFieldException("'granted_groups' must be an array. E.g. [2, '124_3']");
        }

        $project_groups       = array();
        $representation_class = '\\Tuleap\\Project\\REST\\UserGroupRepresentation';
        foreach ($user_groups as $user_group) {
            try {
                call_user_func_array($representation_class.'::checkRESTIdIsAppropriate', array($user_group));
                $value            = call_user_func_array($representation_class.'::getProjectAndUserGroupFromRESTId', array($user_group));

                if ($value['project_id'] && $value['project_id'] != $this->getTracker()->getProject()->getID()) {
                    throw new Tracker_FormElement_InvalidFieldException('Invalid value "'.$user_group.'" for field '.$this->getId());
                }

                $project_groups[] = $value['user_group_id'];
            } catch (Exception $e) {
                if (is_numeric($user_group) && $user_group < ProjectUGroup::DYNAMIC_UPPER_BOUNDARY) {
                    $project_groups[] = $user_group;
                } else {
                    throw new Tracker_FormElement_InvalidFieldException($e->getMessage());
                }
            }
        }

        return $project_groups;
    }

    public function getFieldDataFromRESTValueByField($value, Tracker_Artifact $artifact = null) {
        throw new Tracker_FormElement_RESTValueByField_NotImplementedException();
    }

     /**
     * Get the field data for artifact submission
     *
     * @param string the soap field value
     *
     * @return mixed the field data corresponding to the soap_value for artifact submision
     */
    public function getFieldData($soap_value) {
        return $this->getFieldDataFromArray(explode(',', $soap_value));
    }

    private function getFieldDataFromArray(array $values) {
        $ugroup_ids = array_filter(array_map('intval', $values));
        if (count($ugroup_ids) == 0 || in_array(ProjectUGroup::ANONYMOUS, $ugroup_ids)) {
            return array (
                self::USE_IT => 0,
                'u_groups'   => array()
            );
        } else {
            return array(
                self::USE_IT => 1,
                'u_groups'   => $ugroup_ids
            );
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
        $permission_type = self::PERMISSION_TYPE;
        foreach ($ugroups as $ugroup) {
            if(!$pm->addPermission($permission_type, $artifact_id, $ugroup)) {
                return false;
            }
        }
        return true;
    }

    public function accept(Tracker_FormElement_FieldVisitor $visitor) {
        return $visitor->visitPermissionsOnArtifact($this);
    }
    /**
     * Return REST value of a field for a given changeset
     *
     * @param PFUser                     $user
     * @param Tracker_Artifact_Changeset $changeset
     *
     * @return mixed | null if no values
     */
    public function getRESTValue(PFUser $user, Tracker_Artifact_Changeset $changeset) {
        $value = $changeset->getValue($this);
        if ($value) {
            return $value->getRESTValue($user);
        }
    }

    /**
     * @return Tuleap/Project/REST/UserGroupRepresentation[]
     */
    public function getRESTAvailableValues() {
        $representation_class = '\\Tuleap\\Tracker\\REST\\v1\\TrackerFieldsRepresentations\\PermissionsOnArtifacts';
        $representation       = new $representation_class;
        $project_id           = $this->getTracker()->getGroupId();
        $representation->build($project_id, self::IS_USED_BY_DEFAULT, $this->getAllUserGroups());

        return $representation;
    }
}
