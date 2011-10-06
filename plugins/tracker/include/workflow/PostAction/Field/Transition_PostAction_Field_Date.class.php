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
     * @var Transition the transition
     */
    protected $transition;
    
    /**
     * @var Integer Id of the post action
     */
    protected $id;
    
    /**
     * Constructor
     *
     * @param Transition $transition The transition the post action belongs to
     * @param Integer    $id         Id of the post action
     * @param Integer    $field_id   Id of the field the post action should modify
     * @param Integer    $value_type The type of the value to set
     */
    public function __construct(Transition $transition, $id, $field_id, $value_type) {
        $this->transition = $transition;
        $this->id         = $id;
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
        //TODO: i18n
        return 'Change the value of a date field';
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
     * Get the html code needed to display the post action in workflow admin
     *
     * @return string html
     */
    public function fetch() {
        $html = '';
        $select = '<select name="workflow_postaction_field_date_value_type['.$this->id.']">';
        $select .= '<option value="0" '. ($this->value_type == 0 ? 'selected="selected"' : '') .'>' .$GLOBALS['Language']->getText('global', 'none'). '</option>';
        $select .= '<option value="'. (int)self::CLEAR_DATE .'" '. ($this->value_type === self::CLEAR_DATE ? 'selected="selected"' : '') .'>empty</option>';
        $select .= '<option value="'. (int)self::FILL_CURRENT_TIME .'" '. ($this->value_type === self::FILL_CURRENT_TIME ? 'selected="selected"' : '') .'>the current date</option>';
        $select .= '</select>';
        
        $tracker = $this->transition->getWorkflow()->getTracker();
        $tff = Tracker_FormElementFactory::instance();
        $fields_date = $tff->getUsedFormElementsByType($tracker, array('date'));
        
        $select_field = '<select name="workflow_postaction_field_date['.$this->id.']">';
        $select_field .= '<option value="0" '. ($this->field_id == 0 ? 'selected="selected"' : '') .'>' .$GLOBALS['Language']->getText('global', 'none'). '</option>';
        foreach ($fields_date as $field_date) {
            $selected = $this->field_id == $field_date->getId() ? 'selected="selected"' : '';
            $select_field .= '<option value="'. $field_date->getId() .'" '. $selected.'>'.$field_date->getLabel().'</option>';
        }
        $select_field .= '</select>';
        $html .= 'Change the value of the date field '. $select_field .' to '. $select;
        return $html;
    }
    
    /**
     * Update/Delete actions on the post-action
     *
     * @param Codendi_Request $request
     */
    public function process(Codendi_Request $request) {
        if ($request->getInArray('workflow_postaction_field_date', $this->id)) {
            $field_id   = $this->field_id;
            $value_type = $this->value_type;
            
            // Target field
            if ($request->validInArray('workflow_postaction_field_date', new Valid_UInt($this->id))) {
                $new_field_id = $request->getInArray('workflow_postaction_field_date', $this->id);
                if ($new_field_id != $field_id) {
                    $field = Tracker_FormElementFactory::instance()->getUsedFormElementById($new_field_id);
                    if ($field) {
                        $field_id = $field->getId();
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
     * 
     * @return void
     */
    public function before(array &$fields_data) {
        $new_date_timestamp = null;
        if ($this->value_type === self::FILL_CURRENT_TIME) {
            $new_date_timestamp = $_SERVER['REQUEST_TIME'];
        }
        $fields_data[$this->field_id] = Tracker_Artifact_ChangesetValue_Date::formatDate($new_date_timestamp);
    }
    
    /**
     * Wrapper for Transition_PostAction_Field_DateDao
     * 
     * @return Transition_PostAction_Field_DateDao
     */
    protected function getDao() {
        return new Transition_PostAction_Field_DateDao();
    }
}
?>
