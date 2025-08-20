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

namespace Tuleap\Artidoc\Document\Field\StepsDefinition;

use Tuleap\Artidoc\Document\Field\ConfiguredField;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\StepsDefinitionFieldWithValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\StepValue;
use Tuleap\Option\Option;
use Tuleap\TestManagement\Step\Definition\Field\StepsDefinitionChangesetValue;
use Tuleap\TestManagement\Step\Step;
use Tuleap\Tracker\Artifact\ChangesetValue\Text\TextValueInterpreter;

final readonly class StepsDefinitionFieldWithValueBuilder
{
    public function __construct(
        private TextValueInterpreter $text_value_interpreter,
    ) {
    }

    public function buildStepsDefinitionFieldWithValue(
        ConfiguredField $configured_field,
        ?StepsDefinitionChangesetValue $changeset_value,
    ): StepsDefinitionFieldWithValue {
        return new StepsDefinitionFieldWithValue(
            $configured_field->field->getLabel(),
            $configured_field->display_type,
            $this->buildStepsValues($changeset_value, (int) $configured_field->field->getTracker()->getProject()->getID()),
        );
    }

    /**
     * @return list<StepValue>
     */
    private function buildStepsValues(?StepsDefinitionChangesetValue $changeset_value, int $project_id): array
    {
        if ($changeset_value === null) {
            return [];
        }

        return array_values(array_map(
            fn(Step $step) => $this->buildStepValue($step, $project_id),
            $changeset_value->getValue(),
        ));
    }

    private function buildStepValue(Step $step, int $project_id): StepValue
    {
        return new StepValue(
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
        );
    }
}
