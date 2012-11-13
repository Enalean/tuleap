<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once(dirname(__FILE__) . '/../Condition.class.php');

class Workflow_Transition_Condition_FieldNotEmpty extends Workflow_Transition_Condition {

    const CONDITION_TYPE        = 'notempty';
    
    private $field_id = null;
    
    public function __construct(Transition $transition) {
        parent::__construct($transition);
        $this->formElementFactory = Tracker_FormElementFactory::instance();
    }
    
    /**
     * @see Workflow_Transition_Condition::fetch()
     * @return string The field wrapped in Html 
     */
    public function fetch() {
        $html  = '';
        $html .= $GLOBALS['Language']->getText('workflow_admin','label_define_transition_required_field');
        $html .= '<br />';
        $html .= $GLOBALS['Language']->getText('workflow_admin', 'the_field') . ' ';
        $html .= '<select name="add_notempty_condition">';
        $html .= '<option value="0" '. 
            (($this->field_id === null) ? 'selected="selected"' : '')
            . '>' . $GLOBALS['Language']->getText('global', 'please_choose_dashed'). '</option>';

        foreach($this->getFields() as $field){
            $html .= '<option value="' . $field->getId() . '"';
            
            if($this->field_id !== null && $this->field_id === $field->getId()){ 
                $html .=  'selected="selected"';
            }
            
            $html .= '>';
            $html .= $field->getLabel();   
            $html .= '</option>';
        }
        $html .= '</select>';
        
        $html .= ' ' . $GLOBALS['Language']->getText('workflow_admin', 'field_not_empty');

        return $html;
    }

    /**
     * @see Workflow_Transition_Condition::exportToXml()
     */
    public function exportToXml(&$root, $xmlMapping) {
        $root->addAttribute('type', self::CONDITION_TYPE);
    }

    /**
     * @see Workflow_Transition_Condition::saveObject()
     */
    public function saveObject() {

    }
    
    public function setFieldId($field_id) {
        $this->field_id = $field_id;
        return $this;
    }
    
    /**
     * @return 
     */
    private function getFields(){
        $tracker = $this->transition->getWorkflow()->getTracker();
        return $this->formElementFactory->getUsedFields($tracker);
    }
    
    /**
     * @return 
     */
    public function validate($fields_data) {
        $field = $this->formElementFactory->getUsedFormElementById($this->field_id);
        $value = $fields_data[$this->field_id];
        $is_valid = ! $field->isEmpty($value);
        
        if (! $is_valid) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('workflow_condition', 'invalid_condition', $field->getLabel(). ' ('. $field->getName() .')'));

        }
        return $is_valid;
    }
}
?>
