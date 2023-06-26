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
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\FreestyleMappingFactory;
use Tuleap\Taskboard\Tracker\TaskboardTracker;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class MappedValuesRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private MappedValuesRetriever $mapped_values_retriever;
    private FreestyleMappingFactory&MockObject $freestyle_mapping_factory;
    private \Cardwall_FieldProviders_SemanticStatusFieldRetriever&MockObject $status_retriever;

    protected function setUp(): void
    {
        $this->freestyle_mapping_factory = $this->createMock(FreestyleMappingFactory::class);
        $this->status_retriever          = $this->createMock(\Cardwall_FieldProviders_SemanticStatusFieldRetriever::class);
        $this->mapped_values_retriever   = new MappedValuesRetriever(
            $this->freestyle_mapping_factory,
            $this->status_retriever
        );
    }

    public function testGetValuesMappedToColumnReturnsFreestyleMappingWhenItExists(): void
    {
        $taskboard_tracker = new TaskboardTracker(TrackerTestBuilder::aTracker()->build(), $this->createMock(Tracker::class));
        $ongoing_column    = new \Cardwall_Column(7, 'On Going', 'clockwork-orange');
        $this->freestyle_mapping_factory->expects(self::once())
            ->method('doesFreestyleMappingExist')
            ->with($taskboard_tracker)
            ->willReturn(true);
        $this->freestyle_mapping_factory->expects(self::once())
            ->method('getValuesMappedToColumn')
            ->with($taskboard_tracker, $ongoing_column)
            ->willReturn(new MappedValues([231, 856]));

        $result = $this->mapped_values_retriever->getValuesMappedToColumn($taskboard_tracker, $ongoing_column);
        self::assertSame([231, 856], $result->getValueIds());
    }

    public function testGetValuesMappedToColumnReturnsEmptyWhenNoStatusSemantic(): void
    {
        $tracker           = TrackerTestBuilder::aTracker()->build();
        $taskboard_tracker = new TaskboardTracker(TrackerTestBuilder::aTracker()->build(), $tracker);
        $ongoing_column    = new \Cardwall_Column(7, 'On Going', 'clockwork-orange');
        $this->freestyle_mapping_factory->expects(self::once())
            ->method('doesFreestyleMappingExist')
            ->with($taskboard_tracker)
            ->willReturn(false);
        $this->status_retriever->expects(self::once())
            ->method('getField')
            ->with($tracker)
            ->willReturn(null);

        $result = $this->mapped_values_retriever->getValuesMappedToColumn($taskboard_tracker, $ongoing_column);
        self::assertSame(0, count($result->getValueIds()));
    }

    public function testGetValuesMappedToColumnReturnsEmptyWhenStatusHasNoVisibleValues(): void
    {
        $tracker           = TrackerTestBuilder::aTracker()->build();
        $taskboard_tracker = new TaskboardTracker(TrackerTestBuilder::aTracker()->build(), $tracker);
        $ongoing_column    = new \Cardwall_Column(7, 'On Going', 'clockwork-orange');
        $this->freestyle_mapping_factory->expects(self::once())
            ->method('doesFreestyleMappingExist')
            ->with($taskboard_tracker)
            ->willReturn(false);
        $status_field = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $status_field->expects(self::once())
            ->method('getVisibleValuesPlusNoneIfAny')
            ->willReturn([]);
        $this->status_retriever->expects(self::once())
            ->method('getField')
            ->with($tracker)
            ->willReturn($status_field);

        $result = $this->mapped_values_retriever->getValuesMappedToColumn($taskboard_tracker, $ongoing_column);
        self::assertSame(0, count($result->getValueIds()));
    }

    public function testGetValuesMappedToColumnReturnsTheStatusValueWithTheSameLabelAsTheGivenColumn(): void
    {
        $tracker           = TrackerTestBuilder::aTracker()->build();
        $taskboard_tracker = new TaskboardTracker(TrackerTestBuilder::aTracker()->build(), $tracker);
        $ongoing_column    = new \Cardwall_Column(7, 'On Going', 'clockwork-orange');
        $this->freestyle_mapping_factory->expects(self::once())
            ->method('doesFreestyleMappingExist')
            ->with($taskboard_tracker)
            ->willReturn(false);
        $todo_list_value    = new Tracker_FormElement_Field_List_Bind_StaticValue(564, 'Todo', '', 1, false);
        $ongoing_list_value = new Tracker_FormElement_Field_List_Bind_StaticValue(756, 'On Going', '', 2, false);
        $status_field       = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $status_field->expects(self::once())
            ->method('getVisibleValuesPlusNoneIfAny')
            ->willReturn([$todo_list_value, $ongoing_list_value]);
        $this->status_retriever->expects(self::once())
            ->method('getField')
            ->with($tracker)
            ->willReturn($status_field);

        $result = $this->mapped_values_retriever->getValuesMappedToColumn($taskboard_tracker, $ongoing_column);
        self::assertSame([756], $result->getValueIds());
    }
}
