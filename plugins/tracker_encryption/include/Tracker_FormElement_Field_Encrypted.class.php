<?php
/**
 * Copyright (c) Enalean 2020-present. All rights reserved
 * Copyright (c) STMicroelectronics 2016. All rights reserved
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

use Tuleap\Option\Option;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\FormElement\TrackerFormElementExternalField;
use Tuleap\Tracker\Report\Query\ParametrizedFromWhere;
use Tuleap\TrackerEncryption\ChangesetValue;
use Tuleap\TrackerEncryption\Dao\TrackerPublicKeyDao;
use Tuleap\TrackerEncryption\Dao\ValueDao;

class Tracker_FormElement_Field_Encrypted extends Tracker_FormElement_Field implements TrackerFormElementExternalField // @codingStandardsIgnoreLine
{
    public const TYPE = 'Encrypted';

    /**
     * @return string html
     */
    protected function fetchSubmitValue(array $submitted_values)
    {
        $value = $this->getValueFromSubmitOrDefault($submitted_values);

        $html  = '<div class="input-append encrypted-field">';
        $html .= $this->fetchInput($value, 'password');
        $html .= $this->fetchButton();
        $html .= '</div>';

        return $html;
    }

    /**
     * @return string html
     */
    private function fetchButton()
    {
        $html = '<button class="btn" type="button" id="show_password_' . $this->id . '">
                     <span id="show_password_icon_' . $this->id . '" class="fa fa-eye-slash"></span>
                 </button>';

        return $html;
    }

    /**
     * @return string html
     */
    protected function fetchAdminFormElement()
    {
        return $this->fetchSubmitValue([]);
    }

    public static function getFactoryLabel()
    {
        return dgettext('tuleap-tracker_encryption', 'Encrypted field');
    }

    public static function getFactoryDescription()
    {
          return dgettext('tuleap-tracker_encryption', 'Encrypted field');
    }

    public static function getFactoryIconUseIt()
    {
        return $GLOBALS['HTML']->getImagePath('ic/lock.png');
    }

    public static function getFactoryIconCreate()
    {
        return $GLOBALS['HTML']->getImagePath('ic/lock.png');
    }

    protected function validate(Artifact $artifact, $value)
    {
        $last_changeset_value = $this->getLastChangesetValue($artifact);
        if (
            $last_changeset_value !== null
            && $last_changeset_value->getValue() === $value
        ) {
            return true;
        }

        $maximum_characters_allowed = $this->getMaxSizeAllowed();
        if ($maximum_characters_allowed !== 0 && mb_strlen($value) > $maximum_characters_allowed) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                sprintf(dgettext('tuleap-tracker', '%1$s can not contain more than %2$s characters.'), $this->getLabel(), $maximum_characters_allowed)
            );
            return false;
        }
        return true;
    }

    private function getMaxSizeAllowed()
    {
        $dao_pub_key = new TrackerPublicKeyDao();
        $value_dao   = $this->getValueDao();
        $tracker_key = new Tracker_Key($dao_pub_key, $value_dao, $this->getTrackerId());
        $key         = $tracker_key->getKey();

        return $tracker_key->getFieldSize($key);
    }

    protected function saveValue(
        $artifact,
        $changeset_value_id,
        $value,
        ?Tracker_Artifact_ChangesetValue $previous_changesetvalue,
        CreatedFileURLMapping $url_mapping,
    ) {
        if ($value != "") {
            $dao_pub_key = new TrackerPublicKeyDao();
            $value_dao   = $this->getValueDao();
            $tracker_key = new Tracker_Key($dao_pub_key, $value_dao, $artifact->tracker_id);
            try {
                $encryption_manager = new Encryption_Manager($tracker_key);
                return $this->getValueDao()->create($changeset_value_id, $encryption_manager->encrypt($value));
            } catch (Tracker_EncryptionException $exception) {
                return false;
            }
        } else {
            return $this->getValueDao()->create($changeset_value_id, $value);
        }
    }

    public function accept(Tracker_FormElement_FieldVisitor $visitor)
    {
        return $visitor->visitExternalField($this);
    }

    public function getRESTAvailableValues()
    {
    }

    /**
     * @param Tracker_Report_Criteria $criteria
     *
     * @return string
     * @see fetchCriteria
     */
    public function fetchCriteriaValue($criteria)
    {
        return '';
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    public function fetchRawValue($value)
    {
        return '';
    }

    public function getCriteriaFromWhere(Tracker_Report_Criteria $criteria): Option
    {
        return Option::nothing(ParametrizedFromWhere::class);
    }

    public function getQueryFrom()
    {
        $R1 = 'R1_' . $this->id;
        $R2 = 'R2_' . $this->id;

        return "LEFT JOIN ( tracker_changeset_value AS $R1
                    INNER JOIN tracker_changeset_value_encrypted AS $R2 ON ($R2.changeset_value_id = $R1.id)
                ) ON ($R1.changeset_id = c.id AND $R1.field_id = " . $this->id . " )";
    }

    public function getQuerySelect(): string
    {
        $R2 = 'R2_' . $this->id;

        return "$R2.value AS " . $this->getQuerySelectName();
    }

    protected function getCriteriaDao()
    {
    }

    /**
     * @return string
     */
    protected function fetchArtifactValue(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value = null,
        $submitted_values = [],
    ) {
        $html = '';
        if (
            is_array($submitted_values)
            && isset($submitted_values[$this->getId()])
            && $submitted_values[$this->getId()] !== false
        ) {
            $value = $submitted_values[$this->getId()];
        } else {
            if ($value != null) {
                $value = $value->getValue();
            }
        }
        $html .= $this->fetchEditInput($value);

        return $html;
    }

    /**
     * @return string
     */
    public function fetchArtifactValueReadOnly(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value = null,
    ) {
        if (isset($value) === false || $value->getValue() === '') {
            return $this->getNoValueLabel();
        }

        $purifier = Codendi_HTMLPurifier::instance();

        return $purifier->purify($value->getValue());
    }

    protected function getHiddenArtifactValueForEdition(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values,
    ) {
        return '<div class="tracker_hidden_edition_field" data-field-id="' . $this->getId() . '">' .
            $this->fetchArtifactValue($artifact, $value, $submitted_values) . '</div>';
    }

    private function fetchInput($value, $field_type)
    {
        $html_purifier = Codendi_HTMLPurifier::instance();

        return '<input
            type="' . $field_type . '"
            autocomplete="off"
            id="password_' . $this->id . '"
            class="form-control"
            name="artifact[' . $this->id . ']"
            maxlength="' . $this->getMaxSizeAllowed() . '"
            value= "' . $html_purifier->purify($value, CODENDI_PURIFIER_CONVERT_HTML) . '" />';
    }

    private function fetchEditInput($value)
    {
        return $this->fetchInput($value, 'text');
    }

    protected function fetchArtifactValueWithEditionFormIfEditable(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value = null,
        $submitted_values = [],
    ) {
        return "<div class='tracker-form-element-encrypted'>" . $this->fetchArtifactValueReadOnly($artifact, $value) . "</div>" .
            $this->getHiddenArtifactValueForEdition($artifact, $value, $submitted_values);
    }

    /**
     * @return string html
     */
    protected function fetchSubmitValueMasschange()
    {
        return '';
    }

    /**
     * @return string
     */
    protected function fetchTooltipValue(Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null)
    {
        return '';
    }

    protected function getValueDao()
    {
        return new ValueDao(
            new Tracker_Artifact_Changeset_ValueDao()
        );
    }

    /**
     * @param Tracker_Artifact_Changeset $changeset
     *
     * @return string
     */
    public function fetchRawValueFromChangeset($changeset)
    {
        return '';
    }

    /**
     * @param Tracker_Artifact_Changeset $changeset
     * @param int $value_id
     * @param bool $has_changed
     *
     */
    public function getChangesetValue($changeset, $value_id, $has_changed): ?Tracker_Artifact_ChangesetValue
    {
        $row = $this->getValueDao()->searchById($value_id);
        if ($row === null) {
            return null;
        }

        return new ChangesetValue($value_id, $changeset, $this, $has_changed, $row['value']);
    }

    public function fetchChangesetValue(
        int $artifact_id,
        int $changeset_id,
        mixed $value,
        ?Tracker_Report $report = null,
        ?int $from_aid = null,
    ): string {
        return (string) $value;
    }

    public function fetchArtifactForOverlay(Artifact $artifact, array $submitted_values)
    {
    }

    public function canBeUsedAsReportCriterion()
    {
        return false;
    }

    public function hasChanges(Artifact $artifact, Tracker_Artifact_ChangesetValue $old_value, $new_value)
    {
        return $old_value->getValue() !== $new_value;
    }

    public function getFormAdminVisitor(Tracker_FormElement_Field $element, array $used_element)
    {
        return new Tracker_FormElement_View_Admin_Field($element, $used_element);
    }
}
