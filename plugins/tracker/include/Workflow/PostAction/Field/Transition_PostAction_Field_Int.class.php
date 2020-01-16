<?php
/**
 * Copyright (c) Enalean, 2011-Present. All Rights Reserved.
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

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Transition_PostAction_Field_Int extends Transition_PostAction_Field_Numeric
{

    public const XML_TAG_NAME = 'postaction_field_int';
    public const SHORT_NAME   = 'field_int';

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
        return $GLOBALS['Language']->getText('workflow_admin', 'post_action_change_value_int_field');
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
        $input_value = '<input type="text" name="workflow_postaction_field_int_value['. $purifier->purify($this->id) .
            ']" value="'.$purifier->purify($this->getValue()).'"/>';

        //define the selectbox for date fields
        $tracker = $this->transition->getWorkflow()->getTracker();
        $tff = $this->getFormElementFactory();
        $fields_int = $tff->getUsedFormElementsByType($tracker, array('int'));

        $select_field  = '<select name="workflow_postaction_field_int['.$purifier->purify($this->id).']">';
        $options_field = '';
        $one_selected  = false;
        foreach ($fields_int as $field_int) {
            $selected = '';
            if ($this->field && ($this->field->getId() == $field_int->getId())) {
                $selected     = 'selected="selected"';
                $one_selected = true;
            }
            $options_field .= '<option value="'. $purifier->purify($field_int->getId()) .'" '. $selected.'>'.
                $purifier->purify($field_int->getLabel()).'</option>';
        }
        if (!$one_selected) {
            $select_field .= '<option value="0" '. ($this->field ? 'selected="selected"' : '') .'>' .$GLOBALS['Language']->getText('global', 'please_choose_dashed'). '</option>';
        }
        $select_field .= $options_field;
        $select_field .= '</select>';

        $html .= $GLOBALS['Language']->getText('workflow_admin', 'change_value_int_field_to', array($select_field, $input_value));
        return $html;
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
            $child = $root->addChild(Transition_PostAction_Field_Int::XML_TAG_NAME);
             $child->addAttribute('value', $this->getValue());
             $child->addChild('field_id')->addAttribute('REF', array_search($this->getFieldId(), $xmlMapping));
        }
    }

    /**
     * @return \Transition_PostAction_Field_IntDao
     */
    protected function getDao()
    {
        return new Transition_PostAction_Field_IntDao();
    }

    public function accept(Visitor $visitor)
    {
        $visitor->visitIntField($this);
    }
}
