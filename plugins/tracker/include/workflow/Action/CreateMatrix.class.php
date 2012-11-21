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
require_once 'Abstract.class.php';

class Tracker_Workflow_Action_CreateMatrix extends Tracker_Workflow_Action_Abstract {
    
    public function process(Tracker_IDisplayTrackerLayout $layout, Codendi_Request $request, User $current_user) {
        $k=0;
        $workflow = WorkflowFactory::instance()->getWorkflowByTrackerId($this->tracker->id);

        $field=Tracker_FormElementFactory::instance()->getFormElementById($workflow->field_id);
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
                WorkflowFactory::instance()->deleteTransition($workflow->workflow_id,  $field_value_from, $field_value_to);
                $k++;
            }
        }

        if ($k>0) {
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('workflow_admin','updated'));
            $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?'. http_build_query(array('tracker' => (int)$this->tracker->id, 'func'    => 'admin-workflow')));
        } else {
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('workflow_admin','not_updated'));
            $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?'. http_build_query(array('tracker' => (int)$this->tracker->id, 'func'    => 'admin-workflow')));
        }
    }
    
    private function addTransition(Workflow $workflow, $transition, $field_value_from, $field_value_to) {
        $i=0;
        if ( ! $workflow->isTransitionExist($field_value_from, $field_value_to)) {
            if (WorkflowFactory::instance()-> addTransition((int)$workflow->workflow_id, $transition)) {
                $i++;
            }
         }
         return $i;
    }

}

?>
