<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Document\Field\StepsExecution;

use Tuleap\Artidoc\Document\Field\ConfiguredField;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\StepResultValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\StepsExecutionFieldWithValue;
use Tuleap\Option\Option;
use Tuleap\TestManagement\Step\Execution\Field\StepsExecutionChangesetValue;
use Tuleap\TestManagement\Step\Execution\StepResult;
use Tuleap\Tracker\Artifact\ChangesetValue\Text\TextValueInterpreter;

final readonly class StepsExecutionFieldWithValueBuilder
{
    public function __construct(
        private TextValueInterpreter $text_value_interpreter,
    ) {
    }

    public function buildStepsExecutionFieldWithValue(
        ConfiguredField $configured_field,
        ?StepsExecutionChangesetValue $changeset_value,
    ): StepsExecutionFieldWithValue {
        return new StepsExecutionFieldWithValue(
            $configured_field->field->getLabel(),
            $configured_field->display_type,
            $this->buildStepsResultValues($changeset_value, (int) $configured_field->field->getTracker()->getProject()->getID()),
        );
    }

    /**
     * @return list<StepResultValue>
     */
    private function buildStepsResultValues(?StepsExecutionChangesetValue $changeset_value, int $project_id): array
    {
        if ($changeset_value === null) {
            return [];
        }

        return array_values(array_map(
            fn(StepResult $step_result) => $this->buildStepResultValue($step_result, $project_id),
            $changeset_value->getValue(),
        ));
    }

    private function buildStepResultValue(StepResult $step_result, int $project_id): StepResultValue
    {
        $step = $step_result->getStep();
        return new StepResultValue(
            $this->text_value_interpreter->interpretValueAccordingToFormat(
                $step->getDescriptionFormat(),
                $step->getDescription(),
                $project_id,
            ),
            $step->getExpectedResults() === null
                ? Option::nothing(\Psl\Type\string())
                : Option::fromValue($this->text_value_interpreter->interpretValueAccordingToFormat(
                    $step->getExpectedResultsFormat(),
                    $step->getExpectedResults(),
                    $project_id,
                )),
            $step_result->getStatus(),
        );
    }
}
