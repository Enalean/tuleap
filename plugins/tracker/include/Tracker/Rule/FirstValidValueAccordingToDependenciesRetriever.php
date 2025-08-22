<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Rule;

use Tracker_FormElementFactory;
use Tracker_Rule_List;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindValueIdCollection;
use Tuleap\Tracker\FormElement\Field\ListField;

class FirstValidValueAccordingToDependenciesRetriever
{
    /**
     * @var array<int, FieldListRuleCollection>
     */
    private array $rules_for_field = [];

    public function __construct(private Tracker_FormElementFactory $form_element_factory)
    {
    }

    /**
     * @param Tracker_Rule_List[] $rules all rules indexed by the source or target field
     */
    private function buildCollections(
        ListField $field,
        Artifact $artifact,
        array $rules,
    ): void {
        foreach ($rules as $rule) {
            $source_id = $rule->getSourceFieldId();
            $target_id = $rule->getTargetFieldId();

            if ($source_id === $field->getId()) {
                if (! isset($this->rules_for_field[$target_id])) {
                    $this->rules_for_field[$target_id] = new FieldListRuleCollection(
                        $this->getLastChangesetFieldsValue($target_id, $artifact)
                    );
                }
                $this->rules_for_field[$target_id]->addRule($rule);
                continue;
            }

            if ($target_id === $field->getId()) {
                if (! isset($this->rules_for_field[$source_id])) {
                    $this->rules_for_field[$source_id] = new FieldListRuleCollection(
                        $this->getLastChangesetFieldsValue($source_id, $artifact)
                    );
                }
                $this->rules_for_field[$source_id]->addRule($rule);
            }
        }
    }

    private function getLastChangesetFieldsValue(int $field_id, Artifact $artifact): ?int
    {
        $field_for_rules = $this->form_element_factory->getFieldById($field_id);

        if (! $field_for_rules) {
            return null;
        }

        $changeset_value = $artifact->getValue($field_for_rules);

        if (! $changeset_value || empty($changeset_value->getValue())) {
            return null;
        }

        return (int) $changeset_value->getValue()[0];
    }

    /**
     * @param Tracker_Rule_List[] $rules
     */
    public function getFirstValidValuesAccordingToDependencies(
        BindValueIdCollection $list_of_values,
        ListField $field,
        Artifact $artifact,
        array $rules,
    ): ?int {
        if (empty($list_of_values->getValueIds())) {
            return null;
        }

        $this->buildCollections($field, $artifact, $rules);
        if (empty($this->rules_for_field) && ! empty($list_of_values->getValueIds())) {
            return $list_of_values->getFirstValue();
        }

        foreach ($list_of_values->getValueIds() as $value) {
            if ($this->checkValidValuesWithDependencies($value)) {
                return $value;
            }
        }

        return null;
    }

    /*
     * If the value is not valid for one field, we return false and don't check other fields
     */
    private function checkValidValuesWithDependencies(int $value): bool
    {
        foreach ($this->rules_for_field as $rules) {
            if (! $this->checkValueValidityForField($rules, $value)) {
                return false;
            }
        }

        return true;
    }

    /*
     * Check to validity for all rules in a field
     *  If the field is target of a field dependency, value must be allowed by at least one rule
     *  If the field is source of a field dependency, the value of the target field at the last changeset must be allowed by at least one rule
     */
    private function checkValueValidityForField(FieldListRuleCollection $rules, int $value): bool
    {
        $possible_values = false;
        foreach ($rules->getRules() as $rule) {
            $target_value = (int) $rule->getTargetValue();
            $source_value = (int) $rule->getSourceValue();

            if ($target_value === $value) {
                if ($source_value === $rules->getActualValue()) {
                    $possible_values = true;
                    continue;
                }
            }
            if ($source_value === $value) {
                if ($target_value === $rules->getActualValue()) {
                    $possible_values = true;
                }
            }
        }
        return $possible_values;
    }
}
