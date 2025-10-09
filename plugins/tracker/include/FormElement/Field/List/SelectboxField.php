<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\List;

use Codendi_HTMLPurifier;
use Override;
use PFUser;
use Tracker_Artifact_ChangesetValue;
use Tracker_Artifact_ChangesetValue_List;
use Tracker_FormElement_Field_List_Bind;
use Tracker_FormElement_Field_List_Bind_StaticValue_None;
use Tracker_FormElement_FieldVisitor;
use Tracker_FormElement_IComputeValues;
use Tracker_FormElement_InvalidFieldValueException;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\List\Bind\BindStaticValueUnchanged;
use Tuleap\Tracker\FormElement\Field\ListField;
use WorkflowFactory;

class SelectboxField extends ListField implements Tracker_FormElement_IComputeValues
{
    #[Override]
    public static function getFactoryLabel()
    {
        return dgettext('tuleap-tracker', 'Selectbox');
    }

    #[Override]
    public static function getFactoryDescription()
    {
        return dgettext('tuleap-tracker', 'The user can choose one value among others');
    }

    #[Override]
    public static function getFactoryIconUseIt()
    {
        return $GLOBALS['HTML']->getImagePath('ic/ui-combo-box.png');
    }

    #[Override]
    public static function getFactoryIconCreate()
    {
        return $GLOBALS['HTML']->getImagePath('ic/ui-combo-box--plus.png');
    }

    /**
     * Add some additionnal information beside the field in the artifact form.
     * This is up to the field. It can be html or inline javascript
     * to enhance the user experience
     * @return string
     */
    #[Override]
    public function fetchArtifactAdditionnalInfo(?Tracker_Artifact_ChangesetValue $value, array $submitted_values)
    {
        assert($value instanceof Tracker_Artifact_ChangesetValue_List);
        $html   = parent::fetchArtifactAdditionnalInfo($value, $submitted_values);
        $values = [];
        if (isset($submitted_values[$this->id])) {
            if (! is_array($submitted_values[$this->id])) {
                $submitted_values_array[] = $submitted_values[$this->id];
                $values                   = $submitted_values_array;
            } else {
                $values = $submitted_values[$this->id];
            }
        } else {
            if ($value !== null) {
                foreach ($value->getListValues() as $id => $v) {
                    $values[] = $id;
                }
            }
        }

        if ($this->isJavascriptIncludedInValue($submitted_values)) {
            $html .= $this->displayArtifactJavascript($values);
        }

        return $html;
    }

    private function isJavascriptIncludedInValue(array $submitted_values)
    {
        return ! isset($submitted_values['render_with_javascript'])
               || $submitted_values['render_with_javascript'] === true;
    }

    /**
     * Add some additionnal information beside the field in the submit new artifact form.
     * This is up to the field. It can be html or inline javascript
     * to enhance the user experience
     * @return string
     */
    #[Override]
    public function fetchSubmitAdditionnalInfo(array $submitted_values)
    {
        $html = parent::fetchSubmitAdditionnalInfo($submitted_values);
        if ($this->isJavascriptIncludedInValue($submitted_values)) {
            $html .= $this->displaySubmitJavascript();
        }
        return $html;
    }

    protected function displayArtifactJavascript($changeset_values): string
    {
        $purifier = Codendi_HTMLPurifier::instance();

        $csp_nonce = $GLOBALS['Response']->getCSPNonce();
        $html      = sprintf('<script type="text/javascript" nonce="%s">', $purifier->purify($csp_nonce));
        $html     .= "tuleap.tracker.fields.add('" . (int) $this->getID() . "', '" . $this->getName() . "', '" . $purifier->purify($this->getLabel(), CODENDI_PURIFIER_JS_QUOTE) . "')";
        $values    = $this->getBind()->getAllValues();
        $html     .= "\n\t.addOption('" . dgettext('tuleap-tracker', 'None') . "'.escapeHTML(), '100', " . (empty($changeset_values) ? 'true' : 'false') . ", '')";

        foreach ($values as $id => $value) {
            $dataset = $value->getDataset($this);
            $html   .= "\n\t.addOption('" . $purifier->purify($value->getLabel(), CODENDI_PURIFIER_JS_QUOTE) . "'.escapeHTML(), '" . (int) $id . "', " . (in_array($id, array_values($changeset_values)) ? 'true' : 'false') . ', ' . json_encode($dataset) . ')';
        }
        $html .= ";\n";
        $html .= '</script>';
        return $html;
    }

