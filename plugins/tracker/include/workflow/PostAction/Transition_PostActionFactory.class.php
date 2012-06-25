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
require_once('Field/Transition_PostAction_Field_Int.class.php');
require_once('Field/Transition_PostAction_Field_Float.class.php');
require_once('Field/dao/Transition_PostAction_Field_DateDao.class.php');
require_once('Field/dao/Transition_PostAction_Field_IntDao.class.php');
require_once('Field/dao/Transition_PostAction_Field_FloatDao.class.php');

class Transition_InvalidPostActionException extends Exception {}

/**
 * class Transition_PostActionFactory
 * 
 */
class Transition_PostActionFactory {
    
    /**
     * @var Array of available post actions classes
     */
    protected $post_actions_classes = array(
        'field_date'    => 'Transition_PostAction_Field_Date',
        'field_int' => 'Transition_PostAction_Field_Int',
        'field_float' => 'Transition_PostAction_Field_Float',
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
            $this->getDao($requested_postaction)->create($transition->getTransitionId());
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
    /*public function removePostAction(Transition $transition, $requested_postaction) {
        if (isset($this->post_actions_classes[$requested_postaction])) {
            $this->getDao()->delete($postaction_id);
            // FIXME: Where does $postaction_id come from ?
        }
    }*/
    
    /**
     * Delete post actions for the transition
     *
     * @param Transition $transition           On wich transition we should add the post action
     * @param string     $requested_postaction The type of post action
     *
     * @return void
     */
    /*public function deletePostAction(Transition $transition) {
        $this->getDao()->deletePostActionsByTransitionId($transition->transition_id);
        // FIXME: Is this code used somewhere ?
    }*/
    
    /**
     * Wrapper for Transition_PostAction_Field_DateDao
     *
     * @return Transition_PostAction_Field_DateDao
     */
    protected function getDao($postaction=null) {
        if ($postaction == 'field_int') {
            return new Transition_PostAction_Field_IntDao();
        } else if ($postaction == 'field_float') {
            return new Transition_PostAction_Field_FloatDao();
        }else {
            return new Transition_PostAction_Field_DateDao();
        }
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
        
        foreach ($this->post_actions_classes as $shortname => $klass) {
            foreach($this->loadPostActionRows($transition, $shortname) as $row) {
                $post_actions[] = $this->buildPostAction($transition, $row, $shortname, $klass);
            }
        }
        
        $transition->setPostActions($post_actions);
    }
    
    /**
     * Reconstitute a PostAction from database
     * 
     * @param Transition $transition The transition to which this PostAction is associated
     * @param mixed      $row        The raw data (array-like)
     * @param string     $shortname  The PostAction short name
     * @param string     $klass      The PostAction class name
     * 
     * @return Transition_PostAction
     */
    private function buildPostAction(Transition $transition, $row, $shortname, $klass) {
        $id        = (int)$row['id'];
        $field     = $this->getFormElementFactory()->getFormElementById((int)$row['field_id']);
        $value     = (int)$row[$this->getPostActionValueColumn($shortname)];
        if ($shortname == 'field_float') {
            $value     = (float)$row[$this->getPostActionValueColumn($shortname)];
        }
        
        return new $klass($transition, $id, $field, $value);
    }
    
    /**
     * Retrieves matching PostAction database records.
     * 
     * @param Transition $transition The Transition to which the PostActions must be associated
     * @param string     $shortname  The PostAction type (short name, not class name)
     * 
     * @return DataAccessResult
     */
    private function loadPostActionRows($transition, $shortname) {
        $dao = $this->getDao($shortname);
        return $dao->searchByTransitionId($transition->getTransitionId());
    }
    
    /**
     * The name of the database column holding the PostAction "value".
     * 
     * @param string $shortname The PostAction type shortname.
     * 
     * @return string
     */
    private function getPostActionValueColumn($shortname) {
        if ($shortname == 'field_date') {
            return 'value_type';
        }
        return 'value';
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
        $xml_tag_name          = $xml->getName();
        $post_action_class     = $this->getPostActionClassFromXmlTagName($xml_tag_name);
        $field_id              = $xmlMapping[(string)$xml->field_id['REF']];
        $postaction_attributes = $xml->attributes();
        $value                 = $this->getPostActionValueFromXmlTagName($xml_tag_name, $postaction_attributes);
        
        if ($field_id) {
            return new $post_action_class($transition, 0, $field_id, $value);
        }
    }
    
    private function getPostActionClassFromXmlTagName($xml_tag_name) {
        switch($xml_tag_name) {
            case 'postaction_field_date':
                return 'Transition_PostAction_Field_Date';
            case 'postaction_field_int':
                return 'Transition_PostAction_Field_Int';
            case 'postaction_field_float':
                return 'Transition_PostAction_Field_Float';
            default:
                throw new Transition_InvalidPostActionException($xml_tag_name);
        }
    }
    
    private function getPostActionValueFromXmlTagName($xml_tag_name, $postaction_attributes) {
        switch($xml_tag_name) {
            case 'postaction_field_date':
                return (int) $postaction_attributes['valuetype'];
            case 'postaction_field_int':
                return (int) $postaction_attributes['value'];
            case 'postaction_field_float':
                return (float) $postaction_attributes['value'];
        }
    }
    
   /**
    * Save a postaction object
    * 
    * @param Transition_PostAction $post_action  the object to save
    *
    * @return void
    */
    public function saveObject($post_action) {
        $dao   = $this->getDao($post_action->getShortName());
        
        $dao->save($post_action->getTransition()->getTransitionId(),
                   $post_action->getFieldId(),
                   $this->getValue($post_action));
    }
    
    private function getValue(Transition_PostAction $post_action) {
        if ($post_action->getShortName() == 'field_date') {
            return $post_action->getValueType();
        }        
        return $post_action->getValue();
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
        foreach ($this->post_actions_classes as $shortname => $klass) {
            if ($this->getDao($shortname)->countByFieldId($field->getId()) > 0) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Delete a workflow
     *
     * @param int $workflow_id the id of the workflow
     * 
     */
    public function deleteWorkflow($workflow_id) {
        foreach ($this->post_actions_classes as $shortname => $klass) {
            $this->getDao($shortname)->deletePostActionsByWorkflowId($workflow_id);
        }
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
                    $this->getDao($postaction->getShortname())->duplicate($from_transition_id, $to_transition_id, $from_field_id, $to_field_id);
                }
            }
        }
    }
}
?>
