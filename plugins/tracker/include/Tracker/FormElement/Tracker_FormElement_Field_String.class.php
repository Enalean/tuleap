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

use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;

class Tracker_FormElement_Field_String extends Tracker_FormElement_Field_Text
{

    public $default_properties = array(
        'maxchars'      => array(
            'value' => 0,
            'type'  => 'string',
            'size'  => 3,
        ),
        'size'          => array(
            'value' => 30,
            'type'  => 'string',
            'size'  => 3,
        ),
        'default_value' => array(
            'value' => '',
            'type'  => 'string',
            'size'  => 40,
        ),
    );

    protected function getDao(): Tracker_FormElement_Field_StringDao
    {
        return new Tracker_FormElement_Field_StringDao();
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

    /**
     * The field is permanently deleted from the db
     * This hooks is here to delete specific properties,
     * or specific values of the field.
     * (The field itself will be deleted later)
     * @return bool true if success
     */
    public function delete()
    {
        return $this->getDao()->delete($this->id);
    }

    /**
     * Fetch the html code to display the field value in new artifact submission form
     * @param array $submitted_values the values already submitted
     *
     * @return string html
     */
    protected function fetchSubmitValue(array $submitted_values)
    {
        $html  = '';
        $value = $this->getValueFromSubmitOrDefault($submitted_values);
        $hp    = Codendi_HTMLPurifier::instance();
        $html .= '<input type="text" class="user-mention"
                         data-test="' . $this->getName() . '"
                         name="artifact[' . $this->id . ']"
                         ' . ($this->isRequired() ? 'required' : '') . '
                         size="' . $this->getProperty('size') . '"
                         ' . ($this->getProperty('maxchars') ? 'maxlength="' . $this->getProperty('maxchars') . '"' : '')  . '
                         value="' .  $hp->purify($value, CODENDI_PURIFIER_CONVERT_HTML)  . '" />';
        return $html;
    }

    /**
     * Fetch the html code to display the field value in masschange submission form
     * @param array $submitted_values the values already submitted
     *
     * @return string html
     */
    protected function fetchSubmitValueMasschange()
    {
        $html = '';
        $value = dgettext('tuleap-tracker', 'Unchanged');

        if ($this->isSemanticTitle()) {
            $html .= '<input type="text" readonly="readonly" value="' . $value . '" title="' . $GLOBALS['Language']->getText('plugin_tracker_artifact_masschange', 'cannot_masschange_title') . '" />';
        } else {
            $hp = Codendi_HTMLPurifier::instance();
            $html .= '<input type="text" class="user-mention"
                             name="artifact[' . $this->id . ']"
                             size="' . $this->getProperty('size') . '"
                             ' . ($this->getProperty('maxchars') ? 'maxlength="' . $this->getProperty('maxchars') . '"' : '')  . '
                             value="' .  $hp->purify($value, CODENDI_PURIFIER_CONVERT_HTML)  . '" />';
        }
        return $html;
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
        $value = $value ? $value->getValue() : '';

        if ($value === '') {
            return $this->getNoValueLabel();
        }

        return $value;
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
        $html = '';
        if (isset($submitted_values[$this->getId()])) {
            $value = $submitted_values[$this->getId()];
        } else {
            if ($value != null) {
                $value = $value->getText();
            }
        }
        $hp = Codendi_HTMLPurifier::instance();
        $html .= '<input type="text" class="user-mention"
                         data-test="' . $this->getName() . '"
                         name="artifact[' . $this->id . ']"
                         ' . ($this->isRequired() ? 'required' : '') . '
                         size="' . $this->getProperty('size') . '"
                         ' . ($this->getProperty('maxchars') ? 'maxlength="' . $this->getProperty('maxchars') . '"' : '')  . '
                         value="' .  $hp->purify($value, CODENDI_PURIFIER_CONVERT_HTML)  . '" />';
        return $html;
    }

    /**
     * Display the html field in the admin ui
     * @return string html
     */
    protected function fetchAdminFormElement()
    {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '';
        $value = '';
        if ($this->hasDefaultValue()) {
            $value = $this->getDefaultValue();
        }
        $html .= '<input type="text"
                         size="' . $this->getProperty('size') . '"
                         ' . ($this->getProperty('maxchars') ? 'maxlength="' . $this->getProperty('maxchars') . '"' : '')  . '
                         value="' .  $hp->purify($value, CODENDI_PURIFIER_CONVERT_HTML) . '" autocomplete="off" />';
        return $html;
    }

    public static function getFactoryLabel()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'string');
    }

    public static function getFactoryDescription()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'string_description');
    }

    public static function getFactoryIconUseIt()
    {
        return $GLOBALS['HTML']->getImagePath('ic/ui-text-field.png');
    }

    public static function getFactoryIconCreate()
    {
        return $GLOBALS['HTML']->getImagePath('ic/ui-text-field--plus.png');
    }

    /**
     * Fetch the html code to display the field value in tooltip
     *
     * @param Tracker_Artifact_ChangesetValue_String $value The ChangesetValue_String
     * @return string The html code to display the field value in tooltip
     */
    protected function fetchTooltipValue(Tracker_Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null)
    {
        $hp = Codendi_HTMLPurifier::instance();
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
    public function takesTwoColumns()
    {
        return $this->getProperty('size') > 40;
    }

    /**
     * Validate a value
     *
     * @param Tracker_Artifact $artifact The artifact
     * @param mixed            $value    data coming from the request. May be string or array.
     *
     * @return bool true if the value is considered ok
     */
    protected function validate(Tracker_Artifact $artifact, $value)
    {
        $rule_is_a_string = $this->getRuleString();
        if (! $rule_is_a_string->isValid($value)) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText(
                    'plugin_tracker_common_artifact',
                    'error_string_value',
                    array($this->getLabel())
                )
            );
            return false;
        }

        $maximum_characters_allowed = (int) $this->getProperty('maxchars');
        if ($maximum_characters_allowed !== 0 && mb_strlen($value) > $maximum_characters_allowed) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText(
                    'plugin_tracker_common_artifact',
                    'error_string_max_characters',
                    array($this->getLabel(), $maximum_characters_allowed)
                )
            );
            return false;
        }

        $rule_does_not_contain_carriage_return = $this->getRuleNoCr();
        if (! $rule_does_not_contain_carriage_return->isValid($value)) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText(
                    'plugin_tracker_common_artifact',
                    'error_string_value_characters',
                    array($this->getLabel())
                )
            );
            return false;
        }

        return true;
    }

    protected function getRuleNoCr()
    {
        return new Rule_NoCr();
    }

    /**
     * @see Tracker_FormElement_Field::hasChanges()
     */
    public function hasChanges(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $previous_changesetvalue, $new_value)
    {
        return $previous_changesetvalue->getText() !== (string) $new_value;
    }

    protected function saveValue(
        $artifact,
        $changeset_value_id,
        $value,
        ?Tracker_Artifact_ChangesetValue $previous_changesetvalue,
        CreatedFileURLMapping $url_mapping
    ) {
        return $this->getValueDao()->create($changeset_value_id, $value) &&
               $this->extractCrossRefs($artifact, $value);
    }

    /**
     * Returns the default value for this field, or nullif no default value defined
     *
     * @return mixed The default value for this field, or null if no default value defined
     */
    public function getDefaultValue()
    {
        return $this->getProperty('default_value');
    }

    public function getRestFieldData($value)
    {
        return $this->getFieldData($value);
    }

    public function isEmpty($value, Tracker_Artifact $artifact)
    {
        return trim($value) == '';
    }

    public function accept(Tracker_FormElement_FieldVisitor $visitor)
    {
        return $visitor->visitString($this);
    }
}
