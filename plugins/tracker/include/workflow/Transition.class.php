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
    
    /**
     * @var Tracker_FormElement_Field_List_Value
     */
    protected $from = null;
    
    /**
     * @var Tracker_FormElement_Field_List_Value
     */
    protected $to   = null;
    
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
    
    /**
     * Access From field value
     * 
     * @return Tracker_FormElement_Field_List_Value
     */
    public function getFieldValueFrom() {
        return $this->from;
    }
    
    /**
     * Access To field value
     * 
     * @return Tracker_FormElement_Field_List_Value
     */
    public function getFieldValueTo() {
        return $this->to;
    }
    
    /**
     * Says if this transition is equals to another transtion
     *
     * @param Transition $transition The transition to compare
     *
     * @return bool
     */
    public function equals(Transition $transition) {
        $source_from = $this->getFieldValueFrom();
        $source_to   = $this->getFieldValueTo();
        $target_from = $transition->getFieldValueFrom();
        $target_to   = $transition->getFieldValueTo();
        
        return is_null($source_from) && is_null($target_from) && is_null($source_to) && is_null($target_to)
                
            || is_null($source_from) && is_null($target_from) && !is_null($source_to) && !is_null($target_to) 
                && $source_to->getId() === $target_to->getId()
                
            || !is_null($source_from) && !is_null($target_from) && is_null($source_to) && is_null($target_to) 
                && $source_from->getId() === $target_from->getId()
                
            || !is_null($source_from) && !is_null($target_from) && !is_null($source_to) && !is_null($target_to) 
                && $source_from->getId() === $target_from->getId() && $source_to->getId() === $target_to->getId();
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
     * @param User  $current_user The user who are performing the update
     * 
     * @return void
     */
    public function before(&$fields_data, User $current_user) {
        $post_actions = $this->getPostActions();
        foreach ($post_actions as $post_action) {
            $post_action->before($fields_data, $current_user);
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
    
    /**
     * Get the html code needed to display the post actions in workflow admin
     *
     * @return string html
     */
    public function fetchPostActions() {
        $hp   = Codendi_HTMLPurifier::instance();
        $html = '';
        if ($post_actions = $this->getPostActions()) {
            $html .= '<table class="workflow_actions" width="100%" cellpadding="0" cellspacing="10">';
            foreach ($post_actions as $pa) {
                $classname = 'workflow_actions_'. $pa->getShortName();
                $html .= '<tr><td>';

                // the action itself
                $html .= '<div class="'. $hp->purify($classname) .'">';
                if (!$pa->isDefined()) {
                    $html .= '<div class="alert-message block-message warning">'. $GLOBALS['Language']->getText('workflow_admin', 'post_action_not_defined') .'</div>';
                }
                $html .= $pa->fetch();
                $html .= '</div>';
                $html .= '</td><td>';

                // the delete buttton
                $html .= '<input type="hidden" name="remove_postaction['. (int)$pa->getId() .']" value="0" />';
                $html .= '<label class="pc_checkbox" title="'. $hp->purify($GLOBALS['Language']->getText('workflow_admin','remove_postaction')) .'">&nbsp';
                $html .= '<input type="checkbox" name="remove_postaction['. (int)$pa->getId() .']" value="1" />';
                $html .= '</label>';
                
                $html .= '</td></tr>';
            }
            $html .= '</table>';
        } else {
            $html .= '<p><i>'. $GLOBALS['Language']->getText('workflow_admin', 'no_postaction') .'</i></p>';
        }
        return $html;
    }
}
?>
