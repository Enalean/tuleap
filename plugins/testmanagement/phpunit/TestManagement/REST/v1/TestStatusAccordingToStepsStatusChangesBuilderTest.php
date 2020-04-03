<?php
/**
 * Copyright (c) Enalean, 2018 - 2019. All Rights Reserved.
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

namespace Tuleap\TestManagement\REST\v1;

use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_List_Bind_Static;
use Tracker_FormElement_Field_List_Bind_StaticValue as StaticValue;
use Tuleap\TestManagement\Step\Step;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;

require_once __DIR__ . '/../../../bootstrap.php';

class TestStatusAccordingToStepsStatusChangesBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TestStatusAccordingToStepsStatusChangesBuilder
     */
    private $builder;
    /**
     * @var Tracker_FormElement_Field_List
     */
    private $status_field;

    private $notrun_id = 101;
    private $passed_id = 102;
    private $failed_id = 103;
    private $blocked_id = 104;

    public function setUp(): void
    {
        $this->builder      = new TestStatusAccordingToStepsStatusChangesBuilder();
        $this->status_field = $this->createMock(Tracker_FormElement_Field_List::class);
        $this->status_field->method('getId')->willReturn(42);

        $bind = $this->createMock(Tracker_FormElement_Field_List_Bind_Static::class);
        $this->status_field->method('getBind')->willReturn($bind);

        $values = [
            new StaticValue($this->notrun_id, 'notrun', '', 0, false),
            new StaticValue($this->passed_id, 'passed', '', 0, false),
            new StaticValue($this->failed_id, 'failed', '', 0, false),
            new StaticValue($this->blocked_id, 'blocked', '', 0, false),
        ];
        $bind->method('getAllValues')->willReturn($values);
    }

    public function testNoChangesWhenNoStepsDefined()
    {
        $changes = [];
        $this->builder->enforceTestStatusAccordingToStepsStatus($this->status_field, $changes, [], []);
        $this->assertEmpty($changes);
    }

    public function testNoChangesWhenNoStepsStatusChange()
    {
        $steps_defined_in_test = [
            $this->createMock(Step::class)
        ];

        $changes = [];
        $this->builder->enforceTestStatusAccordingToStepsStatus(
            $this->status_field,
            $changes,
            $steps_defined_in_test,
            []
        );
        $this->assertEmpty($changes);
    }

    /**
     * @dataProvider stepsStatusProvider
     */
    public function testPassWhenAllStepsPassed(array $steps_defined_in_test, array $steps_changes, $expected_status)
    {
        $changes = [];
        $this->builder->enforceTestStatusAccordingToStepsStatus(
            $this->status_field,
            $changes,
            $steps_defined_in_test,
            $steps_changes
        );

        $expected                 = new ArtifactValuesRepresentation();
        $expected->field_id       = $this->status_field->getId();
        $expected->bind_value_ids = [$expected_status];

        $this->assertEquals(
            $expected,
            $changes[0]
        );
    }

    public function stepsStatusProvider()
    {
        $step_1 = $this->createMock(Step::class);
        $step_1->method('getId')->willReturn(1001);
        $step_2 = $this->createMock(Step::class);
        $step_2->method('getId')->willReturn(1002);
        $step_3 = $this->createMock(Step::class);
        $step_3->method('getId')->willReturn(1003);

        return [
            [
                [$step_1, $step_2, $step_3],
                [1001 => 'passed', 1002 => 'passed', 1003 => 'passed'],
                $this->passed_id
            ],
            [
                [$step_1, $step_2, $step_3],
                [1001 => 'passed', 1002 => 'passed', 1003 => 'notrun'],
                $this->notrun_id
            ],
            [
                [$step_1, $step_2, $step_3],
                [1001 => 'passed', 1002 => 'passed', 1003 => 'failed'],
                $this->failed_id
            ],
            [
                [$step_1, $step_2, $step_3],
                [1001 => 'passed', 1002 => 'passed', 1003 => 'blocked'],
                $this->blocked_id
            ],
            [
                [$step_1, $step_2, $step_3],
                [1001 => 'passed', 1002 => 'notrun', 1003 => 'failed'],
                $this->failed_id
            ],
            [
                [$step_1, $step_2, $step_3],
                [1001 => 'passed', 1002 => 'notrun', 1003 => 'blocked'],
                $this->blocked_id
            ],
            [
                [$step_1, $step_2, $step_3],
                [1001 => 'passed', 1002 => 'blocked', 1003 => 'failed'],
                $this->failed_id
            ],
            [
                [$step_1, $step_2, $step_3],
                [1001 => 'passed', 1002 => 'failed', 1003 => 'blocked'],
                $this->failed_id
            ],
            [
                [$step_1, $step_2, $step_3],
                [1001 => 'passed'],
                $this->notrun_id
            ]
        ];
    }
}
