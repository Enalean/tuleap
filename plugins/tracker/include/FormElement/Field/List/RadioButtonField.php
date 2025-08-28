<?php
/**
 * Copyright (c) Enalean, 2014-present. All Rights Reserved.
 * Copyright (c) Jtekt, Jason Team, 2014. All rights reserved
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

use Override;
use Tracker_Artifact_ChangesetValue;
use Tracker_FormElement_Field_List_Bind_StaticValue_None;
use Tracker_FormElement_Field_List_Value;
use Tracker_FormElement_FieldVisitor;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use WorkflowFactory;

final class RadioButtonField extends SelectboxField
{
    #[Override]
    protected function fetchFieldContainerStart(string $id, string $name, string $data_target_fields_ids): string
    {
        return '';
    }

    #[Override]
    protected function fetchFieldValue(Tracker_FormElement_Field_List_Value $value, $name, $is_selected)
    {
        if ($value->getId() == Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID) {
            if ($this->isRequired()) {
                return '';
            }

            $is_selected = true; //Hackalert: "None" selected by default. Overrided when other value is selected
        }
        $label = $this->getBind()->formatChangesetValueWithoutLink($value);

        if (! $name) {
            $name = 'name="admin"';
        }

        $id       = $value->getId();
        $html     = '';
        $checked  = $is_selected ? 'checked="checked"' : '';
        $required = $this->isRequired() ? 'required' : '';

        $html .= '<div class="val_' . $id . '">';
        $html .= '    <label class="radio" for="rb_' . $id . '" data-test="radiobutton-field-value">';
        $html .= '        <input data-test="radiobutton-field-input" type="radio" ' . $name . ' value="' . $id . '" id=rb_' . $id . ' ' . $checked . ' ' . $required . ' valign="middle" />';
        $html .= '    ' . $label . '</label>';
        $html .= '</div>';

        return $html;
    }

    #[Override]
    protected function fetchFieldContainerEnd()
    {
        return '';
    }

    /**
     * @see TrackerField::hasChanges()
     */
    #[Override]
    public function hasChanges(Artifact $artifact, Tracker_Artifact_ChangesetValue $old_value, $new_value)
    {
        return parent::hasChanges($artifact, $old_value, $this->filterZeroWhenArray($new_value));
    }

    #[Override]
    public function isNone($value)
    {
        return parent::isNone($this->filterZeroWhenArray($value));
    }

    private function filterZeroWhenArray($values)
    {
        return is_array($values) ? array_filter($values) : $values;
    }

    #[Override]
    public static function getFactoryLabel()
    {
        return dgettext('tuleap-tracker', 'Radio button');
    }

    #[Override]
    public static function getFactoryDescription()
    {
        return dgettext('tuleap-tracker', 'Radio button');
    }

    #[Override]
    public static function getFactoryIconUseIt()
    {
        return $GLOBALS['HTML']->getImagePath('ic/ui-radio-buttons.png');
    }

    #[Override]
    public static function getFactoryIconCreate()
    {
        return $GLOBALS['HTML']->getImagePath('ic/ui-radio-buttons-plus.png');
    }

    /**
     * Change the type of the button
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
        } elseif ($type === 'sb') {
            return true;
        }
        return false;
    }

    #[Override]
    public function accept(Tracker_FormElement_FieldVisitor $visitor)
    {
        return $visitor->visitRadiobutton($this);
    }
}
