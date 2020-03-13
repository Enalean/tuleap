<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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


require_once('ArtifactRuleFactory.class.php');

/**
* Manager of rules
*
* This is only a proxy to access the factory.
* Maybe there is no need to have this intermediary?
*/
class ArtifactRulesManager
{


    public function __construct()
    {
    }

    protected $rules_by_tracker_id;
    public function getAllRulesByArtifactTypeWithOrder($artifact_type_id)
    {
        if (!isset($this->rules_by_tracker_id[$artifact_type_id])) {
            $fact = $this->_getArtifactRuleFactory();
            $this->rules_by_tracker_id[$artifact_type_id] = $fact->getAllRulesByArtifactTypeWithOrder($artifact_type_id);
        }
        return $this->rules_by_tracker_id[$artifact_type_id];
    }

    public function saveRuleValue($artifact_type_id, $source, $source_value, $target, $target_value)
    {
        $fact = $this->_getArtifactRuleFactory();
        return $fact->saveRuleValue($artifact_type_id, $source, $source_value, $target, $target_value);
    }

    public function deleteRule($rule_id)
    {
        $fact = $this->_getArtifactRuleFactory();
        return $fact->deleteRule($rule_id);
    }

    public function deleteRuleValueBySource($artifact_type_id, $source, $source_value, $target)
    {
        $fact = $this->_getArtifactRuleFactory();
        return $fact->deleteRuleValueBySource($artifact_type_id, $source, $source_value, $target);
    }

    public function deleteRuleValueByTarget($artifact_type_id, $source, $target, $target_value)
    {
        $fact = $this->_getArtifactRuleFactory();
        return $fact->deleteRuleValueByTarget($artifact_type_id, $source, $target, $target_value);
    }

    public function _getArtifactRuleFactory()
    {
        return ArtifactRuleFactory::instance();
    }

    public function deleteRulesByArtifactType($artifact_type_id)
    {
        $fact = $this->_getArtifactRuleFactory();
        return $fact->deleteRulesByArtifactType($artifact_type_id);
    }
    public function deleteRulesByFieldId($artifact_type_id, $field_id)
    {
        $fact = $this->_getArtifactRuleFactory();
        return $fact->deleteRulesByFieldId($artifact_type_id, $field_id);
    }
    public function deleteRulesByValueId($artifact_type_id, $field_id, $value_id)
    {
        $fact = $this->_getArtifactRuleFactory();
        return $fact->deleteRulesByValueId($artifact_type_id, $field_id, $value_id);
    }

    public function copyRules($from_artifact_type_id, $to_artifact_type_id)
    {
        $fact = $this->_getArtifactRuleFactory();
        return $fact->copyRules($from_artifact_type_id, $to_artifact_type_id);
    }

