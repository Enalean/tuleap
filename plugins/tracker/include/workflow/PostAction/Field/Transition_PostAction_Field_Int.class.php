<?php
/**
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
class Transition_PostAction_Field_Int extends Transition_PostAction {
    
    /**
     * @var Integer the value
     */
    protected $value;
    
    /**
     * @var Tracker_FormElement_Field The field the post action should modify
     */
    protected $field;
    
    /**
     * @var $bypass_permissions true if permissions on field can be bypassed at submission or update
     */
    protected $bypass_permissions = false;
    
    /**
     * Constructor
     *
     * @param Transition                   $transition The transition the post action belongs to
     * @param Integer                      $id         Id of the post action
     * @param Tracker_FormElement_Field    $field      The field the post action should modify
     * @param Integer                      $value      The value to set
     */
    public function __construct(Transition $transition, $id, $field, $value) {
        parent::__construct($transition, $id);
        $this->field      = $field;
        $this->value      = $value;
    }
    
    /**
     * Get the shortname of the post action
     *
     * @return string
     */
    public function getShortName() {
        return 'field_int';
    }
    
    /**
     * Get the label of the post action
     *
     * @return string
     */
    public static function getLabel() {
        return $GLOBALS['Language']->getText('workflow_admin', 'post_action_change_value_int_field');
    }

    /**
     * Get the value of bypass_permissions
     *
     * @param Tracker_FormElement_Field $field
     *
     * @return boolean
     */
    public function bypassPermissions($field) {
        return $this->getFieldId() == $field->getId() && $this->bypass_permissions;
    }
    
    /**
     * Say if the action is well defined
     *
     * @return bool
     */
    public function isDefined() {
        return $this->getField() && ($this->value != null);
    }
    
    /**
     * Return the field associated to this post action
     *
     * @return Tracker_FormElement_Field
     */
    public function getField() {
        return $this->field;
    }
    
    /**
     * Get the html code needed to display the post action in workflow admin
     *
     * @return string html
     */
    public function fetch() {
        $html = '';
        //define the selectbox for value_type
        $input_value = '<input type="text" name="workflow_postaction_field_int_value['. $this->id .']">';
        
        //define the selectbox for date fields
        $tracker = $this->transition->getWorkflow()->getTracker();
        $tff = $this->getFormElementFactory();
        $fields_int = $tff->getUsedFormElementsByType($tracker, array('int'));
        
        $select_field  = '<select name="workflow_postaction_field_int['.$this->id.']">';
        $options_field = '';
        $one_selected  = false;
        foreach ($fields_int as $field_int) {
            $selected = '';
            if ($this->field && ($this->field->getId() == $field_int->getId())) {
                $selected     = 'selected="selected"';
                $one_selected = true;
            }            
            $options_field .= '<option value="'. $field_int->getId() .'" '. $selected.'>'.$field_int->getLabel().'</option>';
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
     * @see Transition_PostAction
     */
    public function process(Codendi_Request $request) {
        $field_id = $request->getInArray('workflow_postaction_field_int', $this->id);
        $value    = $request->getInArray('workflow_postaction_field_int_value', $this->id);
        $this->getDao()->updatePostAction($this->id, $field_id, $value);
    }
    
    /**
     * Wrapper for Tracker_FormElementFactory
     *
     * @return Tracker_FormElementFactory
     */
    protected function getFormElementFactory() {
        return Tracker_FormElementFactory::instance();
    }
    
    protected function getDao() {
        return new Transition_PostAction_Field_IntDao();
    }
}
?>
