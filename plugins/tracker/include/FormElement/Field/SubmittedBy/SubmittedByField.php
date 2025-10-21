<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\SubmittedBy;

use Codendi_HTMLPurifier;
use Override;
use PFUser;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue;
use Tracker_CardDisplayPreferences;
use Tracker_FormElement_Field_List_Bind_UsersValue;
use Tracker_FormElement_Field_ReadOnly;
use Tracker_FormElement_FieldVisitor;
use Tracker_FormElementFactory;
use Tracker_Report;
use Tracker_Report_Criteria;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\Option\Option;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\Files\CreatedFileURLMapping;
use Tuleap\Tracker\FormElement\Field\List\Bind\ListFieldBind;
use Tuleap\Tracker\FormElement\Field\List\ListField;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\Report\Query\ParametrizedFromWhere;
use Tuleap\Tracker\Report\Query\ParametrizedSQLFragment;
use UserManager;

final class SubmittedByField extends ListField implements Tracker_FormElement_Field_ReadOnly
{
    public array $default_properties = [];

    /**
     * protected for testing purpose
     */
    protected function getUUIdFactory(): DatabaseUUIDV7Factory
    {
        return new DatabaseUUIDV7Factory();
    }

    #[Override]
    public function getCriteriaFromWhere(Tracker_Report_Criteria $criteria): Option
    {
        return $this->getCriteriaWhereFragment($criteria)->mapOr(
            static fn(ParametrizedSQLFragment $where) => Option::fromValue(
                new ParametrizedFromWhere(
                    '',
                    $where->sql,
                    [],
                    $where->parameters,
                )
            ),
            Option::nothing(ParametrizedFromWhere::class)
        );
    }

    /**
     * @return Option<ParametrizedSQLFragment>
     */
    public function getCriteriaWhereFragment(Tracker_Report_Criteria $criteria): Option
    {
        //Only filter query if criteria is valuated
        $criteria_value = $this->getCriteriaValue($criteria);
        if ($criteria_value) {
            $a             = 'A_' . $this->id;
            $b             = 'B_' . $this->id;
            $ids_to_search = array_values(array_intersect(
                array_values($criteria_value),
                array_merge([100], array_keys($this->getBind()->getAllValues()))
            ));
            if (count($ids_to_search) > 1) {
                $in = \ParagonIE\EasyDB\EasyStatement::open()->in('?*', $ids_to_search);
                return Option::fromValue(
                    new ParametrizedSQLFragment("artifact.submitted_by IN($in)", $in->values())
                );
            } else {
                $id_to_search = $ids_to_search[0] ?? null;
                return Option::fromValue(
                    new ParametrizedSQLFragment('artifact.submitted_by = ?', [$id_to_search])
                );
            }
        }
        return Option::nothing(ParametrizedSQLFragment::class);
    }

    #[Override]
    public function getQuerySelect(): string
    {
        // SubmittedOn is stored in the artifact
        return 'a.submitted_by AS ' . $this->getQuerySelectName();
    }

    #[Override]
    public function getQueryFrom()
    {
        // SubmittedOn is stored in the artifact
        return '';
    }

    #[Override]
    public function getQueryFromAggregate()
    {
        $R1 = 'R1_' . $this->id;
        $R2 = 'R2_' . $this->id;
        return " LEFT JOIN  user AS $R2 ON ($R2.user_id = a.submitted_by ) ";
    }

    /**
     * Get the "group by" statement to retrieve field values
     */
    #[Override]
    public function getQueryGroupby(): string
    {
        // SubmittedOn is stored in the artifact
        return 'a.submitted_by';
    }

    /**
     * Get the "order by" statement to retrieve field values
     */
    #[Override]
    public function getQueryOrderby(): string
    {
        return $this->getQuerySelectName();
    }

    #[Override]
    public static function getFactoryLabel()
    {
        return dgettext('tuleap-tracker', 'Submitted By');
    }

    #[Override]
    public static function getFactoryDescription()
    {
        return dgettext('tuleap-tracker', 'Display the user who submitted the artifact');
    }

    #[Override]
    public static function getFactoryIconUseIt()
    {
        return $GLOBALS['HTML']->getImagePath('ic/user-female.png');
    }

