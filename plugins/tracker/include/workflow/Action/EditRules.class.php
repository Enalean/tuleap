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

class Tracker_Workflow_Action_EditRules {

    /** @var Tracker */
    protected $tracker;
    
    /** @var Tracker_FormElementFactory */
    private $form_element_factory;
    
    private $default_value = 'default_value';

    private $operators = array(
        'lower_than'       => '<',
        'lower_or_equal'   => '<=',
        'equal'            => '=',
        'greater_or_equal' => '>=',
        'greater_than'     => '>',
        'different'        => '!='
    );

    public function __construct(Tracker $tracker) {
        $this->tracker = $tracker;
        $this->form_element_factory = Tracker_FormElementFactory::instance();
    }

    public function process(Tracker_IDisplayTrackerLayout $layout, Codendi_Request $request, User $current_user) {
        if ($request->existAndNonEmpty('add')) {
            // Do the create stuff
            $workflow_rules_url = TRACKER_BASE_URL.'/?'. http_build_query(
                array(
                    'tracker' =>  (int)$this->tracker->id,
                    'func'    =>  'admin-workflow-rules',
                )
            );
            $GLOBALS['Response']->redirect($workflow_rules_url);
        } else {
            $this->displayHeader($layout);
            $this->displayAdd();
            $this->displayFooter($layout);
        }
        
    }
    
    private function displayAdd() {
        echo 'No rules defined';
        echo '<br />';
        $values = $this->getDateFields();
        $checked_val = $this->default_value;
        $add_form_url  = TRACKER_BASE_URL.'/?'. http_build_query(
            array(
                'tracker' =>  (int)$this->tracker->id,
                'func'    =>  'admin-workflow-rules',
            )
        );
        echo '<form name="" method="post" action="'.$add_form_url.'">';
        echo html_build_select_box_from_array($values, 'source_date_field', $checked_val);
        echo html_build_select_box_from_array($this->operators, 'operator');
        echo html_build_select_box_from_array($values, 'target_date_field', $checked_val);
        echo '<input type="submit" name="add" value="'.$GLOBALS['Language']->getText('global', 'add').'" />';
        echo '</form>';
    }


    protected function displayHeader($engine) {
        $this->tracker->displayAdminItemHeader($engine, 'editworkflow');

        $transitions_link = TRACKER_BASE_URL.'/?'. http_build_query(
            array(
                'tracker' =>  (int)$this->tracker->id,
                'func'    =>  'admin-workflow'
            )
        );

        $workflow_link = TRACKER_BASE_URL.'/?'. http_build_query(
            array(
                'tracker' =>  (int)$this->tracker->id,
                'func'    =>  'admin-workflow-rules'
            )
        );
        echo '<div class="tabbable tabs-left">';
        echo '<ul class="nav nav-tabs">';
        echo '<li class="active"><a href="'. $workflow_link .'">Workflow Rules</a></li>'; //TODO: i18n
        echo '<li><a href="'. $transitions_link .'">Transitions</a></li>'; //TODO: i18n
        echo '</ul>';
        echo '<div class="tab-content">';
    }

    protected function displayFooter($engine) {
        echo '</div>';
        echo '</div>';
        $this->tracker->displayFooter($engine);
    }
    
    private function getDateFields() {
        $form_elements = $this->form_element_factory->getFormElementsByType($this->tracker, 'date');
        $values = array(
            $this->default_value => $GLOBALS['Language']->getText('global', 'please_choose_dashed')
        );
        
        foreach ($form_elements as $form_element) {
            $values[$form_element->getId()] = $form_element->getLabel();
        }
        
        return $values;
    }
}

?>
