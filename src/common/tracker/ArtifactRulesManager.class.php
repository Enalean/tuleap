<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 *
 * Originally written by Nicolas Terray, 2006
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * $Id$
 */


require_once('ArtifactRuleFactory.class.php');

/**
* Manager of rules
*
* This is only a proxy to access the factory. 
* Maybe there is no need to have this intermediary?
*/
class ArtifactRulesManager {


	function ArtifactRulesManager() {
    }
    
    function getAllRulesByArtifactTypeWithOrder($artifact_type_id) {
		$fact =& $this->_getArtifactRuleFactory();
        return $fact->getAllRulesByArtifactTypeWithOrder($artifact_type_id);
	}
    
    function saveRuleValue($artifact_type_id, $source, $source_value, $target, $target_value) {
        $fact =& $this->_getArtifactRuleFactory();
        return $fact->saveRuleValue($artifact_type_id, $source, $source_value, $target, $target_value);
    }
    
    function deleteRule($rule_id) {
        $fact =& $this->_getArtifactRuleFactory();
        return $fact->deleteRule($rule_id);
    }
    
    function deleteRuleValueBySource($artifact_type_id, $source, $source_value, $target) {
        $fact =& $this->_getArtifactRuleFactory();
        return $fact->deleteRuleValueBySource($artifact_type_id, $source, $source_value, $target);
    }
    
    function deleteRuleValueByTarget($artifact_type_id, $source, $target, $target_value) {
        $fact =& $this->_getArtifactRuleFactory();
        return $fact->deleteRuleValueByTarget($artifact_type_id, $source, $target, $target_value);
    }
    
    function &_getArtifactRuleFactory() {
        return ArtifactRuleFactory::instance();
    }
    
    function deleteRulesByArtifactType($artifact_type_id) {
        $fact =& $this->_getArtifactRuleFactory();
        return $fact->deleteRulesByArtifactType($artifact_type_id);
    }
    function deleteRulesByFieldId($artifact_type_id, $field_id) {
        $fact =& $this->_getArtifactRuleFactory();
        return $fact->deleteRulesByFieldId($artifact_type_id, $field_id);
    }
    function deleteRulesByValueId($artifact_type_id, $field_id, $value_id) {
        $fact =& $this->_getArtifactRuleFactory();
        return $fact->deleteRulesByValueId($artifact_type_id, $field_id, $value_id);
    }
    
    function copyRules($from_artifact_type_id, $to_artifact_type_id) {
        $fact =& $this->_getArtifactRuleFactory();
        return $fact->copyRules($from_artifact_type_id, $to_artifact_type_id);
    }
    
    /**
     * Check if all the selected values of a submitted artefact are coherent regarding the dependences 
     *
     * @param int $artifact_type_id the artifact id to test
     * @param array $value_field_list the selected values to test for the artifact
     * @param {ArtifactFieldFactory Object} $art_field_fact reference to the artifact field factory of this artifact
     * @return boolean true if the submitted values are coherent regarding the dependencies, false otherwise
     */
    function validate($artifact_type_id, $value_field_list, &$art_field_fact) {
        
        // construction of $values array : selected values in the form
        // $values[$field_id]['field'] = artifactfield Object
        // $values[$field_id]['values'][] = selected value
        $values = array();
        reset($value_field_list);
        while (list($field_name,$value) = each($value_field_list)) {
            $field =& $art_field_fact->getFieldFromName($field_name);
            $values[$field->getID()] = array('field' => &$field, 'values' => is_array($value)?$value:array($value));
        }
        
        // construction of $dependencies array : dependcies defined rules
        // $dependencies[$source_field_id][$target_field_id][] = artifactrulevalue Object
        $dependencies = array();
        foreach($this->getAllRulesByArtifactTypeWithOrder($artifact_type_id) as $rule) {
            if (is_a($rule, 'ArtifactRuleValue')) {
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
                                        $artifact_type_id, 
                                        $source, 
                                        $source_value, 
                                        $target, 
                                        $target_value))
                                    {
                                        $applied = true;
                                        $valid = $dependencies[$source][$target][$rule]->applyTo(
                                            $artifact_type_id, 
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
                                $pb_source_field_values = $values[$source]['field']->getFieldPredefinedValues($artifact_type_id);
                                $pb_source_values = $this->_getSelectedValuesForField($pb_source_field_values, $source, $values[$source]['values']);
                                
                                // looking for the target field value which cause the dependence problem
                                $pb_target_field_values = $values[$target]['field']->getFieldPredefinedValues($artifact_type_id);
                                $pb_target_values = $this->_getSelectedValuesForField($pb_target_field_values, $target, $target_value);
                                
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
     * @access protected
     */
    function _getSelectedValuesForField($db_result, $field_id, $field_values) {
        if (!is_array($field_values)) {
            $field_values = array($field_values);
        }
        $selected_values = array();
        if (db_numrows($db_result) > 1) {
            while ($row = db_fetch_array($db_result)) {
                if ($row['field_id'] == $field_id && in_array($row['value_id'], $field_values)) {
                    $selected_values[] = $row['value'];
                }
            }
        }
        return $selected_values;
    }
    
}

?>
