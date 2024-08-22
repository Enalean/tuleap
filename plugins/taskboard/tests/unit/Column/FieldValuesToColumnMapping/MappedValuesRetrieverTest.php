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

namespace Tuleap\Taskboard\Column\FieldValuesToColumnMapping;

use PHPUnit\Framework\MockObject\MockObject;
use Tracker;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tracker_FormElement_Field_Selectbox;
use Tuleap\Cardwall\Test\Builders\ColumnTestBuilder;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\FreestyleMappedFieldValuesRetriever;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\SearchMappedFieldValuesForColumnStub;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\VerifyMappingExistsStub;
use Tuleap\Taskboard\Tracker\TaskboardTracker;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class MappedValuesRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const ON_GOING_COLUMN_LABEL = 'On Going';
    private \Cardwall_FieldProviders_SemanticStatusFieldRetriever&MockObject $status_retriever;
    private VerifyMappingExistsStub $verify_mapping_exists;
    private SearchMappedFieldValuesForColumnStub $search_values;
    private Tracker $user_stories_tracker;

    protected function setUp(): void
    {
        $this->status_retriever      = $this->createMock(\Cardwall_FieldProviders_SemanticStatusFieldRetriever::class);
        $this->verify_mapping_exists = VerifyMappingExistsStub::withNoMapping();
        $this->search_values         = SearchMappedFieldValuesForColumnStub::withNoMappedValue();

        $this->user_stories_tracker = TrackerTestBuilder::aTracker()->withId(164)->build();
    }

    private function getMappedValues(): MappedValuesInterface
    {
        $taskboard_tracker = new TaskboardTracker(TrackerTestBuilder::aTracker()->build(), $this->user_stories_tracker);
        $ongoing_column    = ColumnTestBuilder::aColumn()->withLabel(self::ON_GOING_COLUMN_LABEL)->build();

        $retriever = new MappedValuesRetriever(
            new FreestyleMappedFieldValuesRetriever(
                $this->verify_mapping_exists,
                $this->search_values,
            ),
            $this->status_retriever
        );

        return $retriever->getValuesMappedToColumn($taskboard_tracker, $ongoing_column);
    }

    public function testGetValuesMappedToColumnReturnsFreestyleMappingWhenItExists(): void
    {
        $this->verify_mapping_exists = VerifyMappingExistsStub::withMapping();
        $this->search_values         = SearchMappedFieldValuesForColumnStub::withValues([231, 856]);

        $result = $this->getMappedValues();
        self::assertSame([231, 856], $result->getValueIds());
    }

    public function testGetValuesMappedToColumnReturnsEmptyWhenNoStatusSemantic(): void
    {
        $this->status_retriever->expects(self::once())
            ->method('getField')
            ->with($this->user_stories_tracker)
            ->willReturn(null);

        $result = $this->getMappedValues();
        self::assertEmpty($result->getValueIds());
    }

    public function testGetValuesMappedToColumnReturnsEmptyWhenStatusHasNoVisibleValues(): void
    {
        $status_field = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $status_field->expects(self::once())
            ->method('getVisibleValuesPlusNoneIfAny')
            ->willReturn([]);
        $this->status_retriever->expects(self::once())
            ->method('getField')
            ->with($this->user_stories_tracker)
            ->willReturn($status_field);

        $result = $this->getMappedValues();
        self::assertEmpty($result->getValueIds());
    }

    public function testGetValuesMappedToColumnReturnsTheStatusValueWithTheSameLabelAsTheGivenColumn(): void
    {
        $todo_list_value    = new Tracker_FormElement_Field_List_Bind_StaticValue(564, 'Todo', '', 1, false);
        $ongoing_list_value = new Tracker_FormElement_Field_List_Bind_StaticValue(756, self::ON_GOING_COLUMN_LABEL, '', 2, false);
        $status_field       = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $status_field->expects(self::once())
            ->method('getVisibleValuesPlusNoneIfAny')
            ->willReturn([$todo_list_value, $ongoing_list_value]);
        $this->status_retriever->expects(self::once())
            ->method('getField')
            ->with($this->user_stories_tracker)
            ->willReturn($status_field);

        $result = $this->getMappedValues();
        self::assertSame([756], $result->getValueIds());
    }
}
