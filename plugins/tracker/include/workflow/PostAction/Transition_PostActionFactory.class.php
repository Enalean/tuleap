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
require_once('CIBuild/Transition_PostAction_CIBuild.class.php');
require_once('Field/dao/Transition_PostAction_Field_DateDao.class.php');
require_once('Field/dao/Transition_PostAction_Field_IntDao.class.php');
require_once('Field/dao/Transition_PostAction_Field_FloatDao.class.php');
require_once('CIBuild/Transition_PostAction_CIBuildDao.class.php');
require_once 'Transition_PostAction_NotFoundException.class.php';
require_once 'Field/Transition_PostAction_FieldFactory.class.php';
require_once 'CIBuild/Transition_PostAction_CIBuildFactory.class.php';

/**
 * class Transition_PostActionFactory
 * 
 */
class Transition_PostActionFactory {
    
    /** @return \Transition_PostAction_FieldFactory */
    public function getFieldFactory() {
        return new Transition_PostAction_FieldFactory(
            Tracker_FormElementFactory::instance(),
            new Transition_PostAction_Field_DateDao(),
            new Transition_PostAction_Field_IntDao(),
            new Transition_PostAction_Field_FloatDao()
        );
    }

    /** @return \Transition_PostAction_CIBuildFactory */
    public function getCIBuildFactory() {
        return new Transition_PostAction_CIBuildFactory(new Transition_PostAction_CIBuildDao());
    }
    
    /**
     * Get html code to let someone choose a post action for a transition
     *
     * @return string html
     */
    public function fetchPostActions() {
        $html ='';
        $html .= '<p>'.$GLOBALS['Language']->getText('workflow_admin', 'add_new_action');
        $html .= '<select name="add_postaction">';
        $html .= $this->getFieldFactory()->fetchPostActions() . $this->getCIBuildFactory()->fetchPostActions();
        $html .= '</select>';
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
        if($requested_postaction === Transition_PostAction_CIBuild::SHORT_NAME) {
            $this->getCIBuildFactory()->addPostAction($transition, $requested_postaction);
        } elseif (in_array($requested_postaction, $this->getFieldFactory()->getTypes())) {
            $this->getFieldFactory()->addPostAction($transition, $requested_postaction);
        } else {
            throw new Transition_PostAction_NotFoundException('Invalid Post Action type');
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
        $post_actions = array_merge(
            $this->getFieldFactory()->loadPostActions($transition),
            $this->getCIBuildFactory()->loadPostActions($transition)
        );
        $transition->setPostActions($post_actions);
    }
    

    
   /**
    * Save a postaction object
    * 
    * @param Transition_PostAction $post_action  the object to save
    *
    * @return void
    */
    public function saveObject(Transition_PostAction $post_action) {
        
        if($post_action instanceof Transition_PostAction_Field) {
            $this->getFieldFactory()->saveObject($post_action);
        } else {
            $this->getCIBuildFactory()->saveObject($post_action);
        }
    }
    
    /**
     * Say if a field is used in its tracker workflow transitions post actions
     *
     * @param Tracker_FormElement_Field $field The field
     *
     * @return bool
     */
    public function isFieldUsedInPostActions(Tracker_FormElement_Field $field) {
        return $this->getCIBuildFactory()->isFieldUsedInPostActions($field)
                || $this->getFieldFactory()->isFieldUsedInPostActions($field);
    }
    
    /**
     * Delete a workflow
     *
     * @param int $workflow_id the id of the workflow
     * 
     */
    public function deleteWorkflow($workflow_id) {
        return $this->getCIBuildFactory()->deleteWorkflow($workflow_id)
                && $this->getFieldFactory()->deleteWorkflow($workflow_id);
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
        $this->getCIBuildFactory()->duplicate($from_transition_id, $to_transition_id, $postactions, $field_mapping);
        $this->getFieldFactory()->duplicate($from_transition_id, $to_transition_id, $postactions, $field_mapping);
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
    public function getInstanceFromXML($xml, &$xmlMapping, Transition $transition) {
        if($this->getFieldFactory()->getInstanceFromXML($xml, $xmlMapping, $transition) instanceof Transition_PostAction_Field) {
            return $this->getFieldFactory()->getInstanceFromXML($xml, $xmlMapping, $transition);
        }

        if($this->getCIBuildFactory()->getInstanceFromXML($xml, $xmlMapping, $transition) instanceof Transition_PostAction_CIBuild) {
            return $this->getCIBuildFactory()->getInstanceFromXML($xml, $xmlMapping, $transition);
        }

        return null;
    }
}
?>
