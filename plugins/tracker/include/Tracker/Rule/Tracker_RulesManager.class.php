<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('Tracker_RuleFactory.class.php');
require_once('Tracker_Rule_Value_View.class.php');

/**
* Manager of rules
*
* This is only a proxy to access the factory. 
* Maybe there is no need to have this intermediary?
*/
class Tracker_RulesManager {
    
    protected $tracker;
    
    public function __construct($tracker) {
        $this->tracker = $tracker;
    }
    
    protected $rules_by_tracker_id;
    function getAllRulesByTrackerWithOrder($tracker_id) {
        if (!isset($this->rules_by_tracker_id[$tracker_id])) {
            $fact = $this->_getTracker_RuleFactory();
            $this->rules_by_tracker_id[$tracker_id] = $fact->getAllRulesByTrackerWithOrder($tracker_id);
        }
        return $this->rules_by_tracker_id[$tracker_id];
    }
    
    function saveRuleValue($tracker_id, $source, $source_value, $target, $target_value) {
        $fact = $this->_getTracker_RuleFactory();
        return $fact->saveRuleValue($tracker_id, $source, $source_value, $target, $target_value);
    }
    
    function deleteRule($rule_id) {
        $fact = $this->_getTracker_RuleFactory();
        return $fact->deleteRule($rule_id);
    }
    
    function deleteRuleValueBySource($tracker_id, $source, $source_value, $target) {
        $fact = $this->_getTracker_RuleFactory();
        return $fact->deleteRuleValueBySource($tracker_id, $source, $source_value, $target);
    }
    
    function deleteRuleValueByTarget($tracker_id, $source, $target, $target_value) {
        $fact = $this->_getTracker_RuleFactory();
        return $fact->deleteRuleValueByTarget($tracker_id, $source, $target, $target_value);
    }
    
    function _getTracker_RuleFactory() {
        return Tracker_RuleFactory::instance();
    }
    
    function deleteRulesByArtifactTracker($tracker_id) {
        $fact = $this->_getTracker_RuleFactory();
        return $fact->deleteRulesByArtifactTracker($tracker_id);
    }
    function deleteRulesByFieldId($tracker_id, $field_id) {
        $fact = $this->_getTracker_RuleFactory();
        return $fact->deleteRulesByFieldId($tracker_id, $field_id);
    }
    function deleteRulesByValueId($tracker_id, $field_id, $value_id) {
        $fact = $this->_getTracker_RuleFactory();
        return $fact->deleteRulesByValueId($tracker_id, $field_id, $value_id);
    }
    
    function copyRules($from_artifact_type_id, $to_artifact_type_id) {
        $fact = $this->_getTracker_RuleFactory();
        return $fact->copyRules($from_artifact_type_id, $to_artifact_type_id);
    }
    
