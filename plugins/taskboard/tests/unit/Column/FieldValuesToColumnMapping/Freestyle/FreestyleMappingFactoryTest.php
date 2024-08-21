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

namespace Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Taskboard\Tracker\TaskboardTracker;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\OpenListFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Tracker\FormElement\Field\ListFields\RetrieveUsedListFieldStub;

final class FreestyleMappingFactoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private MockObject&FreestyleMappingDao $dao;
    private RetrieveUsedListFieldStub $list_field_retriever;

    protected function setUp(): void
    {
        $this->dao                  = $this->createMock(FreestyleMappingDao::class);
        $this->list_field_retriever = RetrieveUsedListFieldStub::withNoField();
    }

    private function getFactory(): FreestyleMappingFactory
    {
        return new FreestyleMappingFactory($this->dao, $this->list_field_retriever);
    }

    public function testGetMappedFieldReturnsNullWhenNoMapping(): void
    {
        $taskboard_tracker = new TaskboardTracker(TrackerTestBuilder::aTracker()->build(), TrackerTestBuilder::aTracker()->build());
        $this->dao->expects(self::once())
            ->method('searchMappedField')
            ->with($taskboard_tracker)
            ->willReturn(null);

        $result = $this->getFactory()->getMappedField($taskboard_tracker);
        self::assertNull($result);
    }

    public function testGetMappedFieldReturnsNullWhenFieldIsNotSelectbox(): void
    {
        $tracker           = TrackerTestBuilder::aTracker()->build();
        $taskboard_tracker = new TaskboardTracker(TrackerTestBuilder::aTracker()->build(), $tracker);
        $this->dao->expects(self::once())
            ->method('searchMappedField')
            ->with($taskboard_tracker)
            ->willReturn(123);
        $field                      = OpenListFieldBuilder::anOpenListField()->withId(123)->build();
        $this->list_field_retriever = RetrieveUsedListFieldStub::withField($field);

        $result = $this->getFactory()->getMappedField($taskboard_tracker);
        self::assertNull($result);
    }

    public function testGetMappedFieldReturnsMappedSelectbox(): void
    {
        $taskboard_tracker = new TaskboardTracker(
            TrackerTestBuilder::aTracker()->build(),
            TrackerTestBuilder::aTracker()->build()
        );
        $this->dao->expects(self::once())
            ->method('searchMappedField')
            ->with($taskboard_tracker)
            ->willReturn(123);
        $field                      = ListFieldBuilder::aListField(123)->build();
        $this->list_field_retriever = RetrieveUsedListFieldStub::withField($field);

        $result = $this->getFactory()->getMappedField($taskboard_tracker);
        self::assertSame($field, $result);
    }

    public function testGetMappedFieldReturnsMappedMultiSelectbox(): void
    {
        $taskboard_tracker = new TaskboardTracker(
            TrackerTestBuilder::aTracker()->build(),
            TrackerTestBuilder::aTracker()->build()
        );
        $this->dao->expects(self::once())
            ->method('searchMappedField')
            ->with($taskboard_tracker)
            ->willReturn(123);
        $field                      = ListFieldBuilder::aListField(123)->withMultipleValues()->build();
        $this->list_field_retriever = RetrieveUsedListFieldStub::withField($field);

        $result = $this->getFactory()->getMappedField($taskboard_tracker);
        self::assertSame($field, $result);
    }

    public function testDoesFreestyleMappingExistDelegatesToDAO(): void
    {
        $taskboard_tracker = new TaskboardTracker(TrackerTestBuilder::aTracker()->build(), TrackerTestBuilder::aTracker()->build());
        $this->dao->expects(self::once())
            ->method('doesFreestyleMappingExist')
            ->with($taskboard_tracker)
            ->willReturn(true);

        self::assertTrue($this->getFactory()->doesFreestyleMappingExist($taskboard_tracker));
    }

    public function testGetValuesMappedToColumnReturnsEmpty(): void
    {
        $taskboard_tracker = new TaskboardTracker(TrackerTestBuilder::aTracker()->build(), TrackerTestBuilder::aTracker()->build());
        $todo_column       = new \Cardwall_Column(12, 'Todo', 'acid-green');
        $this->dao->expects(self::once())
            ->method('searchMappedFieldValuesForColumn')
            ->with($taskboard_tracker, $todo_column)
            ->willReturn([]);

        $result = $this->getFactory()->getValuesMappedToColumn($taskboard_tracker, $todo_column);
        self::assertSame(0, count($result->getValueIds()));
    }

    public function testGetValuesMappedToColumnReturnsValues(): void
    {
        $taskboard_tracker = new TaskboardTracker(TrackerTestBuilder::aTracker()->build(), TrackerTestBuilder::aTracker()->build());
        $todo_column       = new \Cardwall_Column(12, 'Todo', 'acid-green');
        $this->dao->expects(self::once())
            ->method('searchMappedFieldValuesForColumn')
            ->with($taskboard_tracker, $todo_column)
            ->willReturn([['value_id' => 123], ['value_id' => 127]]);

        $result        = $this->getFactory()->getValuesMappedToColumn($taskboard_tracker, $todo_column);
        $mapped_values = $result->getValueIds();
        self::assertSame(2, count($mapped_values));
        self::assertSame([123, 127], $mapped_values);
    }
}
