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
class Transition_PostAction_Field_Float extends Transition_PostAction {
    
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
        return 'field_float';
    }
    
    /**
     * Get the label of the post action
     *
     * @return string
     */
    public static function getLabel() {
        return $GLOBALS['Language']->getText('workflow_admin', 'post_action_change_value_float_field');
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
     * Return ID of the field updated by the post-action
     *
     * @return Integer
     */
    public function getFieldId() {
        if ($this->field) {
            return $this->field->getId();
        } else {
            return 0;
        }
    }
    
    public function getValue() {
        return $this->value;
    }
    
    /**
     * Get the html code needed to display the post action in workflow admin
     *
     * @return string html
     */
    public function fetch() {
        $html = '';        
        $input_value = '<input type="text" name="workflow_postaction_field_float_value['. $this->id .']" value="'.$this->getValue().'"/>';
        
        //define the selectbox for date fields
        $tracker = $this->transition->getWorkflow()->getTracker();
        $tff = $this->getFormElementFactory();
        $fields_float = $tff->getUsedFormElementsByType($tracker, array('float'));
        
        $select_field  = '<select name="workflow_postaction_field_float['.$this->id.']">';
        $options_field = '';
        $one_selected  = false;
        foreach ($fields_float as $field_float) {
            $selected = '';
            if ($this->field && ($this->field->getId() == $field_float->getId())) {
                $selected     = 'selected="selected"';
                $one_selected = true;
            }            
            $options_field .= '<option value="'. $field_float->getId() .'" '. $selected.'>'.$field_float->getLabel().'</option>';
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
    public function process(Codendi_Request $request) {
        if ($request->getInArray('remove_postaction', $this->id)) {
            $this->getDao()->deletePostAction($this->id);
        } else {
            
            $field_id = $this->getFieldId();
            $value    = $request->getInArray('workflow_postaction_field_float_value', $this->id);

            if ($request->validInArray('workflow_postaction_field_float', new Valid_UInt($this->id))) {
                $new_field_id = $request->getInArray('workflow_postaction_field_float', $this->id);
                $field_id = $this->getFieldIdOfPostActionToUpdate($field_id, $new_field_id);
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
     * Execute actions before transition happens
     * 
     * @param Array &$fields_data Request field data (array[field_id] => data)
     * @param User  $current_user The user who are performing the update
     * 
     * @return void
     */
    public function before(array &$fields_data, User $current_user) {
        // Do something only if the value and the float field are properly defined 
        if ($this->isDefined()) {
            $field = $this->getField();
            if ($field->userCanRead($current_user)) {
                $this->addFeedback('info', 'workflow_postaction', 'field_date_current_time', array($field->getLabel(), $this->value));
            }
           
            $fields_data[$this->field->getId()] = $this->value;
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
    public function exportToXml(&$root, $xmlMapping) {
        if ($this->getFieldId()) {
             $child = $root->addChild('postaction_field_float');
             $child->addAttribute('value', $this->getValue());
             $child->addChild('field_id')->addAttribute('REF', array_search($this->getFieldId(), $xmlMapping));
         }
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
        return new Transition_PostAction_Field_FloatDao();
    }
}
?>