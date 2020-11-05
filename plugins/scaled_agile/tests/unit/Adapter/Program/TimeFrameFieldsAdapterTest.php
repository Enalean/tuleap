<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\Adapter\Program;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ScaledAgile\Adapter\TrackerDataAdapter;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\FieldData;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\TimeFrameFields;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\MissingTimeFrameFieldException;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class TimeFrameFieldsAdapterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var TimeFrameFieldsAdapter
     */
    private $adapter;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|SemanticTimeframeBuilder
     */
    private $semantic_timeframe_factory;

    protected function setUp(): void
    {
        $this->semantic_timeframe_factory = \Mockery::mock(SemanticTimeframeBuilder::class);
        $this->adapter                    = new TimeFrameFieldsAdapter($this->semantic_timeframe_factory);
    }

    public function testItThrowsWhenNoStartDateIsFound(): void
    {
        $semantic_timeframe = \Mockery::mock(SemanticTimeframe::class);
        $semantic_timeframe->shouldReceive('getStartDateField')->andReturnNull();
        $source_tracker = TrackerDataAdapter::build(TrackerTestBuilder::aTracker()->withId(123)->build());
        $this->semantic_timeframe_factory->shouldReceive('getSemantic')->with($source_tracker->getFullTracker())->andReturn(
            $semantic_timeframe
        );

        $this->expectException(MissingTimeFrameFieldException::class);
        $this->adapter->build($source_tracker);
    }

    public function testItThrowsWhenNoEndPeriodIsFound(): void
    {
        $source_tracker   = TrackerDataAdapter::build(TrackerTestBuilder::aTracker()->withId(123)->build());
        $start_date_field = new \Tracker_FormElement_Field_Date(
            1,
            $source_tracker->getTrackerId(),
            null,
            "start_date",
            "Start date",
            "",
            true,
            null,
            true,
            true,
            1
        );

        $semantic_timeframe = \Mockery::mock(SemanticTimeframe::class);
        $semantic_timeframe->shouldReceive('getDurationField')->andReturnNull()->once();
        $semantic_timeframe->shouldReceive('getEndDateField')->andReturnNull()->once();
        $semantic_timeframe->shouldReceive('getStartDateField')->andReturn($start_date_field);

        $this->semantic_timeframe_factory->shouldReceive('getSemantic')->with($source_tracker->getFullTracker())->andReturn(
            $semantic_timeframe
        );

        $this->expectException(MissingTimeFrameFieldException::class);
        $this->adapter->build($source_tracker);
    }


    public function testItBuildTimeFrameFieldsBasedOnDuration(): void
    {
        $source_tracker     = TrackerDataAdapter::build(TrackerTestBuilder::aTracker()->withId(123)->build());
        $start_date_field   = new \Tracker_FormElement_Field_Date(
            1,
            $source_tracker->getTrackerId(),
            null,
            "start_date",
            "Start date",
            "",
            true,
            null,
            true,
            true,
            1
        );
        $duration_field     = new \Tracker_FormElement_Field_Integer(
            1,
            $source_tracker->getTrackerId(),
            null,
            "duration",
            "Duration",
            "",
            true,
            null,
            true,
            true,
            2
        );
        $semantic_timeframe = \Mockery::mock(SemanticTimeframe::class);
        $semantic_timeframe->shouldReceive('getDurationField')->andReturn($duration_field);
        $semantic_timeframe->shouldReceive('getStartDateField')->andReturn($start_date_field);
        $this->semantic_timeframe_factory->shouldReceive('getSemantic')->with($source_tracker->getFullTracker())->andReturn(
            $semantic_timeframe
        );

        $field_time_frame_data = TimeFrameFields::fromStartDateAndDuration(
            new FieldData($start_date_field),
            new FieldData($duration_field)
        );

        $this->assertEquals($field_time_frame_data, $this->adapter->build($source_tracker));
    }

    public function testItBuildTimeFrameFieldsBasedOnEndDate(): void
    {
        $source_tracker     = TrackerDataAdapter::build(TrackerTestBuilder::aTracker()->withId(123)->build());
        $start_date_field   = new \Tracker_FormElement_Field_Date(
            1,
            $source_tracker->getTrackerId(),
            null,
            "start_date",
            "Start date",
            "",
            true,
            null,
            true,
            true,
            1
        );
        $end_date_field     = new \Tracker_FormElement_Field_Date(
            1,
            $source_tracker->getTrackerId(),
            null,
            "end_date",
            "End date",
            "",
            true,
            null,
            true,
            true,
            2
        );
        $semantic_timeframe = \Mockery::mock(SemanticTimeframe::class);
        $semantic_timeframe->shouldReceive('getDurationField')->andReturn(null);
        $semantic_timeframe->shouldReceive('getStartDateField')->andReturn($start_date_field);
        $semantic_timeframe->shouldReceive('getEndDateField')->andReturn($end_date_field);
        $this->semantic_timeframe_factory->shouldReceive('getSemantic')->with($source_tracker->getFullTracker())->andReturn(
            $semantic_timeframe
        );

        $field_time_frame_data = TimeFrameFields::fromStartAndEndDates(
            new FieldData($start_date_field),
            new FieldData($end_date_field)
        );

        $this->assertEquals($field_time_frame_data, $this->adapter->build($source_tracker));
    }
}
