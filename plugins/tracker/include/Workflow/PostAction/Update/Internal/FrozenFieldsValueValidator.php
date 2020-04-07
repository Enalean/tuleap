<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Workflow\PostAction\Update\Internal;

use LogicException;
use Tracker;
use Tracker_FormElementFactory;
use Tracker_RuleFactory;
use Tuleap\Tracker\Workflow\PostAction\Update\FrozenFieldsValue;

class FrozenFieldsValueValidator
{
    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;

    /**
     * @var Tracker_RuleFactory
     */
    private $tracker_rule_factory;

    public function __construct(
        Tracker_FormElementFactory $form_element_factory,
        Tracker_RuleFactory $tracker_rule_factory
    ) {
        $this->form_element_factory = $form_element_factory;
        $this->tracker_rule_factory = $tracker_rule_factory;
    }

    /**
     * @throws InvalidPostActionException
     */
    public function validate(Tracker $tracker, FrozenFieldsValue ...$frozen_fields): void
    {
        foreach ($frozen_fields as $frozen_field) {
            if (count($frozen_field->getFieldIds()) !== count(array_unique($frozen_field->getFieldIds()))) {
                throw new InvalidPostActionException(
                    dgettext(
                        'tuleap-tracker',
                        "There should not be duplicate field_ids for 'frozen_fields' actions."
                    )
                );
            }

            $this->validateSelectedField($tracker, $frozen_field);
        }
    }

    private function extractUsedFieldIds(Tracker $tracker): array
    {
        $used_fields    = $this->form_element_factory->getUsedFields($tracker);
        $used_field_ids = [];
        foreach ($used_fields as $used_field) {
            \assert($used_field instanceof \Tracker_FormElement_Field);
            $used_field_ids[] = (int) $used_field->getId();
        }

        return $used_field_ids;
    }

    private function extractFieldDependenciesFieldIds(Tracker $tracker): array
    {
        $involved_fields    = $this->tracker_rule_factory->getInvolvedFieldsByTrackerId($tracker->getId());
        $involved_field_ids = [];
        foreach ($involved_fields as $involved_fields_row) {
            $involved_field_ids[] = (int) $involved_fields_row['source_field_id'];
            $involved_field_ids[] = (int) $involved_fields_row['target_field_id'];
        }

        return array_unique($involved_field_ids);
    }

    /**
     * @throws InvalidPostActionException
     */
    private function validateSelectedField(Tracker $tracker, FrozenFieldsValue $frozen_fields): void
    {
        $used_field_ids     = $this->extractUsedFieldIds($tracker);
        $involved_field_ids = $this->extractFieldDependenciesFieldIds($tracker);
        $workflow           = $tracker->getWorkflow();
        if ($workflow === null) {
            throw new LogicException('Tracker #' . $tracker->getId() . ' does not have a workflow');
        }
        $workflow_field_id  = (int) $workflow->getFieldId();

        foreach ($frozen_fields->getFieldIds() as $field_id) {
            $this->validateFieldIsUsedInTracker($field_id, $used_field_ids);
            $this->validateFieldIsNotUsedInDependencies($field_id, $involved_field_ids);
            $this->validateFieldIsNotUsedToDefineTheWorkflow($field_id, $workflow_field_id);
        }
    }

    /**
     * @throws InvalidPostActionException
     */
    private function validateFieldIsUsedInTracker(int $field_id, array $used_field_ids)
    {
        if (! in_array($field_id, $used_field_ids, true)) {
            throw new InvalidPostActionException(
                sprintf(
                    dgettext(
                        'tuleap-tracker',
                        "The field_id value '%u' does not match a field in use in the tracker."
                    ),
                    $field_id
                )
            );
        }
    }

    /**
     * @throws InvalidPostActionException
     */
    private function validateFieldIsNotUsedInDependencies(int $field_id, array $involved_field_ids)
    {
        foreach ($involved_field_ids as $field_used_in_dependency_id) {
            if ($field_used_in_dependency_id === $field_id) {
                throw new InvalidPostActionException(
                    sprintf(
                        dgettext(
                            'tuleap-tracker',
                            "The field_id value '%u' is used in a field dependency in the tracker."
                        ),
                        $field_id
                    )
                );
            }
        }
    }

    /**
     * @throws InvalidPostActionException
     */
    private function validateFieldIsNotUsedToDefineTheWorkflow(int $field_id, int $workflow_field_id)
    {
        if ($field_id === $workflow_field_id) {
            throw new InvalidPostActionException(
                sprintf(
                    dgettext(
                        'tuleap-tracker',
                        "The field '%u' is used for the workflow and cannot be defined in frozen fields actions."
                    ),
                    $field_id
                )
            );
        }
    }
}
