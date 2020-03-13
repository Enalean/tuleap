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


class Tracker_FormElement_Field_Radiobutton extends Tracker_FormElement_Field_Selectbox
{



    protected function fetchFieldContainerStart($id, $name)
    {
        return '';
    }

    protected function fetchFieldValue(Tracker_FormElement_Field_List_Value $value, $name, $is_selected)
    {
        if ($value->getId() == Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID) {
            if ($this->isRequired()) {
                return '';
            }

            $is_selected = true; //Hackalert: "None" selected by default. Overrided when other value is selected
        }
        $label    = $this->getBind()->formatChangesetValueWithoutLink($value);

        if (!$name) {
            $name = 'name="admin"';
        }

        $id       = $value->getId();
        $html     = '';
        $checked  = $is_selected ? 'checked="checked"' : '';
        $required = $this->isRequired() ? 'required' : '';

        $html .= '<div class="val_' . $id . '">';
        $html .= '    <label class="radio" for="rb_' . $id . '" >';
        $html .= '        <input type="radio" ' . $name . ' value="' . $id . '" id=rb_' . $id . ' ' . $checked . ' ' . $required . ' valign="middle" />';
        $html .= '    ' . $label . '</label>';
        $html .= '</div>';

        return $html;
    }

    protected function fetchFieldContainerEnd()
    {
        return '';
    }

    /**
     * @see Tracker_FormElement_Field::hasChanges()
     */
    public function hasChanges(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $previous_changesetvalue, $new_value)
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
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'radiobtn');
    }

    public static function getFactoryDescription()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'radiobtn_desc');
    }

    public static function getFactoryIconUseIt()
    {
        return $GLOBALS['HTML']->getImagePath('ic/ui-radio-buttons.png');
    }

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
    public function changeType($type)
    {
        if (in_array($type, array('msb', 'cb'))) {
            //do not change from SB to MSB if the field is used to define the workflow
            $wf = WorkflowFactory::instance();
            return !$wf->isWorkflowField($this);
        } elseif ($type === 'sb') {
            return true;
        }
        return false;
    }

    public function accept(Tracker_FormElement_FieldVisitor $visitor)
    {
        return $visitor->visitRadiobutton($this);
    }
}
