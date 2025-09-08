<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\String;

use Codendi_HTMLPurifier;
use EventManager;
use Feedback;
use Rule_NoCr;
use Rule_String;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue;
use Tracker_Artifact_ChangesetValue_String;
use Tracker_FormElement_FieldVisitor;
use Tuleap\Search\ItemToIndexQueue;
use Tuleap\Search\ItemToIndexQueueEventBased;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\Files\CreatedFileURLMapping;
use Tuleap\Tracker\FormElement\Field\Text\TextField;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\FormElement\FieldContentIndexer;
use Tuleap\Tracker\FormElement\FieldSpecificProperties\DeleteSpecificProperties;
use Tuleap\Tracker\FormElement\FieldSpecificProperties\DuplicateSpecificProperties;
use Tuleap\Tracker\FormElement\FieldSpecificProperties\SaveSpecificFieldProperties;
use Tuleap\Tracker\FormElement\FieldSpecificProperties\SearchSpecificProperties;
use Tuleap\Tracker\FormElement\FieldSpecificProperties\StringFieldSpecificPropertiesDAO;
use Tuleap\Tracker\Report\Criteria\CriteriaAlphaNumValueDAO;
use Tuleap\Tracker\Report\Criteria\DeleteReportCriteriaValue;

class StringField extends TextField
{
    public array $default_properties = [
        'maxchars'      => [
            'value' => 0,
            'type'  => 'string',
            'size'  => 3,
        ],
        'size'          => [
            'value' => 30,
            'type'  => 'string',
            'size'  => 3,
        ],
        'default_value' => [
            'value' => '',
            'type'  => 'string',
            'size'  => 40,
        ],
    ];

    #[\Override]
    protected function getDuplicateSpecificPropertiesDao(): DuplicateSpecificProperties
    {
        return new StringFieldSpecificPropertiesDAO();
    }

    #[\Override]
    protected function getDeleteSpecificPropertiesDao(): DeleteSpecificProperties
    {
        return new StringFieldSpecificPropertiesDAO();
    }

    #[\Override]
    protected function getSaveSpecificPropertiesDao(): SaveSpecificFieldProperties
    {
        return new StringFieldSpecificPropertiesDAO();
    }

    #[\Override]
    protected function getSearchSpecificPropertiesDao(): SearchSpecificProperties
    {
        return new StringFieldSpecificPropertiesDAO();
    }

