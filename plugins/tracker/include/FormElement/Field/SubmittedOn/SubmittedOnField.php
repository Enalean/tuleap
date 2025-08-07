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

namespace Tuleap\Tracker\FormElement\Field\SubmittedOn;

use Override;
use PFUser;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue;
use Tracker_Artifact_ChangesetValue_Date;
use Tracker_ArtifactDao;
use Tracker_ArtifactFactory;
use Tracker_FormElement_DateFormatter;
use Tracker_FormElement_Field_Date;
use Tracker_FormElement_Field_ReadOnly;
use Tracker_FormElement_FieldVisitor;
use Tracker_FormElementFactory;
use Tracker_Report_Criteria;
use Tuleap;
use Tuleap\Option\Option;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\Report\Query\ParametrizedFromWhere;

class SubmittedOnField extends Tracker_FormElement_Field_Date implements Tracker_FormElement_Field_ReadOnly
{
    public array $default_properties = [];

    #[Override]
    public function getCriteriaFromWhere(Tracker_Report_Criteria $criteria): Option
    {
        $criteria_value = $this->getCriteriaValue($criteria);
        if (count($criteria_value) === 0) {
            return Option::nothing(ParametrizedFromWhere::class);
        }

        $where = $this->getSQLCompareDate(
            (bool) $criteria->is_advanced,
            $criteria_value['op'],
            $criteria_value['from_date'],
            $criteria_value['to_date'],
            'artifact.submitted_on'
        );

        return Option::fromValue(
            new ParametrizedFromWhere(
                '',
                $where->sql,
                [],
                $where->parameters,
            )
        );
    }

    #[Override]
    public function getQuerySelect(): string
    {
        // SubmittedOn is stored in the artifact
        return 'a.submitted_on AS ' . $this->getQuerySelectName();
    }

    #[Override]
    public function getQueryFrom()
    {
        // SubmittedOn is stored in the artifact
        return '';
    }

    /**
     * Get the "group by" statement to retrieve field values
     */
    #[Override]
    public function getQueryGroupby(): string
    {
        // SubmittedOn is stored in the artifact
        return 'a.submitted_on';
    }

    #[Override]
    public function fetchRawValueFromChangeset(Tracker_Artifact_Changeset $changeset): string
    {
        return $this->formatDate($changeset->getArtifact()->getSubmittedOn());
    }

    #[Override]
    protected function getValueDao()
    {
        return null;
    }

    #[Override]
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

    #[Override]
    public static function getFactoryLabel()
    {
        return dgettext('tuleap-tracker', 'Submitted On');
    }

    #[Override]
    public static function getFactoryDescription()
    {
        return dgettext('tuleap-tracker', 'Display the date the artifact was submitted on');
    }

    #[Override]
    public static function getFactoryIconUseIt()
    {
        return $GLOBALS['HTML']->getImagePath('calendar/cal.png');
    }

    #[Override]
    public static function getFactoryIconCreate()
    {
        return $GLOBALS['HTML']->getImagePath('calendar/cal--plus.png');
    }

    #[Override]
    protected function saveValue(
        $artifact,
        $changeset_value_id,
        $value,
        ?Tracker_Artifact_ChangesetValue $previous_changesetvalue,
        CreatedFileURLMapping $url_mapping,
    ) {
        // user can not change the value of this field
        return false;
    }

    /**
     * Keep the value
     *
     * @param Artifact $artifact The artifact
     * @param int $changeset_value_id The id of the changeset_value
     * @param Tracker_Artifact_ChangesetValue $previous_changesetvalue The data previously stored in the db
     *
     * @return int or array of int
     */
    #[Override]
    protected function keepValue($artifact, $changeset_value_id, Tracker_Artifact_ChangesetValue $previous_changesetvalue)
    {
        //The field is ReadOnly
        return null;
    }

    /**
     * Get the value of this field
     *
     * @param Tracker_Artifact_Changeset $changeset The changeset (needed in only few cases like 'lud' field)
     * @param int $value_id The id of the value
     * @param bool $has_changed If the changeset value has changed from the rpevious one
     *
     * @return Tracker_Artifact_ChangesetValue or null if not found
     */
    #[Override]
    public function getChangesetValue($changeset, $value_id, $has_changed)
    {
        $changeset_value = new Tracker_Artifact_ChangesetValue_Date($value_id, $changeset, $this, $has_changed, $changeset->getArtifact()->getSubmittedOn());
        return $changeset_value;
    }

    /**
     * @see Tracker_FormElement_Field::hasChanges()
     */
    #[Override]
    public function hasChanges(Artifact $artifact, Tracker_Artifact_ChangesetValue $old_value, $new_value)
    {
        // Submitted On is never updated
        return false;
    }

    #[Override]
    public function fetchSubmit(array $submitted_values)
    {
        // We do not display the field in the artifact submit form
        return '';
    }

    #[Override]
    public function fetchSubmitMasschange()
    {
        return '';
    }

    /**
     * Fetch the html code to display the field value in artifact
     *
     * @param Artifact $artifact The artifact
     * @param ?Tracker_Artifact_ChangesetValue $value The actual value of the field
     * @param array $submitted_values The value already submitted by the user
     */
    #[Override]
    protected function fetchArtifactValue(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values,
    ): string {
        return $this->fetchArtifactValueWithEditionFormIfEditable($artifact, $value, $submitted_values);
    }

