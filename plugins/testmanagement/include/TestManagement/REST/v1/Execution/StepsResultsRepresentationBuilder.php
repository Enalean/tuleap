<?php
/**
 * Copyright (c) Enalean, 2018 - present. All Rights Reserved.
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

namespace Tuleap\TestManagement\REST\v1\Execution;

use PFUser;
use Tracker_Artifact;
use Tracker_FormElementFactory;
use Tuleap\TestManagement\REST\v1\DefinitionRepresentation;
use Tuleap\TestManagement\REST\v1\ExecutionRepresentation;
use Tuleap\TestManagement\REST\v1\StepResultRepresentation;
use Tuleap\TestManagement\Step\Definition\Field\StepDefinitionChangesetValue;
use Tuleap\TestManagement\Step\Execution\Field\StepExecutionChangesetValue;
use Tuleap\TestManagement\Step\Execution\StepResult;

class StepsResultsRepresentationBuilder
{
    /** @var Tracker_FormElementFactory */
    private $tracker_form_element_factory;
    /** @var StepsResultsFilter */
    private $steps_results_filter;

    public function __construct(
        Tracker_FormElementFactory $tracker_form_element_factory,
        StepsResultsFilter $steps_results_filter
    ) {
        $this->tracker_form_element_factory = $tracker_form_element_factory;
        $this->steps_results_filter         = $steps_results_filter;
    }

    /**
     *
     * @return StepResultRepresentation[]
     */
    public function build(
        PFUser $user,
        Tracker_Artifact $execution,
        Tracker_Artifact $definition
    ) {
        return array_reduce(
            $this->getStepsResults($user, $execution, $definition),
            function (array $representations, StepResult $step_result) {
                $id = $step_result->getStep()->getId();

                $representations[$id] = new StepResultRepresentation();
                $representations[$id]->build($step_result);

                return $representations;
            },
            []
        );
    }

    /**
     *
     * @return StepResult[]
     */
    private function getStepsResults(PFUser $user, Tracker_Artifact $execution, Tracker_Artifact $definition)
    {
        $execution_changeset_value = $this->getFieldChangeValue(
            $user,
            $execution,
            ExecutionRepresentation::FIELD_STEPS_RESULTS
        );
        if ($execution_changeset_value === null) {
            return [];
        }
        assert($execution_changeset_value instanceof StepExecutionChangesetValue);

        $definition_changeset_value = $this->getFieldChangeValue(
            $user,
            $definition,
            DefinitionRepresentation::FIELD_STEPS
        );
        if ($definition_changeset_value === null) {
            return [];
        }
        assert($definition_changeset_value instanceof StepDefinitionChangesetValue);

        return $this->steps_results_filter->filterStepResultsNotInDefinition(
            $definition_changeset_value,
            $execution_changeset_value
        );
    }

    /**
     * @param  string $field_name
     *
     * @return null | StepDefinitionChangesetValue | StepExecutionChangesetValue
     */
    private function getFieldChangeValue(PFUser $user, Tracker_Artifact $execution, $field_name)
    {
        $results_field = $this->tracker_form_element_factory->getUsedFieldByNameForUser(
            $execution->getTrackerId(),
            $field_name,
            $user
        );
        if (! $results_field) {
            return null;
        }

        $value = $execution->getValue($results_field);
        assert($value instanceof StepDefinitionChangesetValue || $value instanceof StepExecutionChangesetValue);

        return $value;
    }
}
