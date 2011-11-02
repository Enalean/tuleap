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
require_once('PostAction/Transition_PostActionFactory.class.php');

class TransitionFactory {
    
    protected function __construct() {
    }
    
    /**
     * Hold an instance of the class
     */
    protected static $_instance;
    
    /**
     * The singleton method
     * 
     * @return TransitionFactory
     */
    public static function instance() {
        if (!isset(self::$_instance)) {
            $c = __CLASS__;
            self::$_instance = new $c;
        }
        return self::$_instance;
    } 
    
    /**
     * Build a Transition instance
     *
     * @param Array    $row      The data describing the transition
     * @param Workflow $workflow Workflow the transition belongs to
     *
     * @return Transition
     */
    public function getInstanceFromRow($row, Workflow $workflow = null) {
        if (!$workflow) {
            $workflow = WorkflowFactory::instance()->getWorkflow($row['workflow_id']);
        }
        
        $field_values = $workflow->getAllFieldValues();
        $from         = null;
        $to           = null;
        if (isset($field_values[$row['from_id']])) {
            $from = $field_values[$row['from_id']];
        }
        if (isset($field_values[$row['to_id']])) {
            $to = $field_values[$row['to_id']];
        }
        
        $transition = new Transition($row['transition_id'],
                                     $row['workflow_id'],
                                     $from,
                                     $to);
        $this->getPostActionFactory()->loadPostActions($transition);
        return $transition;
    }
    
    /**
     * @return Transition_PostActionFactory
     */
    public function getPostActionFactory() {
        return new Transition_PostActionFactory();
    }
    
    /**
    * Get a transition
    *
    * @param int transition_id The transition_id
    *
    * @return Transition
    */
    public function getTransition($transition_id) {
        $dao = $this->getDao();
        if ($row = $dao->searchById($transition_id)->getRow()) {
            return $this->getInstanceFromRow($row);
        }
        return null;
    }
    
    protected $cache_transition_id = array();
    /**
     * Get a transition id
     *
     * @param int from 
     * @param int to
     *
     * @return Transition
     */
    public function getTransitionId($from, $to) {
        $dao = $this->getDao();
        if ($from != null) {
            $from = $from->getId();
        }
        if ( ! isset($this->cache_transition_id[$from][$to]) ) {
            $this->cache_transition_id[$from][$to] = array(null);
            if ($row = $dao->searchByFromTo($from, $to)->getRow()) {
                $this->cache_transition_id[$from][$to] = array($row['transition_id']);
            }
        }
        return $this->cache_transition_id[$from][$to][0];
    }
    
    /**
     * Say if a field is used in its tracker workflow transitions
     *
     * @param Tracker_FormElement_Field $field The field
     *
     * @return bool
     */
    public function isFieldUsedInTransitions(Tracker_FormElement_Field $field) {
        return $this->getPostActionFactory()->isFieldUsedInPostActions($field);
    }
    
    /**
     * Get the Workflow Transition dao
     *
     * @return Worflow_TransitionDao
     */
    protected function getDao() {
        return new Workflow_TransitionDao();
    }
    
    /**
     * Creates a transition Object
     * 
     * @param SimpleXMLElement $xml         containing the structure of the imported workflow
     * @param array            &$xmlMapping containig the newly created formElements idexed by their XML IDs
     * 
     * @return Workflow The workflow object, or null if error
     */
    public function getInstanceFromXML($xml, &$xmlMapping) {
        
        $from = null;
        if ((string)$xml->from_id['REF'] != 'null') {
            $from = $xmlMapping[(string)$xml->from_id['REF']];
        }
        $to = $xmlMapping[(string)$xml->to_id['REF']];
        
        $transition = new Transition(0, 0, $from, $to);
        $postactions = array();
        foreach ($xml->postactions->postaction_field_date as $p) {
            
            $field_id_postaction = $xmlMapping[(string)$p->field_id['REF']];
            $postaction_attributes = $p->attributes();
            
            $tpaf = new Transition_PostActionFactory();
            $postactions[] = $tpaf->getInstanceFromXML($p, $xmlMapping, $transition);
        }
        $transition->setPostActions($postactions);
        
        //Permissions on transition
        $permissions = array();
        foreach ($xml->permissions->permission as $perm) {
            $ugroup = (string) $perm['ugroup'];
            if (isset($GLOBALS['UGROUPS'][$ugroup])) {
                $permissions[] = $GLOBALS['UGROUPS'][$ugroup];
            }
            $transition->setPermissions($permissions);
        }
        
        return $transition;
    }
    
    /**
     * Delete a workflow
     *
     * @param int the workflow_id to which belongs the transitions
     * @param int the group_id to which belongs the transitions
     * @param Array $transitions, an array of Transition
     */
    public function deleteWorkflow($workflow_id, $group_id, $transitions) {
        
        //Delete permissions
        foreach($transitions as $transition) {
            permission_clear_all($group_id, 'PLUGIN_TRACKER_WORKFLOW_TRANSITION', $transition->getTransitionId(), false);
        }
        
        //Delete postactions
        if ($this->getPostActionFactory()->deleteWorkflow($transitions)) {
            return $this->getDao()->deleteWorkflowTransitions($workflow_id);
        }
    }
}
?>