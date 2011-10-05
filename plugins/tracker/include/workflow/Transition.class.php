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

require_once('PostAction/Transition_PostAction.class.php');

class Transition {
    public $transition_id;
    public $workflow_id;
    public $from;
    public $to;
    /**
     * @var Array of Transition_PostAction
     */
    protected $post_actions = array();
    
    /**
     * @var Workflow
     */
    protected $workflow = null;
    
    /**
     * Constructor
     * 
     * @param Integer                              $transition_id Id of the transition
     * @param Integer                              $workflow_id   Id of the workflow
     * @param Tracker_FormElement_Field_List_Value $from          Source value
     * @param Tracker_FormElement_Field_List_Value $to            Destination value
     */
    public function __construct($transition_id, $workflow_id, $from, $to) {
        $this->transition_id = $transition_id;
        $this->workflow_id   = $workflow_id;
        $this->from          = $from;
        $this->to            = $to;
    }
    
    /**
     * Set workflow
     *
     * @param Workflow $workflow Workflow
     */
    public function setWorkflow(Workflow $workflow) {
        $this->workflow = $workflow;
    }
    
    public function getFieldValueFrom() {
        return $this->from;
    }
    
    public function getFieldValueTo() {
        return $this->to;
    }
    
    public function equals($transition) {
        return $transition->getFieldValueFrom() === $this->from && $transition->getFieldValueTo() === $this->to;
    }
    
    public function getTransitionId() {
        return $this->transition_id;
    }
    
    /**
     * Get parent workflow
     *
     * @return Workflow
     */
    public function getWorkflow() {
        if (!$this->workflow) {
            $this->workflow = WorkflowFactory::instance()->getWorkflow($this->workflow_id);
        }
        return $this->workflow;
    }
    
    public function displayTransitionDetails() {
    }
    
    
    /**
     * Execute actions before transition happens
     * 
     * @param Array $fields_data Request field data (array[field_id] => data)
     * 
     * @return void
     */
    public function before(&$fields_data) {
        $post_actions = $this->getPostActions();
        foreach ($post_actions as $post_action) {
            $post_action->before();
        }
    }
    
    /**
     * Set Post Actions for the transition
     * 
     * @param Array $post_actions array of Transition_PostAction
     * 
     * @return void
     */
    public function setPostActions($post_actions) {
        $this->post_actions = $post_actions;
    }
    
    /**
     * Get Post Actions for the transition
     *
     * @return Array
     */
    public function getPostActions() {
        return $this->post_actions;
    }
    
    
}
?>
