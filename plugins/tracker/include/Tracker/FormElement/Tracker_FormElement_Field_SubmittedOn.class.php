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

require_once('Tracker_FormElement_Field_Date.class.php');
require_once(dirname(__FILE__).'/../Artifact/Tracker_Artifact_ChangesetValue_Date.class.php');
require_once(dirname(__FILE__).'/../Report/dao/Tracker_Report_Criteria_Date_ValueDao.class.php');
require_once('dao/Tracker_FormElement_Field_Value_DateDao.class.php');
require_once('dao/Tracker_FormElement_Field_DateDao.class.php');
require_once(dirname(__FILE__).'/../Artifact/Tracker_Artifact_ChangesetValue_Date.class.php');
require_once('Tracker_FormElement_Field_ReadOnly.class.php');
require_once('common/date/DateHelper.class.php');

class Tracker_FormElement_Field_SubmittedOn extends Tracker_FormElement_Field_Date implements Tracker_FormElement_Field_ReadOnly {
    
    public $default_properties = array();

    protected function getDao() {
        return new Tracker_FormElement_Field_DateDao();
    }
    
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
        if ($criteria_value = $this->getCriteriaValue($criteria)) {
            // SubmittedOn is stored in the artifact
            return $this->getSQLCompareDate(
                $criteria->is_advanced, 
                $criteria_value['op'], 
                $criteria_value['from_date'], 
                $criteria_value['to_date'],
                'artifact.submitted_on'
            );
        }
    }
    
    public function getQuerySelect() {
        // SubmittedOn is stored in the artifact
        return "a.submitted_on AS `" .$this->name ."`";
    }
    
    public function getQueryFrom() {
        // SubmittedOn is stored in the artifact
        return '';
    }
    
    /**
     * Get the "group by" statement to retrieve field values
     */
    public function getQueryGroupby() {
        // SubmittedOn is stored in the artifact
        return 'a.submitted_on';
    }
    
    /**
     * Fetch the value in a specific changeset
     * @param Tracker_Artifact_Changeset $changeset
     * @return string
     */
    public function fetchRawValueFromChangeset($changeset) {
        return $this->formatDate($changeset->getArtifact()->getSubmittedOn());
    }
    
    protected function getValueDao() {
        return null;
    }

    /**
     * @return the label of the field (mainly used in admin part)
     */
    public static function getFactoryLabel() {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'submittedon_label');
    }
    
    /**
     * @return the description of the field (mainly used in admin part)
     */
    public static function getFactoryDescription() {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'submittedon_description');
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
     * Get the value of this field
     *
     * @param Tracker_Artifact_Changeset $changeset   The changeset (needed in only few cases like 'lud' field)
     * @param int                        $value_id    The id of the value
     * @param boolean                    $has_changed If the changeset value has changed from the rpevious one
     *
     * @return Tracker_Artifact_ChangesetValue or null if not found
     */
    public function getChangesetValue($changeset, $value_id, $has_changed) {
        $changeset_value = new Tracker_Artifact_ChangesetValue_Date($value_id, $this, $has_changed, $changeset->getArtifact()->getSubmittedOn());
        return $changeset_value;
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
        // Submitted On is never updated
        return false;
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
        if (!$value) {
            $value = new Tracker_Artifact_ChangesetValue_Date(null, $this, false, $artifact->getSubmittedOn());
        }
        $value = $value->getTimestamp();
        $value = $value ? $this->formatDateTime($value) : '';
        $html .= $value;
        return $html;
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
        if (!$value) {
            $value = new Tracker_Artifact_ChangesetValue_Date(null, $this, false, $artifact->getSubmittedOn());
        }
        $value = $value->getTimestamp();
        $value = $value ? DateHelper::timeAgoInWords($value) : '';
        $html .= $value;
        return $html;
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
     * Fetch data to display the field value in mail
     *
     * @param Tracker_Artifact                $artifact         The artifact
     * @param Tracker_Artifact_ChangesetValue $value            The actual value of the field
     * @param string                          $format           output format
     *
     * @return string
     */
    public function fetchMailArtifactValue(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null, $format='text') {
        if ( empty($value) ) {
            $value = new Tracker_Artifact_ChangesetValue_Date(null, $this, false, $artifact->getSubmittedOn());
        }
        $output = '';
        switch ($format) {
            case 'html':
                $output = $this->fetchArtifactValueReadOnly($artifact, $value);
                break;
            default:
                $value  = $value->getTimestamp();
                $output = $value ? $this->formatDate($value) : '';
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
     * Display the html field in the admin ui
     *
     * @return string html
     */
    protected function fetchAdminFormElement() {
        $html = '';
        $html .= '<div>'. $this->formatDateTime(time()) . '</div>';
        $html .= '<span class="tracker-admin-form-element-help">';
        $html .= $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'submittedon_help');
        $html .= '</span>';
        return $html;
    }

    public function afterCreate() {

    }

    /**
     * Retreive The last date Field value
     *
     * @param Tracker_Artifact $artifact The artifact
     *
     * @return date
     */
    public function getLastValue(Tracker_Artifact $artifact) {
        return date("Y-m-d", $artifact->getSubmittedOn());
    }

    /**
     * Get artifacts that responds to some criteria
     *
     * @param date    $date      The date criteria
     * @param Integer $trackerId The Tracker Id
     *
     * @return Array
     */
    public function getArtifactsByCriterias($date, $trackerId = null) {
        $artifacts = array();
        $dao = new Tracker_ArtifactDao();
        $dar = $dao->getArtifactsBySubmittedOnDate($trackerId, $date);

        if ($dar && !$dar->isError()) {
            $artifactFactory = Tracker_ArtifactFactory::instance();
            foreach ($dar as $row) {
                $artifacts[] = $artifactFactory->getArtifactById($row['artifact_id']);
            }
        }
        return $artifacts;
    }

}

?>