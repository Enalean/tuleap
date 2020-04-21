<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace TestManagement\REST\v1\Execution;

require_once __DIR__ . '/../../../../bootstrap.php';

use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Tuleap\TestManagement\REST\v1\Execution\StepsResultsFilter;
use Tuleap\TestManagement\Step\Definition\Field\StepDefinitionChangesetValue;
use Tuleap\TestManagement\Step\Execution\Field\StepExecutionChangesetValue;
use Tuleap\TestManagement\Step\Execution\StepResult;
use Tuleap\TestManagement\Step\Step;

class StepsResultsFilterTest extends TestCase
{
    /** @var StepsResultsFilter */
    private $filter;

    /** @var PHPUnit_Framework_MockObject_MockObject */
    private $definition_value;

    /** @var PHPUnit_Framework_MockObject_MockObject */
    private $execution_value;

    public function setUp(): void
    {
        parent::setUp();

        $this->filter           = new StepsResultsFilter();
        $this->definition_value = $this->createMock(StepDefinitionChangesetValue::class);
        $this->execution_value  = $this->createMock(StepExecutionChangesetValue::class);
    }

    public function testItFiltersStepResultsNotInDefinition()
    {
        $first_step_definition  = new Step(37, 'forecovert', 'text', 'result01', 'text', 1);
        $second_step_definition = new Step(38, 'qualified', 'text', 'result02', 'text', 2);
        $third_step_definition  = new Step(39, 'vegetablelike', 'text', 'result03', 'text', 3);

        $this->definition_value->expects($this->once())->method('getValue')->willReturn(
            [
                $first_step_definition,
                $second_step_definition,
                $third_step_definition
            ]
        );

        $first_older_step_definition  = new Step(34, 'roundup', 'text', 'result01', 'text', 1);
        $second_older_step_definition = new Step(35, 'pseudobrotherly', 'text', 'result02', 'text', 2);
        $third_older_step_definition  = new Step(36, 'pilar', 'html', 'result03', 'html', 3);

        $first_step_result  = new StepResult($first_older_step_definition, 'passed');
        $second_step_result = new StepResult($second_older_step_definition, 'failed');
        $third_step_result  = new StepResult($third_older_step_definition, 'notrun');

        $this->execution_value->expects($this->once())->method('getValue')->willReturn(
            [
                $first_step_result,
                $second_step_result,
                $third_step_result
            ]
        );

        $result = $this->filter->filterStepResultsNotInDefinition($this->definition_value, $this->execution_value);

        $this->assertEquals([], $result);
    }

    public function testItReturnsAllStepResultsInDefinition()
    {
        $first_step_definition  = new Step(16, 'alumine', 'html', 'result01', 'html', 1);
        $second_step_definition = new Step(17, 'semifloating', 'text', 'result02', 'text', 2);

        $this->definition_value->method('getValue')->willReturn(
            [
                $first_step_definition, $second_step_definition
            ]
        );

        $first_step_result  = new StepResult($first_step_definition, 'notrun');
        $second_step_result = new StepResult($second_step_definition, 'passed');

        $this->execution_value->method('getValue')->willReturn(
            [
                $first_step_result, $second_step_result
            ]
        );

        $result = $this->filter->filterStepResultsNotInDefinition($this->definition_value, $this->execution_value);

        $this->assertEquals(
            [
                $first_step_result, $second_step_result
            ],
            $result
        );
    }
}
