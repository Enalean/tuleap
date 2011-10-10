<?php
/*
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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
require_once(dirname(__FILE__) .'/../Transition_PostAction.class.php');
require_once(dirname(__FILE__) .'/../../../Tracker/FormElement/Tracker_FormElementFactory.class.php');

/**
 * Set the date of a field
 */
class Transition_PostAction_Field_Date extends Transition_PostAction {
    
    /**
     * @const Clear the date.
     */
    const CLEAR_DATE = 1;
    
    /**
     * @const Fill the date to the current time
     */
    const FILL_CURRENT_TIME = 2;
    
    /**
     * @var Integer the type of the value. CLEAR_DATE | FILL_CURRENT_TIME
     */
    protected $value_type;
    
    /**
     * @var Integer Id of the field the post action should modify
     */
    protected $field_id;
    
    /**
     * Constructor
     *
     * @param Transition $transition The transition the post action belongs to
     * @param Integer    $id         Id of the post action
     * @param Integer    $field_id   Id of the field the post action should modify
     * @param Integer    $value_type The type of the value to set
     */
    public function __construct(Transition $transition, $id, $field_id, $value_type) {
        parent::__construct($transition, $id);
        $this->field_id   = $field_id;
        $this->value_type = $value_type;
    }
    
    /**
     * @return string The shortname of the post action
     */
    public function getShortName() {
        return 'field_date';
    }
    
    /**
     * @return string The label of the post action
     */
    public function getLabel() {
        return $GLOBALS['Language']->getText('workflow_admin','post_action_change_value_date_field');
    }
    
    /**
     * Return ID of the field updated by the post-action
     *
     * @return Integer
     */
    public function getFieldId() {
        return $this->field_id;
    }

    /**
     * Return the field associated to this post action
     *
     * @return Tracker_FormElement_Field
     */
    protected function getField() {
        if ($this->field_id) {
            return $this->getFormElementFactory()->getFormElementById($this->field_id);
        }
        return null;
    }

    /**
     * Say if the action is well defined
     *
     * @return bool
     */
    public function isDefined() {
        return $this->getField() && ($this->value_type === self::CLEAR_DATE || $this->value_type === self::FILL_CURRENT_TIME);
    }
 
    
    /**
     * Get the html code needed to display the post action in workflow admin
     *
     * @return string html
     */
    public function fetch() {
        $html = '';

        //define the selectbox for value_type
        $select_value_type = '<select name="workflow_postaction_field_date_value_type['.$this->id.']">';
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
        
        $select_field  = '<select name="workflow_postaction_field_date['.$this->id.']">';
        $options_field = '';
        $one_selected  = false;
        foreach ($fields_date as $field_date) {
            $selected = '';
            if ($this->field_id == $field_date->getId()) {
                $selected     = 'selected="selected"';
                $one_selected = true;
            }            
            $options_field .= '<option value="'. $field_date->getId() .'" '. $selected.'>'.$field_date->getLabel().'</option>';
        }
        if (!$one_selected) {
            $select_field .= '<option value="0" '. ($this->field_id == 0 ? 'selected="selected"' : '') .'>' .$GLOBALS['Language']->getText('global', 'please_choose_dashed'). '</option>';
        }
        $select_field .= $options_field;
        $select_field .= '</select>';

        $html .= $GLOBALS['Language']->getText('workflow_admin','change_value_date_field_to', array($select_field, $select_value_type));
        
        return $html;
    }
    
    /**
     * Update/Delete actions on the post-action
     *
     * @param Codendi_Request $request
     */
    public function process(Codendi_Request $request) {
        if ($request->getInArray('remove_postaction', $this->id)) {
            $this->getDao()->deletePostAction($this->id);
        } else {
            $field_id   = $this->field_id;
            $value_type = $this->value_type;
            
            // Target field
            if ($request->validInArray('workflow_postaction_field_date', new Valid_UInt($this->id))) {
                $new_field_id = $request->getInArray('workflow_postaction_field_date', $this->id);
                if ($new_field_id != $field_id) {
                    $new_field = $this->getFormElementFactory()->getUsedFormElementById($new_field_id);
                    if ($new_field) {
                        $already_used = $this->getDao()->searchByFieldId($this->transition->getTransitionId(), $new_field->getId());
                        if (count($already_used)) {
                            $this->addFeedback('error', 'workflow_admin', 'postaction_on_field_already_exist', array($new_field->getLabel()));
                        } else {
                            $field_id = $new_field->getId();
                        }
                    }
                }
            }
            
            // Value Type
            $valid_value_type = new Valid_WhiteList($this->id, array(self::CLEAR_DATE, self::FILL_CURRENT_TIME));
            if ($request->validInArray('workflow_postaction_field_date_value_type', $valid_value_type)) {
                $value_type = $request->getInArray('workflow_postaction_field_date_value_type', $this->id);
            }
            if ($field_id != $this->field_id || $value_type != $this->value_type) {
                $this->getDao()->updatePostAction($this->id, $field_id, $value_type);
            }
        }
    }
    
    /**
     * Execute actions before transition happens
     * 
     * @param Array $fields_data Request field data (array[field_id] => data)
     * @param User  $current_user The user who are performing the update
     * 
     * @return void
     */
    public function before(array &$fields_data, User $current_user) {
        // Do something only if the value_type and the date field are properly defined 
        if ($this->isDefined()) {
            $field = $this->getField();
            if ($field->userCanRead($current_user)) {
                if ($field->userCanUpdate($current_user)) {
                    if ($this->value_type === self::FILL_CURRENT_TIME) {
                        $new_date_timestamp = $field->formatDate($_SERVER['REQUEST_TIME']);
                        $this->addFeedback('info', 'workflow_postaction', 'field_date_current_time', array($field->getLabel(), $new_date_timestamp));
                    } else {
                        $new_date_timestamp = $field->formatDate(null);
                        $this->addFeedback('info', 'workflow_postaction', 'field_date_clear', array($field->getLabel()));
                    }
                    $fields_data[$this->field_id] = $new_date_timestamp;
                } else {
                    $this->addFeedback('warning', 'workflow_postaction', 'field_date_no_perms', array($field->getLabel()));
                }
            }
        }
    }
    
    /**
     * Wrapper for Transition_PostAction_Field_DateDao
     * 
     * @return Transition_PostAction_Field_DateDao
     */
    protected function getDao() {
        return new Transition_PostAction_Field_DateDao();
    }
    
    /**
     * @return Tracker_FormElementFactory
     */
    protected function getFormElementFactory() {
        return Tracker_FormElementFactory::instance();
    }
}
?>
