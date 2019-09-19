<?php
/**
 * Copyright (c) Enalean, 2011-2015. All Rights Reserved.
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

use Tuleap\Tracker\Workflow\PostAction\Visitor;

/**
 * Set the date of a field
 */
class Transition_PostAction_Field_Float extends Transition_PostAction_Field_Numeric
{//phpcs:ignore

    public const XML_TAG_NAME = 'postaction_field_float';
    public const SHORT_NAME   = 'field_float';

    /**
     * Get the shortname of the post action
     *
     * @return string
     */
    public function getShortName()
    {
        return self::SHORT_NAME;
    }

    /**
     * Get the label of the post action
     *
     * @return string
     */
    public static function getLabel()
    {
        return $GLOBALS['Language']->getText('workflow_admin', 'post_action_change_value_float_field');
    }

    /**
     * Get the html code needed to display the post action in workflow admin
     *
     * @return string html
     */
    public function fetch()
    {
        $purifier    = Codendi_HTMLPurifier::instance();
        $html        = '';
        $input_value = '<input type="text" name="workflow_postaction_field_float_value['. $purifier->purify($this->id) .
            ']" value="'.$purifier->purify($this->getValue()) .'"/>';

        //define the selectbox for date fields
        $tracker = $this->transition->getWorkflow()->getTracker();
        $tff = $this->getFormElementFactory();
        $fields_float = $tff->getUsedFormElementsByType($tracker, array('float'));

        $select_field  = '<select name="workflow_postaction_field_float['.$purifier->purify($this->id).']">';
        $options_field = '';
        $one_selected  = false;
        foreach ($fields_float as $field_float) {
            $selected = '';
            if ($this->field && ($this->field->getId() == $field_float->getId())) {
                $selected     = 'selected="selected"';
                $one_selected = true;
            }
            $options_field .= '<option value="'. $purifier->purify($field_float->getId()) .'" '. $selected.'>'.
                $purifier->purify($field_float->getLabel()).'</option>';
        }
        if (!$one_selected) {
            $select_field .= '<option value="0" '. ($this->field ? 'selected="selected"' : '') .'>' .$GLOBALS['Language']->getText('global', 'please_choose_dashed'). '</option>';
        }
        $select_field .= $options_field;
        $select_field .= '</select>';

        $html .= $GLOBALS['Language']->getText('workflow_admin', 'change_value_float_field_to', array($select_field, $input_value));
        return $html;
    }

    /**
     * @see Transition_PostAction
     */
    public function process(Codendi_Request $request)
    {
        if ($request->getInArray('remove_postaction', $this->id)) {
            $this->getDao()->deletePostAction($this->id);
        } else {
            $field_id = $this->getFieldId();
            $value    = $request->getInArray('workflow_postaction_field_float_value', $this->id);

            if ($request->validInArray('workflow_postaction_field_float', new Valid_UInt($this->id))) {
                $new_field_id = $request->getInArray('workflow_postaction_field_float', $this->id);
                $field_id = $this->getFieldIdOfPostActionToUpdate($new_field_id);
                //Check if value is an float
                $field = $this->getFormElementFactory()->getUsedFormElementById($field_id);
                if ($field) {
                    $field->validateValue($value);
                }
            }
            // Update if something changed
            if ($field_id != $this->getFieldId() || $value != $this->value) {
                $this->getDao()->updatePostAction($this->id, $field_id, $value);
            }
        }
    }

    /**
     * Export postactions date to XML
     *
     * @param SimpleXMLElement &$root     the node to which the postaction is attached (passed by reference)
     * @param array            $xmlMapping correspondance between real ids and xml IDs
     *
     * @return void
     */
    public function exportToXml(SimpleXMLElement $root, $xmlMapping)
    {
        if ($this->getFieldId()) {
            $child = $root->addChild(Transition_PostAction_Field_Float::XML_TAG_NAME);
             $child->addAttribute('value', $this->getValue());
             $child->addChild('field_id')->addAttribute('REF', array_search($this->getFieldId(), $xmlMapping));
        }
    }

    /**
     * @return \Transition_PostAction_Field_FloatDao
     */
    protected function getDao()
    {
        return new Transition_PostAction_Field_FloatDao();
    }

    public function accept(Visitor $visitor)
    {
        $visitor->visitFloatField($this);
    }
}