    #[\Override]
    public function getDeleteCriteriaValueDAO(): DeleteReportCriteriaValue
    {
        return new CriteriaAlphaNumValueDAO();
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
    #[\Override]
    public function getChangesetValue($changeset, $value_id, $has_changed)
    {
        $changeset_value = null;
        if ($row = $this->getValueDao()->searchById($value_id, $this->id)->getRow()) {
            $changeset_value = new Tracker_Artifact_ChangesetValue_String(
                $value_id,
                $changeset,
                $this,
                $has_changed,
                $row['value'],
                $row['body_format']
            );
        }
        return $changeset_value;
    }

    #[\Override]
    protected function fetchSubmitValue(array $submitted_values): string
    {
        $html  = '';
        $value = $this->getValueFromSubmitOrDefault($submitted_values);
        $hp    = Codendi_HTMLPurifier::instance();
        $html .= '<input type="text"
                         data-test-field-input
                         data-test="' . $this->getName() . '"
                         name="artifact[' . $this->id . ']"
                         ' . ($this->isRequired() ? 'required' : '') . '
                         size="' . $this->getProperty('size') . '"
                         ' . ($this->getProperty('maxchars') ? 'maxlength="' . $this->getProperty('maxchars') . '"' : '') . '
                         value="' . $hp->purify($value, CODENDI_PURIFIER_CONVERT_HTML) . '" />';
        return $html;
    }

    #[\Override]
    protected function fetchSubmitValueMasschange(): string
    {
        $html  = '';
        $value = dgettext('tuleap-tracker', 'Unchanged');

        if ($this->isSemanticTitle()) {
            $html .= '<input type="text" readonly="readonly" value="' . $value . '" title="' . dgettext('tuleap-tracker', 'This field is the title of the artifact. It is not allowed to masschange it.') . '" />';
        } else {
            $hp    = Codendi_HTMLPurifier::instance();
            $html .= '<input type="text"
                             name="artifact[' . $this->id . ']"
                             size="' . $this->getProperty('size') . '"
                             ' . ($this->getProperty('maxchars') ? 'maxlength="' . $this->getProperty('maxchars') . '"' : '') . '
                             value="' . $hp->purify($value, CODENDI_PURIFIER_CONVERT_HTML) . '" />';
        }
        return $html;
    }

    /**
     * Fetch the html code to display the field value in artifact in read only mode
     *
     * @param Artifact $artifact The artifact
     * @param Tracker_Artifact_ChangesetValue $value The actual value of the field
     *
     * @return string
     */
    #[\Override]
    public function fetchArtifactValueReadOnly(Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null)
    {
        $value = $value ? $value->getValue() : '';

        if ($value === '') {
            return $this->getNoValueLabel();
        }

        return '<div class="tracker-string-field-value">' . $value . '</div>';
    }

    /**
     * Fetch the html code to display the field value in artifact
     *
     * @param Artifact $artifact The artifact
     * @param Tracker_Artifact_ChangesetValue $value The actual value of the field
     * @param array $submitted_values The value already submitted by the user
     */
    #[\Override]
    protected function fetchArtifactValue(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values,
    ): string {
        $html = '';
        if (isset($submitted_values[$this->getId()])) {
            $value = $submitted_values[$this->getId()];
        } else {
            if ($value != null) {
                $value = $value->getText();
            }
        }
        $hp    = Codendi_HTMLPurifier::instance();
        $html .= '<input type="text"
                         data-test-field-input
                         data-test="' . $this->getName() . '"
                         name="artifact[' . $this->id . ']"
                         ' . ($this->isRequired() ? 'required' : '') . '
                         size="' . $this->getProperty('size') . '"
                         ' . ($this->getProperty('maxchars') ? 'maxlength="' . $this->getProperty('maxchars') . '"' : '') . '
                         value="' . $hp->purify($value, CODENDI_PURIFIER_CONVERT_HTML) . '" />';
        return $html;
    }

    /**
     * Display the html field in the admin ui
     * @return string html
     */
    #[\Override]
    protected function fetchAdminFormElement()
    {
        $hp    = Codendi_HTMLPurifier::instance();
        $html  = '';
        $value = '';
        if ($this->hasDefaultValue()) {
            $value = $this->getDefaultValue();
        }
        $html .= '<input type="text"
                         data-test="field-default-value"
                         size="' . $this->getProperty('size') . '"
                         ' . ($this->getProperty('maxchars') ? 'maxlength="' . $this->getProperty('maxchars') . '"' : '') . '
                         value="' . $hp->purify($value, CODENDI_PURIFIER_CONVERT_HTML) . '" autocomplete="off" />';
        return $html;
    }

    #[\Override]
    public static function getFactoryLabel()
    {
        return dgettext('tuleap-tracker', 'String');
    }

    #[\Override]
    public static function getFactoryDescription()
    {
        return dgettext('tuleap-tracker', 'A single line text');
    }

    #[\Override]
    public static function getFactoryIconUseIt()
    {
        return $GLOBALS['HTML']->getImagePath('ic/ui-text-field.png');
    }

    #[\Override]
    public static function getFactoryIconCreate()
    {
        return $GLOBALS['HTML']->getImagePath('ic/ui-text-field--plus.png');
    }

    #[\Override]
    protected function fetchTooltipValue(Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null): string
    {
        $hp   = Codendi_HTMLPurifier::instance();
        $html = '';
        if ($value) {
            $html .= $value->getValue();
        }
        return $html;
    }

    /**
     * Tells if the field takes two columns
     * Ugly legacy hack to display fields in columns
     * @return bool
     */
    #[\Override]
    public function takesTwoColumns()
    {
        return $this->getProperty('size') > 40;
    }

    /**
     * Validate a value
     *
     * @param Artifact $artifact The artifact
     * @param mixed $value data coming from the request. May be string or array.
     *
     * @return bool true if the value is considered ok
     */
    #[\Override]
    protected function validate(Artifact $artifact, $value)
    {
        $rule_is_a_string = $this->getRuleString();
        if (! $rule_is_a_string->isValid($value)) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                sprintf(dgettext('tuleap-tracker', '%1$s is not a string.'), $this->getLabel())
            );
            return false;
        }

