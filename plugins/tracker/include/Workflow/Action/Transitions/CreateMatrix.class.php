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


class Tracker_Workflow_Action_Transitions_CreateMatrix extends Tracker_Workflow_Action_Transitions {
    /** @var WorkflowFactory */
    private $workflow_factory;

    /** @var Tracker_FormElementFactory */
    private $form_element_factory;

    public function __construct(Tracker $tracker, WorkflowFactory $workflow_factory, Tracker_FormElementFactory $form_element_factory) {
        parent::__construct($tracker);
        $this->workflow_factory     = $workflow_factory;
        $this->form_element_factory = $form_element_factory;
    }

    private function processEnabled(Workflow $workflow, $switch_to_is_used) {
        $should_change_activation = (bool)$switch_to_is_used != (bool)$workflow->isUsed();
        if ($should_change_activation && $this->workflow_factory->updateActivation((int)$workflow->workflow_id, $switch_to_is_used)) {
            $feedback_key = 'workflow_'. ($switch_to_is_used ? 'enabled' : 'disabled');
            $GLOBALS['Response']->addFeedback(
                'info',
                $GLOBALS['Language']->getText('workflow_admin', $feedback_key),
                CODENDI_PURIFIER_DISABLED
            );
        }
    }

    public function process(Tracker_IDisplayTrackerLayout $layout, Codendi_Request $request, PFUser $current_user) {
        $workflow = $this->workflow_factory->getWorkflowByTrackerId($this->tracker->id);
        $this->processEnabled($workflow, $request->get('is_used'));

        $k=0;

        $field=$this->form_element_factory->getFormElementById($workflow->field_id);
        $field_values = $field->getBind()->getAllValues();

        $currMatrix=array();
        $field_value_from=null;
        //Add an initial state transition
        foreach($field_values as $field_value_id_to=>$field_value_to) {
           //$field_value_from=;
           $transition = '_'.$field_value_id_to;

           if ($request->existAndNonEmpty($transition)) {
               $currMatrix[]=array('', $field_value_id_to);
               $k+=$this->addTransition($workflow, $transition, $field_value_from, $field_value_to);
           }
        }

        //Add a transition
        foreach($field_values as $field_value_id_from=>$field_value_from) {
           foreach($field_values as $field_value_id_to=>$field_value_to) {
               $transition = $field_value_id_from.'_'.$field_value_id_to;
               if ($request->existAndNonEmpty($transition)) {
                   $currMatrix[]=array($field_value_id_from, $field_value_id_to);
                   $k+=$this->addTransition($workflow, $transition, $field_value_from, $field_value_to);
                }
           }
        }

        //Delete a transition
        $transitions_in_db = $workflow->getTransitions();
        $nb_transitions_in_db = count($transitions_in_db);
        for($i=0;$i<$nb_transitions_in_db ;$i++) {

            $field_value_from = $transitions_in_db[$i]->getFieldValueFrom();
            $field_value_to   = $transitions_in_db[$i]->getFieldValueTo();
             //Treatment of the initial state
            if($field_value_from==null) {
                $value_to_search=array('',$field_value_to->getId());
                //$field_value_from->getId()='';
            }else {
                $value_to_search=array($field_value_from->getId() , $field_value_to->getId());
            }

            if(!in_array($value_to_search, $currMatrix)){
                $this->workflow_factory->deleteTransition($workflow->workflow_id,  $field_value_from, $field_value_to);
                $k++;
            }
        }

        if ($k>0) {
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('workflow_admin','updated'));
        }
        $GLOBALS['Response']->redirect(
            TRACKER_BASE_URL.'/?'. http_build_query(
                array(
                    'tracker' => (int)$this->tracker->id,
                    'func'    => Workflow::FUNC_ADMIN_TRANSITIONS
                )
            )
        );
    }

    private function addTransition(Workflow $workflow, $transition, $field_value_from, $field_value_to) {
        $i=0;
        if ( ! $workflow->isTransitionExist($field_value_from, $field_value_to)) {
            if ($this->workflow_factory->addTransition((int)$workflow->workflow_id, $transition)) {
                $i++;
            }
         }
         return $i;
    }

}

?>
