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
class Transition_PostAction_Field_Date extends Transition_PostAction_Field
{//phpcs:ignore

    /**
     * @const Clear the date.
     */
    public const CLEAR_DATE = 1;

    /**
     * @const Fill the date to the current time
     */
    public const FILL_CURRENT_TIME = 2;

    public const SHORT_NAME   = 'field_date';
    public const XML_TAG_NAME = 'postaction_field_date';

    /**
     * @var int the type of the value. CLEAR_DATE | FILL_CURRENT_TIME
     */
    protected $value_type;

    /**
     * Constructor
     *
     * @param Transition                   $transition The transition the post action belongs to
     * @param int $id Id of the post action
     * @param Tracker_FormElement_Field    $field      The field the post action should modify
     * @param int $value_type The type of the value to set
     */
    public function __construct(Transition $transition, $id, $field, $value_type)
    {
        parent::__construct($transition, $id, $field);
        $this->value_type = $value_type;
    }

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
     * Get the value type of the post action
     *
     * @return int
     */
    public function getValueType()
    {
        return $this->value_type;
    }

    /**
     * Get the label of the post action
     *
     * @return string
     */
    public static function getLabel()
    {
        return $GLOBALS['Language']->getText('workflow_admin', 'post_action_change_value_date_field');
    }

    /**
     * Say if the action is well defined
     *
     * @return bool
     */
    public function isDefined()
    {
        return $this->getField() && ($this->value_type === self::CLEAR_DATE || $this->value_type === self::FILL_CURRENT_TIME);
    }

    /**
     * Get the html code needed to display the post action in workflow admin
     *
     * @return string html
     */
    public function fetch()
    {
        $purifier = Codendi_HTMLPurifier::instance();
        $html     = '';

        //define the selectbox for value_type
        $select_value_type = '<select name="workflow_postaction_field_date_value_type['. $this->id .']">';
        if ($this->value_type !== self::CLEAR_DATE && $this->value_type !== self::FILL_CURRENT_TIME) {
            $select_value_type .= '<option value="0" '. ($this->value_type == 0 ? 'selected="selected"' : '') .'>' .$GLOBALS['Language']->getText('global', 'please_choose_dashed'). '</option>';
        }
        // clear
        $selected = ($this->value_type === self::CLEAR_DATE ? 'selected="selected"' : '');
        $select_value_type .= '<option value="'. (int)self::CLEAR_DATE .'" '. $selected .'>';
        $select_value_type .= $GLOBALS['Language']->getText('workflow_admin', 'post_action_field_date_empty');
        $select_value_type .= '</option>';
        // current time
        $selected = ($this->value_type === self::FILL_CURRENT_TIME ? 'selected="selected"' : '');
        $select_value_type .= '<option value="'. (int)self::FILL_CURRENT_TIME .'" '. $selected .'>';
        $select_value_type .= $GLOBALS['Language']->getText('workflow_admin', 'post_action_field_date_current_time');
        $select_value_type .= '</option>';
        $select_value_type .= '</select>';

        //define the selectbox for date fields
        $tracker = $this->transition->getWorkflow()->getTracker();
        $tff = $this->getFormElementFactory();
        $fields_date = $tff->getUsedFormElementsByType($tracker, array('date'));

        $select_field  = '<select name="workflow_postaction_field_date['.$purifier->purify($this->id).']">';
        $options_field = '';
        $one_selected  = false;
        foreach ($fields_date as $field_date) {
            $selected = '';
            if ($this->field && ($this->field->getId() == $field_date->getId())) {
                $selected     = 'selected="selected"';
                $one_selected = true;
            }
            $options_field .= '<option value="'. $purifier->purify($field_date->getId()) .'" '. $selected.'>'.$purifier->purify($field_date->getLabel()).'</option>';
        }
        if (!$one_selected) {
            $select_field .= '<option value="0" '. ($this->field ? 'selected="selected"' : '') .'>' .$GLOBALS['Language']->getText('global', 'please_choose_dashed'). '</option>';
        }
        $select_field .= $options_field;
        $select_field .= '</select>';

        $html .= $GLOBALS['Language']->getText('workflow_admin', 'change_value_date_field_to', array($select_field, $select_value_type));

        return $html;
    }

    /**
     * Update/Delete action
     *
     * @param Codendi_Request $request The user's request
     *
     * @return void
     */
    public function process(Codendi_Request $request)
    {
        if ($request->getInArray('remove_postaction', $this->id)) {
            $this->getDao()->deletePostAction($this->id);
        } else {
            $field_id     = $this->getFieldId();
            $value_type   = $this->value_type;

            // Target field
            if ($request->validInArray('workflow_postaction_field_date', new Valid_UInt($this->id))) {
                $new_field_id = $request->getInArray('workflow_postaction_field_date', $this->id);
                $field_id = $this->getFieldIdOfPostActionToUpdate($new_field_id);
            }

            // Value Type
            $valid_value_type = new Valid_WhiteList($this->id, array(self::CLEAR_DATE, self::FILL_CURRENT_TIME));
            if ($request->validInArray('workflow_postaction_field_date_value_type', $valid_value_type)) {
                $value_type = $request->getInArray('workflow_postaction_field_date_value_type', $this->id);
            }

            // Update if something changed
            if ($field_id != $this->getFieldId() || $value_type != $this->value_type) {
                $this->getDao()->updatePostAction($this->id, $field_id, $value_type);
            }
        }
    }

    /**
     * Execute actions before transition happens
     *
     * @param Array &$fields_data Request field data (array[field_id] => data)
     * @param PFUser  $current_user The user who are performing the update
     *
     * @return void
     */
    public function before(array &$fields_data, PFUser $current_user)
    {
        // Do something only if the value_type and the date field are properly defined
        if ($this->isDefined()) {
            $field = $this->getField();
            if ($this->value_type === self::FILL_CURRENT_TIME) {
                $new_date_timestamp = $field->formatDate($_SERVER['REQUEST_TIME']);
                if ($field->userCanRead($current_user)) {
                    $this->addFeedback('info', 'workflow_postaction', 'field_value_set', array($field->getLabel(), $new_date_timestamp));
                }
            } else {
                $new_date_timestamp = $field->formatDate(null);
                if ($field->userCanRead($current_user)) {
                    $this->addFeedback('info', 'workflow_postaction', 'field_clear', array($field->getLabel()));
                }
            }
            $fields_data[$this->field->getId()] = $new_date_timestamp;
            $this->bypass_permissions = true;
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
            $child = $root->addChild(Transition_PostAction_Field_Date::XML_TAG_NAME);
             $child->addAttribute('valuetype', $this->getValueType());
             $child->addChild('field_id')->addAttribute('REF', array_search($this->getFieldId(), $xmlMapping));
        }
    }

    /**
     * Wrapper for Transition_PostAction_Field_DateDao
     *
     * @return Transition_PostAction_Field_DateDao
     */
    protected function getDao()
    {
        return new Transition_PostAction_Field_DateDao();
    }

    public function accept(Visitor $visitor)
    {
        $visitor->visitDateField($this);
    }
}
