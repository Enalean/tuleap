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

namespace Tuleap\Tracker\Semantic\Timeframe;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tracker;
use Tracker_FormElementFactory;

class SemanticTimeframeBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItBuildsANotConfiguredSemantic(): void
    {
        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(42);

        $dao = Mockery::mock(SemanticTimeframeDao::class);
        $dao->shouldReceive('searchByTrackerId')
            ->with(42)
            ->once()
            ->andReturn(null);

        $factory = Mockery::mock(Tracker_FormElementFactory::class);

        $builder = new SemanticTimeframeBuilder($dao, $factory);
        $this->assertEquals(
            new SemanticTimeframe($tracker, new TimeframeNotConfigured()),
            $builder->getSemantic($tracker)
        );
    }

    public function testItBuildsASemanticWithEndDate(): void
    {
        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(42);

        $dao = Mockery::mock(SemanticTimeframeDao::class);
        $dao->shouldReceive('searchByTrackerId')
            ->with(42)
            ->once()
            ->andReturn([
                'start_date_field_id' => 101,
                'duration_field_id' => null,
                'end_date_field_id' => 104
            ]);

        $start_date_field = Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $end_date_field   = Mockery::mock(\Tracker_FormElement_Field_Date::class);

        $factory = Mockery::mock(Tracker_FormElementFactory::class);
        $factory->shouldReceive('getUsedDateFieldById')
                ->with($tracker, 101)
                ->once()
                ->andReturn($start_date_field);

        $factory->shouldReceive('getUsedDateFieldById')
            ->with($tracker, 104)
            ->once()
            ->andReturn($end_date_field);

        $builder = new SemanticTimeframeBuilder($dao, $factory);
        $this->assertEquals(
            new SemanticTimeframe($tracker, new TimeframeWithEndDate($start_date_field, $end_date_field)),
            $builder->getSemantic($tracker)
        );
    }

    public function testItBuildsASemanticWithDuration(): void
    {
        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(42);

        $dao = Mockery::mock(SemanticTimeframeDao::class);
        $dao->shouldReceive('searchByTrackerId')
            ->with(42)
            ->once()
            ->andReturn([
                'start_date_field_id' => 101,
                'duration_field_id' => 104,
                'end_date_field_id' => null
            ]);

        $start_date_field = Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $duration_field   = Mockery::mock(\Tracker_FormElement_Field_Numeric::class);

        $factory = Mockery::mock(Tracker_FormElementFactory::class);
        $factory->shouldReceive('getUsedDateFieldById')
            ->with($tracker, 101)
            ->once()
            ->andReturn($start_date_field);

        $factory->shouldReceive('getUsedFieldByIdAndType')
            ->with($tracker, 104, ['int', 'float', 'computed'])
            ->once()
            ->andReturn($duration_field);

        $builder = new SemanticTimeframeBuilder($dao, $factory);
        $this->assertEquals(
            new SemanticTimeframe($tracker, new TimeframeWithDuration($start_date_field, $duration_field)),
            $builder->getSemantic($tracker)
        );
    }

    public function testItReturnsANotConfiguredSemanticIfThereIsNoDurationNorEndDateField(): void
    {
        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(42);

        $dao = Mockery::mock(SemanticTimeframeDao::class);
        $dao->shouldReceive('searchByTrackerId')
            ->with(42)
            ->once()
            ->andReturn([
                'start_date_field_id' => 101,
                'duration_field_id' => null,
                'end_date_field_id' => null
            ]);

        $start_date_field = Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $factory          = Mockery::mock(Tracker_FormElementFactory::class);

        $factory->shouldReceive('getUsedDateFieldById')
            ->with($tracker, 101)
            ->once()
            ->andReturn($start_date_field);

        $builder = new SemanticTimeframeBuilder($dao, $factory);
        $this->assertFalse($builder->getSemantic($tracker)->isDefined());
    }
}
