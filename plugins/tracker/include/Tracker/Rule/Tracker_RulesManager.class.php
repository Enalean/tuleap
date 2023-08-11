<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\Layout\IncludeAssets;
use Tuleap\Tracker\Rule\InvolvedFieldsInRule;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;
use Tuleap\Tracker\Rule\TrackerRulesListValidator;
use Tuleap\Tracker\Rule\TrackerRulesDateValidator;

/**
* Manager of rules
*
* This is only a proxy to access the factory.
* Maybe there is no need to have this intermediary?
*/
class Tracker_RulesManager
{
    /**
     *
     * @var Tracker
     */
    protected $tracker;

    /** @var Tracker_FormElementFactory */
    protected $form_element_factory;

     /** @var Tracker_Rule_Date_Factory */
    protected $rule_date_factory;

    /**
     *
     * @var Tracker_Rule_List_Factory
     */
    private $rule_list_factory;

    /** @var FrozenFieldsDao */
    private $frozen_fields_dao;

    /**
     * @var TrackerRulesListValidator
     */
    private $tracker_rules_list_validator;

    /**
     * @var TrackerRulesDateValidator
     */
    private $tracker_rules_date_validator;

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var array
     */
    private $rules_by_tracker_id = [];

    public function __construct(
        Tracker $tracker,
        Tracker_FormElementFactory $form_element_factory,
        FrozenFieldsDao $frozen_fields_dao,
        TrackerRulesListValidator $tracker_rules_list_validator,
        TrackerRulesDateValidator $tracker_rules_date_validator,
        TrackerFactory $tracker_factory,
    ) {
        $this->tracker                      = $tracker;
        $this->form_element_factory         = $form_element_factory;
        $this->frozen_fields_dao            = $frozen_fields_dao;
        $this->tracker_rules_list_validator = $tracker_rules_list_validator;
        $this->tracker_rules_date_validator = $tracker_rules_date_validator;
        $this->tracker_factory              = $tracker_factory;
    }

    /**
     *
     * @param int $tracker_id
     * @return Tracker_Rule_List[]
     */
    public function getAllListRulesByTrackerWithOrder($tracker_id)
    {
        if (! isset($this->rules_by_tracker_id[$tracker_id])) {
            $this->rules_by_tracker_id[$tracker_id] = $this->getRuleFactory()
                    ->getAllListRulesByTrackerWithOrder($tracker_id);
        }
        return $this->rules_by_tracker_id[$tracker_id];
    }

    /**
     *
     * @param int $tracker_id
     * @return array An array of Tracker_Rule_Date objects
     */
    public function getAllDateRulesByTrackerId($tracker_id)
    {
        return $this->getTrackerRuleDateFactory()
                    ->searchByTrackerId($tracker_id);
    }

    /**
     *
     * @return Tracker_Rule_Date_Factory
     */
    public function getTrackerRuleDateFactory()
    {
        if ($this->rule_date_factory ==  null) {
            $this->rule_date_factory = new Tracker_Rule_Date_Factory(new Tracker_Rule_Date_Dao(), $this->form_element_factory);
        }

        return $this->rule_date_factory;
    }

