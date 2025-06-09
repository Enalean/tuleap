<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Status\Done;

use PHPUnit\Framework\MockObject\MockObject;
use Tracker;
use Tracker_FormElement_Field_List_Value;
use Tracker_FormElement_InvalidFieldValueException;
use Tuleap\Tracker\Semantic\Status\TrackerSemanticStatus;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SemanticDoneLoaderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private SemanticDoneValueChecker&MockObject $value_checker;
    private SemanticDoneDao&MockObject $dao;
    private SemanticDoneLoader $loader;
    private Tracker $tracker;
    private TrackerSemanticStatus&MockObject $semantic_status;

    public function setUp(): void
    {
        $this->tracker = TrackerTestBuilder::aTracker()->build();

        $this->semantic_status = $this->createMock(TrackerSemanticStatus::class);

        $this->dao           = $this->createMock(SemanticDoneDao::class);
        $this->value_checker = $this->createMock(SemanticDoneValueChecker::class);

        $this->loader = new SemanticDoneLoader($this->dao, $this->value_checker);
    }

    public function tearDown(): void
    {
        unset($GLOBALS['_SESSION']);
    }

    public function testLoadWhenStatusIsNotDefined(): void
    {
        $this->semantic_status->method('getField')->willReturn(null);

        $semantic_done = $this->loader->load($this->tracker, $this->semantic_status);

        $this->assertEquals([], $semantic_done->getDoneValuesIds());
    }

    public function testLoadWhenStatusIsDefined(): void
    {
        $done_value = $this->createMock(Tracker_FormElement_Field_List_Value::class);
        $done_value->method('getId')->willReturn(3);

        $delivered_value = $this->createMock(Tracker_FormElement_Field_List_Value::class);
        $delivered_value->method('getId')->willReturn(4);

        $this->value_checker->method('isValueAPossibleDoneValue')->willReturn(true);

        $bind = $this->createMock(\Tracker_FormElement_Field_List_Bind_Static::class);
        $bind->method('getValue')->willReturnCallback(static fn (int $id) => match ($id) {
            3 => $done_value,
            4 => $delivered_value,
        });

        $status_field = $this->createMock(\Tracker_FormElement_Field_Selectbox::class);
        $status_field->method('getBind')->willReturn($bind);

        $this->semantic_status->method('getField')->willReturn($status_field);
        $this->dao->method('getSelectedValues')->willReturn([['value_id' => 3], ['value_id' => 4]]);

        $semantic_done = $this->loader->load($this->tracker, $this->semantic_status);

        $this->assertEquals([3, 4], $semantic_done->getDoneValuesIds());
    }

    public function testLoadIgnoreValuesThatCannotBeDone(): void
    {
        $done_value = $this->createMock(Tracker_FormElement_Field_List_Value::class);
        $done_value->method('getId')->willReturn(3);

        $delivered_value = $this->createMock(Tracker_FormElement_Field_List_Value::class);
        $delivered_value->method('getId')->willReturn(4);

        $this->value_checker
            ->method('isValueAPossibleDoneValue')
            ->with($done_value, $this->semantic_status)
            ->willReturnCallback(
                static fn(Tracker_FormElement_Field_List_Value $value) => $value === $done_value
            );

        $bind = $this->createMock(\Tracker_FormElement_Field_List_Bind_Static::class);
        $bind->method('getValue')->willReturnCallback(static fn (int $id) => match ($id) {
            3 => $done_value,
            4 => $delivered_value,
        });

        $status_field = $this->createMock(\Tracker_FormElement_Field_Selectbox::class);
        $status_field->method('getBind')->willReturn($bind);

        $this->semantic_status->method('getField')->willReturn($status_field);
        $this->dao->method('getSelectedValues')->willReturn([['value_id' => 3], ['value_id' => 4]]);

        $semantic_done = $this->loader->load($this->tracker, $this->semantic_status);

        $this->assertEquals([3], $semantic_done->getDoneValuesIds());
    }

    public function testLoadIgnoreUnknownValues(): void
    {
        $done_value = $this->createMock(Tracker_FormElement_Field_List_Value::class);
        $done_value->method('getId')->willReturn(3);

        $this->value_checker->method('isValueAPossibleDoneValue')->willReturn(true);

        $bind = $this->createMock(\Tracker_FormElement_Field_List_Bind_Static::class);
        $bind->method('getValue')->willReturnCallback(static fn (int $id) => match ($id) {
            3 => $done_value,
            4 => throw new Tracker_FormElement_InvalidFieldValueException(),
        });

        $status_field = $this->createMock(\Tracker_FormElement_Field_Selectbox::class);
        $status_field->method('getBind')->willReturn($bind);

        $this->semantic_status->method('getField')->willReturn($status_field);
        $this->dao->method('getSelectedValues')->willReturn([['value_id' => 3], ['value_id' => 4]]);

        $semantic_done = $this->loader->load($this->tracker, $this->semantic_status);

        $this->assertEquals([3], $semantic_done->getDoneValuesIds());
    }
}
