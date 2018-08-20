<?php
/**
 * Copyright (c) Enalean, 2015-2018. All Rights Reserved.
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


/**
* Manager of rules
*
* This is only a proxy to access the factory.
* Maybe there is no need to have this intermediary?
*/
class Tracker_RulesManager {

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

    public function __construct($tracker, Tracker_FormElementFactory $form_element_factory) {
        $this->tracker              = $tracker;
        $this->form_element_factory = $form_element_factory;
    }

    /**
     *
     * @param int $tracker_id
     * @return Tracker_Rule_List[]
     */
    public function getAllListRulesByTrackerWithOrder($tracker_id) {
        if (!isset($this->rules_by_tracker_id[$tracker_id])) {
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
    public function getAllDateRulesByTrackerId($tracker_id) {
        return $this->getTrackerRuleDateFactory()
                    ->searchByTrackerId($tracker_id);
    }

    /**
     *
     * @return Tracker_Rule_Date_Factory
     */
    public function getTrackerRuleDateFactory() {
        if($this->rule_date_factory ==  null) {
            $this->rule_date_factory = new Tracker_Rule_Date_Factory(new Tracker_Rule_Date_Dao(), $this->form_element_factory);
        }

        return $this->rule_date_factory;
    }

    /**
     *
     * @param Tracker_Rule_Date_Factory $factory
     * @return \Tracker_RulesManager
     */
    public function setRuleDateFactory(Tracker_Rule_Date_Factory $factory) {
        $this->rule_date_factory = $factory;
        return $this;
    }
    
    /**
     *
     * @return Tracker_Rule_List_Factory
     */
    public function getTrackerRuleListFactory() {
        if($this->rule_list_factory ==  null) {
            $this->rule_list_factory = new Tracker_Rule_List_Factory(new Tracker_Rule_List_Dao());
        }

        return $this->rule_list_factory;
    }

    /**
     *
     * @param Tracker_Rule_List_Factory $factory
     * @return \Tracker_RulesManager
     */
    public function setRuleListFactory(Tracker_Rule_List_Factory $factory) {
        $this->rule_list_factory = $factory;
        return $this;
    }

    /**
     *
     * @return Tracker_RuleFactory
     */
    function getRuleFactory() {
        return Tracker_RuleFactory::instance();
    }

    public function setTrackerFormElementFactory(Tracker_FormElementFactory $factory) {
        $this->form_element_factory = $factory;
    }

    public function getTrackerFormElementFactory() {
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
     * @return boolean True if the submitted values are coherent regarding the rules,
     * false otherwise
     */
    function validate($tracker_id, $value_field_list) {
        $valid_list_rules = $this->validateListRules($tracker_id, $value_field_list);
        $valid_date_rules = $this->validateDateRules($tracker_id, $value_field_list);

        if(! $valid_list_rules || ! $valid_date_rules) {
            return false;
        }

        return true;
    }

    /**
     * Returns the selected values of a field
     *
     * @param Tracker_FormElement_Field $field
     * @param array $value_field_list the selected values to test for the artifact
     *
     * @return array the selected values
     */
    function getSelectedValuesForField($field, $value_field_list) {
        if (!is_array($value_field_list)) {
            $value_field_list = array($value_field_list);
        }
        $selected_values = array();
        foreach ($value_field_list as $value_field) {
            $selected_values[] = $field->getBind()->formatArtifactValue($value_field);

        }
        return $selected_values;
    }

    function fieldIsAForbiddenSource($tracker_id, $field_id, $target_id) {
        return !$this->ruleExists($tracker_id, $field_id, $target_id) &&
                (
                    $field_id == $target_id ||
                    $this->isCyclic($tracker_id, $field_id, $target_id) ||
                    $this->fieldHasSource($tracker_id, $target_id)
               );
    }

    function isCyclic($tracker_id, $source_id, $target_id) {
        if ($source_id == $target_id) {
            return true;
        } else {
            $rules = $this->getAllListRulesByTrackerWithOrder($tracker_id);
            $found = false;
            foreach ($rules as $rule) {
                if ($found) {
                    break;
                }
                if ($rule->source_field == $target_id) {
                    $found = $this->isCyclic($tracker_id, $source_id, $rule->target_field);
                }
            }
            return $found;
        }
    }

    function fieldIsAForbiddenTarget($tracker_id, $field_id, $source_id) {
        return !$this->ruleExists($tracker_id, $source_id, $field_id) &&
                (
                    $field_id == $source_id ||
                    $this->isCyclic($tracker_id, $source_id, $field_id) ||
                    $this->fieldHasSource($tracker_id, $field_id)
               );
    }

    function fieldHasTarget($tracker_id, $field_id) {
        $rules = $this->getAllListRulesByTrackerWithOrder($tracker_id);
        foreach ($rules as $rule) {
            if ($rule->source_field == $field_id) {
                return true;
            }
        }
        return false;
    }

    function fieldHasSource($tracker_id, $field_id) {
        $rules = $this->getAllListRulesByTrackerWithOrder($tracker_id);
        foreach ($rules as $rule) {
            if ($rule->target_field == $field_id) {
                return true;
            }
        }
        return false;
    }

    function valueHasTarget($tracker_id, $field_id, $value_id, $target_id) {
        $rules = $this->getAllListRulesByTrackerWithOrder($tracker_id);
        foreach ($rules as $rule) {
            if ($rule->source_field == $field_id && $rule->source_value == $value_id && $rule->target_field == $target_id) {
                return true;
            }
        }
        return false;
    }

    function valueHasSource($tracker_id, $field_id, $value_id, $source_id) {
        $rules = $this->getAllListRulesByTrackerWithOrder($tracker_id);
        foreach ($rules as $rule) {
            if ($rule->target_field == $field_id && $rule->target_value == $value_id && $rule->source_field == $source_id) {
                return true;
            }
        }
        return false;
    }

    function ruleExists($tracker_id, $source_id, $target_id) {
        $rules = $this->getAllListRulesByTrackerWithOrder($tracker_id);
        foreach ($rules as $rule) {
            if ($rule->source_field == $source_id && $rule->target_field == $target_id) {
                return true;
            }
        }
        return false;
    }

    function getAllSourceFields($target_id) {
        $sources     = array();
        $used_fields = $this->form_element_factory->getUsedSbFields($this->tracker);
        foreach($used_fields as $field) {
            if (!$target_id || !$this->fieldIsAForbiddenSource($this->tracker->id, $field->getId(), $target_id)) {
                $sources[$field->getId()] = $field;
            }
        }
        return $sources;
    }

    function getAllTargetFields($source_id) {
        $targets     = array();
        $used_fields = $this->form_element_factory->getUsedSbFields($this->tracker);
        foreach($used_fields as $field) {
            if (!$source_id || !$this->fieldIsAForbiddenTarget($this->tracker->id, $field->getId(), $source_id)) {
                $targets[$field->getId()] = $field;
            }
        }
        return $targets;
    }

    function displayRules($engine, $source_field = false, $target_field = false, $source_value = false, $target_value = false) {
        $this->tracker->displayAdminItemHeader($engine, 'dependencies');
        echo '<p>'. $GLOBALS['Language']->getText('plugin_tracker_field_dependencies','inline_help') .'</p>';
        echo '<br />';
        $this->displayEditForm($source_field, $target_field, $source_value, $target_value);
        echo '<br />';
        $this->tracker->displayFooter($engine);
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
    public function getDependenciesBySourceTarget($tracker_id, $field_source_id, $field_target_id) {
        $fact = $this->getRuleFactory();
        return $fact->getDependenciesBySourceTarget($tracker_id, $field_source_id, $field_target_id);
    }

    public function deleteRulesBySourceTarget($tracker_id, $field_source_id, $field_target_id) {
        $fact = $this->getRuleFactory();
        return $fact->deleteRulesBySourceTarget($tracker_id, $field_source_id, $field_target_id);
    }

    public function process($engine, $request, $current_user) {
        if ($request->get('source_field') && !$request->get('target_field')) {
            $source_field = $request->get('source_field');
            $this->displayChooseSourceAndTarget($engine, $request, $current_user, $source_field);
        } else if($request->get('source_field') && $request->get('target_field')) {
            if (!$request->isPost() || !$request->get('create_field_dependencies')) {
                $source_field = $request->get('source_field');
                $target_field = $request->get('target_field');
                $tracker_id = $this->tracker->id;

                if ($this->isCyclic($tracker_id, $source_field, $target_field) ||
                    $this->fieldIsAForbiddenSource($tracker_id, $source_field, $target_field) ||
                    $this->fieldIsAForbiddenTarget($tracker_id, $target_field, $source_field)
                ) {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_field_dependencies','dependencies_not_authorized'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?'. http_build_query(array('tracker' => (int)$tracker_id, 'func'    => 'admin-dependencies')));
                } else {

                    $this->displayDefineDependencies($engine, $request, $current_user, $source_field, $target_field);
                }
            } else {
                //We delete all previous rules
                $this->deleteRulesBySourceTarget($this->tracker->id, $request->get('source_field'), $request->get('target_field'));

                //Add dependencies in db
                $field_source = $this->form_element_factory->getFormElementById($request->get('source_field'));
                $field_source_values = $field_source->getVisibleValuesPlusNoneIfAny();

                $field_target = $this->form_element_factory->getFormElementById($request->get('target_field'));
                $field_target_values = $field_target->getVisibleValuesPlusNoneIfAny();

                $currMatrix=array();

                foreach($field_source_values as $field_source_value_id =>$field_source_value) {
                   foreach($field_target_values as $field_target_value_id => $field_target_value) {
                       $dependency = $field_source_value_id.'_'.$field_target_value_id;
                       if ($request->existAndNonEmpty($dependency)) {
                           $currMatrix[]=array($field_source_value_id, $field_target_value_id);
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
                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('workflow_admin','updated'));
                $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?'. http_build_query(array('tracker' => (int)$this->tracker->id, 'func'    => 'admin-dependencies')));
            }
        } else {
            $this->displayChooseSourceAndTarget($engine, $request, $current_user, null);
        }
    }


    function displayChooseSourceAndTarget($engine, $request, $current_user, $source_field_id) {
        $hp = Codendi_HTMLPurifier::instance();
        $this->tracker->displayAdminItemHeader($engine, 'dependencies');
        echo '<p>'. $GLOBALS['Language']->getText('plugin_tracker_field_dependencies','inline_help') .'</p>';

        echo '<form action="'.TRACKER_BASE_URL.'/?" method="GET">';
        echo '<input type="hidden" name="tracker" value="'. (int)$this->tracker->id .'" />';
        echo '<input type="hidden" name="func" value="admin-dependencies" />';

        //source
        $source_field = $this->form_element_factory->getFormElementById($source_field_id);
        if (!$source_field) {
            echo '<select name="source_field" onchange="this.form.submit()">';
            echo '<option value="0">'. $GLOBALS['Language']->getText('plugin_tracker_field_dependencies','choose_source_field') .'</option>';
            $sources = $this->getAllSourceFields(null);
            foreach($sources as $id => $field) {
                echo '<option value="'. $hp->purify($id) .'">';
                echo $hp->purify($field->getLabel(), CODENDI_PURIFIER_CONVERT_HTML);
                echo '</option>';
            }
            echo '</select>';
        } else {
            echo '<input type="hidden" name="source_field" value="'. $hp->purify($source_field_id) .'" />';
            echo $hp->purify($source_field->getLabel());
        }

        echo ' &rarr; ';

        //target
        $disabled = '';
        if (!$source_field) {
            $disabled = 'disabled="disabled" readonly="readonly"';
        }
        echo '<select name="target_field" '. $disabled .'>';
        echo '<option value="0">'. $GLOBALS['Language']->getText('plugin_tracker_field_dependencies','choose_target_field') .'</option>';
        if ($source_field) {
            $sources = $this->getAllTargetFields($source_field_id);
            foreach($sources as $id => $field) {
                echo '<option value="'. $id .'">';
                echo $hp->purify($field->getLabel(), CODENDI_PURIFIER_CONVERT_HTML);
                echo '</option>';
            }
        }
        echo '</select>';

        echo ' <input type="submit" name="choose_source" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" />';
        echo '</form>';

        //Shortcut
        $sources_targets = $this->getRuleFactory()->getInvolvedFieldsByTrackerId($this->tracker->id);
        if (count($sources_targets)) {
            $dependencies = array();
            foreach ($sources_targets as $row) {
                if ($source = $this->form_element_factory->getFormElementById($row['source_field_id'])) {
                    if ($target = $this->form_element_factory->getFormElementById($row['target_field_id'])) {
                        $d = '<a href="'.TRACKER_BASE_URL.'/?'. http_build_query(
                            array(
                                'tracker'      => (int)$this->tracker->id,
                                'func'         => 'admin-dependencies',
                                'source_field' => $row['source_field_id'],
                                'target_field' => $row['target_field_id'],
                            )
                        ) .'">';
                        $d .= $hp->purify($source->getLabel()) .' &rarr; '. $hp->purify($target->getLabel());
                        $d .= '</a>';
                        $dependencies[] = $d;
                    }
                }
            }

            if ($dependencies) {
                echo '<p>'.$GLOBALS['Language']->getText('plugin_tracker_field_dependencies','choose_existing_dependency').'</p>';
                echo '<ul><li>'. implode('</li><li>', $dependencies) .'</li></ul>';
            }
            echo '</ul>';
        }

        $this->tracker->displayFooter($engine);

    }

    function displayDefineDependencies($engine, $request, $current_user, $source_field_id, $target_field_id) {
        $hp = Codendi_HTMLPurifier::instance();
        $this->tracker->displayAdminItemHeader($engine, 'dependencies');
        $source_field = $this->form_element_factory->getFieldById($source_field_id);
        $target_field = $this->form_element_factory->getFieldById($target_field_id);
        //Display creation form
        echo '<h3>'.$GLOBALS['Language']->getText('plugin_tracker_field_dependencies','dependencies_matrix_title').'</h3>';
        echo '<p>'. $GLOBALS['Language']->getText(
                'plugin_tracker_field_dependencies',
                'dependencies_matrix_help',
                array(
                    $hp->purify($source_field->getlabel()),
                    $hp->purify($target_field->getlabel())
                )
            ) .'</p>';

        $this->displayDependenciesMatrix($source_field, $target_field);
    }


    protected function displayDependenciesMatrix($source_field, $target_field, $dependencies=null) {
       $source_field_values = $source_field->getVisibleValuesPlusNoneIfAny();
       $target_field_values = $target_field->getVisibleValuesPlusNoneIfAny();

       $purifier = Codendi_HTMLPurifier::instance();
       echo '<form action="'.TRACKER_BASE_URL.'/?'. http_build_query(array('tracker' => (int)$this->tracker->id, 'source_field' => $source_field->getId(), 'target_field' => $target_field->getId(), 'func'    => 'admin-dependencies')) .'" method="POST">';
       echo '<table id="tracker_field_dependencies_matrix">';

       echo "<tr class=\"".util_get_alt_row_color(1)."\">\n";
       echo "<td></td>";
       foreach($target_field_values as $target_field_value_id=>$target_field_value) {
           echo '<td class="matrix_cell">'.$purifier->purify($target_field_value->getLabel())."</td>";
       }
       echo "</tr>";

       $dependencies = $this->getDependenciesBySourceTarget($this->tracker->id, $source_field->getId(), $target_field->getId());

       $j=0;
       //Display the available transitions
       foreach($source_field_values as $source_field_value_id=>$source_field_value) {
           echo "<tr class=\"".util_get_alt_row_color($j)."\">\n";
           echo "<td>".$purifier->purify($source_field_value->getLabel())."</td>";
           foreach($target_field_values as $target_field_value_id =>$target_field_value) {
               $box_value = $source_field_value_id.'_'.$target_field_value_id;
               $this->displayCheckbox($source_field_value_id, $target_field_value_id, $dependencies, $box_value);
           }
           echo "</tr>\n";
           $j++;
       }

        echo '</table>';
        echo '<a href="'.TRACKER_BASE_URL.'/?'. http_build_query(
            array(
                'tracker' => (int)$this->tracker->id,
                'func'    => 'admin-dependencies',
            )
        ). '">';
        echo '&laquo; '. $GLOBALS['Language']->getText('global', 'btn_cancel');
        echo '</a> ';
        echo '<input type="submit" name="create_field_dependencies" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" />';
        echo '</FORM>';
    }

    protected function displayCheckbox($source_field_value_id, $target_field_value_id, $dependencies, $box_value) {
        $checked = '';
        if(count($dependencies)>0) {
            foreach($dependencies as $dependency) {
                if($source_field_value_id==$dependency->source_value && $target_field_value_id==$dependency->target_value) {
                    $checked = 'checked="checked"';
                    break;
                }
             }
          }

          echo '<td class="matrix_cell" ><label class="pc_checkbox"><input type="checkbox" name="'.$box_value.'" '. $checked .'>&nbsp;</label></td>';
    }

    function displayRulesAsJavascript() {
        $html = '<script type="text/javascript">';
        $html .= "\n//------------------------------------------------------\n";
        $rules = $this->getAllListRulesByTrackerWithOrder($this->tracker->id);
        if ($rules && count($rules) > 0) {
            foreach ($rules as $key => $nop) {
                $trvv = new Tracker_Rule_List_View($rules[$key]);
                $html .= 'codendi.tracker.rules_definitions.push(';
                $html .= $trvv->fetchJavascript();
                $html .= ");\n";
            }
        }
        $html .= "\n//------------------------------------------------------\n";
        $html .= "</script>";
        return $html;
    }

    /** @return bool */
    public function isUsedInFieldDependency(Tracker_FormElement $field) {
        $field_id = $field->getId();
        $list_rules = $this->getAllListRulesByTrackerWithOrder($this->tracker->getId());
        $date_rules = $this->getAllDateRulesByTrackerId($this->tracker->getId());
        $rules = array_merge($list_rules, $date_rules);
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
    public function exportToXml(SimpleXMLElement $root, $xmlMapping) {
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

    /**
     *
     * @param int $tracker_id
     * @param array $value_field_list
     * @return boolean
     */
    protected function validateDateRules($tracker_id, $value_field_list) {
        $rules = $this->getAllDateRulesByTrackerId($tracker_id);

        foreach ($rules as $rule) {
            
            if (! $this->dateRuleApplyToSubmittedFields($rule, $value_field_list)) {
                return false;
            }
                    
            if (! $this->validateDateRuleOnSubmittedFields($rule, $value_field_list)) {
                $source_field = $this->getField($rule->getSourceFieldId());
                $target_field = $this->getField($rule->getTargetFieldId());
                $feedback = $GLOBALS['Language']->getText(
                        'plugin_tracker_artifact',
                        'rules_date_not_valid',
                        array(
                            $source_field->getLabel() ,
                            $rule->getComparator(),
                            $target_field->getLabel() 
                        )
                    );
                
                $GLOBALS['Response']->addFeedback('error', $feedback);
                
                $source_field->setHasErrors(true);
                $target_field->setHasErrors(true);
                return false;
            }
        }

        return true;
    }

    private function dateRuleApplyToSubmittedFields(Tracker_Rule_Date $rule, array $value_field_list) {
        $is_valid = true;
        if(! array_key_exists($rule->getSourceFieldId(), $value_field_list)) {
            $source_field = $this->getField($rule->getSourceFieldId());
            $feedback = $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'missing_field_value') . $source_field->getLabel();
            
            $GLOBALS['Response']->addUniqueFeedback('error', $feedback);
            $source_field->setHasErrors(true);
            $is_valid = false;
        }
        
        if(! array_key_exists($rule->getTargetFieldId(), $value_field_list)) {
            $target_field = $this->getField($rule->getTargetFieldId());
            $feedback = $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'missing_field_value') . $target_field->getLabel();
            $GLOBALS['Response']->addUniqueFeedback('error', $feedback);
            $target_field->setHasErrors(true);
            $is_valid = false;
        } 
          
        return $is_valid;
    }

    /** @return bool */
    private function validateDateRuleOnSubmittedFields(Tracker_Rule_Date $rule, array $value_field_list) {
        $source_value = $value_field_list[$rule->getSourceFieldId()];
        $target_value = $value_field_list[$rule->getTargetFieldId()];

        return $rule->validate($source_value, $target_value);
    }

    /**
     * Checks that the submitted values do not break field dependencies.
     *
     * The logic is that if a rule from a source field to a target field exists
     * AND the incoming source value corresponds to a source value of ONE of the rule source values
     * then the incoming target value must be in one of the rules.
     * I.e one rule must be satsified for a given (source_id, target_id, source_value) trio.
     *
     * @param int $tracker_id
     * @param array $value_field_list
     * @return boolean
     */
    protected function validateListRules($tracker_id, $value_field_list) {
         // construction of $values array : selected values in the form
        // $values[$field_id]['field'] = artifactfield Object
        // $values[$field_id]['values'][] = selected value
        $values = array();
        foreach ($value_field_list as $field_id => $value) {
            if ($field = $this->getTrackerFormElementFactory()->getFormElementById($field_id)) {
                $values[$field->getID()] = array('field' => $field, 'values' => is_array($value)?$value:array($value));
            }
        }
        // construction of $dependencies array : dependcies defined rules
        // $dependencies[$source_field_id][$target_field_id][] = artifactrulevalue Object
        $dependencies = array();
        foreach($this->getAllListRulesByTrackerWithOrder($tracker_id) as $rule) {
            if (is_a($rule, 'Tracker_Rule_List')) {
                if (!isset($dependencies[$rule->source_field])) {
                    $dependencies[$rule->source_field] = array();
                }
                if (!isset($dependencies[$rule->source_field][$rule->target_field])) {
                    $dependencies[$rule->source_field][$rule->target_field] = array();
                }
                $dependencies[$rule->source_field][$rule->target_field][] = $rule;
            }
        }

        $error_occured = false;
        foreach ($dependencies as $source => $not_used) {
            if ($error_occured) {
                break;
            }
            if (isset($values[$source])) {
                foreach ($dependencies[$source] as $target => $not_used_target) {
                    if ($error_occured) {
                        break;
                    }
                    if (isset($values[$target])) {
                        foreach ($values[$target]['values'] as $target_value) {
                            if ($error_occured) {
                                break;
                            }
                            if($target_value == Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID
                                    || $target_value == null) {
                                
                                /*
                                 * Field dependencies are only set between fields with values.
                                 * Ideally, field dependencies between all other fields and the null/ 100
                                 * value should be set.
                                 */
                                continue;
                            }
                            //Foreach target values we look if there is at least one source value whith corresponding rule valid
                            $valid = false;
                            foreach ($values[$source]['values'] as $source_value) {
                                if ($valid) {
                                    break;
                                }
                                
                                $applied = false;
                                foreach ($dependencies[$source][$target] as $rule) {
                                    if ($applied && $valid) {
                                        break;
                                    }
                                    if ($rule->canApplyTo(
                                        $tracker_id,
                                        $source,
                                        $source_value,
                                        $target,
                                        $target_value))
                                    {
                                        $applied = true;
                                        $valid = $rule->applyTo(
                                            $tracker_id,
                                            $source,
                                            $source_value,
                                            $target,
                                            $target_value);
                                    }
                                }
                            }
                            // when a dependence problem is detected, we detail the message error
                            // to explain the fields that trigger the problem
                            if (! $valid) {
                                                               
                                $error_occured = true;
                                // looking for the source field value which cause the dependence problem
                                $source_field = $this->getTrackerFormElementFactory()->getFormElementById($source);
                                if(is_null($value_field_list[$source])) {
                                    $pb_source_values = array();
                                } else {
                                    $pb_source_values = $this->getSelectedValuesForField($source_field, $value_field_list[$source]);
                                }
                                $source_field->setHasErrors(true);

                                // looking for the target field value which cause the dependence problem
                                $target_field = $this->getTrackerFormElementFactory()->getFormElementById($target);
                                if(is_null($target_value)) {
                                   $pb_target_values = array();
                                } else {
                                    $pb_target_values = $this->getSelectedValuesForField($target_field, $target_value);
                                }
                                $target_field->setHasErrors(true);
                               // detailled error message
                               $GLOBALS['Response']->addFeedback('error', $values[$source]['field']->getLabel().'('. implode(', ', $pb_source_values) .') -> '.$values[$target]['field']->getLabel().'('. implode(', ', $pb_target_values) .')');
                            }
                        }
                    }
                }
            }
        }
        return !$error_occured;
    }

    private function getField($field_id) {
        $tracker_list_factory = $this->getTrackerFormElementFactory();
        $field                = $tracker_list_factory->getFormElementById($field_id);
        return $field;
    }

    /**
     * @param Boolean $user_can_read_workdlow_field
     *
     * @return array { 'dates' => [...], 'lists' => [...] }
     */
    public function exportToSOAP($user_can_read_workdlow_field) {
        $soap = array(
            'dates' => array(),
            'lists' => array()
        );
        if ($user_can_read_workdlow_field) {
            foreach ($this->getAllListRulesByTrackerWithOrder($this->tracker->getId()) as $rule) {
                $soap['lists'][] = $rule->exportToSOAP();
            }
            foreach ($this->getAllDateRulesByTrackerId($this->tracker->getId()) as $rule) {
                $export = $rule->exportToSOAP();
                if ($export) {
                    $soap['dates'][] = $rule->exportToSOAP();
                }
            }
        }
        return $soap;
    }
}
?>
