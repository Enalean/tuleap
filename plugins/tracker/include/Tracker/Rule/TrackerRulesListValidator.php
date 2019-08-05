<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Rule;

use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_List_Bind_StaticValue_None;
use Tracker_FormElementFactory;
use Tracker_Rule_List;

class TrackerRulesListValidator
{
    /**
     * Tracker_FormElementFactory
     */
    private $form_element_factory;

    public function __construct(Tracker_FormElementFactory $form_element_factory)
    {
        $this->form_element_factory = $form_element_factory;
    }

    /**
     * Checks that the submitted values do not break field dependencies.
     *
     * The logic is that if a rule from a source field to a target field exists
     * AND the incoming source value corresponds to a source value of ONE of the rule source values
     * then the incoming target value must be in one of the rules.
     * I.e one rule must be satsified for a given (source_id, target_id, source_value) trio.
     *
     */
    public function validateListRules(int $tracker_id, array $value_field_list, array $list_rules) : bool
    {
        // construction of $values array : selected values in the form
        // $values[$field_id]['field'] = artifactfield Object
        // $values[$field_id]['values'][] = selected value
        $values = array();
        foreach ($value_field_list as $field_id => $value) {
            if ($field = $this->form_element_factory->getFormElementListById($field_id)) {
                $values[$field->getID()] = array('field' => $field, 'values' => is_array($value)?$value:array($value));
            }
        }
        // construction of $dependencies array : dependcies defined rules
        // $dependencies[$source_field_id][$target_field_id][] = artifactrulevalue Object
        $dependencies = array();
        foreach ($list_rules as $rule) {
            if ($rule instanceof Tracker_Rule_List) {
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
                            if ($target_value == Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID
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
                                        $target_value
                                    )
                                    ) {
                                        $applied = true;
                                        $valid = $rule->applyTo(
                                            $tracker_id,
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
                                $source_field = $this->form_element_factory->getFormElementListById($source);
                                if ($source_field !== null) {
                                    if (is_null($value_field_list[$source])) {
                                        $pb_source_values = array();
                                    } else {
                                        if (! is_array($value_field_list[$source])) {
                                            $value_field_list[$source] = [$value_field_list[$source]];
                                        }
                                        $pb_source_values = $this->getSelectedValuesForField($source_field, $value_field_list[$source]);
                                    }
                                    $source_field->setHasErrors(true);

                                    // looking for the target field value which cause the dependence problem
                                    $target_field = $this->form_element_factory->getFormElementListById($target);
                                    if ($target_field !== null) {
                                        if ($target_value !== null && ! is_array($target_value)) {
                                            $target_value = [$target_value];
                                        }
                                        if (is_null($target_value)) {
                                            $pb_target_values = array();
                                        } else {
                                            $pb_target_values = $this->getSelectedValuesForField($target_field, $target_value);
                                        }
                                        $target_field->setHasErrors(true);
                                        // detailled error message
                                        $GLOBALS['Response']->addFeedback('error', $values[$source]['field']->getLabel() . '(' . implode(', ', $pb_source_values) . ') -> ' . $values[$target]['field']->getLabel() . '(' . implode(', ', $pb_target_values) . ')');
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return !$error_occured;
    }

    private function getSelectedValuesForField(Tracker_FormElement_Field_List $field, array $value_field_list) : array
    {
        $selected_values = array();
        foreach ($value_field_list as $value_field) {
            $selected_values[] = $field->getBind()->formatArtifactValue($value_field);
        }
        return $selected_values;
    }
}