    #[Override]
    public static function getFactoryIconCreate()
    {
        return $GLOBALS['HTML']->getImagePath('ic/user-female--plus.png');
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
     * Hook called after a creation of a field
     *
     * @param bool $tracker_is_empty
     * @return void
     */
    #[Override]
    public function afterCreate(array $form_element_data, $tracker_is_empty)
    {
        //force the bind
        $form_element_data['bind-type'] = 'users';
        $form_element_data['bind']      = [
            'value_function' => [
                'artifact_submitters',
            ],
        ];
        parent::afterCreate($form_element_data, $tracker_is_empty);
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

    #[Override]
    public function getFullRESTValue(PFUser $user, Tracker_Artifact_Changeset $changeset)
    {
        $value              = Tracker_FormElement_Field_List_Bind_UsersValue::fromId($this->getUUIdFactory()->buildUUIDFromBytesData($this->getUUIdFactory()->buildUUIDBytes()), $changeset->getArtifact()->getSubmittedBy());
        $submitted_by_value = $value->getFullRESTValue($this);

        $artifact_field_value_full_representation = new \Tuleap\Tracker\REST\Artifact\ArtifactFieldValueFullRepresentation();
        $artifact_field_value_full_representation->build(
            $this->getId(),
            Tracker_FormElementFactory::instance()->getType($this),
            $this->getLabel(),
            $submitted_by_value
        );
        return $artifact_field_value_full_representation;
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
        return $this->fetchArtifactValueWithEditionFormIfEditable($artifact, $value, $submitted_values);
    }

    /**
     * Fetch the html code to display the field value in artifact in read only mode
     *
     * @param Artifact $artifact The artifact
     * @param Tracker_Artifact_ChangesetValue $value The actual value of the field
     *
     * @return string
     */
    #[Override]
    public function fetchArtifactValueReadOnly(Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null)
    {
        $purifier   = Codendi_HTMLPurifier::instance();
        $html       = '';
        $bind_value = Tracker_FormElement_Field_List_Bind_UsersValue::fromId($this->getUUIdFactory()->buildUUIDFromBytesData($this->getUUIdFactory()->buildUUIDBytes()), $artifact->getSubmittedBy());
        $bind_value = $purifier->purify($bind_value->getLabel());
        $html      .= $bind_value;
        return $html;
    }

    #[Override]
    public function fetchArtifactCopyMode(Artifact $artifact, array $submitted_values)
    {
        return '';
    }

    #[Override]
    public function fetchArtifactValueWithEditionFormIfEditable(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values,
    ) {
        return $this->fetchArtifactValueReadOnly($artifact, $value);
    }

    /**
     * Fetch the field value in artifact to be displayed in mail
     */
    #[Override]
    public function fetchMailArtifactValue(
        Artifact $artifact,
        PFUser $user,
        bool $ignore_perms,
        ?Tracker_Artifact_ChangesetValue $value = null,
        string $format = 'text',
    ): string {
        $output = '';

        $bind_value = Tracker_FormElement_Field_List_Bind_UsersValue::fromId($this->getUUIdFactory()->buildUUIDFromBytesData($this->getUUIdFactory()->buildUUIDBytes()), $artifact->getSubmittedBy());

        switch ($format) {
            case 'html':
                $output .= $this->fetchArtifactValueReadOnly($artifact);
                break;
            default:
                $output = $this->getBind()->formatMailArtifactValue($bind_value->getId());
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
     * Display the html field in the admin ui
     *
     * @return string html
     */
    #[Override]
    protected function fetchAdminFormElement()
    {
        $purifier   = Codendi_HTMLPurifier::instance();
        $html       = '';
        $fake_value = Tracker_FormElement_Field_List_Bind_UsersValue::fromId($this->getUUIdFactory()->buildUUIDFromBytesData($this->getUUIdFactory()->buildUUIDBytes()), (int) UserManager::instance()->getCurrentUser()->getId());
        $html      .= $purifier->purify($fake_value->getLabel()) . '<br />';
        $html      .= '<span class="tracker-admin-form-element-help">';
        $html      .= dgettext('tuleap-tracker', 'The field is automatically set to artifact submission user');
        $html      .= '</span>';
        return $html;
    }

    #[Override]
    public function fetchChangesetValue(
        int $artifact_id,
        int $changeset_id,
        mixed $value,
        ?Tracker_Report $report = null,
        ?array $redirection_parameters = null,
    ): string {
        return $this->getBind()->formatChangesetValue(Tracker_FormElement_Field_List_Bind_UsersValue::fromId($this->getUUIdFactory()->buildUUIDFromBytesData($this->getUUIdFactory()->buildUUIDBytes()), $value));
    }

    #[Override]
    protected function fetchTooltipValue(Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null): string
    {
        return $this->fetchArtifactValueReadOnly($artifact, $value);
    }

    /**
     * @see TrackerField::fetchCardValue()
     */
    #[Override]
    public function fetchCardValue(Artifact $artifact, ?Tracker_CardDisplayPreferences $display_preferences = null)
    {
        $value = Tracker_FormElement_Field_List_Bind_UsersValue::fromId($this->getUUIdFactory()->buildUUIDFromBytesData($this->getUUIdFactory()->buildUUIDBytes()), $artifact->getSubmittedBy());
        return $value->fetchCard($display_preferences);
    }

    /**
     * Display the field for CSV
     * Used in CSV data export
     */
    #[Override]
    public function fetchCSVChangesetValue(int $artifact_id, int $changeset_id, mixed $value, ?Tracker_Report $report): string
    {
        return $this->getBind()->formatChangesetValueForCSV(Tracker_FormElement_Field_List_Bind_UsersValue::fromId($this->getUUIdFactory()->buildUUIDFromBytesData($this->getUUIdFactory()->buildUUIDBytes()), $value));
    }

    /**
     * Say if this fields suport notifications
     *
     * @return bool
     */
    #[Override]
    public function isNotificationsSupported()
    {
        return true;
    }

    /**
     * Say if we export the bind in the XML
     *
     * @return bool
     */
    #[Override]
    public function shouldBeBindXML()
    {
        return false;
    }

    public function getUserManager()
    {
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
    #[Override]
    public function getFieldData($value)
    {
        $um = $this->getUserManager();
        $u  = $um->getUserByUserName($value);
        if ($u) {
            return $u->getId();
        } else {
            return null;
        }
    }

    #[Override]
    public function isNone($value)
    {
        return false;
    }

    #[Override]
    public function accept(Tracker_FormElement_FieldVisitor $visitor)
    {
        return $visitor->visitSubmittedBy($this);
    }

    #[Override]
    public function getDefaultValue()
    {
        return ListFieldBind::NONE_VALUE;
    }

    #[Override]
    public function getFieldDataFromRESTValue(array $value, ?Artifact $artifact = null)
    {
        return null;
    }

    #[Override]
    public function isAlwaysInEditMode(): bool
    {
        return false;
    }
}
