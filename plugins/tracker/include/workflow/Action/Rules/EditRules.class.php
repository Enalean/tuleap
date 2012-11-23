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

require_once TRACKER_BASE_DIR .'/workflow/Action/Rules.class.php';
require_once TRACKER_BASE_DIR .'/Tracker/Rule/Date/Factory.class.php';
require_once dirname(__FILE__).'/../../../../tests/builders/aField.php';

class Tracker_Workflow_Action_Rules_EditRules extends Tracker_Workflow_Action_Rules {

    const PARAMETER_REMOVE_RULES = 'remove_rules';

    /** @var Tracker_FormElementFactory */
    private $form_element_factory;

    private $default_value = 'default_value';

    /** @var Tracker_Rule_Date_Factory */
    private $rule_date_factory;

    private $url_query;

    private $operators = array(
        'lower_than'       => '<',
        'lower_or_equal'   => '≤',
        'equal'            => '=',
        'greater_or_equal' => '≥',
        'greater_than'     => '>',
        'different'        => '≠'
    );

    public function __construct(Tracker $tracker, Tracker_FormElementFactory $form_element_factory, Tracker_Rule_Date_Factory $rule_date_factory) {
        parent::__construct($tracker);
        $this->form_element_factory = $form_element_factory;
        $this->rule_date_factory    = $rule_date_factory;
        $this->url_query            = TRACKER_BASE_URL.'/?'. http_build_query(
            array(
                'tracker' => (int)$this->tracker->id,
                'func'    => 'admin-workflow-rules',
            )
        );
    }

    private function shouldUpdateRules(Codendi_Request $request) {
        $should_delete_rules = $request->get(self::PARAMETER_REMOVE_RULES);
        $exist_source_field  = $request->existAndNonEmpty('source_date_field');
        $exist_target_field  = $request->existAndNonEmpty('target_date_field');
        $should_add_rules    = $exist_source_field && $exist_target_field;

        return $should_delete_rules || $should_add_rules;
    }

    public function process(Tracker_IDisplayTrackerLayout $layout, Codendi_Request $request, User $current_user) {
        if ($this->shouldUpdateRules($request)) {
            $this->updateRules($request);
            $GLOBALS['Response']->redirect($this->url_query);
        } else {
            $this->displayPane($layout);
        }
    }

    private function updateRules(Codendi_Request $request) {
        $remove_rules = $request->getValidated(self::PARAMETER_REMOVE_RULES, 'array', array());
        foreach ($remove_rules as $rule_id) {
            $this->deleteRuleById((int)$rule_id);
        }
    }

    private function deleteRuleById($rule_id) {
        $rule = $this->rule_date_factory->searchById($rule_id);
        if ($rule) {
            $this->rule_date_factory->delete($rule);
        }
    }

    private function displayPane(Tracker_IDisplayTrackerLayout $layout) {
        $this->displayHeader($layout);
        echo '<div class="workflow_rules">';
        echo '<h3>'. 'Define global rules' .'</h3>'; //TODO: i18n
        echo '<p class="help">'. 'Those rules will be applied on each creation/update of artifacts.' .'</p>'; //TODO: i18n
        echo '<form method="post" action="'. $this->url_query .'">';
        $this->displayRules();
        $this->displayAdd();
        echo '<p><input type="submit" name="add" value="'.$GLOBALS['Language']->getText('global', 'btn_submit').'" /></p>';
        echo '</form>';
        echo '</div>' ;
        $this->displayFooter($layout);
    }

    private function displayRules() {
        $rules = $this->getRules();
        echo '<ul class="workflow_existing_rules">';
        foreach ($rules as $rule) {
            echo '<li class="workflow_rule_action">';
            echo $rule['source_field']->getLabel();
            echo '&nbsp;&nbsp;';
            echo $this->operators[$rule['operator']];
            echo '&nbsp;&nbsp;';
            echo $rule['target_field']->getLabel();
            echo '<label class="pc_checkbox pc_check_unchecked" title="Remove the rule">&nbsp;';
            echo '<input type="checkbox" name="remove_rule[]" value="'.$rule['id'].'" ></input>';
            echo '</label>';
            echo '</li>';
        }
        echo '</ul>';
    }

    private function getRules() {
        $fake_result = array(
            array('id' => 23, 'source_field' => aDateField()->withLabel('Planned end date')->build(),  'operator' => 'greater_than',     'target_field' => aDateField()->withLabel('Planned start date')->build()),
            array('id' => 31, 'source_field' => aDateField()->withLabel('Actual start date')->build(), 'operator' => 'greater_or_equal', 'target_field' => aDateField()->withLabel('Planned start date')->build()),
        );
        return $fake_result;
    }

    private function displayAdd() {
        $values = $this->getDateFields();
        $checked_val = $this->default_value;
        echo 'Add a new rule: ';//TODO: i18n
        echo html_build_select_box_from_array($values, 'source_date_field', $checked_val);
        echo html_build_select_box_from_array($this->operators, 'operator');
        echo html_build_select_box_from_array($values, 'target_date_field', $checked_val);
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
