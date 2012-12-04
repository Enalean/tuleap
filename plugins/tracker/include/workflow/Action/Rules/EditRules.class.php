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

    const PARAMETER_ADD_RULE     = 'add_rule';
    const PARAMETER_UPDATE_RULES = 'update_rules';
    const PARAMETER_REMOVE_RULES = 'remove_rules';

    const PARAMETER_SOURCE_FIELD = 'source_date_field';
    const PARAMETER_TARGET_FIELD = 'target_date_field';
    const PARAMETER_COMPARATOR   = 'comparator';

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

    private function shouldAddUpdateOrDeleteRules(Codendi_Request $request) {
        $should_delete_rules = is_array($request->get(self::PARAMETER_REMOVE_RULES));
        $should_update_rules = is_array($request->get(self::PARAMETER_UPDATE_RULES));

        return $should_delete_rules || $should_update_rules || $this->shouldAddRule($request);
    }

    private function shouldAddRule(Codendi_Request $request) {
        $source_field_id = $this->getFieldIdFromAddRequest($request, self::PARAMETER_SOURCE_FIELD);
        $target_field_id = $this->getFieldIdFromAddRequest($request, self::PARAMETER_TARGET_FIELD);

        $fields_exist         = $source_field_id && $target_field_id;
        $fields_are_different = $source_field_id != $target_field_id;

        if ($fields_exist && ! $fields_are_different) {
            $error_msg = $GLOBALS['Language']->getText('workflow_admin', 'same_field');
            $GLOBALS['Response']->addFeedback('error', $error_msg);
        }

        if ($fields_exist) {
            $fields_have_good_type = $this->fieldsAreDateOnes($source_field_id, $target_field_id);
        }

        $exist_comparator = (bool)$this->getComparatorFromAddRequest($request);

        return $fields_exist && $fields_are_different && $exist_comparator && $fields_have_good_type;
    }

    private function getFieldIdFromAddRequest(Codendi_Request $request, $source_or_target) {
        $add = $request->get(self::PARAMETER_ADD_RULE);
        if (is_array($add) && isset($add[$source_or_target])) {
            return (int)$add[$source_or_target];
        }
    }

    private function getComparatorFromAddRequest(Codendi_Request $request) {
        $add = $request->get(self::PARAMETER_ADD_RULE);
        $rule = new Rule_WhiteList(Tracker_Rule_Date::$allowed_comparators);
        if (is_array($add) && isset($add[self::PARAMETER_COMPARATOR]) && $rule->isValid($add[self::PARAMETER_COMPARATOR])) {
            return $add[self::PARAMETER_COMPARATOR];
        }
    }

    private function fieldsAreDateOnes($source_field_id, $target_field_id) {
        $source_field_is_date = (bool)$this->rule_date_factory->getUsedDateFieldById($this->tracker, $source_field_id);
        $target_field_is_date = (bool)$this->rule_date_factory->getUsedDateFieldById($this->tracker, $target_field_id);

        return $source_field_is_date && $target_field_is_date;
    }

    public function process(Tracker_IDisplayTrackerLayout $layout, Codendi_Request $request, User $current_user) {
        if ($this->shouldAddUpdateOrDeleteRules($request)) {
            // Verify CSRF Protection
            $this->token->check();
            $this->addUpdateOrDeleteRules($request);
            $GLOBALS['Response']->redirect($this->url_query);
        } else {
            $this->displayPane($layout);
        }
    }

    private function addUpdateOrDeleteRules(Codendi_Request $request) {
        $this->updateRules($request);
        $this->removeRules($request);
        $this->addRule($request);
    }

    private function updateRules(Codendi_Request $request) {
        $rules_to_update = $request->get(self::PARAMETER_UPDATE_RULES);
        if (is_array($rules_to_update)) {
            foreach ($rules_to_update as $rule_id => $params) {
                $rule = $this->rule_date_factory->getRule($this->tracker, (int)$rule_id);
                $rule->setSourceFieldId((int)$params[self::PARAMETER_SOURCE_FIELD]);
                $rule->setTargetFieldId((int)$params[self::PARAMETER_TARGET_FIELD]);
                $rule->setComparator($params[self::PARAMETER_COMPARATOR]);
                $this->rule_date_factory->save($rule);
            }
        }
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
                $this->getFieldIdFromAddRequest($request, self::PARAMETER_SOURCE_FIELD),
                $this->getFieldIdFromAddRequest($request, self::PARAMETER_TARGET_FIELD),
                $this->tracker->getId(),
                $this->getComparatorFromAddRequest($request)
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
        $fields = $this->getListOfDateFieldLabels();
        $rules  = $this->getRules();
        echo '<ul class="workflow_existing_rules">';
        foreach ($rules as $rule) {
            $name_prefix = self::PARAMETER_UPDATE_RULES .'['. $rule->getId() .']';
            echo '<li class="workflow_rule_action">';
            $this->displayFieldSelector($fields, $name_prefix .'['. self::PARAMETER_SOURCE_FIELD .']', $rule->getSourceField()->getId());
            $this->displayComparatorSelector($name_prefix .'['. self::PARAMETER_COMPARATOR .']', $rule->getComparator());
            $this->displayFieldSelector($fields, $name_prefix .'['. self::PARAMETER_TARGET_FIELD .']', $rule->getTargetField()->getId());
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

    private function displayComparatorSelector($name, $selected = null) {
        $comparators = array_combine(Tracker_Rule_Date::$allowed_comparators, Tracker_Rule_Date::$allowed_comparators);
        echo html_build_select_box_from_array($comparators, $name, $selected);
    }

    private function displayFieldSelector(array $fields, $name, $selected) {
        echo html_build_select_box_from_array($fields, $name, $selected);
    }

    private function displayAdd() {
        $fields   = $this->getListOfDateFieldLabelsPlusPleaseChoose();
        $selected = $this->default_value;
        echo $GLOBALS['Language']->getText('workflow_admin','add_new_rule').' ';
        $this->displayFieldSelector($fields, self::PARAMETER_ADD_RULE .'['. self::PARAMETER_SOURCE_FIELD .']', $selected);
        $this->displayComparatorSelector(self::PARAMETER_ADD_RULE .'['. self::PARAMETER_COMPARATOR .']');
        $this->displayFieldSelector($fields, self::PARAMETER_ADD_RULE .'['. self::PARAMETER_TARGET_FIELD .']', $selected);
    }

    private function getListOfDateFieldLabelsPlusPleaseChoose() {
        $labels = array(
            $this->default_value => $GLOBALS['Language']->getText('global', 'please_choose_dashed')
        );

        return $labels + $this->getListOfDateFieldLabels();
    }

    private function getListOfDateFieldLabels() {
        $labels = array();
        $form_elements = $this->rule_date_factory->getUsedDateFields($this->tracker);
        foreach ($form_elements as $form_element) {
            $labels[$form_element->getId()] = $form_element->getLabel();
        }

        return $labels;
    }
}

?>
