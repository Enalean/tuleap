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


class Tracker_FormElement_Field_SubmittedBy extends Tracker_FormElement_Field_List implements Tracker_FormElement_Field_ReadOnly {

    public $default_properties = array();

    /**
     * The field is permanently deleted from the db
     * This hooks is here to delete specific properties,
     * or specific values of the field.
     * (The field itself will be deleted later)
     * @return boolean true if success
     */
    public function delete() {
        return true;
    }

    public function getCriteriaFrom($criteria) {
        // SubmittedOn is stored in the artifact
        return '';
    }

    public function getCriteriaWhere($criteria) {
        //Only filter query if criteria is valuated
        if ($criteria_value= $this->getCriteriaValue($criteria)) {
            $a = 'A_'. $this->id;
            $b = 'B_'. $this->id;
            $ids_to_search = array_intersect(
                               array_values($criteria_value),
                               array_merge(array(100),array_keys($this->getBind()->getAllValues())));
            if (count($ids_to_search) > 1) {
                return " artifact.submitted_by IN(". implode(',', $ids_to_search) .") ";
            } else if (count($ids_to_search)) {
                return " artifact.submitted_by = ". implode('', $ids_to_search) ." ";
            }
        }
        return '';
    }

    public function getQuerySelect() {
        // SubmittedOn is stored in the artifact
        return "a.submitted_by AS `". $this->name ."`";
    }

    public function getQueryFrom() {
        // SubmittedOn is stored in the artifact
        return '';
    }
    public function getQueryFromAggregate() {
        $R1 = 'R1_'. $this->id;
        $R2 = 'R2_'. $this->id;
        return " LEFT JOIN  user AS $R2 ON ($R2.user_id = a.submitted_by ) ";
    }

    /**
     * Get the "group by" statement to retrieve field values
     */
    public function getQueryGroupby() {
        // SubmittedOn is stored in the artifact
        return 'a.submitted_by';
    }

    /**
     * Get the "order by" statement to retrieve field values
     */
    public function getQueryOrderby() {
        return $this->name;
    }

    /**
     * @return the label of the field (mainly used in admin part)
     */
    public static function getFactoryLabel() {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'submittedby_label');
    }

    /**
     * @return the description of the field (mainly used in admin part)
     */
    public static function getFactoryDescription() {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'submittedby_description');
    }

    /**
     * @return the path to the icon
     */
    public static function getFactoryIconUseIt() {
        return $GLOBALS['HTML']->getImagePath('ic/user-female.png');
    }

    /**
     * @return the path to the icon
     */
    public static function getFactoryIconCreate() {
        return $GLOBALS['HTML']->getImagePath('ic/user-female--plus.png');
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
        // user can not change the value of this field
        return null;
    }
    
    /**
     * Keep the value 
     * 
     * @param Tracker_Artifact                $artifact                The artifact
     * @param int                             $changeset_value_id      The id of the changeset_value 
     * @param Tracker_Artifact_ChangesetValue $previous_changesetvalue The data previously stored in the db
     *
     * @return int or array of int
     */
    protected function keepValue($artifact, $changeset_value_id, Tracker_Artifact_ChangesetValue $previous_changesetvalue) {
        //The field is ReadOnly
        return null;
    }

    /**
     * Hook called after a creation of a field
     *
     * @param array $data The data used to create the field
     *
     * @return void
     */
    public function afterCreate($formElement_data) {
        //force the bind
        $formElement_data['bind-type'] = 'users';
        $formElement_data['bind'] = array(
            'value_function' => array(
                'artifact_submitters',
            )
        );
        parent::afterCreate($formElement_data);
    }

    public function fetchSubmit($submitted_values = array()) {
        // We do not display the field in the artifact submit form
        return '';
    }

    public function fetchSubmitMasschange($submitted_values = array()) {
        // We do not display the field in the artifact submit form
        return '';
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
        $value = new Tracker_FormElement_Field_List_Bind_UsersValue($artifact->getSubmittedBy());
        $value = $value->getLabel();
        $html .= $value;
        return $html;
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
        $output = '';
        
        $value = new Tracker_FormElement_Field_List_Bind_UsersValue($artifact->getSubmittedBy());
        
        switch($format) {
            case 'html':
                $output .= $this->fetchArtifactValueReadOnly($artifact);
                break;
            default:
                $output = $this->getBind()->formatMailArtifactValue($value->getId());
                break;
        }
        return $output;
    }

    /**
     * Say if the value is valid. If not valid set the internal has_error to true.
     *
     * @param Tracker_Artifact $artifact The artifact
     * @param mixed            $value    data coming from the request. May be string or array.
     *
     * @return bool true if the value is considered ok
     */
    public function isValid(Tracker_Artifact $artifact, $value) {
        // this field is always valid as it is not filled by users.
        return true;
    }
    
     /**
     * Validate a field
     *
     * @param Tracker_Artifact                $artifact             The artifact to check
     * @param mixed                           $submitted_value      The submitted value
     * @param Tracker_Artifact_ChangesetValue $last_changeset_value The last changeset value of the field (give null if no old value)
     *
     * @return boolean true on success or false on failure
     */
    public function validateField(Tracker_Artifact $artifact, $submitted_value, Tracker_Artifact_ChangesetValue $last_changeset_value = null) {
        $is_valid = true;
        if ($last_changeset_value === null && $submitted_value === null && $this->isRequired()) {
            $is_valid = false;
            $this->setHasErrors(true);
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_common_artifact', 'err_required', $this->getLabel(). ' ('. $this->getName() .')'));
        } else if ($submitted_value !== null &&  ! $this->userCanUpdate()) {
            $is_valid = true;
            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'field_not_taken_account', array($this->getName())));
        } 
        return $is_valid;
    }
    
    /**
     * Display the html field in the admin ui
     *
     * @return string html
     */
    protected function fetchAdminFormElement() {
        $html = '';
        $fake_value = new Tracker_FormElement_Field_List_Bind_UsersValue(UserManager::instance()->getCurrentUser()->getId());
        $html .= $fake_value->getLabel() . '<br />';
        $html .= '<span class="tracker-admin-form-element-help">';
        $html .= $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'submittedby_help');
        $html .= '</span>';
        return $html;
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
        return $this->getBind()->formatChangesetValue(new Tracker_FormElement_Field_List_Bind_UsersValue($value));
    }

    /**
     * Display the field for CSV
     * Used in CSV data export
     *
     * @param int $artifact_id the corresponding artifact id
     * @param int $changeset_id the corresponding changeset
     * @param mixed $value the value of the field
     *
     * @return string
     */
    public function fetchCSVChangesetValue($artifact_id, $changeset_id, $value) {
        return $this->getBind()->formatChangesetValueForCSV(new Tracker_FormElement_Field_List_Bind_UsersValue($value));
    }
    
    /**
     * Say if this fields suport notifications
     *
     * @return bool
     */
    public function isNotificationsSupported() {
        return true;
    }
    
    /**
     * Say if we export the bind in the XML
     *
     * @return bool
     */
    public function shouldBeBindXML() {
        return false;
    }
    
    public function getUserManager() {
        return UserManager::instance();
    }
     /**
     * Get the field data for artifact submission
     * Check if the user name exists in the platform
     *
     * @param string the user name
     *
     * @return int the user id
     */
    public function getFieldData($soap_value) {
        $um = $this->getUserManager();
        $u = $um->getUserByUserName($soap_value);
        if ($u) {
            return $u->getId();
        } else {
            return null;
        }
    }

    public function isNone($value) {
        return false;
    }
}
?>