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

use Cardwall_Column;
use PHPUnit\Framework\MockObject\MockObject;
use Planning_Milestone;
use Tracker;
use Tracker_FormElement_Field_Selectbox;
use Tuleap\Taskboard\Tracker\TaskboardTracker;
use Tuleap\Taskboard\Tracker\TrackerCollection;
use Tuleap\Taskboard\Tracker\TrackerCollectionRetriever;

final class TrackerMappingPresenterBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private TrackerMappingPresenterBuilder $builder;
    private MockObject&TrackerCollectionRetriever $trackers_retriever;
    private MockObject&MappedFieldRetriever $mapped_field_retriever;
    private MockObject&MappedValuesRetriever $mapped_values_retriever;

    protected function setUp(): void
    {
        $this->trackers_retriever      = $this->createMock(TrackerCollectionRetriever::class);
        $this->mapped_field_retriever  = $this->createMock(MappedFieldRetriever::class);
        $this->mapped_values_retriever = $this->createMock(MappedValuesRetriever::class);
        $this->builder                 = new TrackerMappingPresenterBuilder(
            $this->trackers_retriever,
            $this->mapped_field_retriever,
            $this->mapped_values_retriever
        );
    }

    public function testNoTrackers(): void
    {
        $milestone      = $this->createMock(Planning_Milestone::class);
        $ongoing_column = new Cardwall_Column(25, 'On Going', 'graffiti-yellow');
        $this->trackers_retriever->expects(self::once())
            ->method('getTrackersForMilestone')
            ->with($milestone)
            ->willReturn(new TrackerCollection([]));

        self::assertEquals([], $this->builder->buildMappings($milestone, $ongoing_column));
    }

    public function testNoValuesForTracker(): void
    {
        $milestone_tracker = $this->createMock(Tracker::class);
        $milestone         = $this->createMock(Planning_Milestone::class);
        $ongoing_column    = new Cardwall_Column(25, 'On Going', 'graffiti-yellow');
        $tracker           = $this->mockTracker(76);
        $taskboard_tracker = new TaskboardTracker($milestone_tracker, $tracker);
        $this->trackers_retriever->expects(self::once())
            ->method('getTrackersForMilestone')
            ->with($milestone)
            ->willReturn(new TrackerCollection([$taskboard_tracker]));
        $this->mockMappedField('3086', $taskboard_tracker);
        $this->mockMappedValues([], $taskboard_tracker, $ongoing_column);

        $result = $this->builder->buildMappings($milestone, $ongoing_column);

        $expected_empty_mapping = new TrackerMappingPresenter(76, 3086, []);
        self::assertEquals([$expected_empty_mapping], $result);
    }

    public function testBuildMappingsReturnsMappingsForGivenColumn(): void
    {
        $milestone_tracker = $this->createMock(Tracker::class);
        $milestone         = $this->createMock(Planning_Milestone::class);
        $ongoing_column    = new Cardwall_Column(25, 'On Going', 'graffiti-yellow');
        $tracker           = $this->mockTracker(76);
        $taskboard_tracker = new TaskboardTracker($milestone_tracker, $tracker);
        $this->trackers_retriever->expects(self::once())
            ->method('getTrackersForMilestone')
            ->with($milestone)
            ->willReturn(new TrackerCollection([$taskboard_tracker]));
        $this->mockMappedField('3086', $taskboard_tracker);
        $this->mockMappedValues([1674], $taskboard_tracker, $ongoing_column);

        $result = $this->builder->buildMappings($milestone, $ongoing_column);

        $expected_mapping = new TrackerMappingPresenter(76, 3086, [new ListFieldValuePresenter(1674)]);
        self::assertEquals([$expected_mapping], $result);
    }

    public function testBuildMappingsMultipleTrackers(): void
    {
        $milestone_tracker        = $this->createMock(Tracker::class);
        $milestone                = $this->createMock(Planning_Milestone::class);
        $ongoing_column           = new Cardwall_Column(25, 'On Going', 'graffiti-yellow');
        $first_tracker            = $this->mockTracker(76);
        $first_taskboard_tracker  = new TaskboardTracker($milestone_tracker, $first_tracker);
        $second_tracker           = $this->mockTracker(83);
        $second_taskboard_tracker = new TaskboardTracker($milestone_tracker, $second_tracker);
        $this->trackers_retriever->expects(self::once())
            ->method('getTrackersForMilestone')
            ->with($milestone)
            ->willReturn(new TrackerCollection([$first_taskboard_tracker, $second_taskboard_tracker]));

        $mapped_field_01 = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $mapped_field_01->method('getId')->willReturn('3086');

        $mapped_field_02 = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $mapped_field_02->method('getId')->willReturn('4597');

        $this->mapped_field_retriever->method('getField')->willReturnMap([
            [$first_taskboard_tracker, $mapped_field_01],
            [$second_taskboard_tracker, $mapped_field_02],
        ]);

        $mapped_values_01 = new MappedValues([1674]);
        $mapped_values_02 = new MappedValues([1857, 1858]);
        $this->mapped_values_retriever
            ->method('getValuesMappedToColumn')
            ->willReturnMap([
                [$first_taskboard_tracker, $ongoing_column, $mapped_values_01],
                [$second_taskboard_tracker, $ongoing_column, $mapped_values_02],
            ]);

        $result = $this->builder->buildMappings($milestone, $ongoing_column);

        $expected_first_mapping  = new TrackerMappingPresenter(76, 3086, [new ListFieldValuePresenter(1674)]);
        $expected_second_mapping = new TrackerMappingPresenter(
            83,
            4597,
            [new ListFieldValuePresenter(1857), new ListFieldValuePresenter(1858)]
        );
        self::assertEquals([$expected_first_mapping, $expected_second_mapping], $result);
    }

    private function mockTracker(int $tracker_id): MockObject&Tracker
    {
        $tracker = $this->createMock(\Tracker::class);
        $tracker->method('getId')->willReturn($tracker_id);
        return $tracker;
    }

    private function mockMappedField(string $field_id, TaskboardTracker $taskboard_tracker): void
    {
        $mapped_field = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $mapped_field->method('getId')->willReturn($field_id);
        $this->mapped_field_retriever->method('getField')
            ->with(self::callback(
                function (TaskboardTracker $arg) use ($taskboard_tracker): bool {
                    return $arg->getTrackerId() === $taskboard_tracker->getTrackerId();
                }
            ))
            ->willReturn($mapped_field);
    }

    private function mockMappedValues(
        array $value_ids,
        TaskboardTracker $taskboard_tracker,
        Cardwall_Column $column,
    ): void {
        $mapped_values = new MappedValues($value_ids);
        $this->mapped_values_retriever
            ->expects(self::once())
            ->method('getValuesMappedToColumn')
            ->with(
                self::callback(
                    function (TaskboardTracker $arg) use ($taskboard_tracker): bool {
                        return $arg->getTrackerId() === $taskboard_tracker->getTrackerId();
                    }
                ),
                self::callback(
                    function (Cardwall_Column $col_arg) use ($column): bool {
                        return $col_arg === $column;
                    }
                ),
            )
            ->willReturn($mapped_values);
    }
}
