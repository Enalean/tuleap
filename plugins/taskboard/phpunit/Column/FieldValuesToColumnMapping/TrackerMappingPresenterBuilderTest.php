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
use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Planning_Milestone;
use Tracker;
use Tracker_FormElement_Field_Selectbox;
use Tuleap\Taskboard\Tracker\TaskboardTracker;
use Tuleap\Taskboard\Tracker\TrackerCollection;
use Tuleap\Taskboard\Tracker\TrackerCollectionRetriever;

final class TrackerMappingPresenterBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var TrackerMappingPresenterBuilder */
    private $builder;
    /** @var M\LegacyMockInterface|M\MockInterface|TrackerCollectionRetriever */
    private $trackers_retriever;
    /** @var M\LegacyMockInterface|M\MockInterface|MappedFieldRetriever */
    private $mapped_field_retriever;
    /** @var M\LegacyMockInterface|M\MockInterface|MappedValuesRetriever */
    private $mapped_values_retriever;

    protected function setUp(): void
    {
        $this->trackers_retriever      = M::mock(TrackerCollectionRetriever::class);
        $this->mapped_field_retriever  = M::mock(MappedFieldRetriever::class);
        $this->mapped_values_retriever = M::mock(MappedValuesRetriever::class);
        $this->builder                 = new TrackerMappingPresenterBuilder(
            $this->trackers_retriever,
            $this->mapped_field_retriever,
            $this->mapped_values_retriever
        );
    }

    public function testNoTrackers(): void
    {
        $milestone      = M::mock(Planning_Milestone::class);
        $ongoing_column = new Cardwall_Column(25, 'On Going', 'graffiti-yellow');
        $this->trackers_retriever->shouldReceive('getTrackersForMilestone')
            ->with($milestone)
            ->once()
            ->andReturn(new TrackerCollection([]));

        $this->assertEquals([], $this->builder->buildMappings($milestone, $ongoing_column));
    }

    public function testNoValuesForTracker(): void
    {
        $milestone_tracker = M::mock(Tracker::class);
        $milestone         = M::mock(Planning_Milestone::class);
        $ongoing_column    = new Cardwall_Column(25, 'On Going', 'graffiti-yellow');
        $tracker           = $this->mockTracker('76');
        $taskboard_tracker = new TaskboardTracker($milestone_tracker, $tracker);
        $this->trackers_retriever->shouldReceive('getTrackersForMilestone')
            ->with($milestone)
            ->once()
            ->andReturn(new TrackerCollection([$taskboard_tracker]));
        $this->mockMappedField('3086', $taskboard_tracker);
        $this->mockMappedValues([], $taskboard_tracker, $ongoing_column);

        $result = $this->builder->buildMappings($milestone, $ongoing_column);

        $expected_empty_mapping = new TrackerMappingPresenter(76, 3086, []);
        $this->assertEquals([$expected_empty_mapping], $result);
    }

    public function testBuildMappingsReturnsMappingsForGivenColumn(): void
    {
        $milestone_tracker = M::mock(Tracker::class);
        $milestone         = M::mock(Planning_Milestone::class);
        $ongoing_column    = new Cardwall_Column(25, 'On Going', 'graffiti-yellow');
        $tracker           = $this->mockTracker('76');
        $taskboard_tracker = new TaskboardTracker($milestone_tracker, $tracker);
        $this->trackers_retriever->shouldReceive('getTrackersForMilestone')
            ->with($milestone)
            ->once()
            ->andReturn(new TrackerCollection([$taskboard_tracker]));
        $this->mockMappedField('3086', $taskboard_tracker);
        $this->mockMappedValues([1674], $taskboard_tracker, $ongoing_column);

        $result = $this->builder->buildMappings($milestone, $ongoing_column);

        $expected_mapping = new TrackerMappingPresenter(76, 3086, [new ListFieldValuePresenter(1674)]);
        $this->assertEquals([$expected_mapping], $result);
    }

    public function testBuildMappingsMultipleTrackers(): void
    {
        $milestone_tracker        = M::mock(Tracker::class);
        $milestone                = M::mock(Planning_Milestone::class);
        $ongoing_column           = new Cardwall_Column(25, 'On Going', 'graffiti-yellow');
        $first_tracker            = $this->mockTracker('76');
        $first_taskboard_tracker  = new TaskboardTracker($milestone_tracker, $first_tracker);
        $second_tracker           = $this->mockTracker('83');
        $second_taskboard_tracker = new TaskboardTracker($milestone_tracker, $second_tracker);
        $this->trackers_retriever->shouldReceive('getTrackersForMilestone')
            ->with($milestone)
            ->once()
            ->andReturn(new TrackerCollection([$first_taskboard_tracker, $second_taskboard_tracker]));

        $this->mockMappedField('3086', $first_taskboard_tracker);
        $this->mockMappedValues([1674], $first_taskboard_tracker, $ongoing_column);
        $this->mockMappedField('4597', $second_taskboard_tracker);
        $this->mockMappedValues([1857, 1858], $second_taskboard_tracker, $ongoing_column);

        $result = $this->builder->buildMappings($milestone, $ongoing_column);

        $expected_first_mapping  = new TrackerMappingPresenter(76, 3086, [new ListFieldValuePresenter(1674)]);
        $expected_second_mapping = new TrackerMappingPresenter(
            83,
            4597,
            [new ListFieldValuePresenter(1857), new ListFieldValuePresenter(1858)]
        );
        $this->assertEquals([$expected_first_mapping, $expected_second_mapping], $result);
    }

    /**
     * @return M\LegacyMockInterface|M\MockInterface|Tracker
     */
    private function mockTracker(string $tracker_id)
    {
        $tracker = M::mock(\Tracker::class);
        $tracker->shouldReceive('getId')->andReturn($tracker_id);
        return $tracker;
    }

    private function mockMappedField(string $field_id, TaskboardTracker $taskboard_tracker): void
    {
        $mapped_field = M::mock(Tracker_FormElement_Field_Selectbox::class);
        $mapped_field->shouldReceive('getId')->andReturn($field_id);
        $this->mapped_field_retriever->shouldReceive('getField')
            ->withArgs(
                function (TaskboardTracker $arg) use ($taskboard_tracker) {
                    return $arg->getTrackerId() === $taskboard_tracker->getTrackerId();
                }
            )
            ->andReturn($mapped_field);
    }

    private function mockMappedValues(
        array $value_ids,
        TaskboardTracker $taskboard_tracker,
        Cardwall_Column $column
    ): void {
        $mapped_values = new MappedValues($value_ids);
        $this->mapped_values_retriever->shouldReceive('getValuesMappedToColumn')
            ->withArgs(
                function (TaskboardTracker $arg, Cardwall_Column $col_arg) use ($taskboard_tracker, $column) {
                    return $arg->getTrackerId() === $taskboard_tracker->getTrackerId() && $col_arg === $column;
                }
            )
            ->once()
            ->andReturn($mapped_values);
    }
}