    /**
     * Fetch the html code to display the field value in artifact in read only mode
     *
     * @param Artifact $artifact The artifact
     * @param ?Tracker_Artifact_ChangesetValue $value The actual value of the field
     *
     * @return string
     */
    #[Override]
    public function fetchArtifactValueReadOnly(Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null)
    {
        $html = '';
        if (! $value) {
            $value = new Tracker_Artifact_ChangesetValue_Date(null, $artifact->getFirstChangeset(), $this, false, $artifact->getSubmittedOn());
        }
        assert($value instanceof Tracker_Artifact_ChangesetValue_Date);
        $timestamp = $value->getTimestamp();
        $value     = $timestamp ? $this->formatDateForDisplay($timestamp) : '';
        $html     .= $value;

        return $html;
    }

    #[Override]
    public function fetchArtifactValueWithEditionFormIfEditable(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values,
    ) {
        return $this->fetchArtifactValueReadOnly($artifact, $value);
    }

    #[Override]
    protected function fetchTooltipValue(Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null): string
    {
        if (! $value) {
            $value = new Tracker_Artifact_ChangesetValue_Date(null, $artifact->getFirstChangeset(), $this, false, $artifact->getSubmittedOn());
        }
        assert($value instanceof Tracker_Artifact_ChangesetValue_Date);

        return parent::fetchTooltipValue($artifact, $value);
    }

    /**
     * Validate a field
     *
     * @param mixed $submitted_value The submitted value
     */
    #[Override]
    public function validateFieldWithPermissionsAndRequiredStatus(
        Artifact $artifact,
        $submitted_value,
        PFUser $user,
        ?Tracker_Artifact_ChangesetValue $last_changeset_value = null,
        ?bool $is_submission = null,
    ): bool {
        $is_valid = true;
        if ($last_changeset_value === null && $submitted_value === null && $this->isRequired()) {
            $is_valid = false;
            $this->setHasErrors(true);
            $GLOBALS['Response']->addFeedback('error', sprintf(dgettext('tuleap-tracker', 'The field %1$s is required.'), $this->getLabel() . ' (' . $this->getName() . ')'));
        } elseif ($submitted_value !== null && ! $this->userCanUpdate()) {
            $is_valid = true;
            $GLOBALS['Response']->addFeedback('warning', sprintf(dgettext('tuleap-tracker', 'The field "%1$s" will not be taken into account.'), $this->getName()));
        }
        return $is_valid;
    }

    /**
     * Fetch data to display the field value in mail
     */
    #[Override]
    public function fetchMailArtifactValue(
        Artifact $artifact,
        PFUser $user,
        bool $ignore_perms,
        ?Tracker_Artifact_ChangesetValue $value = null,
        string $format = 'text',
    ): string {
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
                $timestamp = $value->getTimestamp();
                $output    = $timestamp !== null ? $this->formatDate($timestamp) : '';
                break;
        }
        return $output;
    }

    /**
     * Say if the value is valid. If not valid set the internal has_error to true.
     *
     * @param Artifact $artifact The artifact
     * @param mixed $value data coming from the request. May be string or array.
     *
     * @return bool true if the value is considered ok
     */
    #[Override]
    public function isValid(Artifact $artifact, $value)
    {
        // this field is always valid as it is not filled by users.
        return true;
    }

    /**
     * Display the html field in the admin ui
     *
     * @return string html
     */
    #[Override]
    protected function fetchAdminFormElement()
    {
        $html  = '';
        $html .= '<div>' . $this->formatDateTime(time()) . '</div>';
        $html .= '<span class="tracker-admin-form-element-help">';
        $html .= dgettext('tuleap-tracker', 'The field is automatically set to artifact submission date');
        $html .= '</span>';
        return $html;
    }

    #[Override]
    public function afterCreate(array $form_element_data, $tracker_is_empty)
    {
    }

    /**
     * Retreive The last date Field value
     *
     * @param Artifact $artifact The artifact
     *
     * @return string|false
     */
    #[Override]
    public function getLastValue(Artifact $artifact)
    {
        return date(Tracker_FormElement_DateFormatter::DATE_FORMAT, $artifact->getSubmittedOn());
    }

    /**
     * Get artifacts that responds to some criteria
     *
     * @param date $date The date criteria
     * @param int $trackerId The Tracker Id
     *
     * @return Array
     */
    #[Override]
    public function getArtifactsByCriterias($date, $trackerId = null)
    {
        $artifacts = [];
        $dao       = new Tracker_ArtifactDao();
        $dar       = $dao->getArtifactsBySubmittedOnDate($trackerId, $date);

        if ($dar && ! $dar->isError()) {
            $artifactFactory = Tracker_ArtifactFactory::instance();
            foreach ($dar as $row) {
                $artifacts[] = $artifactFactory->getArtifactById($row['artifact_id']);
            }
        }
        return $artifacts;
    }

    #[Override]
    public function accept(Tracker_FormElement_FieldVisitor $visitor)
    {
        return $visitor->visitSubmittedOn($this);
    }

    #[Override]
    public function isTimeDisplayed()
    {
        return true;
    }

    #[Override]
    public function getFieldDataFromRESTValue(array $value, ?Artifact $artifact = null)
    {
        return null;
    }
}
