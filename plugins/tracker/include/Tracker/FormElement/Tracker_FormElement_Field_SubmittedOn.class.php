<?php
/**
 * Copyright (c) Enalean, 2011-Present. All Rights Reserved.
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

use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;

class Tracker_FormElement_Field_SubmittedOn extends Tracker_FormElement_Field_Date implements Tracker_FormElement_Field_ReadOnly
{

    public $default_properties = array();

    protected function getDao()
    {
        return new Tracker_FormElement_Field_DateDao();
    }

    /**
     * The field is permanently deleted from the db
     * This hooks is here to delete specific properties,
     * or specific values of the field.
     * (The field itself will be deleted later)
     * @return bool true if success
     */
    public function delete()
    {
        return true;
    }

    public function getCriteriaFrom($criteria)
    {
        // SubmittedOn is stored in the artifact
        return '';
    }

    public function getCriteriaWhere($criteria)
    {
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
        return '';
    }

    public function getQuerySelect()
    {
        // SubmittedOn is stored in the artifact
        return "a.submitted_on AS `" . $this->name . "`";
    }

    public function getQueryFrom()
    {
        // SubmittedOn is stored in the artifact
        return '';
    }

    /**
     * Get the "group by" statement to retrieve field values
     */
    public function getQueryGroupby()
    {
        // SubmittedOn is stored in the artifact
        return 'a.submitted_on';
    }

    /**
     * Fetch the value in a specific changeset
     * @param Tracker_Artifact_Changeset $changeset
     * @return string
     */
    public function fetchRawValueFromChangeset($changeset)
    {
        return $this->formatDate($changeset->getArtifact()->getSubmittedOn());
    }

    protected function getValueDao()
    {
        return null;
    }

    public function getFullRESTValue(PFUser $user, Tracker_Artifact_Changeset $changeset)
    {
        $artifact_field_value_full_representation = new Tuleap\Tracker\REST\Artifact\ArtifactFieldValueFullRepresentation();
        $artifact_field_value_full_representation->build(
            $this->getId(),
            Tracker_FormElementFactory::instance()->getType($this),
            $this->getLabel(),
            date('c', $changeset->getArtifact()->getSubmittedOn())
        );
        return $artifact_field_value_full_representation;
    }

    public static function getFactoryLabel()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'submittedon_label');
    }

    public static function getFactoryDescription()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'submittedon_description');
    }

    public static function getFactoryIconUseIt()
    {
        return $GLOBALS['HTML']->getImagePath('calendar/cal.png');
    }

    public static function getFactoryIconCreate()
    {
        return $GLOBALS['HTML']->getImagePath('calendar/cal--plus.png');
    }

    protected function saveValue(
        $artifact,
        $changeset_value_id,
        $value,
        ?Tracker_Artifact_ChangesetValue $previous_changesetvalue,
        CreatedFileURLMapping $url_mapping
    ) {
        // user can not change the value of this field
        return false;
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
    protected function keepValue($artifact, $changeset_value_id, Tracker_Artifact_ChangesetValue $previous_changesetvalue)
    {
        //The field is ReadOnly
        return null;
    }

    /**
     * Get the value of this field
     *
     * @param Tracker_Artifact_Changeset $changeset   The changeset (needed in only few cases like 'lud' field)
     * @param int                        $value_id    The id of the value
     * @param bool $has_changed If the changeset value has changed from the rpevious one
     *
     * @return Tracker_Artifact_ChangesetValue or null if not found
     */
    public function getChangesetValue($changeset, $value_id, $has_changed)
    {
        $changeset_value = new Tracker_Artifact_ChangesetValue_Date($value_id, $changeset, $this, $has_changed, $changeset->getArtifact()->getSubmittedOn());
        return $changeset_value;
    }

    /**
     * @see Tracker_FormElement_Field::hasChanges()
     */
    public function hasChanges(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $old_value, $new_value)
    {
        // Submitted On is never updated
        return false;
    }


    public function fetchSubmit(array $submitted_values)
    {
        // We do not display the field in the artifact submit form
        return '';
    }

    public function fetchSubmitMasschange()
    {
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
    protected function fetchArtifactValue(
        Tracker_Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values
    ) {
        return $this->fetchArtifactValueWithEditionFormIfEditable($artifact, $value, $submitted_values);
    }

    /**
     * Fetch the html code to display the field value in artifact in read only mode
     *
     * @param Tracker_Artifact                $artifact The artifact
     * @param Tracker_Artifact_ChangesetValue $value    The actual value of the field
     *
     * @return string
     */
    public function fetchArtifactValueReadOnly(Tracker_Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null)
    {
        $html = '';
        if (!$value) {
            $value = new Tracker_Artifact_ChangesetValue_Date(null, $artifact->getFirstChangeset(), $this, false, $artifact->getSubmittedOn());
        }
        assert($value instanceof Tracker_Artifact_ChangesetValue_Date);
        $timestamp = $value->getTimestamp();
        $value     = $timestamp ? $this->formatDateForDisplay($timestamp) : '';
        $html .= $value;

        return $html;
    }

    public function fetchArtifactValueWithEditionFormIfEditable(
        Tracker_Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values
    ) {
        return $this->fetchArtifactValueReadOnly($artifact, $value);
    }

    /**
     * Fetch the html code to display the field value in tooltip
     *
     * @param Tracker_Artifact_ChangesetValue $value The changeset value for this field
     * @return string
     */
    protected function fetchTooltipValue(Tracker_Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null)
    {
        $html = '';
        if (!$value) {
            $value = new Tracker_Artifact_ChangesetValue_Date(null, $artifact->getFirstChangeset(), $this, false, $artifact->getSubmittedOn());
        }
        assert($value instanceof Tracker_Artifact_ChangesetValue_Date);
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
     * @return bool true on success or false on failure
     */
    public function validateFieldWithPermissionsAndRequiredStatus(Tracker_Artifact $artifact, $submitted_value, ?Tracker_Artifact_ChangesetValue $last_changeset_value = null, $is_submission = null)
    {
        $is_valid = true;
        if ($last_changeset_value === null && $submitted_value === null && $this->isRequired()) {
            $is_valid = false;
            $this->setHasErrors(true);
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_common_artifact', 'err_required', $this->getLabel() . ' (' . $this->getName() . ')'));
        } elseif ($submitted_value !== null && ! $this->userCanUpdate()) {
            $is_valid = true;
            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'field_not_taken_account', array($this->getName())));
        }
        return $is_valid;
    }

    /**
     * Fetch data to display the field value in mail
     *
     * @param Tracker_Artifact                $artifact         The artifact
     * @param PFUser                          $user             The user who will receive the email
     * @param bool $ignore_perms
     * @param Tracker_Artifact_ChangesetValue $value            The actual value of the field
     * @param string                          $format           output format
     *
     * @return string
     */
    public function fetchMailArtifactValue(
        Tracker_Artifact $artifact,
        PFUser $user,
        $ignore_perms,
        ?Tracker_Artifact_ChangesetValue $value = null,
        $format = 'text'
    ) {
        if (empty($value)) {
            $value = new Tracker_Artifact_ChangesetValue_Date(null, $artifact->getFirstChangeset(), $this, false, $artifact->getSubmittedOn());
        }
        assert($value instanceof Tracker_Artifact_ChangesetValue_Date);
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
    public function isValid(Tracker_Artifact $artifact, $value)
    {
        // this field is always valid as it is not filled by users.
        return true;
    }

    /**
     * Display the html field in the admin ui
     *
     * @return string html
     */
    protected function fetchAdminFormElement()
    {
        $html = '';
        $html .= '<div>' . $this->formatDateTime(time()) . '</div>';
        $html .= '<span class="tracker-admin-form-element-help">';
        $html .= $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'submittedon_help');
        $html .= '</span>';
        return $html;
    }

    public function afterCreate(array $form_element_data, $tracker_is_empty)
    {
    }

    /**
     * Retreive The last date Field value
     *
     * @param Tracker_Artifact $artifact The artifact
     *
     * @return date
     */
    public function getLastValue(Tracker_Artifact $artifact)
    {
        return date(Tracker_FormElement_DateFormatter::DATE_FORMAT, $artifact->getSubmittedOn());
    }

    /**
     * Get artifacts that responds to some criteria
     *
     * @param date    $date      The date criteria
     * @param int $trackerId The Tracker Id
     *
     * @return Array
     */
    public function getArtifactsByCriterias($date, $trackerId = null)
    {
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

    public function accept(Tracker_FormElement_FieldVisitor $visitor)
    {
        return $visitor->visitSubmittedOn($this);
    }

    public function isTimeDisplayed()
    {
        return true;
    }

    public function getFieldDataFromRESTValue(array $value, ?Tracker_Artifact $artifact = null)
    {
         return null;
    }
}