    /**
     * Check if all the selected values of a submitted artefact are coherent regarding the dependences
     *
     * @param int $artifact_type_id the artifact id to test
     * @param array $value_field_list the selected values to test for the artifact
     * @param {ArtifactFieldFactory Object} $art_field_fact reference to the artifact field factory of this artifact
     * @return bool true if the submitted values are coherent regarding the dependencies, false otherwise
     */
    public function validate($artifact_type_id, $value_field_list, $art_field_fact)
    {
        // construction of $values array : selected values in the form
        // $values[$field_id]['field'] = artifactfield Object
        // $values[$field_id]['values'][] = selected value
        $values = array();
        foreach ($value_field_list as $field_name => $value) {
            $field = $art_field_fact->getFieldFromName($field_name);
            $values[$field->getID()] = array('field' => $field, 'values' => is_array($value) ? $value : array($value));
        }

        // construction of $dependencies array : dependcies defined rules
        // $dependencies[$source_field_id][$target_field_id][] = artifactrulevalue Object
        $dependencies = array();
        foreach ($this->getAllRulesByArtifactTypeWithOrder($artifact_type_id) as $rule) {
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
        foreach ($dependencies as $source => $dep) {
            if ($error_occured) {
                break;
            }
            if (isset($values[$source])) {
                foreach ($dependencies[$source] as $target => $dep_source_value) {
                    if ($error_occured) {
                        break;
                    }
                    if (isset($values[$target])) {
                        foreach ($values[$target]['values'] as $target_value) {
                            if ($error_occured) {
                                break;
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
                                        $artifact_type_id,
                                        $source,
                                        $source_value,
                                        $target,
                                        $target_value
                                    )) {
                                        $applied = true;
                                        $valid = $rule->applyTo(
                                            $artifact_type_id,
                                            $source,
                                            $source_value,
                                            $target,
                                            $target_value
                                        );
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
                                if (empty($pb_target_values)) {
                                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('tracker_index', 'missing_dependency', $values[$target]['field']->getLabel()));
                                }
                                if (empty($pb_source_values)) {
                                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('tracker_index', 'missing_dependency', $values[$source]['field']->getLabel()));
                                }
                                $GLOBALS['Response']->addFeedback('error', $values[$source]['field']->getLabel() . '(' . implode(', ', $pb_source_values) . ') -> ' . $values[$target]['field']->getLabel() . '(' . implode(', ', $pb_target_values) . ')');
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
    public function _getSelectedValuesForField($db_result, $field_id, $field_values)
    {
        if (!is_array($field_values)) {
            $field_values = array($field_values);
        }
        $selected_values = array();
        if (db_numrows($db_result) > 1) {
            while ($row = db_fetch_array($db_result)) {
                if (isset($row['field_id'])) {
                    if ($row['field_id'] == $field_id && in_array($row['value_id'], $field_values)) {
                        $selected_values[] = $row['value'];
                    }
                } elseif (in_array($row['user_id'], $field_values)) {
                    $selected_values[] = $row['user_name'];
                }
            }
        }
        return $selected_values;
    }

    public function fieldIsAForbiddenSource($artifact_type_id, $field_id, $target_id)
    {
        return !$this->ruleExists($artifact_type_id, $field_id, $target_id) &&
                (
                    $field_id == $target_id ||
                    $this->isCyclic($artifact_type_id, $field_id, $target_id) ||
                    $this->fieldHasSource($artifact_type_id, $target_id)
               );
    }

    public function isCyclic($artifact_type_id, $source_id, $target_id)
    {
        if ($source_id == $target_id) {
            return true;
        } else {
            $rules = $this->getAllRulesByArtifactTypeWithOrder($artifact_type_id);
            $found = false;
            foreach ($rules as $rule) {
                if ($found) {
                    break;
                }
                if ($rule->source_field == $target_id) {
                    $found = $this->isCyclic($artifact_type_id, $source_id, $rule->target_field);
                }
            }
            return $found;
        }
    }

    public function fieldIsAForbiddenTarget($artifact_type_id, $field_id, $source_id)
    {
        return !$this->ruleExists($artifact_type_id, $source_id, $field_id) &&
                (
                    $field_id == $source_id ||
                    $this->isCyclic($artifact_type_id, $source_id, $field_id) ||
                    $this->fieldHasSource($artifact_type_id, $field_id)
               );
    }

    public function fieldHasTarget($artifact_type_id, $field_id)
    {
        $rules = $this->getAllRulesByArtifactTypeWithOrder($artifact_type_id);
        $found = false;
        foreach ($rules as $rule) {
            if ($found) {
                break;
            }
            $found = ($rule->source_field == $field_id);
        }
        return $found;
    }

    public function fieldHasSource($artifact_type_id, $field_id)
    {
        $rules = $this->getAllRulesByArtifactTypeWithOrder($artifact_type_id);
        $found = false;
        foreach ($rules as $rule) {
            if ($found) {
                break;
            }
            $found = ($rule->target_field == $field_id);
        }
        return $found;
    }

    public function valueHasTarget($artifact_type_id, $field_id, $value_id, $target_id)
    {
        $rules = $this->getAllRulesByArtifactTypeWithOrder($artifact_type_id);
        $found = false;
        foreach ($rules as $rule) {
            if ($found) {
                break;
            }
            $found = ($rule->source_field == $field_id && $rule->source_value == $value_id && $rule->target_field == $target_id);
        }
        return $found;
    }

    public function valueHasSource($artifact_type_id, $field_id, $value_id, $source_id)
    {
        $rules = $this->getAllRulesByArtifactTypeWithOrder($artifact_type_id);
        $found = false;
        foreach ($rules as $rule) {
            if ($found) {
                break;
            }
            $found = ($rule->target_field == $field_id && $rule->target_value == $value_id && $rule->source_field == $source_id);
        }
        return $found;
    }

    public function ruleExists($artifact_type_id, $source_id, $target_id)
    {
        $rules = $this->getAllRulesByArtifactTypeWithOrder($artifact_type_id);
        $found = false;
        foreach ($rules as $rule) {
            if ($found) {
                break;
            }
            $found = ($rule->source_field == $source_id && $rule->target_field == $target_id);
        }
        return $found;
    }
}
