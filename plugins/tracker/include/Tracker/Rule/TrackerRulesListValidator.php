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

use Feedback;
use Tracker;
use Tracker_FormElement_Field_List;
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

    public function validateListRules(Tracker $tracker, array $value_field_list, array $list_rules): bool
    {
        $values = [];
        foreach ($value_field_list as $field_id => $value) {
            $field = $this->form_element_factory->getFormElementListById((int) $field_id);
            if ($field) {
                $values[$field->getID()] = ['field' => $field, 'values' => is_array($value) ? $value : [$value]];
            }
        }

        $dependencies = [];
        foreach ($list_rules as $rule) {
            $dependencies = $this->getDependencies($rule, $dependencies);
        }

        $error_occured = false;
        foreach ($dependencies as $source => $not_used) {
            if ($error_occured) {
                break;
            }
            if (isset($values[$source])) {
                $error_occured = $this->checkFieldsValidityForAllDependencies(
                    $tracker,
                    $dependencies,
                    $values,
                    $source,
                    $value_field_list
                );
            }
        }
        return !$error_occured;
    }

    private function getDependencies(Tracker_Rule_List $rule, array $dependencies): array
    {
        if (!isset($dependencies[$rule->source_field])) {
            $dependencies[$rule->source_field] = [];
        }
        if (!isset($dependencies[$rule->source_field][$rule->target_field])) {
            $dependencies[$rule->source_field][$rule->target_field] = [];
        }
        $dependencies[$rule->source_field][$rule->target_field][] = $rule;

        return $dependencies;
    }

    private function checkFieldsValidityForAllDependencies(Tracker $tracker, array $dependencies, array $values, int $source, array $value_field_list): bool
    {
        $error_occured = false;
        foreach ($dependencies[$source] as $target => $not_used_target) {
            if ($error_occured) {
                break;
            }
            if (isset($values[$target])) {
                $error_occured = $this->checkFieldsValidity(
                    $tracker,
                    $values,
                    $target,
                    $source,
                    $dependencies,
                    $value_field_list
                );
            }
        }

        return $error_occured;
    }

    private function checkFieldsValidity(Tracker $tracker, array $values, int $target, int $source, $dependencies, array $value_field_list): bool
    {
        $error_occured = false;
        $target_values = $values[$target]['values'];
        $source_values = $values[$source]['values'];
        foreach ($target_values as $target_value) {
            if ($error_occured) {
                break;
            }

            $valid = $this->areRulesValid($tracker, $source_values, $target_value, $dependencies, $source, $target);

            if (!$valid) {
                $error_occured = true;
                $this->dealDependencesProblems($source, $target, $values, $target_value, $value_field_list);
            }
        }
        return $error_occured;
    }

    private function areRulesValid(
        Tracker $tracker,
        array $source_values,
        $target_value,
        array $dependencies,
        int $source,
        int $target
    ): bool {
        $valid = false;
        foreach ($source_values as $source_value) {
            if ($valid) {
                break;
            }

            $applied = false;
            foreach ($dependencies[$source][$target] as $rule) {
                if ($applied && $valid) {
                    break;
                }
                if ($rule->canApplyTo($tracker->getId(), $source, $source_value, $target, $target_value)) {
                    $applied = true;
                    $valid   = $rule->applyTo($tracker->getId(), $source, $source_value, $target, $target_value);
                }
            }
        }

        return $valid;
    }

    private function dealDependencesProblems(int $source, int $target, array $values, $target_value, array $value_field_list): void
    {
        $source_field = $this->form_element_factory->getFormElementListById($source);
        if ($source_field !== null) {
            if ($value_field_list[$source] === null) {
                $pb_source_values = [];
            } else {
                if (!is_array($value_field_list[$source])) {
                    $value_field_list[$source] = [$value_field_list[$source]];
                }

                $pb_source_values = $this->getSelectedValuesForField($source_field, $value_field_list[$source]);
            }

            $source_field->setHasErrors(true);

            $target_field = $this->form_element_factory->getFormElementListById($target);

            if ($target_field !== null) {
                if ($target_value !== null && !is_array($target_value)) {
                    $target_value = [$target_value];
                }

                if ($target_value === null) {
                    $pb_target_values = [];
                } else {
                    $pb_target_values = $this->getSelectedValuesForField($target_field, $target_value);
                }

                $target_field->setHasErrors(true);
                $target_label = $values[$target]['field']->getLabel();
                $source_label = $values[$source]['field']->getLabel();

                $this->sendFeedbackError($target_label, $source_label, $pb_source_values, $pb_target_values);
            }
        }
    }

    private function sendFeedbackError(string $target_label, string $source_label, array $pb_source_values, array $pb_target_values): void
    {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            $source_label .
            '(' . implode(', ', $pb_source_values) . ') -> '
            . $target_label
            . '(' . implode(', ', $pb_target_values) . ')'
        );
    }

    private function getSelectedValuesForField(Tracker_FormElement_Field_List $field, array $value_field_list): array
    {
        $selected_values = [];
        foreach ($value_field_list as $value_field) {
            $selected_values[] = $field->getBind()->formatArtifactValue($value_field);
        }
        return $selected_values;
    }
}
