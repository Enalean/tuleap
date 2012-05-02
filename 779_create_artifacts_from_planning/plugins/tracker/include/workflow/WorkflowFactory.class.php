<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('Workflow.class.php');
require_once('Transition.class.php');
require_once('Workflow_Dao.class.php');
require_once('Workflow_TransitionDao.class.php');
require_once('common/permission/PermissionsManager.class.php');


class WorkflowFactory {
    
    protected function __construct() {
    }
    
    /**
     * Hold an instance of the class
     */
    protected static $_instance;
    
    /**
     * The singleton method
     * 
     * @return WorkflowFactory
     */
    public static function instance() {
        if (!isset(self::$_instance)) {
            $c = __CLASS__;
            self::$_instance = new $c;
        }
        return self::$_instance;
    } 
    
    /**
     * Build a Workflow instance
     *
     * @param array $row The data describing the workflow
     *
     * @return Tracker_Workflow
     */
    public function getInstanceFromRow($row) {
        return new Workflow($row['workflow_id'],
                                          $row['tracker_id'],
                                          $row['field_id'],
                                          $row['is_used']);
    }
    
    public function getWorkflow($workflow_id) {
        if ($row = $this->getDao()->searchById($workflow_id)->getRow()) {
            return $this->getInstanceFromRow($row);
        }
        return null;
    }
    
    
    /**
     * Create a workflow
     *
     * @param Tracker $tracker_id The tracker the workflow refers to
     * @param string $field_id the field_id on which the workflow applies
     *
     * @return int the id of the workflow. False if error
     */
    public function create($tracker_id, $field_id) {
        return $this->getDao()->create($tracker_id, $field_id);
    }
    
    /**
     * Update workflow activation
     *
     * @param int $workflow_id the workflow id
     * @param int $is_used 1 if enable, 0 otherwise.
     *
     * @return int the id of the workflow. False if error
     */
    public function updateActivation($workflow_id, $is_used) {
        return $this->getDao()->updateActivation($workflow_id, $is_used);
    }
    
    /**
     * Delete a workflow
     *
     * @param int $workflow_id the workflow id
     */
    public function delete($workflow_id) {
        return $this->getDao()->delete($workflow_id);
    }
    
    /**
     * Delete a workflow
     *
     * @param int $workflow_id the workflow id
     */
    public function deleteWorkflow($workflow_id) {
        $workflow = $this->getWorkflow($workflow_id);
        if ($this->getTransitionFactory()->deleteWorkflow($workflow)) {
            return $this->delete($workflow_id);
        }
    }
    
    /**
     * Add a transition
     *
     * @param int $workflow_id The workflow id
     * @param string $transition the transition to insert
     * 
     * @return int the id of the transition. False if error
     */
    public function addTransition($workflow_id, $transition) {
        $values = explode("_",$transition);
        $from = $values[0];
        $to = $values[1];
        return $this->getTransitionDao()->addTransition($workflow_id, $from, $to);
    }
    
    /**
     * Get a transition id
     *
     * @param int $workflow_id The workflow id
     * @param string $transition the transition to insert
     * 
     * @return int the id of the transition. False if error
     */
     public function getTransitionId($workflow_id, $transition) {
        $values = explode("_",$transition);
        $from = $values[0];
        $to = $values[1];
        return $this->getTransitionDao()->searchTransitionId($workflow_id, $from, $to);
    }
    
     /**
     * Get a workflow id
     *
     * @param int transition_id
     *
     * @return int the id of the workflow. False if error
     */
     public function getWorkflowId($transition_id) {
        return $this->getTransitionDao()->getWorkflowId($transition_id);
    }

    /**
     * Delete a transition
     *
     * @param int $workflow_id The workflow id
     * @param string $from the transition to insert
     * @param string $to the transition to insert
     */
    public function deleteTransition($workflow_id, $from, $to) {
        if($from==null) {
            return $this->getTransitionDao()->deleteTransition($workflow_id, null, $to->getId());
        }else {
            return $this->getTransitionDao()->deleteTransition($workflow_id, $from->getId(), $to->getId());
        }
    }
    
    protected $cache_workflowfield;
    /**
     * Get the Workflow object for the tracker $tracker_id
     * 
     * @param int $tracker_id the Id of the tracker
     *
     * @return Workflow the worflow object, or null if there is no workflow
     */
    public function getWorkflowByTrackerId($tracker_id) {
        if (!isset($this->cache_workflowfield[$tracker_id])) {
            $this->cache_workflowfield[$tracker_id] = array(null);
            // only one field per workflow
            if ($row = $this->getDao()->searchByTrackerId($tracker_id)->getRow()) {
                $this->cache_workflowfield[$tracker_id] = array($this->getInstanceFromRow($row));
            }
        }
        return $this->cache_workflowfield[$tracker_id][0];
    }
    
