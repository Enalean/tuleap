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
     * Get the Workflow Transition dao
     *
     * @return Worflow_TransitionDao
     */
    protected function getDao() {
        return new Workflow_TransitionDao();
    }
}
?>