    private function getIncludeAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../../../frontend-assets',
            '/assets/trackers'
        );
    }

    /**
     *
     * @return \Tracker_RulesManager
     */
    public function setRuleDateFactory(Tracker_Rule_Date_Factory $factory)
    {
        $this->rule_date_factory = $factory;
        return $this;
    }

    /**
     *
     * @return Tracker_Rule_List_Factory
     */
    public function getTrackerRuleListFactory()
    {
        if ($this->rule_list_factory ==  null) {
            $this->rule_list_factory = new Tracker_Rule_List_Factory(new Tracker_Rule_List_Dao());
        }

        return $this->rule_list_factory;
    }

    /**
     *
     * @return \Tracker_RulesManager
     */
    public function setRuleListFactory(Tracker_Rule_List_Factory $factory)
    {
        $this->rule_list_factory = $factory;
        return $this;
    }

    /**
     *
     * @return Tracker_RuleFactory
     */
    public function getRuleFactory()
    {
        return Tracker_RuleFactory::instance();
    }

    public function setTrackerFormElementFactory(Tracker_FormElementFactory $factory)
    {
        $this->form_element_factory = $factory;
    }

    public function getTrackerFormElementFactory()
    {
        if ($this->form_element_factory === null) {
            $this->form_element_factory = Tracker_FormElementFactory::instance();
        }

        return $this->form_element_factory;
    }

    /**
     * Check if all the selected values of a submitted artefact
     * are coherent regarding the rules
     *
     * @param int $tracker_id the artifact id to test
     * @param array $value_field_list the selected values to test for the artifact     *
     * @return bool True if the submitted values are coherent regarding the rules,
     * false otherwise
     */
    public function validate($tracker_id, $value_field_list)
    {
        $tracker =  $this->tracker_factory->getTrackerByid($tracker_id);
        if ($tracker === null) {
            return false;
        }

        $valid_list_rules = $this->tracker_rules_list_validator
            ->validateListRules($tracker, $value_field_list, $this->getAllListRulesByTrackerWithOrder($tracker_id));

        $valid_date_rules = $this->tracker_rules_date_validator
            ->validateDateRules($value_field_list, $this->getAllDateRulesByTrackerId($tracker_id));

        if (! $valid_list_rules || ! $valid_date_rules) {
            return false;
        }

        return true;
    }

    public function fieldIsAForbiddenSource($tracker_id, $field_id, $target_id)
    {
        return ! $this->ruleExists($tracker_id, $field_id, $target_id) &&
            (
                $field_id == $target_id ||
                $this->checkIfRuleIsCyclic($tracker_id, $field_id, $target_id) ||
                $this->fieldHasSource($tracker_id, $target_id) ||
                $this->isFieldUsedInFrozenFieldsTransitionPostAction($field_id)
            );
    }

    public function checkIfRuleIsCyclic($tracker_id, $source_id, $target_id): bool
    {
        $involved_fields_collection = $this->getRuleFactory()->getInvolvedFieldsByTrackerIdCollection((int) $tracker_id);
        return $this->isCyclic((int) $tracker_id, (int) $source_id, (int) $target_id, $involved_fields_collection);
    }

    /**
     * @param InvolvedFieldsInRule[] $involved_fields_collection
     */
    private function isCyclic(int $tracker_id, int $source_id, int $target_id, array $involved_fields_collection): bool
    {
        if ($source_id === $target_id) {
            return true;
        }

        $found = false;
        foreach ($involved_fields_collection as $involved_fields) {
            if ($found) {
                break;
            }
            if ($involved_fields->getSourceFieldId() === $target_id) {
                $found = $this->isCyclic($tracker_id, $source_id, $involved_fields->getTargetFieldId(), $involved_fields_collection);
            }
        }
        return $found;
    }

    public function fieldIsAForbiddenTarget($tracker_id, $field_id, $source_id)
    {
        return ! $this->ruleExists($tracker_id, $source_id, $field_id) &&
            (
                $field_id == $source_id ||
                $this->checkIfRuleIsCyclic($tracker_id, $source_id, $field_id) ||
                $this->fieldHasSource($tracker_id, $field_id) ||
                $this->isFieldUsedInFrozenFieldsTransitionPostAction($field_id)
            );
    }

    public function fieldHasTarget($tracker_id, $field_id)
    {
        $rules = $this->getAllListRulesByTrackerWithOrder($tracker_id);
        foreach ($rules as $rule) {
            if ($rule->source_field == $field_id) {
                return true;
            }
        }
        return false;
    }

    public function fieldHasSource($tracker_id, $field_id)
    {
        $rules = $this->getAllListRulesByTrackerWithOrder($tracker_id);
        foreach ($rules as $rule) {
            if ($rule->target_field == $field_id) {
                return true;
            }
        }
        return false;
    }

    public function valueHasTarget($tracker_id, $field_id, $value_id, $target_id)
    {
        $rules = $this->getAllListRulesByTrackerWithOrder($tracker_id);
        foreach ($rules as $rule) {
            if ($rule->source_field == $field_id && $rule->source_value == $value_id && $rule->target_field == $target_id) {
                return true;
            }
        }
        return false;
    }

    public function valueHasSource($tracker_id, $field_id, $value_id, $source_id)
    {
        $rules = $this->getAllListRulesByTrackerWithOrder($tracker_id);
        foreach ($rules as $rule) {
            if ($rule->target_field == $field_id && $rule->target_value == $value_id && $rule->source_field == $source_id) {
                return true;
            }
        }
        return false;
    }

    public function ruleExists($tracker_id, $source_id, $target_id)
    {
        $rules = $this->getAllListRulesByTrackerWithOrder($tracker_id);
        foreach ($rules as $rule) {
            if ($rule->source_field == $source_id && $rule->target_field == $target_id) {
                return true;
            }
        }
        return false;
    }

    private function getAllSourceFields()
    {
        $sources     = [];
        $used_fields = $this->form_element_factory->getUsedSbFields($this->tracker);
        foreach ($used_fields as $field) {
            if (! $this->fieldIsAForbiddenSource($this->tracker->id, $field->getId(), null)) {
                $sources[$field->getId()] = $field;
            }
        }
        return $sources;
    }

    public function getAllTargetFields($source_id)
    {
        $targets     = [];
        $used_fields = $this->form_element_factory->getUsedSbFields($this->tracker);
        foreach ($used_fields as $field) {
            if (! $source_id || ! $this->fieldIsAForbiddenTarget($this->tracker->id, $field->getId(), $source_id)) {
                $targets[$field->getId()] = $field;
            }
        }
        return $targets;
    }

    public function getFieldTargets(Tracker_FormElement_Field $source_field): array
    {
        $targets_ids = [];
        $used_fields = $this->form_element_factory->getUsedSbFields($this->tracker);
        foreach ($used_fields as $field) {
            if ($this->ruleExists($this->tracker->getId(), $source_field->getId(), $field->getId())) {
                $targets_ids[] = $field->getId();
            }
        }
        return $targets_ids;
    }

    //New interface
     /**
     *getDependenciesBySourceTarget .
     *
     * @param $tracker_id, the id of the tracker
     * @param $field_source_id, the id of the source field
     * @param $field_target_id, the id of the target field
     *
     * @return array of Tracker_Rule_List
     */
    public function getDependenciesBySourceTarget($tracker_id, $field_source_id, $field_target_id)
    {
        $fact = $this->getRuleFactory();
        return $fact->getDependenciesBySourceTarget($tracker_id, $field_source_id, $field_target_id);
    }

    public function deleteRulesBySourceTarget($tracker_id, $field_source_id, $field_target_id)
    {
        $fact = $this->getRuleFactory();
        return $fact->deleteRulesBySourceTarget($tracker_id, $field_source_id, $field_target_id);
    }

    public function process($engine, $request, $current_user)
    {
        if ($request->get('source_field') && ! $request->get('target_field')) {
            $source_field = $request->get('source_field');
            $this->displayChooseSourceAndTarget($engine, $request, $current_user, $source_field);
        } elseif ($request->get('source_field') && $request->get('target_field')) {
            if (! $request->isPost() || ! $request->get('create_field_dependencies')) {
                $source_field = $request->get('source_field');
                $target_field = $request->get('target_field');
                $tracker_id   = $this->tracker->id;

                if (
                    $this->checkIfRuleIsCyclic($tracker_id, $source_field, $target_field) ||
                    $this->fieldIsAForbiddenSource($tracker_id, $source_field, $target_field) ||
                    $this->fieldIsAForbiddenTarget($tracker_id, $target_field, $source_field)
                ) {
                    $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-tracker', 'Non authorized dependency.Please, select other fields.'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?' . http_build_query(['tracker' => (int) $tracker_id, 'func'    => 'admin-dependencies']));
                } else {
                    $this->displayDefineDependencies($engine, $request, $current_user, $source_field, $target_field);
                }
            } else {
                //We delete all previous rules
                $this->deleteRulesBySourceTarget($this->tracker->id, $request->get('source_field'), $request->get('target_field'));

                //Add dependencies in db
                $field_source        = $this->form_element_factory->getFormElementById($request->get('source_field'));
                $field_source_values = $field_source->getVisibleValuesPlusNoneIfAny();

                $field_target        = $this->form_element_factory->getFormElementById($request->get('target_field'));
                $field_target_values = $field_target->getVisibleValuesPlusNoneIfAny();

                $currMatrix = [];

                foreach ($field_source_values as $field_source_value_id => $field_source_value) {
                    foreach ($field_target_values as $field_target_value_id => $field_target_value) {
                        $dependency = $field_source_value_id . '_' . $field_target_value_id;
                        if ($request->existAndNonEmpty($dependency)) {
                            $currMatrix[] = [$field_source_value_id, $field_target_value_id];
                            $this->getTrackerRuleListFactory()->create(
                                $field_source->getId(),
                                $field_target->getId(),
                                $this->tracker->id,
                                $field_source_value_id,
                                $field_target_value_id
                            );
                        }
                    }
                }
                $GLOBALS['Response']->addFeedback('info', dgettext('tuleap-tracker', 'Transitions updated'));
                $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?' . http_build_query(['tracker' => (int) $this->tracker->id, 'func'    => 'admin-dependencies']));
            }
        } else {
            $this->displayChooseSourceAndTarget($engine, $request, $current_user, null);
        }
    }

    private function displayChooseSourceAndTarget($engine, $request, $current_user, $source_field_id)
    {
        $hp    = Codendi_HTMLPurifier::instance();
        $title = dgettext('tuleap-tracker', 'Manage field dependencies');
        $this->tracker->displayAdminItemHeader($engine, 'dependencies', $title);

        echo '<h2 class="almost-tlp-title">' . $title . '</h2>';
        echo '<p>' . dgettext('tuleap-tracker', 'Select a source field and a target field to edit dependencies between them.') . '</p>';

        echo '<form action="' . TRACKER_BASE_URL . '/?" method="GET">';
        echo '<input type="hidden" name="tracker" value="' . (int) $this->tracker->id . '" />';
        echo '<input type="hidden" name="func" value="admin-dependencies" />';

        //source
        $source_field = $this->form_element_factory->getFormElementById($source_field_id);
        if (! $source_field) {
            echo '<select name="source_field" data-test="source_field" onchange="this.form.submit()">';
            echo '<option value="0">' . dgettext('tuleap-tracker', '-- Choose source field') . '</option>';
            $sources = $this->getAllSourceFields();
            foreach ($sources as $id => $field) {
                echo '<option value="' . $hp->purify($id) . '">';
                echo $hp->purify($field->getLabel(), CODENDI_PURIFIER_CONVERT_HTML);
                echo '</option>';
            }
            echo '</select>';
        } else {
            echo '<input type="hidden" name="source_field" value="' . $hp->purify($source_field_id) . '" />';
            echo $hp->purify($source_field->getLabel());
        }

        echo ' &rarr; ';

        //target
        $disabled = '';
        if (! $source_field) {
            $disabled = 'disabled="disabled" readonly="readonly"';
        }
        echo '<select name="target_field" data-test="target_field" ' . $disabled . '>';
        echo '<option value="0">' . dgettext('tuleap-tracker', '-- Choose target field') . '</option>';
        if ($source_field) {
            $sources = $this->getAllTargetFields($source_field_id);
            foreach ($sources as $id => $field) {
                echo '<option value="' . $id . '">';
                echo $hp->purify($field->getLabel(), CODENDI_PURIFIER_CONVERT_HTML);
                echo '</option>';
            }
        }
        echo '</select>';

        echo ' <input type="submit" name="choose_source" data-test="choose_source_button" value="' . $GLOBALS['Language']->getText('global', 'btn_submit') . '" />';
        echo '</form>';

        //Shortcut
        $sources_targets = $this->getRuleFactory()->getInvolvedFieldsByTrackerId($this->tracker->id);
        if (count($sources_targets)) {
            $dependencies = [];
            foreach ($sources_targets as $row) {
                if ($source = $this->form_element_factory->getFormElementById($row['source_field_id'])) {
                    if ($target = $this->form_element_factory->getFormElementById($row['target_field_id'])) {
                        $d              = '<a href="' . TRACKER_BASE_URL . '/?' . http_build_query(
                            [
                                'tracker'      => (int) $this->tracker->id,
                                'func'         => 'admin-dependencies',
                                'source_field' => $row['source_field_id'],
                                'target_field' => $row['target_field_id'],
                            ]
                        ) . '">';
                        $d             .= $hp->purify($source->getLabel()) . ' &rarr; ' . $hp->purify($target->getLabel());
                        $d             .= '</a>';
                        $dependencies[] = $d;
                    }
                }
            }

            if ($dependencies) {
                echo '<p data-test="existing-field-dependency-label">' . dgettext('tuleap-tracker', 'Or select an existing field dependency') . '</p>';
                echo '<ul><li>' . implode('</li><li>', $dependencies) . '</li></ul>';
            }
            echo '</ul>';
        }

        $this->tracker->displayFooter($engine);
    }

    public function displayDefineDependencies($engine, $request, $current_user, $source_field_id, $target_field_id)
    {
        $hp    = Codendi_HTMLPurifier::instance();
        $title = dgettext('tuleap-tracker', 'Define dependencies');

        $GLOBALS['HTML']->addStylesheet($this->getIncludeAssets()->getFileURL('dependencies-matrix.css'));
        $this->tracker->displayAdminItemHeader($engine, 'dependencies', $title, ['body_class' => ['has-sidebar-with-pinned-header']]);
        $source_field = $this->form_element_factory->getFieldById($source_field_id);
        $target_field = $this->form_element_factory->getFieldById($target_field_id);
        //Display creation form
        echo '<h2 class="almost-tlp-title">' . $title . '</h2>';
        $source_field_label = $source_field === null ? '' : $source_field->getLabel();
        $target_field_label = $target_field === null ? '' : $target_field->getLabel();
        echo '<p>' . sprintf(dgettext('tuleap-tracker', 'Define dependencies between <b>%1$s</b> as source and <b>%2$s</b> as target:'), $hp->purify($source_field_label), $hp->purify($target_field_label)) . '</p>';

        $this->displayDependenciesMatrix($source_field, $target_field);

        $this->tracker->displayFooter($engine);
    }

    protected function displayDependenciesMatrix($source_field, $target_field, $dependencies = null)
    {
        $source_field_values = $source_field->getVisibleValuesPlusNoneIfAny();
        $target_field_values = $target_field->getVisibleValuesPlusNoneIfAny();

        $purifier = Codendi_HTMLPurifier::instance();
        echo '<form action="' . TRACKER_BASE_URL . '/?' . http_build_query(['tracker' => (int) $this->tracker->id, 'source_field' => $source_field->getId(), 'target_field' => $target_field->getId(), 'func' => 'admin-dependencies']) . '" method="POST">';
        echo '<table class="tlp-table" id="tracker-field-dependencies-matrix" data-test="tracker-field-dependencies-matrix">';

        echo "<thead><th class='matrix-cell matrix-empty-cell matrix-label-cell'></th>";
        foreach ($target_field_values as $target_field_value_id => $target_field_value) {
            echo '<th class="matrix-cell matrix-label-cell">' . $purifier->purify($target_field_value->getLabel()) . "</th>";
        }
        echo "</thead>";

        $dependencies = $this->getDependenciesBySourceTarget($this->tracker->id, $source_field->getId(), $target_field->getId());

        $j = 0;
        echo "<tbody>";
       //Display the available transitions
        foreach ($source_field_values as $source_field_value_id => $source_field_value) {
            echo "<tr class=\"" . util_get_alt_row_color($j) . "\" data-test='matrix-row'>\n";
            echo "<th class='matrix-label-cell'>" . $purifier->purify($source_field_value->getLabel()) . "</th>";
            foreach ($target_field_values as $target_field_value_id => $target_field_value) {
                $box_value = $source_field_value_id . '_' . $target_field_value_id;
                $this->displayCheckbox($source_field_value_id, $target_field_value_id, $dependencies, $box_value);
            }
            echo "</tr>\n";
            $j++;
        }

        echo '</tbody></table>';
        echo '<a href="' . TRACKER_BASE_URL . '/?' . http_build_query(
            [
                'tracker' => (int) $this->tracker->id,
                'func'    => 'admin-dependencies',
            ]
        ) . '">';
        echo '&laquo; ' . $GLOBALS['Language']->getText('global', 'btn_cancel');
        echo '</a> ';
        echo '<input type="submit" name="create_field_dependencies" data-test="create-field-dependencies-button" value="' . $GLOBALS['Language']->getText('global', 'btn_submit') . '" />';
        echo '</FORM>';
    }

    protected function displayCheckbox($source_field_value_id, $target_field_value_id, $dependencies, $box_value)
    {
        $checked = '';
        if (count($dependencies) > 0) {
            foreach ($dependencies as $dependency) {
                if ($source_field_value_id == $dependency->source_value && $target_field_value_id == $dependency->target_value) {
                    $checked = 'checked="checked"';
                    break;
                }
            }
        }

        echo '<td class="matrix-cell" ><label class="pc_checkbox"><input type="checkbox" data-test="create-dependency" class=" tracker-field-dependencies-checkbox" name="' . $box_value . '" ' . $checked . '>&nbsp;</label></td>';
    }

    public function displayRulesAsJavascript(): string
    {
        $purifier  = Codendi_HTMLPurifier::instance();
        $csp_nonce = $GLOBALS['Response']->getCSPNonce();
        $html      = sprintf('<script type="text/javascript" nonce="%s">', $purifier->purify($csp_nonce));
        $html     .= "\n//------------------------------------------------------\n";
        $rules     = $this->getAllListRulesByTrackerWithOrder($this->tracker->id);
        if ($rules && count($rules) > 0) {
            foreach ($rules as $key => $nop) {
                $trvv  = new Tracker_Rule_List_View($rules[$key]);
                $html .= 'tuleap.tracker.rules_definitions.push(';
                $html .= $trvv->fetchJavascript();
                $html .= ");\n";
            }
        }
        $html .= "\n//------------------------------------------------------\n";
        $html .= "</script>";
        return $html;
    }

    /** @return bool */
    public function isUsedInFieldDependency(Tracker_FormElement $field)
    {
        $field_id   = $field->getId();
        $list_rules = $this->getAllListRulesByTrackerWithOrder($this->tracker->getId());
        $date_rules = $this->getAllDateRulesByTrackerId($this->tracker->getId());
        $rules      = array_merge($list_rules, $date_rules);
        foreach ($rules as $rule) {
            if ($rule->isUsedInRule($field->getId())) {
                return true;
            }
        }
        return false;
    }

    /**
     * Export workflow to XML
     *
     * @param SimpleXMLElement $root     the node to which the workflow is attached (passed by reference)
     * @param array            $xmlMapping correspondance between real ids and xml IDs
     *
     * @return void
     */
    public function exportToXml(SimpleXMLElement $root, array $xmlMapping)
    {
        $this->getTrackerRuleDateFactory()->exportToXml(
            $root,
            $xmlMapping,
            $this->tracker->getId()
        );
        $this->getTrackerRuleListFactory()->exportToXml(
            $root,
            $xmlMapping,
            $this->getTrackerFormElementFactory(),
            $this->tracker->getId()
        );
    }

    private function isFieldUsedInFrozenFieldsTransitionPostAction($field_id)
    {
        return $this->frozen_fields_dao->isFieldUsedInPostAction($field_id);
    }
}