        $maximum_characters_allowed = (int) $this->getProperty('maxchars');
        if ($maximum_characters_allowed !== 0 && mb_strlen($value) > $maximum_characters_allowed) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                sprintf(dgettext('tuleap-tracker', '%1$s can not contain more than %2$s characters.'), $this->getLabel(), $maximum_characters_allowed)
            );
            return false;
        }

        $rule_does_not_contain_carriage_return = $this->getRuleNoCr();
        if (! $rule_does_not_contain_carriage_return->isValid($value)) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                sprintf(dgettext('tuleap-tracker', '%1$s can contain neiher carriage return nor null char.'), $this->getLabel())
            );
            return false;
        }

        return true;
    }

    protected function getRuleString(): Rule_String
    {
        return new Rule_String();
    }

    /**
     * Validate a required field
     *
     * @param Artifact $artifact The artifact to check
     * @param mixed $submitted_value The submitted value
     *
     * @return bool true on success or false on failure
     */
    #[\Override]
    public function isValidRegardingRequiredProperty(Artifact $artifact, $submitted_value): bool
    {
        return TrackerField::isValidRegardingRequiredProperty($artifact, $submitted_value);
    }

    protected function getRuleNoCr()
    {
        return new Rule_NoCr();
    }

    /**
     * @see TrackerField::hasChanges()
     */
    #[\Override]
    public function hasChanges(Artifact $artifact, Tracker_Artifact_ChangesetValue $old_value, $new_value)
    {
        return $old_value->getText() !== (string) $new_value;
    }

    #[\Override]
    protected function saveValue(
        $artifact,
        $changeset_value_id,
        $value,
        ?Tracker_Artifact_ChangesetValue $previous_changesetvalue,
        CreatedFileURLMapping $url_mapping,
    ) {
        $res = $this->getValueDao()->create($changeset_value_id, $value) &&
               $this->extractCrossRefs($artifact, $value);

        if ($res) {
            $this->addRawValueToSearchIndex(new ItemToIndexQueueEventBased(EventManager::instance()), $artifact, $value);
        }

        return $res;
    }

    #[\Override]
    public function addChangesetValueToSearchIndex(ItemToIndexQueue $index_queue, Tracker_Artifact_ChangesetValue $changeset_value): void
    {
        assert($changeset_value instanceof Tracker_Artifact_ChangesetValue_String);
        $this->addRawValueToSearchIndex(
            $index_queue,
            $changeset_value->getChangeset()->getArtifact(),
            $changeset_value->getText(),
        );
    }

    private function addRawValueToSearchIndex(ItemToIndexQueue $index_queue, Artifact $artifact, string $content): void
    {
        $event_dispatcher = EventManager::instance();
        (new FieldContentIndexer($index_queue, $event_dispatcher))->indexFieldContent(
            $artifact,
            $this,
            $content,
            \Tuleap\Search\ItemToIndex::CONTENT_TYPE_PLAINTEXT,
        );
    }

    /**
     * Returns the default value for this field, or nullif no default value defined
     *
     * @return mixed The default value for this field, or null if no default value defined
     */
    #[\Override]
    public function getDefaultValue()
    {
        return $this->getProperty('default_value');
    }

    #[\Override]
    public function getRestFieldData($value)
    {
        return $this->getFieldData($value);
    }

    #[\Override]
    public function isEmpty($value, Artifact $artifact)
    {
        return trim($value) == '';
    }

    #[\Override]
    public function accept(Tracker_FormElement_FieldVisitor $visitor)
    {
        return $visitor->visitString($this);
    }
}
