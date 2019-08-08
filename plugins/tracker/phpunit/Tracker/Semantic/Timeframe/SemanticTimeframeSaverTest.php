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
use PHPUnit\Framework\TestCase;
use Tracker;
use Tracker_FormElement_Field_Date;
use Tracker_FormElement_Field_Numeric;

class SemanticTimeframeSaverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\MockInterface|SemanticTimeframeDao
     */
    private $dao;
    /**
     * @var SemanticTimeframeSaver
     */
    private $savior;

    protected function setUp(): void
    {
        $this->dao    = Mockery::mock(SemanticTimeframeDao::class);
        $this->savior = new SemanticTimeframeSaver($this->dao);
    }

    public function testItDoesNotSaveIfThereIsNoField(): void
    {
        $semantic_timeframe = new SemanticTimeframe(
            Mockery::mock(Tracker::class),
            null,
            null,
            null
        );

        $this->dao
            ->shouldReceive('save')
            ->never();

        $this->assertFalse($this->savior->save($semantic_timeframe));
    }

    public function testItDoesNotSaveIfThereIsNoStartDateField(): void
    {
        $semantic_timeframe = new SemanticTimeframe(
            Mockery::mock(Tracker::class),
            null,
            Mockery::mock(Tracker_FormElement_Field_Numeric::class),
            null
        );

        $this->dao
            ->shouldReceive('save')
            ->never();

        $this->assertFalse($this->savior->save($semantic_timeframe));
    }

    public function testItDoesNotSaveIfThereIsNoDurationNorEndDateField(): void
    {
        $semantic_timeframe = new SemanticTimeframe(
            Mockery::mock(Tracker::class),
            Mockery::mock(Tracker_FormElement_Field_Date::class),
            null,
            null,
            null
        );

        $this->dao
            ->shouldReceive('save')
            ->never();

        $this->assertFalse($this->savior->save($semantic_timeframe));
    }

    public function testItSavesTheSemanticWithDuration(): void
    {
        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(1);

        $start_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('getId')->andReturn(101);

        $duration_field = Mockery::mock(Tracker_FormElement_Field_Numeric::class);
        $duration_field->shouldReceive('getId')->andReturn(102);

        $semantic_timeframe = new SemanticTimeframe(
            $tracker,
            $start_date_field,
            $duration_field,
            null
        );

        $this->dao
            ->shouldReceive('save')
            ->with(1, 101, 102, null)
            ->once()
            ->andReturn(true);

        $this->assertTrue($this->savior->save($semantic_timeframe));
    }

    public function testItSavesTheSemanticWithEndDate(): void
    {
        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(1);

        $start_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('getId')->andReturn(101);

        $end_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $end_date_field->shouldReceive('getId')->andReturn(102);

        $semantic_timeframe = new SemanticTimeframe(
            $tracker,
            $start_date_field,
            null,
            $end_date_field
        );

        $this->dao
            ->shouldReceive('save')
            ->with(1, 101, null, 102)
            ->once()
            ->andReturn(true);

        $this->assertTrue($this->savior->save($semantic_timeframe));
    }
}
