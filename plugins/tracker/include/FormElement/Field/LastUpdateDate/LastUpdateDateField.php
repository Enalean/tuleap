<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Tracker\FormElement\Field\LastUpdateDate;

use Override;
use PFUser;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetDao;
use Tracker_Artifact_ChangesetValue;
use Tracker_Artifact_ChangesetValue_Date;
use Tracker_ArtifactFactory;
use Tracker_FormElement_DateFormatter;
use Tracker_FormElement_Field_ReadOnly;
use Tracker_FormElement_FieldVisitor;
use Tracker_FormElementFactory;
use Tracker_Report_Criteria;
use Tuleap\Option\Option;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\Date\DateField;
use Tuleap\Tracker\FormElement\Field\Files\CreatedFileURLMapping;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\Report\Query\ParametrizedFromWhere;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueFullRepresentation;

final class LastUpdateDateField extends DateField implements Tracker_FormElement_Field_ReadOnly
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
            'c.submitted_on'
        );

        //Last update date is stored in the changeset (the date of the changeset)
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
        //Last update date is stored in the changeset (the date of the changeset)
        return 'c.submitted_on AS ' . $this->getQuerySelectName();
    }

    #[Override]
    public function getQueryFrom()
    {
        //Last update date is stored in the changeset (the date of the changeset)
        return '';
    }

    /**
     * Get the "group by" statement to retrieve field values
     */
    #[Override]
    public function getQueryGroupby(): string
    {
        //Last update date is stored in the changeset (the date of the changeset)
        return 'c.submitted_on';
    }

    #[Override]
    public function fetchRawValueFromChangeset(Tracker_Artifact_Changeset $changeset): string
    {
        return $this->formatDate($changeset->getSubmittedOn());
    }

    #[Override]
    protected function getValueDao()
    {
        return null;
    }

    #[Override]
    public static function getFactoryLabel()
    {
        return dgettext('tuleap-tracker', 'Last Update Date');
    }

    #[Override]
    public static function getFactoryDescription()
    {
        return dgettext('tuleap-tracker', 'Display the last update date of the artifact');
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
        $changeset_value = new Tracker_Artifact_ChangesetValue_Date($value_id, $changeset, $this, $has_changed, $changeset->getSubmittedOn());
        return $changeset_value;
    }

    /**
     * @see TrackerField::hasChanges()
     */
    #[Override]
    public function hasChanges(Artifact $artifact, Tracker_Artifact_ChangesetValue $old_value, $new_value)
    {
        //The last update date is never updated
        return false;
    }

    /**
     * Fetch the html code to display the field value in artifact
     *
     * @param Artifact $artifact The artifact
     * @param Tracker_Artifact_ChangesetValue $value The actual value of the field
     * @param array $submitted_values The value already submitted by the user
     */
    #[Override]
    protected function fetchArtifactValue(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values,
    ): string {
        return $this->fetchArtifactValueWithEditionFormIfEditable($artifact, $value);
    }

    /**
     * Fetch the html code to display the field value in artifact in read only mode
     *
     * @param Artifact $artifact The artifact
     * @param Tracker_Artifact_ChangesetValue $value The actual value of the field
     *
     * @return string
     * @todo Pass the changeset to not necessarily retrieve the last update date from the *last* chagneset (audit)
     *
     */
    #[Override]
    public function fetchArtifactValueReadOnly(Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null)
    {
        $html = '';
        if (! $value) {
            // TODO use $changeset instead of $artifact->getLastChangeset()
            // see @todo in the comment
            $value = $this->getChangesetValue($artifact->getLastChangeset(), null, false);
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
        ?Tracker_Artifact_ChangesetValue $value = null,
        $submitted_values = [],
    ) {
        return $this->fetchArtifactValueReadOnly($artifact, $value);
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
            // TODO use $changeset instead of $artifact->getLastChangeset()
            // see @todo in the comment
            $value = $this->getChangesetValue($artifact->getLastChangeset(), null, false);
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

    #[Override]
    protected function fetchTooltipValue(Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null): string
    {
        if (! $value) {
            // TODO use $changeset instead of $artifact->getLastChangeset()
            // see @todo in the comment
            $value = $this->getChangesetValue($artifact->getLastChangeset(), null, false);
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
        $html .= dgettext('tuleap-tracker', 'The field is automatically set to last artifact update date');
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
        return date(Tracker_FormElement_DateFormatter::DATE_FORMAT, (int) $artifact->getLastChangeset()->getSubmittedOn());
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
        $dao       = new Tracker_Artifact_ChangesetDao();
        $dar       = $dao->getArtifactsByFieldAndLastUpdateDate($trackerId, $date);
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
        return $visitor->visitLastUpdateDate($this);
    }

    #[Override]
    public function isTimeDisplayed()
    {
        return true;
    }

    #[Override]
    public function getFullRESTValue(PFUser $user, Tracker_Artifact_Changeset $changeset)
    {
        $artifact_field_value_full_representation = new ArtifactFieldValueFullRepresentation();
        $artifact_field_value_full_representation->build(
            $this->getId(),
            Tracker_FormElementFactory::instance()->getType($this),
            $this->getLabel(),
            date('c', $changeset->getArtifact()->getLastUpdateDate())
        );

        return $artifact_field_value_full_representation;
    }
}
