<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

use Tracker_FormElement_Field_List_Bind_Static;
use Tuleap\TestManagement\Step\Step;
use Tuleap\Tracker\FormElement\Field\ListField;
use Tuleap\Tracker\Test\Builders\ArtifactValuesRepresentationBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;

require_once __DIR__ . '/../../../bootstrap.php';

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class TestStatusAccordingToStepsStatusChangesBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const NOT_RUN_ID = 101;
    private const PASSED_ID  = 102;
    private const FAILED_ID  = 103;
    private const BLOCKED_ID = 104;

    /**
     * @var TestStatusAccordingToStepsStatusChangesBuilder
     */
    private $builder;
    /**
     * @var ListField
     */
    private $status_field;

    #[\Override]
    public function setUp(): void
    {
        $this->builder      = new TestStatusAccordingToStepsStatusChangesBuilder();
        $this->status_field = $this->createMock(ListField::class);
        $this->status_field->method('getId')->willReturn(42);

        $bind = $this->createMock(Tracker_FormElement_Field_List_Bind_Static::class);
        $this->status_field->method('getBind')->willReturn($bind);

        $values = [
            ListStaticValueBuilder::aStaticValue('notrun')->withId(self::NOT_RUN_ID)->build(),
            ListStaticValueBuilder::aStaticValue('passed')->withId(self::PASSED_ID)->build(),
            ListStaticValueBuilder::aStaticValue('failed')->withId(self::FAILED_ID)->build(),
            ListStaticValueBuilder::aStaticValue('blocked')->withId(self::BLOCKED_ID)->build(),
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
            new Step(1, '', '', null, '', 1),
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

    #[\PHPUnit\Framework\Attributes\DataProvider('stepsStatusProvider')]
    public function testPassWhenAllStepsPassed(array $steps_defined_in_test, array $steps_changes, $expected_status)
    {
        $changes = [];
        $this->builder->enforceTestStatusAccordingToStepsStatus(
            $this->status_field,
            $changes,
            $steps_defined_in_test,
            $steps_changes
        );

        $expected = ArtifactValuesRepresentationBuilder::aRepresentation($this->status_field->getId())
            ->withBindValueIds($expected_status)
            ->build();

        $this->assertEquals(
            $expected,
            $changes[0]
        );
    }

    public static function stepsStatusProvider()
    {
        $step_1 = new Step(1001, 'description', 'text', null, 'text', 1);
        $step_2 = new Step(1002, 'description', 'text', null, 'text', 2);
        $step_3 = new Step(1003, 'description', 'text', null, 'text', 3);

        return [
            [
                [$step_1, $step_2, $step_3],
                [1001 => 'passed', 1002 => 'passed', 1003 => 'passed'],
                self::PASSED_ID,
            ],
            [
                [$step_1, $step_2, $step_3],
                [1001 => 'passed', 1002 => 'passed', 1003 => 'notrun'],
                self::NOT_RUN_ID,
            ],
            [
                [$step_1, $step_2, $step_3],
                [1001 => 'passed', 1002 => 'passed', 1003 => 'failed'],
                self::FAILED_ID,
            ],
            [
                [$step_1, $step_2, $step_3],
                [1001 => 'passed', 1002 => 'passed', 1003 => 'blocked'],
                self::BLOCKED_ID,
            ],
            [
                [$step_1, $step_2, $step_3],
                [1001 => 'passed', 1002 => 'notrun', 1003 => 'failed'],
                self::FAILED_ID,
            ],
            [
                [$step_1, $step_2, $step_3],
                [1001 => 'passed', 1002 => 'notrun', 1003 => 'blocked'],
                self::BLOCKED_ID,
            ],
            [
                [$step_1, $step_2, $step_3],
                [1001 => 'passed', 1002 => 'blocked', 1003 => 'failed'],
                self::FAILED_ID,
            ],
            [
                [$step_1, $step_2, $step_3],
                [1001 => 'passed', 1002 => 'failed', 1003 => 'blocked'],
                self::FAILED_ID,
            ],
            [
                [$step_1, $step_2, $step_3],
                [1001 => 'passed'],
                self::NOT_RUN_ID,
            ],
        ];
    }
}
