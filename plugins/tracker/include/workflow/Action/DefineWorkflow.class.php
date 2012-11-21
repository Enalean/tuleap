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

class Tracker_Workflow_Action_DefineWorkflow  extends Tracker_Workflow_Action_Abstract {
    
    public function process(Tracker_IDisplayTrackerLayout $layout, Codendi_Request $request, User $current_user) {
        $hp = Codendi_HTMLPurifier::instance();
        $workflow = WorkflowFactory::instance()->getWorkflowByTrackerId($this->tracker->id);
        $this->displayHeader($layout);

        if (count($workflow)) {
            $this->displayAdminWorkflow($layout, $request, $current_user, $workflow);
        } else {
            //Display creation form
            echo '<p>';
            echo $GLOBALS['Language']->getText('workflow_admin','choose_field');
            echo '<p>';
            $aff =Tracker_FormElementFactory::instance();

            echo '<form action="'.TRACKER_BASE_URL.'/?'. http_build_query(array('tracker' => (int)$this->tracker->id, 'func'    => 'admin-workflow')).'" method="POST">';
            echo '<SELECT name="field_id">';
            //We display only the 'sb' static type field
            foreach ($aff->getUsedFormElementsByType($this->tracker, 'sb') as $field) {
                $bf = new Tracker_FormElement_Field_List_BindFactory();
                if ($bf->getType($field->getBind())=='static') {
                    echo '<OPTION value='.$field->id.'>'.$field->label.'</OPTION>';
                }
            }
            echo '</SELECT>';

            echo '<input type="submit" name="create" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" />';
            echo '</from>';

        }
        $this->displayFooter($layout);
    }
    
     private function displayAdminWorkflow(TrackerManager $layout, Codendi_Request $request, User $current_user, Workflow $workflow) {
        echo '<form action="'.TRACKER_BASE_URL.'/?'. http_build_query(
            array(
                'tracker' => (int)$this->tracker->id,
                'func'    => 'admin-workflow')
            ) .'" method="POST">';

        $this->displayField($workflow);
        $this->displayEnabled($workflow);

        echo '<input type="submit" name="enable_workflow" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" />';
        echo '</FORM>';
        $this->displayTransitionsMatrix($workflow, $layout, $request, $current_user);
    }

    private function displayField(Workflow $workflow) {
        $field = Tracker_FormElementFactory::instance()->getFormElementById($workflow->field_id);
        $hp = Codendi_HTMLPurifier::instance();
        echo '<p>';
        echo '<label>'. $GLOBALS['Language']->getText('workflow_admin','field') .'</label>: ';
        echo $hp->purify($field->label);
        $delete_title = $GLOBALS['Language']->getText('workflow_admin','delete');
        $delete_url = TRACKER_BASE_URL.'/?'. http_build_query(
            array(
                'tracker' => (int)$this->tracker->id,
                'func'    => 'admin-workflow',
                'delete'  => (int)$workflow->workflow_id
            )
        );
        $onclick = 'return confirm(\''.addslashes($GLOBALS['Language']->getText('workflow_admin','delete_workflow')).'\')';
        echo ' <a href="'. $delete_url .'" onClick="'. $onclick .'" title="'. $delete_title .'">';
        echo $GLOBALS['HTML']->getImage('ic/cross.png', array('style' => 'vertical-align:middle;'));
        echo '</a>';
        echo '</p>';
    }

    private function displayEnabled(Workflow $workflow) {
        $checked = '';
        if ($workflow->is_used) {
            $checked = 'checked="checked"';
        }
        echo '<p>';
        echo '<label><input type="checkbox" name="is_used" '. $checked .'> ';
        echo $GLOBALS['Language']->getText('workflow_admin', 'enabled') .'</label>';
        echo '</p>';
    }
    
    protected function displayTransitionsMatrix($workflow, $layout, $request, $current_user) {

        $workflow = WorkflowFactory::instance()->getWorkflowByTrackerId($this->tracker->id);
        echo '<h3>'.$GLOBALS['Language']->getText('workflow_admin','title_define_transitions').'</h3>';
        $field =Tracker_FormElementFactory::instance()->getFormElementById($workflow->field_id);
        if ($workflow->hasTransitions()) {
            $transitions = $workflow->getTransitions($workflow->workflow_id) ;
            $field->displayTransitionsMatrix($transitions);
        }else {
             $field->displayTransitionsMatrix();
        }
    }

}

?>
