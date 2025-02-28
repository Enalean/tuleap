<?php
/**
 * Copyright (c) Jtekt, Jason Team, 2012. All rights reserved
 * Copyright (c) Enalean, 2015-present. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\Artifact;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_FormElement_Field_Checkbox extends Tracker_FormElement_Field_MultiSelectbox
{
    private const NOT_INDICATED_VALUE = '0';

    public array $default_properties = [];

    protected function fetchFieldContainerStart(string $id, string $name, string $data_target_fields_ids): string
    {
        return '';
    }

    protected function fetchFieldValue(Tracker_FormElement_Field_List_Value $value, $name, $is_selected)
    {
        if ($value->getId() == Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID) {
            return '';
        }
        $id      = $value->getId();
        $html    = '';
        $checked = $is_selected ? 'checked="checked"' : '';

        $html .= '<label class="checkbox" for="cb_' . $id . '" data-test="checkbox-field-value"><input type="hidden" ' . $name . ' value="0"  />';
        $html .= '<input type="checkbox" data-test="checkbox-field-input" ' . $name . ' value="' . $id . '" id=cb_' . $id . ' ' . $checked . ' valign="middle" />';
        $html .= $this->getBind()->formatChangesetValueWithoutLink($value) . '</label>';

        return $html;
    }

    protected function fetchArtifactValueReadOnlyForMail(Artifact $artifact, Tracker_Artifact_ChangesetValue $value): string
    {
        return parent::fetchArtifactValueReadOnly($artifact, $value);
    }

    /**
     * Fetch the html code to display the field value in artifact in read only mode
     */
    public function fetchArtifactValueReadOnly(Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null): string
    {
        $selected_values_ids = ($value && $value instanceof Tracker_Artifact_ChangesetValue_List)
            ? array_keys($value->getListValues())
            : [];
        $visible_values      = $this->getBind()->getAllVisibleValues();
        if (empty($visible_values)) {
            return $this->getNoValueLabel();
        }
        if (count($visible_values) === 1 && isset($visible_values[Tracker_FormElement_Field_List_Bind::NONE_VALUE])) {
            return $this->getNoValueLabel();
        }
        $html = '<ul class="tracker-read-only-checkbox-list">';
        foreach ($visible_values as $bind_id => $bind_value) {
            if ($bind_id == Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID) {
                continue;
            }
            $checked = in_array($bind_id, $selected_values_ids);
            $html   .= '<li>';
            $html   .= '<span class="tracker-read-only-checkbox-list-item">' . ($checked ? '[x]' : '[ ]') . '</span> ' . $this->getBind()->formatChangesetValueWithoutLink($bind_value);
            $html   .= '</li>';
        }
        $html .= '</ul>';
        return $html;
    }

    protected function fetchFieldContainerEnd()
    {
        return '';
    }

    /**
     * @see Tracker_FormElement_Field::hasChanges()
     */
    public function hasChanges(Artifact $artifact, Tracker_Artifact_ChangesetValue $previous_changesetvalue, $new_value)
    {
        return parent::hasChanges($artifact, $previous_changesetvalue, $this->filterZeroWhenArray($new_value));
    }

    public function isNone($value)
    {
        return parent::isNone($this->filterZeroWhenArray($value));
    }

    private function filterZeroWhenArray($values)
    {
        return is_array($values) ? array_filter($values) : $values;
    }

    public static function getFactoryLabel()
    {
        return dgettext('tuleap-tracker', 'Checkbox');
    }

    public static function getFactoryDescription()
    {
        return dgettext('tuleap-tracker', 'Checkbox');
    }

    public static function getFactoryIconUseIt()
    {
        return $GLOBALS['HTML']->getImagePath('ic/ui-check-box.png');
    }

    public static function getFactoryIconCreate()
    {
        return $GLOBALS['HTML']->getImagePath('ic/ui-check--plus.png');
    }

    /**
     * Change the type of the checkbox
     * @param string $type the new type
     *
     * @return bool true if the change is allowed and successful
     */
    public function changeType($type)
    {
        if (in_array($type, ['sb', 'msb', 'rb'])) {
            // We should remove the entry in msb table
            // However we keep it for the case where admin changes its mind.
            return true;
        }
        return false;
    }

    public function accept(Tracker_FormElement_FieldVisitor $visitor)
    {
        return $visitor->visitCheckbox($this);
    }

    public function checkValueExists(?string $value_id): bool
    {
        return $value_id === self::NOT_INDICATED_VALUE || parent::checkValueExists($value_id);
    }
}
