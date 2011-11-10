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

require_once('Field/Transition_PostAction_Field_Date.class.php');
require_once('Field/dao/Transition_PostAction_Field_DateDao.class.php');

/**
 * class Transition_PostActionFactory
 * 
 */
class Transition_PostActionFactory {
    
    /**
     * @var Array of available post actions classes
     */
    protected $post_actions_classes = array(
        'field_date' => 'Transition_PostAction_Field_Date',
    );
    
    /**
     * Get html code to let someone choose a post action for a transition
     *
     * @return string html
     */
    public function fetchPostActions() {
        $html = '';
        $html .= '<p>'.$GLOBALS['Language']->getText('workflow_admin', 'add_new_action');
        $html .= '<select name="add_postaction">';
        $html .= '<option value="" selected>--</option>';
        
        foreach ($this->post_actions_classes as $shortname => $klass) {
            //Waiting for PHP5.3 and $klass::staticMethod() and Late Static Binding
            eval("\$label = $klass::getLabel();");
            $html .= '<option value="'. $shortname .'">';
            $html .= $label;
            $html .= '</option>';
        }
        
        $html .= '</select></p>';
        return $html;
    }
    
    /**
     * Create a new post action for the transition
     *
     * @param Transition $transition           On wich transition we should add the post action
     * @param string     $requested_postaction The type of post action
     *
     * @return void
     */
    public function addPostAction(Transition $transition, $requested_postaction) {
        if (isset($this->post_actions_classes[$requested_postaction])) {
            $this->getDao()->create($transition->getTransitionId());
        }
    }
    
    /**
     * Remove a post action for the transition
     *
     * @param Transition $transition           On wich transition we should add the post action
     * @param string     $requested_postaction The type of post action
     *
     * @return void
     */
    public function removePostAction(Transition $transition, $requested_postaction) {
        if (isset($this->post_actions_classes[$requested_postaction])) {
            $this->getDao()->delete($postaction_id);
        }
    }
    
    /**
     * Delete post actions for the transition
     *
     * @param Transition $transition           On wich transition we should add the post action
     * @param string     $requested_postaction The type of post action
     *
     * @return void
     */
    public function deletePostAction(Transition $transition) {
        $this->getDao()->deletePostActionsByTransitionId($transition->transition_id);
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
     * Load the post actions that belong to a transition
     * 
     * @param Transition $transition The transition
     *
     * @return void
     */
    public function loadPostActions(Transition $transition) {
        $post_actions = array();
        foreach ($this->getDao()->searchByTransitionId($transition->getTransitionId()) as $row) {
            $field = $this->getFormElementFactory()->getFormElementById((int)$row['field_id']);
            $post_actions[] = new Transition_PostAction_Field_Date($transition, (int)$row['id'], $field, (int)$row['value_type']);
        }
        $transition->setPostActions($post_actions);
    }
    
    /**
     * Creates a postaction Object
     * 
     * @param SimpleXMLElement $xml         containing the structure of the imported postaction
     * @param array            &$xmlMapping containig the newly created formElements idexed by their XML IDs
     * @param Transition       $transition     to which the postaction is attached
     * 
     * @return Transition_PostAction The  Transition_PostAction object, or null if error
     */
    public function getInstanceFromXML($xml, &$xmlMapping, $transition) {
        
        $postaction_attributes = $xml->attributes();
        
        if ($xmlMapping[(string)$xml->field_id['REF']]) {
            $postaction = new Transition_PostAction_Field_Date($transition, 0, $xmlMapping[(string)$xml->field_id['REF']], (int) $postaction_attributes['valuetype']);
            return $postaction;
        }
    }
    
   /**
    * Save a postaction object
    * 
    * @param Transition_PostAction $postaction  the object to save
    *
    * @return void
    */
    public function saveObject($postaction) {
        if (($postaction_id = $this->getDao()->create($postaction->getTransition()->getTransitionId())) > 0) {
            $this->getDao()->updatePostAction($postaction_id, $postaction->getFieldId(), $postaction->getValueType());
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
    
    /**
     * Say if a field is used in its tracker workflow transitions post actions
     *
     * @param Tracker_FormElement_Field $field The field
     *
     * @return bool
     */
    public function isFieldUsedInPostActions(Tracker_FormElement_Field $field) {
        return count($this->getDao()->searchByFieldId($field->getId())) > 0;
    }
    
    /**
     * Delete a workflow
     *
     * @param int $workflow_id the id of the workflow
     * 
     */
    public function deleteWorkflow($workflow_id) {
        return $this->getDao()->deletePostActionsByWorkflowId($workflow_id);
    }
    
   /**
    * Duplicate postactions of a transition
    *
    * @param int $from_transition_id the id of the template transition
    * @param int $to_transition_id the id of the transition
    * @param Array $postactions 
    * @param Array $field_mapping the field mapping
    * 
    */
    public function duplicate($from_transition_id, $to_transition_id, $postactions, $field_mapping) {
        foreach ($postactions as $postaction) {
            $from_field_id = $postaction->getFieldId();
            foreach ($field_mapping as $mapping) {
                if ($mapping['from'] == $from_field_id) {
                    $to_field_id = $mapping['to'];
                    $this->getDao()->duplicate($from_transition_id, $to_transition_id, $from_field_id, $to_field_id);                    
                }
            }
        }
    }
}
?>
