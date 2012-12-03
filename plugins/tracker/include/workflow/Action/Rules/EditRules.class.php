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

    private $default_value = 'default_value';

    /** @var Tracker_Rule_Date_Factory */
    private $rule_date_factory;

    private $url_query;

    public function __construct(Tracker $tracker, Tracker_Rule_Date_Factory $rule_date_factory, CSRFSynchronizerToken $token) {
        parent::__construct($tracker);
        $this->rule_date_factory    = $rule_date_factory;
        $this->token                = $token;
        $this->url_query            = TRACKER_BASE_URL.'/?'. http_build_query(
            array(
                'tracker' => (int)$this->tracker->id,
                'func'    => Workflow::FUNC_ADMIN_RULES,
            )
        );
    }

    private function shouldUpdateRules(Codendi_Request $request) {
        $should_delete_rules = is_array($request->get(self::PARAMETER_REMOVE_RULES));

        return $should_delete_rules || $this->shouldAddRule($request);
    }

    private function shouldAddRule(Codendi_Request $request) {
        $source_field_id = (int)$request->getValidated('source_date_field', 'uint');
        $target_field_id = (int)$request->getValidated('target_date_field', 'uint');

        $fields_exist         = $source_field_id && $target_field_id;
        $fields_are_different = $source_field_id != $target_field_id;

        if ($fields_exist && ! $fields_are_different) {
            $error_msg = $GLOBALS['Language']->getText('workflow_admin', 'same_field');
            $GLOBALS['Response']->addFeedback('error', $error_msg);
        }

        if ($fields_exist) {
            $fields_have_good_type = $this->fieldsAreDateOnes($source_field_id, $target_field_id);
        }

        $valid_comparator = new Valid_WhiteList('comparator', Tracker_Rule_Date::$allowed_comparators);
        $valid_comparator->required();
        $exist_comparator = $request->valid($valid_comparator);

        return $fields_exist && $fields_are_different && $exist_comparator && $fields_have_good_type;
    }

    private function fieldsAreDateOnes($source_field_id, $target_field_id) {
        $source_field_is_date = (bool)$this->rule_date_factory->getUsedDateFieldById($this->tracker, $source_field_id);
        $target_field_is_date = (bool)$this->rule_date_factory->getUsedDateFieldById($this->tracker, $target_field_id);

        return $source_field_is_date && $target_field_is_date;
    }

    public function process(Tracker_IDisplayTrackerLayout $layout, Codendi_Request $request, User $current_user) {
        if ($this->shouldUpdateRules($request)) {

            // Verify CSRF Protection
            $this->token->check();
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
        $nb_deleted = 0;
        if (is_array($remove_rules)) {
            foreach ($remove_rules as $rule_id) {
                if ($this->rule_date_factory->deleteById($this->tracker->getId(), (int)$rule_id)) {
                    ++$nb_deleted;
                }
            }
            if ($nb_deleted) {
                $delete_msg = $GLOBALS['Language']->getText('workflow_admin', 'deleted_rules');
                $GLOBALS['Response']->addFeedback('info', $delete_msg);
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
            $create_msg = $GLOBALS['Language']->getText('workflow_admin', 'created_rule');
            $GLOBALS['Response']->addFeedback('info', $create_msg);
        }
    }

    private function displayPane(Tracker_IDisplayTrackerLayout $layout) {
        $this->displayHeader($layout);
        echo '<div class="workflow_rules">';
        echo '<h3>'. $GLOBALS['Language']->getText('workflow_admin','title_define_global_date_rules') .'</h3>';
        echo '<p class="help">'. $GLOBALS['Language']->getText('workflow_admin','hint_date_rules_definition') .'</p>';
        echo '<form method="post" action="'. $this->url_query .'">';
        // CSRF Protection
        echo $this->token->fetchHTMLInput();
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
        $comparators = array_combine(Tracker_Rule_Date::$allowed_comparators, Tracker_Rule_Date::$allowed_comparators);
        $values      = $this->getDateFields();
        $checked_val = $this->default_value;
        echo $GLOBALS['Language']->getText('workflow_admin','add_new_rule').' ';
        echo html_build_select_box_from_array($values, 'source_date_field', $checked_val);
        echo html_build_select_box_from_array($comparators, 'comparator');
        echo html_build_select_box_from_array($values, 'target_date_field', $checked_val);
    }

    private function getDateFields() {
        $form_elements = $this->rule_date_factory->getUsedDateFields($this->tracker);
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