    /**
     * Say if a field is used in its tracker workflow or post actions
     *
     * @param Tracker_FormElement_Field $field The field
     *
     * @return bool
     */
    public function isFieldUsedInWorkflow(Tracker_FormElement_Field $field) {
        return $this->isWorkflowField($field) || $this->getTransitionFactory()->isFieldUsedInTransitions($field);
    }
    
    /**
     * Say if a field is used to define a workflow
     *
     * @param Tracker_FormElement_Field $field The field
     *
     * @return bool
     */
    public function isWorkflowField(Tracker_FormElement_Field $field) {
        $workflow = $this->getWorkflowByTrackerId($field->getTracker()->getId());
        if ($workflow) {
            return $field->getId() == $workflow->getFieldId();
        }
        return false;
    }
    
    /**
     *Get the transition_id 
     * @param int the id of the field_value_from
     * @param int the id of the field_value_to
     *
     * @return int the transition_id
     */
    public function getTransitionIdFromTo($workflow_id, $field_value_from, $field_value_to) {
        return $this->getTransitionDao()->getTransitionId($workflow_id, $field_value_from, $field_value_to);
    }    
    
    /**
     * Wrapper for TransitionFactory
     *
     * @return TransitionFactory
     */
    protected function getTransitionFactory() {
        return TransitionFactory::instance();
    }
    
    /**
     * Duplicate the workflow
     * 
     * @param $from_tracker_id the template tracker id
     * @param $to_tracker_id the tracker id
     * @param $from_id the id of the field
     * @param $to_id the id of the duplicated field
     * @param Array $values array of old and new values of the field    
     * @param Array $field_mapping the field mapping
     * @param Array $ugroup_mapping the ugroup mapping
     *
     * @return void
     */
     public function duplicate($from_tracker_id, $to_tracker_id, $from_id, $to_id, $values, $field_mapping, $ugroup_mapping, $duplicate_type) {
         if ($workflow = $this->getWorkflowByTrackerId($from_tracker_id)) {
            $is_used = $workflow->getIsUsed();
            
            //Duplicate workflow
            if ($id = $this->getDao()->duplicate($to_tracker_id, $from_id, $to_id, $values, $is_used)) {
                $transitions = $workflow->getTransitions();
                //Duplicate transitions
                $this->getTransitionFactory()->duplicate($values, $id, $transitions, $field_mapping, $ugroup_mapping, $duplicate_type);
            }
         }
     }
     
     /**
     * Creates a workflow Object
     * 
     * @param SimpleXMLElement $xml         containing the structure of the imported workflow
     * @param array            &$xmlMapping containig the newly created formElements idexed by their XML IDs
     * @param Tracker          $tracker     to which the workflow is attached
     * 
     * @return Workflow The workflow object, or null if error
     */
    public function getInstanceFromXML($xml, &$xmlMapping, $tracker) {
        
        $xml_field_id = $xml->field_id;
        $xml_field_attributes = $xml_field_id->attributes();        
        $field_id = $xmlMapping[(string)$xml_field_attributes['REF']];

        $transitions = array();
        foreach($xml->transitions->transition as $t) {
            $tf = $this->getTransitionFactory();
            $transitions[] = $tf->getInstanceFromXML($t, $xmlMapping);
        }
        
        return new Workflow(0, $tracker, $field_id, $xml->is_used, $transitions);
    }
    
   /**
    * Creates new workflow in the database
    * 
    * @param Workflow $workflow The workflow to save
    * @param Tracker          $tracker  The tracker
    * 
    * @return void
    */
    public function saveObject($workflow, $tracker) {
        $workflow->setTracker($tracker);
        $dao = $this->getDao();
        $daot = $this->getTransitionDao();
        
        $workflow_id = $dao->save($workflow->tracker_id->id, $workflow->field_id->id, $workflow->is_used);
        
        //Save transitions        
        foreach($workflow->getTransitions() as $transition) {
            $tf = $this->getTransitionFactory();
            $tf->saveObject($workflow_id, $transition);
        }
    }
     
    /**
     * Get the Workflow dao
     *
     * @return Worflow_Dao
     */
    protected function getDao() {
        return new Workflow_Dao();
    }
    
    /**
     * Get the Workflow Transition dao
     *
     * @return Worflow_TransitionDao
     */
    protected function getTransitionDao() {
        return new Workflow_TransitionDao();
    }
}
?>