    protected function displaySubmitJavascript(): string
    {
        $hp            = Codendi_HTMLPurifier::instance();
        $csp_nonce     = $GLOBALS['Response']->getCSPNonce();
        $html          = sprintf('<script type="text/javascript" nonce="%s">', $hp->purify($csp_nonce));
        $html         .= "tuleap.tracker.fields.add('" . (int) $this->getID() . "', '" . $hp->purify($this->getName(), CODENDI_PURIFIER_JS_QUOTE) . "', '" . $hp->purify($this->getLabel(), CODENDI_PURIFIER_JS_QUOTE) . "')";
        $default_value = $this->getDefaultValue();
        $values        = $this->getBind()->getAllValues();
        $html         .= "\n\t.addOption('None'.escapeHTML(), '100', " . ($default_value == 100 ? 'true' : 'false') . ", '')";
        $html         .= "\n\t.addOption('" . $hp->purify($GLOBALS['Language']->getText('global', 'unchanged'), CODENDI_PURIFIER_JS_QUOTE) . "'.escapeHTML(), '" . $hp->purify(BindStaticValueUnchanged::VALUE_ID, CODENDI_PURIFIER_JS_QUOTE) . "', false, '')";

        foreach ($values as $id => $value) {
            $dataset = $value->getDataset($this);
            $html   .= "\n\t.addOption('" . $hp->purify($value->getLabel(), CODENDI_PURIFIER_JS_QUOTE) . "'.escapeHTML(), '" . (int) $id . "', " . ($id == $default_value ? 'true' : 'false') . ', ' . json_encode($dataset) . ')';
        }
        $html .= ";\n";
        $html .= '</script>';
        return $html;
    }

    /**
     * Change the type of the select box
     *
     * @param string $type the new type
     *
     * @return bool true if the change is allowed and successful
     */
    #[Override]
    public function changeType($type)
    {
        if (in_array($type, ['msb', 'cb'])) {
            //do not change from SB to MSB if the field is used to define the workflow
            $wf = WorkflowFactory::instance();
            return ! $wf->isWorkflowField($this);
        } elseif ($type === 'rb') {
            return true;
        }
        return false;
    }

    /**
     * @return bool true if the value corresponds to none
     */
    #[Override]
    public function isNone($value)
    {
        return $value === null ||
               $value === '' ||
               $value === '100' ||
               $value === self::NONE_VALUE ||
               $value === [] ||
               (is_array($value) && $this->arrayContainsNone($value));
    }

    protected function arrayContainsNone(array $value)
    {
        return count($value) === 1 && array_pop($value) == '100';
    }

    #[Override]
    public function getComputedValue(
        PFUser $user,
        Artifact $artifact,
        $timestamp = null,
    ) {
        if ($this->userCanRead($user)) {
            return $this->getCurrentValue($artifact);
        }
        return null;
    }

    #[Override]
    public function getCachedValue(PFUser $user, Artifact $artifact, $timestamp = null)
    {
        return $this->getComputedValue($user, $artifact, $timestamp);
    }

    private function getCurrentValue(Artifact $artifact)
    {
        $changeset_value = $artifact->getValue($this);

        if ($changeset_value) {
            $values = $this->getBind()->getNumericValues($changeset_value);
            if (! empty($values)) {
                return $values[0];
            }
        }
        return null;
    }

    #[Override]
    public function getFieldDataFromRESTValue(array $value, ?Artifact $artifact = null)
    {
        if (array_key_exists('bind_value_ids', $value) && is_array($value['bind_value_ids'])) {
            $submitted_bind_value_ids = array_filter(array_unique($value['bind_value_ids']));
            if (count($submitted_bind_value_ids) > 1) {
                throw new Tracker_FormElement_InvalidFieldValueException('Selectbox fields can only have one value');
            }

            if (empty($submitted_bind_value_ids)) {
                return ListField::NONE_VALUE;
            }

            return $this->getBindValueIdFromSubmittedBindValueId($submitted_bind_value_ids[0]);
        }

        throw new Tracker_FormElement_InvalidFieldValueException('List fields values must be passed as an array of ids (integer) in \'bind_value_ids\''
                                                                 . ' Expected format for field ' . $this->id . ' : {"field_id": 1548, "bind_value_ids": [457]}');
    }

    /**
     * @param string|int $submitted_bind_value_id
     *
     * @throws Tracker_FormElement_InvalidFieldValueException
     */
    protected function getBindValueIdFromSubmittedBindValueId($submitted_bind_value_id): int
    {
        if ((int) $submitted_bind_value_id === ListField::NONE_VALUE) {
            return ListField::NONE_VALUE;
        }

        $bind_value_id = $this->getBind()->getFieldDataFromRESTValue($submitted_bind_value_id);
        if (empty($bind_value_id)) {
            throw new Tracker_FormElement_InvalidFieldValueException("The submitted value $submitted_bind_value_id is invalid");
        }

        return $bind_value_id;
    }

    #[Override]
    public function getFieldDataFromCSVValue($csv_value, ?Artifact $artifact = null)
    {
        if ($csv_value !== '100' && $this->isNone($csv_value)) {
            return Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID;
        }
        return $this->getFieldData($csv_value);
    }

    #[Override]
    public function accept(Tracker_FormElement_FieldVisitor $visitor)
    {
        return $visitor->visitSelectbox($this);
    }

    #[Override]
    public function getDefaultValue()
    {
        $default_array = $this->getBind()->getDefaultValues();

        if ($default_array && is_array($default_array) && count($default_array) === 1) {
            $keys = array_keys($default_array);
            return array_shift($keys);
        }

        return Tracker_FormElement_Field_List_Bind::NONE_VALUE;
    }

    #[Override]
    public function isAlwaysInEditMode(): bool
    {
        return false;
    }
}