    /**
     * Check if all the selected values of a submitted artefact are coherent regarding the dependencies
     *
     * @param int $tracker_id the artifact id to test
     * @param array $value_field_list the selected values to test for the artifact
     * @param {Tracker_FormElementFactory Object} $ff reference to the artifact field factory of this artifact
     *
     * @return boolean true if the submitted values are coherent regarding the dependencies, false otherwise
     */
    function validate($tracker_id, $value_field_list, $ff) {
        // construction of $values array : selected values in the form
        // $values[$field_id]['field'] = artifactfield Object
        // $values[$field_id]['values'][] = selected value
        $values = array();
        reset($value_field_list);
        while (list($field_id,$value) = each($value_field_list)) {
            $field = $ff->getFormElementById($field_id);
            $values[$field->getID()] = array('field' => $field, 'values' => is_array($value)?$value:array($value));
        }
        // construction of $dependencies array : dependcies defined rules
        // $dependencies[$source_field_id][$target_field_id][] = artifactrulevalue Object
        $dependencies = array();
        foreach($this->getAllRulesByTrackerWithOrder($tracker_id) as $rule) {
            if (is_a($rule, 'Tracker_Rule_Value')) {
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
        reset($dependencies);
        while(!$error_occured && (list($source,) = each($dependencies))) {
            if (isset($values[$source])) {
                reset($dependencies[$source]);
                while(!$error_occured && (list($target,) = each($dependencies[$source]))) {
                    if (isset($values[$target])) {
                       
                        reset($values[$target]['values']);
                        while(!$error_occured && (list(,$target_value) = each($values[$target]['values']))) {
                            //Foreach target values we look if there is at least one source value whith corresponding rule valid
                            $valid = false;
                            reset($values[$source]['values']);
                            while(!$valid && (list(,$source_value) = each($values[$source]['values']))) {
                                $applied = false;
                                reset($dependencies[$source][$target]);
                                while(!($applied && $valid) && (list($rule,) = each($dependencies[$source][$target]))) {
                                    
                                    if ($dependencies[$source][$target][$rule]->canApplyTo(
                                        $tracker_id,
                                        $source, 
                                        $source_value, 
                                        $target, 
                                        $target_value))
                                    {                                        
                                        $applied = true;                                        
                                        $valid = $dependencies[$source][$target][$rule]->applyTo(
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
                                $source_field = $ff->getFormElementById($source);
                                $pb_source_values = $this->_getSelectedValuesForField($source_field, $value_field_list[$source]);
                                $source_field->setHasErrors(true);                                
                                
                                // looking for the target field value which cause the dependence problem
                                $target_field = $ff->getFormElementById($target);
                                $pb_target_values = $this->_getSelectedValuesForField($target_field, $target_value);
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
    
    /**
     * Returns the selected values of a field
     * 
     * @param Tracker_FormElement_Field $field 
     * @param array $value_field_list the selected values to test for the artifact
     *
     * @return array the selected values
     */
    function _getSelectedValuesForField($field, $value_field_list) {
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
            $rules = $this->getAllRulesByTrackerWithOrder($tracker_id);
            $found = false;
            while (!$found && (list(,$rule) = each($rules))) {
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
        $rules = $this->getAllRulesByTrackerWithOrder($tracker_id);
        $found = false;
        while (!$found && (list(,$rule) = each($rules))) {
            $found = ($rule->source_field == $field_id);
        }
        return $found;
    }
    
    function fieldHasSource($tracker_id, $field_id) {
        $rules = $this->getAllRulesByTrackerWithOrder($tracker_id);
        $found = false;
        while (!$found && (list(,$rule) = each($rules))) {
            $found = ($rule->target_field == $field_id);
        }
        return $found;
    }
    
    function valueHasTarget($tracker_id, $field_id, $value_id, $target_id) {
        $rules = $this->getAllRulesByTrackerWithOrder($tracker_id);
        $found = false;
        while (!$found && (list(,$rule) = each($rules))) {
            $found = ($rule->source_field == $field_id && $rule->source_value == $value_id && $rule->target_field == $target_id);
        }
        return $found;
    }
    
    function valueHasSource($tracker_id, $field_id, $value_id, $source_id) {
        $rules = $this->getAllRulesByTrackerWithOrder($tracker_id);
        $found = false;
        while (!$found && (list(,$rule) = each($rules))) {
            $found = ($rule->target_field == $field_id && $rule->target_value == $value_id && $rule->source_field == $source_id);
        }
        return $found;
    }
    
    function ruleExists($tracker_id, $source_id, $target_id) {
        $rules = $this->getAllRulesByTrackerWithOrder($tracker_id);
        $found = false;
        while (!$found && (list(,$rule) = each($rules))) {
            $found = ($rule->source_field == $source_id && $rule->target_field == $target_id);
        }
        return $found;
    }
    
    /*function displayFieldsAndValuesAsJavascript() {
        $hp = Codendi_HTMLPurifier::instance();
        echo "\n//------------------------------------------------------\n";
        $ff = Tracker_FormElementFactory::instance();
        $used_fields = $ff->getUsedSbFields($this->tracker);
        foreach($used_fields as $field) {
            $values = $field->getAllValues();
            if (is_array($values)) {
                echo "codendi.tracker.fields.add('".(int)$field->getID()."', '".$field->getName()."', '". $hp->purify(SimpleSanitizer::unsanitize($field->getLabel()), CODENDI_PURIFIER_JS_QUOTE) ."')";
                $default_value = $field->getDefaultValue();
                foreach ($values as $value) {
                //while ($row = db_fetch_array($values)) {
                    echo "\n\t.addOption('".  $hp->purify(SimpleSanitizer::unsanitize($value->getLabel()), CODENDI_PURIFIER_JS_QUOTE)  ."'.escapeHTML(), '". (int)$value->getId() ."', ". ($value->getId()==$default_value?'true':'false') .")";
                //}
                }
                echo ";\n";
            }
        }
        echo "\n//------------------------------------------------------\n";
    }*/
    
    /*function displayRulesAsJavascript() {
        echo "\n//------------------------------------------------------\n";
        $rules = $this->getAllRulesByTrackerWithOrder($this->tracker->getId());
        if ($rules && count($rules) > 0) {
            foreach ($rules as $key => $nop) {
                $html = new Tracker_Rule_Value_View($rules[$key]);
                echo 'codendi.tracker.rules_definitions.push(';
                $html->fetchJavascript();
                echo ");\n";
            }
        }
        echo "\n//------------------------------------------------------\n";
    }*/
    
    function getAllSourceFields($target_id) {
        $sources = array();
        $ff = Tracker_FormElementFactory::instance();
        $used_fields = $ff->getUsedSbFields($this->tracker);
        
        foreach($used_fields as $field) {
            if (!$target_id || !$this->fieldIsAForbiddenSource($this->tracker->id, $field->getId(), $target_id)) {
                $sources[$field->getId()] = $field;
            }
        }
        return $sources;
    }
    
    function getAllTargetFields($source_id) {
        $targets = array();
       
        $ff = Tracker_FormElementFactory::instance();
        $used_fields = $ff->getUsedSbFields($this->tracker);
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
     * @return array of Tracker_Rule_Value
     */
    public function getDependenciesBySourceTarget($tracker_id, $field_source_id, $field_target_id) {
        $fact = $this->_getTracker_RuleFactory();
        return $fact->getDependenciesBySourceTarget($tracker_id, $field_source_id, $field_target_id);
    }
    
    public function deleteRulesBySourceTarget($tracker_id, $field_source_id, $field_target_id) {
        $fact = $this->_getTracker_RuleFactory();
        return $fact->deleteRulesBySourceTarget($tracker_id, $field_source_id, $field_target_id);
    }
    
    public function process($engine, $request, $current_user) {
        //$this->displayRules($engine);
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
                $field_source = Tracker_FormElementFactory::instance()->getFormElementById($request->get('source_field'));
                $field_source_values = $field_source->getBind()->getAllValues();
                
                $field_target = Tracker_FormElementFactory::instance()->getFormElementById($request->get('target_field'));
                $field_target_values = $field_target->getBind()->getAllValues();
                
                $currMatrix=array();
                           
                foreach($field_source_values as $field_source_value_id =>$field_source_value) {
                   foreach($field_target_values as $field_target_value_id => $field_target_value) {
                       $dependency = $field_source_value_id.'_'.$field_target_value_id;
                       if ($request->existAndNonEmpty($dependency)) {
                           $currMatrix[]=array($field_source_value_id, $field_target_value_id);
                           $this->saveRuleValue($this->tracker->id, $field_source->getId(), $field_source_value_id, $field_target->getId(), $field_target_value_id);
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
        $ff = Tracker_FormElementFactory::instance();
        $hp = Codendi_HTMLPurifier::instance();
        $this->tracker->displayAdminItemHeader($engine, 'dependencies');
        echo '<p>'. $GLOBALS['Language']->getText('plugin_tracker_field_dependencies','inline_help') .'</p>';
        
        echo '<form action="'.TRACKER_BASE_URL.'/?" method="GET">';
        echo '<input type="hidden" name="tracker" value="'. (int)$this->tracker->id .'" />';
        echo '<input type="hidden" name="func" value="admin-dependencies" />';
        
        //source
        $source_field = $ff->getFormElementById($source_field_id);
        if (!$source_field) {
            echo '<select name="source_field" onchange="this.form.submit()">';
            echo '<option value="0">'. $GLOBALS['Language']->getText('plugin_tracker_field_dependencies','choose_source_field') .'</option>';
            $sources = $this->getAllSourceFields(null);
            foreach($sources as $id => $field) {
                echo '<option value="'. $id .'">';
                echo $hp->purify(SimpleSanitizer::unsanitize($field->getLabel()), CODENDI_PURIFIER_CONVERT_HTML);
                echo '</option>';
            }
            echo '</select>';
        } else {
            echo '<input type="hidden" name="source_field" value="'. $source_field_id .'" />';
            echo $source_field->getLabel();
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
                echo $hp->purify(SimpleSanitizer::unsanitize($field->getLabel()), CODENDI_PURIFIER_CONVERT_HTML);
                echo '</option>';
            }
        }
        echo '</select>';
        
        echo ' <input type="submit" name="choose_source" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" />';
        echo '</from>';
        
        //Shortcut
        $sources_targets = $this->_getTracker_RuleFactory()->getInvolvedFieldsByTrackerId($this->tracker->id);
        if (count($sources_targets)) {
            $dependencies = array();
            foreach ($sources_targets as $row) {
                if ($source = $ff->getFormElementById($row['source_field_id'])) {
                    if ($target = $ff->getFormElementById($row['target_field_id'])) {
                        $d = '<a href="'.TRACKER_BASE_URL.'/?'. http_build_query(
                            array(
                                'tracker'      => (int)$this->tracker->id, 
                                'func'         => 'admin-dependencies',
                                'source_field' => $row['source_field_id'],
                                'target_field' => $row['target_field_id'],
                            )
                        ) .'">';
                        $d .= $source->getLabel() .' &rarr; '. $target->getLabel();
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
        $ff = Tracker_FormElementFactory::instance();
        $source_field = $ff->getFieldById($source_field_id);
        $target_field = $ff->getFieldById($target_field_id);
        //Display creation form
        echo '<h3>'.$GLOBALS['Language']->getText('plugin_tracker_field_dependencies','dependencies_matrix_title').'</h3>';
        echo '<p>'. $GLOBALS['Language']->getText('plugin_tracker_field_dependencies','dependencies_matrix_help', array($source_field->getlabel(), $target_field->getlabel())) .'</p>';
     
        $this->displayDependenciesMatrix($source_field, $target_field);
    }
    

    protected function displayDependenciesMatrix($source_field, $target_field, $dependencies=null) {
       
       $source_field_values = array();
       foreach ($source_field->getBind()->getAllValues() as $id => $v) {
           if (!$v->isHidden()) {
               $source_field_values[$id] = $v;
           }
       }
       
       $target_field_values = array();
       foreach ($target_field->getBind()->getAllValues() as $id => $v) {
           if (!$v->isHidden()) {
               $target_field_values[$id] = $v;
           }
       }       
       
       $nb_target_field_values =count($target_field_values);
       echo '<form action="'.TRACKER_BASE_URL.'/?'. http_build_query(array('tracker' => (int)$this->tracker->id, 'source_field' => $source_field->getId(), 'target_field' => $target_field->getId(), 'func'    => 'admin-dependencies')) .'" method="POST">';
       echo '<table id="tracker_field_dependencies_matrix">';
                    
       echo "<tr class=\"".util_get_alt_row_color(1)."\">\n";
       echo "<td></td>";
       foreach($target_field_values as $target_field_value_id=>$target_field_value) {
           echo '<td class="matrix_cell">'.$target_field_value->getLabel()."</td>";
       }
       echo "</tr>";
       
       $dependencies = $this->getDependenciesBySourceTarget($this->tracker->id, $source_field->getId(), $target_field->getId());

       $j=0;
       //Display the available transitions
       foreach($source_field_values as $source_field_value_id=>$source_field_value) {
           echo "<tr class=\"".util_get_alt_row_color($j)."\">\n";
           echo "<td>".$source_field_value->getLabel()."</td>";
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
        $rules = $this->getAllRulesByTrackerWithOrder($this->tracker->id);
        if ($rules && count($rules) > 0) {
            foreach ($rules as $key => $nop) {
                $trvv = new Tracker_Rule_Value_View($rules[$key]);
                $html .= 'codendi.tracker.rules_definitions.push(';
                $html .= $trvv->fetchJavascript();
                $html .= ");\n";
            }
        }
        $html .= "\n//------------------------------------------------------\n";
        $html .= "</script>";
        return $html;
    }
    
    function isUsedInFieldDependency($field) {
        $field_id = $field->getId();
        $rules = $this->getAllRulesByTrackerWithOrder($this->tracker->id);
        foreach ($rules as $rule) {
            if ($rule->isUsedInRule($field->getId())) return true;
        }
        return false;
    }
}

?>
