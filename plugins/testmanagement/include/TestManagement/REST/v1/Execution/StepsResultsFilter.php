<?php
/**
 * Copyright (c) Enalean, 2018-present. All Rights Reserved.
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

use Tuleap\TestManagement\Step\Definition\Field\StepDefinitionChangesetValue;
use Tuleap\TestManagement\Step\Execution\Field\StepExecutionChangesetValue;
use Tuleap\TestManagement\Step\Execution\StepResult;
use Tuleap\TestManagement\Step\Step;

class StepsResultsFilter
{
    /**
     * @return StepResult[]
     */
    public function filterStepResultsNotInDefinition(
        StepDefinitionChangesetValue $definition_changeset_value,
        StepExecutionChangesetValue $execution_changeset_value
    ) {
        $definition_value               = $definition_changeset_value->getValue();
        $step_definition_ids = array_map(
            function (Step $step_definition) {
                return $step_definition->getId();
            },
            $definition_value
        );

        $execution_value = $execution_changeset_value->getValue();
        return array_filter(
            $execution_value,
            function (StepResult $step_result) use ($step_definition_ids) {
                return in_array($step_result->getStep()->getId(), $step_definition_ids);
            }
        );
    }
}
