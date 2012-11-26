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

    /** @var array */
    private $comparators = array(
        Tracker_Rule_Date::COMPARATOR_LESS_THAN              => '<',
        Tracker_Rule_Date::COMPARATOR_LESS_THAN_OR_EQUALS    => '≤',
        Tracker_Rule_Date::COMPARATOR_EQUALS                 => '=',
        Tracker_Rule_Date::COMPARATOR_GREATER_THAN_OR_EQUALS => '≥',
        Tracker_Rule_Date::COMPARATOR_GREATER_THAN           => '>',
        Tracker_Rule_Date::COMPARATOR_NOT_EQUALS             => '≠',
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
        $should_delete_rules = is_array($request->get(self::PARAMETER_REMOVE_RULES));

        return $should_delete_rules || $this->shouldAddRule($request);
    }

    private function shouldAddRule(Codendi_Request $request) {
        $source_field = $request->getValidated('source_date_field', 'uint');
        $target_field = $request->getValidated('target_date_field', 'uint');

        $fields_exist_and_are_different = $source_field && $target_field && ($source_field != $target_field);

        $valid_comparator = new Valid_WhiteList('comparator', Tracker_Rule_Date::$allowed_comparators);
        $valid_comparator->required();
        $exist_comparator = $request->valid($valid_comparator);

        return $fields_exist_and_are_different && $exist_comparator;
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
        $this->removeRules($request);
        $this->addRule($request);
    }

    private function removeRules(Codendi_Request $request) {
        $remove_rules = $request->get(self::PARAMETER_REMOVE_RULES);
        if (is_array($remove_rules)) {
            foreach ($remove_rules as $rule_id) {
                $this->rule_date_factory->deleteById($this->tracker->getId(), (int)$rule_id);
            }
        }
    }

    private function addRule(Codendi_Request $request) {
        if ($this->shouldAddRule($request)) {
            $this->rule_date_factory->create(
                (int)$request->get('source_date_field'),
                (int)$request->get('target_date_field'),
                $this->tracker->getId(),
                $request->get('comparator')
            );
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
            echo $rule->getSourceField()->getLabel();
            echo ' <span class="workflow_rule_date_comparator">';
            echo $rule->getComparator();
            echo '</span> ';
            echo $rule->getTargetField()->getLabel();
            echo '<label class="pc_checkbox pc_check_unchecked" title="Remove the rule">&nbsp;';
            echo '<input type="checkbox" name="'. self::PARAMETER_REMOVE_RULES .'[]" value="'.$rule->getId().'" ></input>';
            echo '</label>';
            echo '</li>';
        }
        echo '</ul>';
    }

    private function getRules() {
        return $this->rule_date_factory->searchByTrackerId($this->tracker->getId());
    }

    private function displayAdd() {
        $values = $this->getDateFields();
        $checked_val = $this->default_value;
        echo 'Add a new rule: ';//TODO: i18n
        echo html_build_select_box_from_array($values, 'source_date_field', $checked_val);
        echo html_build_select_box_from_array($this->comparators, 'comparator');
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
