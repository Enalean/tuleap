<?php
/**
 * Copyright (c) Enalean, 2012-2018. All Rights Reserved.
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

class Tracker_Workflow_Action_Transitions_DefineWorkflow  extends Tracker_Workflow_Action_Transitions {
    /** @var WorkflowFactory */
    private $workflow_factory;

    /** @var Tracker_FormElementFactory */
    private $form_element_factory;

    public function __construct(Tracker $tracker, WorkflowFactory $workflow_factory, Tracker_FormElementFactory $form_element_factory) {
        parent::__construct($tracker);
        $this->workflow_factory     = $workflow_factory;
        $this->form_element_factory = $form_element_factory;
    }

    public function process(Tracker_IDisplayTrackerLayout $layout, Codendi_Request $request, PFUser $current_user) {
        $hp = Codendi_HTMLPurifier::instance();
        $workflow = $this->workflow_factory->getWorkflowByTrackerId($this->tracker->id);
        $this->displayHeader($layout);
        echo '<h3>'.$GLOBALS['Language']->getText('workflow_admin','title_define_transitions').'</h3>';

        echo '<div class="workflow_transitions">';
        if ($workflow !== null) {
            $this->displayAdminWorkflow($layout, $request, $current_user, $workflow);
        } else {
            //Display creation form
            echo '<p>';
            echo $GLOBALS['Language']->getText('workflow_admin','choose_field');
            echo '<p>';

            echo '<form action="'.TRACKER_BASE_URL.'/?'. http_build_query(array(
                'tracker' => (int)$this->tracker->id, 
                'func'    => Workflow::FUNC_ADMIN_TRANSITIONS
            )).'" method="POST">';
            echo '<SELECT name="field_id">';
            //We display only the 'sb' static type field
            foreach ($this->form_element_factory->getUsedFormElementsByType($this->tracker, 'sb') as $field) {
                $bf = new Tracker_FormElement_Field_List_BindFactory();
                if ($bf->getType($field->getBind())=='static') {
                    echo '<OPTION value='.$hp->purify($field->id).'>'.$hp->purify($field->label).'</OPTION>';
                }
            }
            echo '</SELECT>';
            echo '<br>';
            echo '<input type="submit" name="create" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" />';
            echo '</form>';

        }
        echo '</div>';
        $this->displayFooter($layout);
    }

     private function displayAdminWorkflow(TrackerManager $layout, Codendi_Request $request, PFUser $current_user, Workflow $workflow) {
        echo '<form action="'.TRACKER_BASE_URL.'/?'. http_build_query(
            array(
                'tracker' => (int)$this->tracker->id,
                'func'    => Workflow::FUNC_ADMIN_TRANSITIONS)
            ) .'" method="POST">';

        $this->displayField($workflow);
        $this->displayEnabled($workflow);

        $this->displayTransitionsMatrix($workflow, $layout, $request, $current_user);
        echo '<input type="submit" name="transitions" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" />';
        echo '</form>';
     }

    private function displayField(Workflow $workflow) {
        $field = $this->form_element_factory->getFormElementById($workflow->field_id);
        $hp = Codendi_HTMLPurifier::instance();
        echo '<p>';
        echo '<label>'. $GLOBALS['Language']->getText('workflow_admin','field') .'</label> ';
        echo '<strong>'. $hp->purify($field->label) .'</strong>';
        $delete_title = $GLOBALS['Language']->getText('workflow_admin','delete');
        $delete_url = TRACKER_BASE_URL.'/?'. http_build_query(
            array(
                'tracker' => (int)$this->tracker->id,
                'func'    => Workflow::FUNC_ADMIN_TRANSITIONS,
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
        $checked    = '';
        $classnames = '';
        if ($workflow->is_used) {
            $checked = 'checked="checked"';
        } else {
            $classnames = 'alert alert-warning';
        }
        echo '<div class="'. $classnames .'">';
        if (! $workflow->is_used) {
            echo '<h4>'. $GLOBALS['Language']->getText('workflow_admin', 'transitions_deactivated') .'</h4>';
        }
        echo '<p>';
        echo '<input type="hidden" name="is_used" value="0" />';
        echo '<label><input type="checkbox" name="is_used" value="1" '. $checked .'> ';
        echo $GLOBALS['Language']->getText('workflow_admin', 'enabled') .'</label>';
        echo '</p>';
        echo '</div>';
    }

    protected function displayTransitionsMatrix($workflow, $layout, $request, $current_user) {
        $workflow = $this->workflow_factory->getWorkflowByTrackerId($this->tracker->id);
        $field = $this->form_element_factory->getFormElementById($workflow->field_id);
        if ($workflow->hasTransitions()) {
            $transitions = $workflow->getTransitions($workflow->workflow_id) ;
            $field->displayTransitionsMatrix($transitions);
        } else {
            $field->displayTransitionsMatrix();
        }
    }
}
?>
